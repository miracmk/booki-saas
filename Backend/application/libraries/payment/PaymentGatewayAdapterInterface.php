<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi / RandevuBurada Ecosystem
 * Interface: PaymentGatewayAdapterInterface
 * 
 * Abstraction layer for Pre-Authorization, Void, Capture, and Marketplace Split.
 * ---------------------------------------------------------------------------- */

interface PaymentGatewayAdapterInterface
{
    /**
     * Issue a Pre-Authorization hold on the customer's card.
     *
     * @param float  $amount      Amount to authorize/hold.
     * @param string $currency    Currency code (e.g., 'TRY').
     * @param array  $cardPayload Card tokens or 3D session data.
     * @param array  $metadata    Appointment and merchant metadata.
     * 
     * @return array Standardized result containing 'transaction_id', 'status', 'auth_code', 'raw_response'.
     * @throws RuntimeException On gateway failure.
     */
    public function holdPreAuth(float $amount, string $currency, array $cardPayload, array $metadata): array;

    /**
     * Void / Release an active pre-authorization hold (Scenario A: Customer Arrived).
     *
     * @param string $transactionId The gateway authorization / transaction ID.
     * @param array  $options       Optional parameters (reason, appointment_id).
     * 
     * @return array Standardized result containing 'status', 'voided_at', 'raw_response'.
     * @throws RuntimeException On gateway failure.
     */
    public function voidPreAuth(string $transactionId, array $options = []): array;

    /**
     * Capture a pre-authorized hold with optional marketplace split (Scenario B: No-Show).
     *
     * @param string $transactionId The gateway authorization / transaction ID.
     * @param float  $amount        Amount to capture (e.g. deposit amount).
     * @param array  $splitData     Marketplace split details (platform_amount, merchant_amount, sub_merchant_id).
     * 
     * @return array Standardized result containing 'status', 'captured_amount', 'split_result', 'raw_response'.
     * @throws RuntimeException On gateway failure.
     */
    public function capturePreAuth(string $transactionId, float $amount, array $splitData = []): array;
}
