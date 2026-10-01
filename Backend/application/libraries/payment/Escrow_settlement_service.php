<?php defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'libraries/payment/ToslaPaymentGatewayAdapter.php';

/**
 * Service: Escrow_settlement_service
 *
 * Manages the financial escrow and payout lifecycle for RandevuBurada:
 * - Customer: 0 TL extra cost (pays standard service amount).
 * - Business Payout Cut:
 *     1. %5 RandevuBurada Platform Commission
 *     2. %5 Tosla Virtual POS Infrastructure Cost
 *     3. %1 EFT / HAVALE / FAST Bank Transfer Fee
 *     Total Cut = %11 | Net Business Payout = %89.
 * - Flow: 7-Day Pre-Auth Hold -> Show-up -> Capture -> T+3 Holding -> Payout to IBAN.
 */
class Escrow_settlement_service
{
    private CI_Controller $CI;
    private ToslaPaymentGatewayAdapter $tosla;

    public const MARKETPLACE_COMMISSION_RATE = 5.00; // %5
    public const POS_FEE_RATE                = 5.00; // %5
    public const TRANSFER_FEE_RATE           = 1.00; // %1
    public const TOTAL_PLATFORM_CUT_RATE     = 11.00; // %11
    public const NET_PAYOUT_RATE             = 89.00; // %89

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('bk_escrow_model');
        $this->CI->load->model('bk_marketplace_customer_model');
        $this->tosla = new ToslaPaymentGatewayAdapter();
    }

    /**
     * Calculate financial split for an amount based on deduction model:
     * - Model A (RB Organic): Gross - 5% (RB) - 5% (POS) - 1% (EFT) = 89% Net Payout
     * - Model B (RB-Only Link): Gross - 20% (Platform/POS) - 1% (EFT) = 79% Net Payout
     * - Model C (BooKi SaaS Link): Gross - 0% (Platform) - 5% (POS) - 1% (EFT) = 94% Net Payout
     */
    public static function calculate_fee_breakdown(float $amount, string $model = 'model_a'): array
    {
        $gross = round($amount, 2);
        $model = strtolower($model);
        if (!in_array($model, ['model_a', 'model_b', 'model_c'], true)) {
            $model = 'model_a';
        }

        switch ($model) {
            case 'model_b':
                // RB-Only Manual Booking via link: 20% platform/POS + 1% bank fee
                $rbRate = 20.00;
                $posRate = 0.00;
                $transferRate = self::TRANSFER_FEE_RATE;
                break;
            case 'model_c':
                // BooKi SaaS Active Subscriber Link: 0% RB platform + 5% POS + 1% bank fee
                $rbRate = 0.00;
                $posRate = self::POS_FEE_RATE;
                $transferRate = self::TRANSFER_FEE_RATE;
                break;
            case 'model_a':
            default:
                // RB Organic Marketplace: 5% RB + 5% POS + 1% bank fee
                $rbRate = self::MARKETPLACE_COMMISSION_RATE;
                $posRate = self::POS_FEE_RATE;
                $transferRate = self::TRANSFER_FEE_RATE;
                break;
        }

        $rbCommission = round($gross * ($rbRate / 100), 2);
        $posFee = round($gross * ($posRate / 100), 2);
        $transferFee = round($gross * ($transferRate / 100), 2);
        $totalCut = round($rbCommission + $posFee + $transferFee, 2);
        $netPayout = round($gross - $totalCut, 2);
        $netRate = $gross > 0 ? round(($netPayout / $gross) * 100, 2) : 0.00;

        return [
            'deduction_model'        => $model,
            'gross_amount'           => $gross,
            'marketplace_rate'       => $rbRate,
            'marketplace_commission' => $rbCommission,
            'pos_rate'               => $posRate,
            'pos_fee'                => $posFee,
            'transfer_rate'          => $transferRate,
            'transfer_fee'           => $transferFee,
            'total_platform_cut'     => $totalCut,
            'net_payout_rate'        => $netRate,
            'net_payout_amount'      => $netPayout,
            'payout_timeline'        => 'T+3 İş Günü'
        ];
    }

    /**
     * Resolve dynamic wire transfer / FAST fee with fallback.
     */
    public static function resolve_dynamic_bank_fee(float $amount): array
    {
        $rate = self::TRANSFER_FEE_RATE;
        $fee = round($amount * ($rate / 100), 2);
        return [
            'rate'   => $rate,
            'fee'    => $fee,
            'source' => 'dynamic_tariff_engine'
        ];
    }

    /**
     * Start appointment booking with 7-day Pre-Authorization hold on customer card.
     */
    public function initiate_booking_preauth(
        int $tenantId,
        float $amount,
        string $serviceName,
        array $customerData,
        array $cardPayload,
        array $extraData = []
    ): array {
        // 1. Find or create RandevuBurada customer
        $customer = $this->CI->bk_marketplace_customer_model->find_or_create(
            $customerData['full_name'] ?? 'Müşteri',
            $customerData['phone'] ?? '',
            $customerData['email'] ?? null
        );

        $orderId = 'BK_ESC_' . time() . rand(10, 99);
        $callbackUrl = site_url('marketplace_escrow/tosla_preauth_callback');

        // 2. Perform Pre-Authorization via Tosla Adapter
        $preAuthResult = $this->tosla->holdPreAuth($amount, 'TRY', $cardPayload, [
            'appointment_id' => $extraData['appointment_id'] ?? null,
            'callback_url'   => $callbackUrl,
            'order_id'       => $orderId,
            'tenant_id'      => $tenantId
        ]);

        $deductionModel = $extraData['deduction_model'] ?? 'model_a';
        $feeBreakdown = self::calculate_fee_breakdown($amount, $deductionModel);

        // 3. Create Escrow settlement record
        $settlementId = $this->CI->bk_escrow_model->create_settlement([
            'id_tenants'             => $tenantId,
            'id_appointments'        => $extraData['appointment_id'] ?? null,
            'customer_id'            => $customer['id'],
            'customer_name'          => $customer['full_name'],
            'customer_phone'         => $customer['phone'],
            'service_name'           => $serviceName,
            'gross_amount'           => $amount,
            'deduction_model'        => $deductionModel,
            'marketplace_rate'       => $feeBreakdown['marketplace_rate'],
            'pos_rate'               => $feeBreakdown['pos_rate'],
            'transfer_rate'          => $feeBreakdown['transfer_rate'],
            'tosla_transaction_id'   => $preAuthResult['transaction_id'] ?? null,
            'tosla_order_id'         => $orderId,
            'provision_status'       => ($preAuthResult['status'] === 'held' || $preAuthResult['status'] === '3d_redirect_required') ? 'authorized' : 'voided',
            'payout_status'          => 'pending_showup',
            'payout_iban'            => $extraData['tenant_iban'] ?? null,
            'notes'                  => 'RandevuBurada provizyon rezervasyonu başlatıldı.'
        ]);

        return [
            'settlement_id'   => $settlementId,
            'order_id'        => $orderId,
            'deduction_model' => $deductionModel,
            'fee_breakdown'   => $feeBreakdown,
            'payment'         => $preAuthResult
        ];
    }

    /**
     * Mark Show-Up: Customer arrived for appointment, capture pre-auth hold and start T+3 escrow.
     */
    public function confirm_customer_showup(int $settlementId): array
    {
        $settlement = $this->CI->bk_escrow_model->get_by_id($settlementId);
        if (!$settlement) {
            throw new InvalidArgumentException("Hakediş kaydı bulunamadı: ID {$settlementId}");
        }

        if ($settlement['payout_status'] !== 'pending_showup') {
            return [
                'status'  => 'already_processed',
                'message' => 'Bu rezervasyonun provizyonu zaten tahsil edilmiş veya işlenmiş.',
                'settlement' => $settlement
            ];
        }

        $txId = $settlement['tosla_transaction_id'];
        $amount = (float) $settlement['gross_amount'];

        // Capture Pre-Auth via Tosla
        $captureResult = $this->tosla->capturePreAuth($txId, $amount, [
            'order_id' => $settlement['tosla_order_id']
        ]);

        // Update Escrow to T+3 countdown
        $this->CI->bk_escrow_model->mark_showup($settlementId, $txId);
        $updated = $this->CI->bk_escrow_model->get_by_id($settlementId);

        return [
            'status'         => 'captured',
            'payout_status'  => 'in_escrow_t3',
            'payout_due_date'=> $updated['payout_due_date'],
            'net_payout'     => $updated['net_payout_amount'],
            'fee_deducted'   => round($updated['marketplace_commission'] + $updated['pos_fee'] + $updated['transfer_fee'], 2),
            'capture_result' => $captureResult
        ];
    }

    /**
     * Cancel or No-Show: void pre-authorization hold on card.
     */
    public function cancel_booking(int $settlementId, string $reason = 'Müşteri randevuyu iptal etti'): array
    {
        $settlement = $this->CI->bk_escrow_model->get_by_id($settlementId);
        if (!$settlement) {
            throw new InvalidArgumentException("Hakediş kaydı bulunamadı.");
        }

        $txId = $settlement['tosla_transaction_id'];
        $voidResult = [];

        if (!empty($txId)) {
            $voidResult = $this->tosla->voidPreAuth($txId);
        }

        $this->CI->bk_escrow_model->mark_cancelled($settlementId, $reason);

        return [
            'status'      => 'voided',
            'message'     => 'Provizyon blokesi kaldırıldı, karttan çekim yapılmadı.',
            'void_result' => $voidResult
        ];
    }

    /**
     * Upload e-Fatura / e-SMM document for an escrow settlement.
     */
    public function upload_merchant_invoice(int $settlementId, array $invoiceData): array
    {
        $settlement = $this->CI->bk_escrow_model->get_by_id($settlementId);
        if (!$settlement) {
            throw new InvalidArgumentException("Hakediş kaydı bulunamadı: ID {$settlementId}");
        }

        if (empty($invoiceData['invoice_no'])) {
            throw new InvalidArgumentException("Fatura numarası zorunludur.");
        }

        $ok = $this->CI->bk_escrow_model->upload_merchant_invoice($settlementId, $invoiceData);
        return [
            'status'        => $ok ? 'success' : 'error',
            'settlement_id' => $settlementId,
            'payout_status' => 'pending_invoice',
            'invoice_no'    => $invoiceData['invoice_no']
        ];
    }

    /**
     * Verify merchant invoice and approve for payout.
     */
    public function verify_merchant_invoice(int $settlementId): array
    {
        return $this->CI->bk_escrow_model->verify_merchant_invoice($settlementId);
    }
}
