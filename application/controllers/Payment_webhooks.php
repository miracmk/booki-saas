<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - payment webhooks controller (2026-08-27).
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
     * iyzico webhook endpoint.
     */
    public function iyzico(): void
    {
        $this->handle_webhook('iyzico');
    }

    /**
     * PayTR webhook endpoint.
     */
    public function paytr(): void
    {
        $this->handle_webhook('paytr');
    }

    /**
     * Stripe webhook endpoint.
     */
    public function stripe(): void
    {
        $this->handle_webhook('stripe');
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

            // If payment succeeded, update appointment payment status
            if ($event['status'] === 'succeeded' && !empty($transaction['id_appointments'])) {
                $appointment_id = (int) $transaction['id_appointments'];

                // TODO: Call Appointments_model::set_payment() to mark appointment as paid
                // This assumes Appointments_model has a set_payment() method
                log_message('info', "Payment_webhooks::{$gateway} - payment succeeded for appointment {$appointment_id}");
            }

            // Ki Reservation (Dalga 1) - if this transaction is linked to a POS order (see
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

            response('', 200);
        } catch (Throwable $e) {
            log_message('error', "Payment_webhooks::{$gateway} - " . $e->getMessage());

            response('', 500);
        }
    }
}
