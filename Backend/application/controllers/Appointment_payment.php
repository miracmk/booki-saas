<?php defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'libraries/payment/AppointmentPaymentService.php';

/* ----------------------------------------------------------------------------
 * BooKi / RandevuBurada Ecosystem
 * Controller: Appointment_payment
 * 
 * Endpoints for:
 * 1. Customer Arrived (Scenario A: Void PreAuth + 200 TL fee deduction)
 * 2. Customer No-Show (Scenario B: Capture PreAuth + 200 TL / 800 TL split)
 * 3. Pre-auth deposit initialization with TBK 178 contract validation
 * 4. Automated 3-Hour Post-Appointment No-Show Cron Job
 * ---------------------------------------------------------------------------- */

class Appointment_payment extends App_Controller
{
    private AppointmentPaymentService $paymentService;

    public function __construct()
    {
        parent::__construct();
        $this->paymentService = new AppointmentPaymentService();
    }

    /**
     * Trigger Scenario A: Customer Arrived
     * POST /appointment_payment/customer_arrived/{appointment_id}
     */
    public function customer_arrived(int $appointment_id): void
    {
        try {
            method('post');

            $result = $this->paymentService->handleCustomerShow($appointment_id);

            json_response([
                'status'  => 'success',
                'message' => 'Müşteri gelişi teyit edildi. Kart blokajı kaldırıldı, hizmet bedeli (200 TL) bakiyeden düşüldü.',
                'data'    => $result
            ]);
        } catch (Throwable $e) {
            json_response([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Trigger Scenario B: Customer No-Show (Manual Merchant Action)
     * POST /appointment_payment/customer_no_show/{appointment_id}
     */
    public function customer_no_show(int $appointment_id): void
    {
        try {
            method('post');

            $result = $this->paymentService->handleCustomerNoShow($appointment_id);

            json_response([
                'status'  => 'success',
                'message' => 'No-Show işlemi yapıldı. TBK m. 178 uyarınca cayma tazminatı tahsil edilip pazaryeri hakedişi dağıtıldı.',
                'data'    => $result
            ]);
        } catch (Throwable $e) {
            json_response([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Initialize Pre-Authorization Hold on Booking Checkout
     * POST /appointment_payment/hold_deposit/{appointment_id}
     */
    public function hold_deposit(int $appointment_id): void
    {
        try {
            method('post');

            $post = $this->input->post(null, true) ?: json_decode(file_get_contents('php://input'), true);

            $cardPayload = [
                'card_holder_name' => $post['card_holder_name'] ?? '',
                'card_number'      => $post['card_number'] ?? '',
                'expire_month'     => $post['expire_month'] ?? '',
                'expire_year'      => $post['expire_year'] ?? '',
                'cvv'              => $post['cvv'] ?? '',
            ];

            $clientContext = [
                'ip'             => $this->input->ip_address(),
                'terms_accepted' => $post['legal_terms_accepted'] ?? false
            ];

            $result = $this->paymentService->holdDepositPreAuth($appointment_id, $cardPayload, $clientContext);

            json_response([
                'status'  => 'success',
                'message' => 'Ön provizyon başarıyla oluşturuldu.',
                'data'    => $result
            ]);
        } catch (Throwable $e) {
            json_response([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Automated Cron Job: Process unhandled appointments 3 hours post-start
     * CLI: php index.php appointment_payment cron_auto_noshow
     * GET/POST: /appointment_payment/cron_auto_noshow (protected by secret or internal call)
     */
    public function cron_auto_noshow(): void
    {
        $threeHoursAgo = date('Y-m-d H:i:s', strtotime('-3 hours'));

        // Look for appointments scheduled 3+ hours ago that are still pending/confirmed with active preauth hold
        $unhandledAppointments = $this->db
            ->where('end_datetime <=', $threeHoursAgo)
            ->where_in('status', ['pending', 'confirmed'])
            ->where('preauth_status', 'held')
            ->get('appointments')
            ->result_array();

        $processed = [];
        $errors = [];

        foreach ($unhandledAppointments as $appt) {
            try {
                $res = $this->paymentService->handleCustomerNoShow((int)$appt['id']);
                $processed[] = [
                    'appointment_id' => $appt['id'],
                    'captured_amount' => $res['captured_amount'],
                    'platform_fee'    => $res['platform_fee'],
                    'merchant_payout' => $res['merchant_payout']
                ];
            } catch (Throwable $e) {
                $errors[] = [
                    'appointment_id' => $appt['id'],
                    'error'          => $e->getMessage()
                ];
            }
        }

        json_response([
            'status'         => 'success',
            'processed_count'=> count($processed),
            'processed'      => $processed,
            'errors'         => $errors,
            'timestamp'      => date('Y-m-d H:i:s')
        ]);
    }
}
