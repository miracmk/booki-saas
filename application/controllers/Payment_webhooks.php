<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - payment webhooks controller (2026-08-27).
 *
 * Public webhook endpoints for payment gateways (iyzico, PayTR, Stripe).
 * CSRF protection is disabled for these endpoints (see routes.php csrf_exclude_uris).
 * Signature verification is handled per-gateway via Payment_gateway_interface.
 * ---------------------------------------------------------------------------- */

class Payment_webhooks extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('payment_settings_model');
        $this->load->model('payment_transactions_model');
        $this->load->model('appointments_model');
    }

    /**
     * Unified Virtual POS callback endpoint.
     *
     * Handles both asynchronous server-to-server notifications (webhooks/IPN) and
     * synchronous 3D Secure browser redirects (return URL) for all payment gateways
     * (iyzico, PayTR, Stripe, Garanti BBVA, Enpara, Odeal).
     *
     * Endpoint formats:
     *   - POST/GET /payment/callback
     *   - POST/GET /payment/callback/{gateway}
     *   - POST/GET /payment_webhooks/callback
     *   - POST/GET /payment_webhooks/callback/{gateway}
     *   - Query: ?gateway=iyzico&tenant=mytenant
     *
     * @param string|null $gateway Optional gateway slug (iyzico, paytr, stripe, garanti, etc.)
     */
    public function callback(?string $gateway = null): void
    {
        // 1. Resolve gateway identifier
        if (empty($gateway)) {
            $gateway = $this->input->get_post('gateway') ?: $this->input->get_post('provider');
        }

        // Auto-detect gateway if still unspecified
        if (empty($gateway)) {
            $gateway = $this->detect_gateway();
        }

        $gateway = strtolower(trim((string) $gateway));

        // 2. Distinguish browser return (customer 3D Secure redirect) vs server-to-server webhook
        if ($this->is_browser_return_request()) {
            $this->handle_browser_return($gateway);

            return;
        }

        // 3. Process server-to-server webhook
        $this->handle_webhook($gateway);
    }

    /**
     * iyzico webhook endpoint.
     */
    public function iyzico(): void
    {
        $this->handle_webhook('iyzico');
        $this->callback('iyzico');
    }

    /**
     * PayTR webhook endpoint.
     */
    public function paytr(): void
    {
        $this->handle_webhook('paytr');
        $this->callback('paytr');
    }

    /**
     * Stripe webhook endpoint.
     */
    public function stripe(): void
    {
        $this->handle_webhook('stripe');
        $this->callback('stripe');
    }

    /**
     * Auto-detect the payment gateway from request headers and parameters.
     */
    private function detect_gateway(): string
    {
        $headers = $this->input->request_headers();
        $raw_body = (string) file_get_contents('php://input');

        if (!empty($headers['Stripe-Signature']) || !empty($headers['stripe-signature'])) {
            return 'stripe';
        }

        if (
            !empty($headers['X-IYZ-SIGNATURE']) ||
            !empty($headers['x-iyz-signature']) ||
            $this->input->post('token') !== null ||
            str_contains($raw_body, 'conversationId') ||
            str_contains($raw_body, 'paymentId')
        ) {
            return 'iyzico';
        }

        if (
            $this->input->post('merchant_oid') !== null ||
            $this->input->post('total_amount') !== null ||
            $this->input->post('hash') !== null
        ) {
            return 'paytr';
        }

        if (
            $this->input->post('mdstatus') !== null ||
            $this->input->post('oid') !== null
        ) {
            return 'garanti';
        }

        try {
            $settings = $this->payment_settings_model->get_settings();

            return !empty($settings['active_gateway']) && $settings['active_gateway'] !== 'none'
                ? $settings['active_gateway']
                : 'iyzico';
        } catch (Throwable) {
            return 'iyzico';
        }
    }

    /**
     * Determine if this request is a user's browser redirecting back from 3D Secure.
     */
    private function is_browser_return_request(): bool
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        if ($method === 'GET') {
            return true;
        }

        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        $has_html_accept = str_contains($accept, 'text/html');

        // iyzico 3D Secure sends a POST with 'token' to callbackUrl
        if ($this->input->post('token') !== null && $has_html_accept) {
            return true;
        }

        // Generic browser return query flags
        if ($this->input->get('return') === '1' || $this->input->get('status') !== null) {
            return true;
        }

        return false;
    }

    /**
     * Handle user browser redirect after 3D Secure authentication.
     */
    private function handle_browser_return(string $gateway): void
    {
        $token = $this->input->get_post('token');
        $status = strtolower((string) ($this->input->get_post('status') ?? ''));
        $transaction = null;
        $appointment = null;

        if (!empty($token)) {
            $transaction = $this->payment_transactions_model->find_by_intent_id($token);
        }

        $appointment_hash = null;

        if ($transaction !== null && !empty($transaction['id_appointments'])) {
            try {
                $appt = $this->appointments_model->find((int) $transaction['id_appointments']);
                $appointment_hash = $appt['hash'] ?? null;
            } catch (Throwable) {
                // Ignore missing appointment lookup error
            }
        }

        // If appointment hash is available and status is succeeded, redirect directly to appointment confirmation
        if (!empty($appointment_hash) && ($status === 'success' || $status === 'succeeded' || empty($status))) {
            redirect('booking_confirmation/of/' . $appointment_hash);

            return;
        }

        $is_success = ($status === 'success' || $status === 'succeeded' || ($transaction && $transaction['status'] === 'succeeded'));

        html_vars([
            'page_title' => 'BooKi — Ödeme Sonucu',
            'payment_status' => $is_success ? 'succeeded' : 'failed',
            'payment_error_message' => $is_success ? null : ($this->input->get_post('failed_reason_msg') ?: 'Ödeme tamamlanamadı.'),
            'transaction_ref' => $transaction['provider_transaction_id'] ?? $transaction['intent_id'] ?? $token ?: null,
            'amount' => $transaction['amount'] ?? null,
            'currency' => $transaction['currency'] ?? 'TRY',
            'gateway' => $gateway,
            'redirect_url' => !empty($appointment_hash) ? site_url('booking_confirmation/of/' . $appointment_hash) : null,
        ]);

        $this->load->view('pages/payment_callback_status');
    }

    /**
     * Generic webhook handler - called by all gateway endpoints.
     *
     * @param string $gateway Gateway name (iyzico, paytr, stripe).
     */
    private function handle_webhook(string $gateway): void
    {
        try {
            method('post');

            // Get the raw webhook body
            $raw_body = file_get_contents('php://input');

            if (empty($raw_body)) {
                log_message('warning', "Payment_webhooks::{$gateway} - empty webhook body");

                response('', 400);

                return;
            }

            // Get payment settings
            $settings = $this->payment_settings_model->get_settings();

            // Check if this gateway is active
            if ($settings['active_gateway'] !== $gateway) {
                log_message('warning', "Payment_webhooks::{$gateway} - webhook received but gateway is not active");

                response('', 403);

                return;
            }

            // Create gateway instance and verify webhook signature
            $payment_gateway = Payment_gateway_factory::make($settings);

            if ($payment_gateway === null) {
                log_message('error', 'Payment_webhooks::handle_webhook - no active payment gateway');

                response('', 403);

                return;
            }

            $headers = $this->input->request_headers();

            if (!$payment_gateway->verify_webhook_signature($raw_body, $headers)) {
                log_message('error', "Payment_webhooks::{$gateway} - webhook signature verification failed");

                response('', 403);

                return;
            }

            // Parse the webhook event
            $event = $payment_gateway->parse_webhook_event($raw_body, $headers);

            // Find the transaction by intent_id or provider_transaction_id
            $transaction = null;

            if (!empty($event['intent_id'])) {
                $transaction = $this->payment_transactions_model->find_by_intent_id($event['intent_id']);
            }

            if ($transaction === null && !empty($event['transaction_id'])) {
                $transaction = $this->payment_transactions_model->find_by_provider_transaction_id($event['transaction_id']);
            }

            if ($transaction === null) {
                log_message('warning', "Payment_webhooks::{$gateway} - transaction not found for event: " . json_encode($event));

                response('', 200);

                return;
            }

            // Update transaction status
            $this->payment_transactions_model->update_status(
                $transaction['id'],
                $event['status'],
                $raw_body,
            );

            // If payment succeeded, reconcile the linked appointment's payment record. The transaction status
            // above is already persisted at this point - this block is best-effort (like the order update below)
            // and must never fail the webhook response itself.
            if ($event['status'] === 'succeeded' && !empty($transaction['id_appointments'])) {
                try {
                    $appointment_id = (int) $transaction['id_appointments'];

                    // find() throws if the appointment no longer exists -> caught below as a warning.
                    $appointment = $this->appointments_model->find($appointment_id);

                    // Total the customer is expected to pay: price_override when set, else the service price.
                    $total = null;

                    if (!empty($appointment['price_override'])) {
                        $total = (float) $appointment['price_override'];
                    } elseif (!empty($appointment['id_services'])) {
                        $this->load->model('services_model');

                        $service = $this->services_model->find((int) $appointment['id_services']);
                        $total = !empty($service['price']) ? (float) $service['price'] : null;
                    }

                    if ($total === null || $total <= 0) {
                        $total = (float) ($transaction['amount'] ?? 0);
                    }

                    $collected = (float) ($transaction['amount'] ?? 0);
                    $balance = max(0.0, $total - $collected);

                    // A deposit only ever covers part of the price, so the appointment stays 'pending' (shown as
                    // unpaid in get_unpaid_sessions()) with the collected/remaining amounts recorded; when the
                    // payment covers the full price it is marked 'collected'.
                    if ($balance <= 0) {
                        $this->appointments_model->set_payment(
                            $appointment_id,
                            PAYMENT_STATUS_COLLECTED,
                            'virtual_pos',
                            $total,
                            0.0,
                            false,
                            0,
                        );
                    } else {
                        $this->appointments_model->set_payment(
                            $appointment_id,
                            PAYMENT_STATUS_PENDING,
                            'virtual_pos',
                            $collected,
                            $balance,
                            false,
                            0,
                        );
                    }

                    log_message(
                        'info',
                        "Payment_webhooks::{$gateway} - payment succeeded for appointment {$appointment_id}"
                            . " (collected: {$collected}, balance: {$balance})",
                    );
                } catch (Throwable $reconcile_error) {
                    log_message(
                        'warning',
                        "Payment_webhooks::{$gateway} - appointment payment reconciliation failed for "
                            . $transaction['id_appointments'] . ': ' . $reconcile_error->getMessage(),
                    );
                }
            }

            // BooKi (Dalga 1) - if this transaction is linked to a POS order (see
            // Orders_model::checkout(), migration 121's id_orders column), flip the order's status
            // in step with the transaction's. update_status() above already persisted the
            // transaction status change unconditionally - this is a pure best-effort side effect,
            // never allowed to fail the webhook response itself.
            if (!empty($transaction['id_orders'])) {
                try {
                    $this->load->model('orders_model');

                    $order_status = match ($event['status']) {
                        'succeeded' => 'paid',
                        'refunded', 'partially_refunded' => 'refunded',
                        default => null,
                    };

                    if ($order_status !== null) {
                        $this->orders_model->update_status((int) $transaction['id_orders'], $order_status);
                    }
                } catch (Throwable $order_error) {
                    log_message(
                        'warning',
                        "Payment_webhooks::{$gateway} - order status update failed for order " .
                            $transaction['id_orders'] . ': ' . $order_error->getMessage(),
                    );
                }
            }

            if ($gateway === 'paytr') {
                echo 'OK';

                return;
            }

            response('', 200);
        } catch (Throwable $e) {
            log_message('error', "Payment_webhooks::{$gateway} - " . $e->getMessage());

            response('', 500);
        }
    }
}
