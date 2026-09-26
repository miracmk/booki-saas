<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - payment gateway interface (2026-08-27).
 *
 * Contract for all payment gateway implementations (iyzico, PayTR, Stripe).
 * Each gateway must implement these methods to work with the payment system.
 * ---------------------------------------------------------------------------- */

interface Payment_gateway_interface
{
    /**
     * Create a payment intent for the given amount.
     *
     * @param float $amount Amount to charge.
     * @param string $currency Currency code (e.g., 'TRY', 'USD').
     * @param array $metadata Additional metadata (appointment_id, customer_id, etc.).
     *
     * @return array Response with at minimum an 'intent_id' field.
     *
     * @throws RuntimeException On API errors.
     */
    public function create_payment_intent(float $amount, string $currency, array $metadata): array;

    /**
     * Charge/capture a payment intent.
     *
     * @param string $intent_id Payment intent ID from create_payment_intent().
     * @param array $payload Additional data needed for charging (token, card details, etc.).
     *
     * @return array Response with at minimum 'status' (succeeded/failed) and
     *               'provider_transaction_id' fields.
     *
     * @throws RuntimeException On API errors.
     */
    public function charge(string $intent_id, array $payload): array;

    /**
     * Refund a previously charged transaction.
     *
     * @param string $provider_transaction_id Transaction ID from charge() response.
     * @param float|null $amount Partial refund amount; null = full refund.
     *
     * @return array Response with 'status' and 'refund_id' fields.
     *
     * @throws RuntimeException On API errors.
     */
    public function refund(string $provider_transaction_id, ?float $amount = null): array;

    /**
     * Verify webhook signature (ensures the webhook came from the gateway, not a spoofed source).
     *
     * @param string $raw_body Raw webhook body (before JSON parsing).
     * @param array $headers HTTP headers from the webhook request.
     *
     * @return bool True if signature is valid, false otherwise.
     */
    public function verify_webhook_signature(string $raw_body, array $headers): bool;

    /**
     * Parse a webhook event into a normalized format.
     *
     * @param string $raw_body Raw webhook body.
     * @param array $headers HTTP headers.
     *
     * @return array Normalized event with keys: 'type' (event name), 'transaction_id',
     *               'status', 'amount', 'currency', 'metadata'.
     *
     * @throws RuntimeException On parsing errors.
     */
    public function parse_webhook_event(string $raw_body, array $headers): array;
}
