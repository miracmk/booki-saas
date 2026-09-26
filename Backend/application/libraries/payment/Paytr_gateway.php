<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - PayTR payment gateway (2026-08-27).
 *
 * Stub implementation of Payment_gateway_interface for PayTR.
 * Will be implemented in a future phase.
 * ---------------------------------------------------------------------------- */

require_once __DIR__ . '/Payment_gateway_interface.php';
require_once __DIR__ . '/Payment_gateway_abstract.php';

class Paytr_gateway extends Payment_gateway_abstract
{
    /**
     * Create a payment intent for the given amount.
     *
     * @param float $amount Amount to charge.
     * @param string $currency Currency code.
     * @param array $metadata Additional metadata.
     *
     * @return array
     */
    public function create_payment_intent(float $amount, string $currency, array $metadata): array
    {
        throw new RuntimeException('PayTR gateway is not yet implemented.');
    }

    /**
     * Charge/capture a payment intent.
     *
     * @param string $intent_id Payment intent ID.
     * @param array $payload Additional data for charging.
     *
     * @return array
     */
    public function charge(string $intent_id, array $payload): array
    {
        throw new RuntimeException('PayTR gateway is not yet implemented.');
    }

    /**
     * Refund a previously charged transaction.
     *
     * @param string $provider_transaction_id Transaction ID.
     * @param float|null $amount Partial refund amount; null = full refund.
     *
     * @return array
     */
    public function refund(string $provider_transaction_id, ?float $amount = null): array
    {
        throw new RuntimeException('PayTR gateway is not yet implemented.');
    }

    /**
     * Verify webhook signature.
     *
     * @param string $raw_body Raw webhook body.
     * @param array $headers HTTP headers.
     *
     * @return bool
     */
    public function verify_webhook_signature(string $raw_body, array $headers): bool
    {
        throw new RuntimeException('PayTR gateway is not yet implemented.');
    }

    /**
     * Parse a webhook event.
     *
     * @param string $raw_body Raw webhook body.
     * @param array $headers HTTP headers.
     *
     * @return array
     */
    public function parse_webhook_event(string $raw_body, array $headers): array
    {
        throw new RuntimeException('PayTR gateway is not yet implemented.');
    }
}
