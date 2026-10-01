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
     * Calculate financial split for an amount.
     */
    public static function calculate_fee_breakdown(float $amount): array
    {
        $gross = round($amount, 2);
        $rbCommission = round($gross * (self::MARKETPLACE_COMMISSION_RATE / 100), 2);
        $posFee = round($gross * (self::POS_FEE_RATE / 100), 2);
        $transferFee = round($gross * (self::TRANSFER_FEE_RATE / 100), 2);
        $totalCut = round($rbCommission + $posFee + $transferFee, 2);
        $netPayout = round($gross - $totalCut, 2);

        return [
            'gross_amount'           => $gross,
            'marketplace_rate'       => self::MARKETPLACE_COMMISSION_RATE,
            'marketplace_commission' => $rbCommission,
            'pos_rate'               => self::POS_FEE_RATE,
            'pos_fee'                => $posFee,
            'transfer_rate'          => self::TRANSFER_FEE_RATE,
            'transfer_fee'           => $transferFee,
            'total_platform_cut'     => $totalCut,
            'net_payout_rate'        => self::NET_PAYOUT_RATE,
            'net_payout_amount'      => $netPayout,
            'payout_timeline'        => 'T+3 İş Günü'
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

        $feeBreakdown = self::calculate_fee_breakdown($amount);

        // 3. Create Escrow settlement record
        $settlementId = $this->CI->bk_escrow_model->create_settlement([
            'id_tenants'             => $tenantId,
            'id_appointments'        => $extraData['appointment_id'] ?? null,
            'customer_id'            => $customer['id'],
            'customer_name'          => $customer['full_name'],
            'customer_phone'         => $customer['phone'],
            'service_name'           => $serviceName,
            'gross_amount'           => $amount,
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
            'settlement_id' => $settlementId,
            'order_id'      => $orderId,
            'fee_breakdown' => $feeBreakdown,
            'payment'       => $preAuthResult
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
}
