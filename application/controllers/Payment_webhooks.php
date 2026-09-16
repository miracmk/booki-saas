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
