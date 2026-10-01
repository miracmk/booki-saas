<?php defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'libraries/payment/Escrow_settlement_service.php';

/**
 * Controller: Marketplace_escrow
 *
 * Exposes Escrow & Settlement operations for RandevuBurada:
 * - Public breakdown preview (%5 RB + %5 POS + %1 EFT = %11)
 * - Booking with 7-day Pre-Authorization hold
 * - Show-up confirmation & T+3 escrow countdown initiation
 * - Tenant financial summary API
 */
class Marketplace_escrow extends App_Controller
{
    private Escrow_settlement_service $escrow;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('bk_escrow_model');
        $this->load->model('bk_marketplace_customer_model');
        $this->escrow = new Escrow_settlement_service();
    }

    /**
     * Preview financial breakdown for an amount.
     * GET/POST /marketplace_escrow/calculate_breakdown?amount=1000
     */
    public function calculate_breakdown(): void
    {
        $amount = (float) ($this->input->get_post('amount') ?: 0);
        if ($amount <= 0) {
            json_response(['status' => 'error', 'message' => 'Lütfen geçerli bir tutar belirtin.'], 400);
            return;
        }

        $breakdown = Escrow_settlement_service::calculate_fee_breakdown($amount);
        json_response([
            'status' => 'success',
            'data'   => $breakdown
        ]);
    }

    /**
     * Initiate appointment booking with 7-day card pre-auth hold.
     * POST /marketplace_escrow/create_booking_preauth
     */
    public function create_booking_preauth(): void
    {
        try {
            method('post');
            $post = $this->input->post(null, true) ?: json_decode((string)file_get_contents('php://input'), true);

            $tenantId = (int) ($post['tenant_id'] ?? 0);
            $amount = (float) ($post['amount'] ?? 0);
            $serviceName = trim((string)($post['service_name'] ?? 'Randevu Hizmeti'));

            if ($tenantId <= 0 || $amount <= 0) {
                throw new InvalidArgumentException('İşletme ve tutar bilgisi zorunludur.');
            }

            $customerData = [
                'full_name' => $post['customer_name'] ?? 'Müşteri',
                'phone'     => $post['customer_phone'] ?? '',
                'email'     => $post['customer_email'] ?? null
            ];

            if (empty($customerData['phone'])) {
                throw new InvalidArgumentException('Müşteri telefon numarası zorunludur.');
            }

            $cardPayload = [
                'card_holder_name' => $post['card_holder_name'] ?? $post['card_holder'] ?? '',
                'card_number'      => $post['card_number'] ?? '',
                'expire_month'     => $post['expire_month'] ?? '',
                'expire_year'      => $post['expire_year'] ?? '',
                'cvv'              => $post['cvv'] ?? ''
            ];

            $result = $this->escrow->initiate_booking_preauth(
                $tenantId,
                $amount,
                $serviceName,
                $customerData,
                $cardPayload,
                [
                    'appointment_id' => $post['appointment_id'] ?? null,
                    'tenant_iban'    => $post['tenant_iban'] ?? null
                ]
            );

            json_response([
                'status'        => 'success',
                'settlement_id' => $result['settlement_id'],
                'order_id'      => $result['order_id'],
                'fee_breakdown' => $result['fee_breakdown'],
                'payment'       => $result['payment']
            ]);
        } catch (Throwable $e) {
            json_response([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Mark customer show-up: Captures pre-auth hold into Escrow and starts T+3 countdown.
     * POST /marketplace_escrow/mark_showup
     */
    public function mark_showup(): void
    {
        try {
            method('post');
            $post = $this->input->post(null, true) ?: json_decode((string)file_get_contents('php://input'), true);
            $settlementId = (int) ($post['settlement_id'] ?? 0);

            if ($settlementId <= 0) {
                throw new InvalidArgumentException('Geçerli bir hakediş ID belirtilmelidir.');
            }

            $res = $this->escrow->confirm_customer_showup($settlementId);
            json_response([
                'status' => 'success',
                'data'   => $res
            ]);
        } catch (Throwable $e) {
            json_response([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Cancel appointment and void 7-day pre-auth hold.
     * POST /marketplace_escrow/cancel_booking
     */
    public function cancel_booking(): void
    {
        try {
            method('post');
            $post = $this->input->post(null, true) ?: json_decode((string)file_get_contents('php://input'), true);
            $settlementId = (int) ($post['settlement_id'] ?? 0);
            $reason = trim((string)($post['reason'] ?? 'Müşteri iptal etti'));

            if ($settlementId <= 0) {
                throw new InvalidArgumentException('Geçerli bir hakediş ID belirtilmelidir.');
            }

            $res = $this->escrow->cancel_booking($settlementId, $reason);
            json_response([
                'status' => 'success',
                'data'   => $res
            ]);
        } catch (Throwable $e) {
            json_response([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Tenant Financial Summary API: Returns gross sales, fees, T+3 escrow, and payout balances.
     * GET /marketplace_escrow/tenant_financial_summary?tenant_id=1
     */
    public function tenant_financial_summary(): void
    {
        $tenantId = (int) ($this->input->get('tenant_id') ?: session('tenant_id') ?: session('user_id') ?: 0);
        if ($tenantId <= 0) {
            json_response(['status' => 'error', 'message' => 'İşletme kimliği bulunamadı.'], 400);
            return;
        }

        $summary = $this->bk_escrow_model->get_tenant_financial_summary($tenantId);
        json_response([
            'status' => 'success',
            'tenant_id' => $tenantId,
            'financial_summary' => $summary
        ]);
    }
}
