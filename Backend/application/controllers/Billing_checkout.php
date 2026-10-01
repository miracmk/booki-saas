<?php defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'libraries/payment/PlatformBillingConfig.php';
require_once APPPATH . 'libraries/payment/ToslaPaymentGatewayAdapter.php';

/* ----------------------------------------------------------------------------
 * BooKi / RandevuBurada Ecosystem
 * Controller: Billing_checkout
 * 
 * Manages SaaS Subscriptions and AI Assistant Add-on purchases via Tosla İşim:
 * 1. Plan Subscriptions (Starter, Professional, Premium - Monthly/Annual)
 * 2. AI Conversation Packages (1K: 300 TL, 5K: 1.400 TL, 10K: 2.500 TL)
 * 3. 3D Secure Payment Initialization & Callback Processing
 * Supports dynamic Sandbox (Dev) vs Live Production (Prod) credentials.
 * ---------------------------------------------------------------------------- */

class Billing_checkout extends App_Controller
{
    private ToslaPaymentGatewayAdapter $gateway;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();

        // Platform Master POS: Tosla İşim (Environment-Aware)
        $this->gateway = new ToslaPaymentGatewayAdapter();
    }

    /**
     * Get list of available SaaS plans and AI Conversation packages.
     * GET /billing_checkout/tariffs
     */
    public function tariffs(): void
    {
        json_response([
            'status' => 'success',
            'data'   => [
                'plans'                 => PlatformBillingConfig::PLANS,
                'included_ai_limit'     => PlatformBillingConfig::INCLUDED_AI_CONVERSATIONS,
                'ai_addons'             => PlatformBillingConfig::AI_CONVERSATION_ADDONS,
                'is_sandbox'            => $this->gateway->isSandbox(),
                'commission_structure'  => [
                    'payment_gateway_rate' => PlatformBillingConfig::RATE_PAYMENT_GATEWAY * 100 . '%',
                    'deposit_gateway_rate' => PlatformBillingConfig::RATE_DEPOSIT_GATEWAY_COMBINED * 100 . '%',
                    'combined_total_rate'  => PlatformBillingConfig::RATE_FULL_GATEWAY_COMBINED * 100 . '%',
                    'standalone_deposit'   => PlatformBillingConfig::RATE_STANDALONE_DEPOSIT * 100 . '%',
                    'note'                 => 'Ödeme altyapısı kullanımında kapora sistemi şarttır.'
                ]
            ]
        ]);
    }

    /**
     * Initiate checkout for SaaS Plan Subscription.
     * POST /billing_checkout/subscribe_plan
     * Payload: { plan_key: 'starter'|'professional'|'premium', period: 'monthly'|'annual', card_number, expire_month, expire_year, cvv, card_holder_name }
     */
    public function subscribe_plan(): void
    {
        try {
            method('post');

            $post = $this->input->post(null, true) ?: json_decode((string)file_get_contents('php://input'), true);

            $planKey = $post['plan_key'] ?? '';
            $period  = $post['period'] ?? 'monthly';

            if (!isset(PlatformBillingConfig::PLANS[$planKey])) {
                throw new InvalidArgumentException("Geçersiz paket seçimi: {$planKey}");
            }

            $plan = PlatformBillingConfig::PLANS[$planKey];
            $amount = ($period === 'annual') ? $plan['annual_total_price'] : $plan['monthly_price'];
            $tenantId = session('user_id') ?: ($post['tenant_id'] ?? null);

            $orderId = 'BK_SUB_' . time() . rand(10, 99);

            $metadata = [
                'type'         => 'saas_subscription',
                'plan_key'     => $planKey,
                'period'       => $period,
                'tenant_id'    => $tenantId,
                'amount'       => $amount,
                'order_id'     => $orderId,
                'callback_url' => site_url('billing_checkout/tosla_subscription_callback')
            ];

            $cardPayload = [
                'card_holder_name' => $post['card_holder_name'] ?? $post['card_holder'] ?? '',
                'card_number'      => $post['card_number'] ?? '',
                'expire_month'     => $post['expire_month'] ?? '',
                'expire_year'      => $post['expire_year'] ?? '',
                'cvv'              => $post['cvv'] ?? ''
            ];

            // Execute direct 3D payment through Tosla
            $paymentResult = $this->gateway->directThreeDPayment(
                $amount,
                $orderId,
                $metadata['callback_url'],
                $cardPayload,
                $metadata
            );

            // If in simulated sandbox mode where payment completes instantly
            if (($paymentResult['status'] ?? '') === 'success') {
                $this->activateSubscription($tenantId, $planKey, $period, $amount, $orderId);
            }

            json_response([
                'status'   => 'success',
                'order_id' => $orderId,
                'amount'   => $amount,
                'plan'     => $plan['name'],
                'period'   => $period,
                'payment'  => $paymentResult
            ]);
        } catch (Throwable $e) {
            json_response([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Initiate checkout for AI Conversation Package Addon.
     * POST /billing_checkout/purchase_ai_addon
     * Payload: { addon_key: 'ai_1k'|'ai_5k'|'ai_10k', card_number, expire_month, expire_year, cvv, card_holder_name }
     */
    public function purchase_ai_addon(): void
    {
        try {
            method('post');

            $post = $this->input->post(null, true) ?: json_decode((string)file_get_contents('php://input'), true);

            $addonKey = $post['addon_key'] ?? '';
            if (!isset(PlatformBillingConfig::AI_CONVERSATION_ADDONS[$addonKey])) {
                throw new InvalidArgumentException("Geçersiz AI paketi: {$addonKey}");
            }

            $addon = PlatformBillingConfig::AI_CONVERSATION_ADDONS[$addonKey];
            $amount = $addon['price'];
            $tenantId = session('user_id') ?: ($post['tenant_id'] ?? null);

            $orderId = 'BK_AI_' . time() . rand(10, 99);

            $metadata = [
                'type'          => 'ai_addon_purchase',
                'addon_key'     => $addonKey,
                'conversations' => $addon['conversations'],
                'tenant_id'     => $tenantId,
                'amount'        => $amount,
                'order_id'      => $orderId,
                'callback_url'  => site_url('billing_checkout/tosla_addon_callback')
            ];

            $cardPayload = [
                'card_holder_name' => $post['card_holder_name'] ?? $post['card_holder'] ?? '',
                'card_number'      => $post['card_number'] ?? '',
                'expire_month'     => $post['expire_month'] ?? '',
                'expire_year'      => $post['expire_year'] ?? '',
                'cvv'              => $post['cvv'] ?? ''
            ];

            $paymentResult = $this->gateway->directThreeDPayment(
                $amount,
                $orderId,
                $metadata['callback_url'],
                $cardPayload,
                $metadata
            );

            json_response([
                'status'        => 'success',
                'order_id'      => $orderId,
                'amount'        => $amount,
                'addon'         => $addon['label'],
                'conversations' => $addon['conversations'],
                'payment'       => $paymentResult
            ]);
        } catch (Throwable $e) {
            json_response([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * 3D Secure Callback for SaaS Subscriptions.
     * Tosla returns customer browser back to this URL after bank OTP verification.
     */
    public function tosla_subscription_callback(): void
    {
        $postData = $this->input->post(null, true) ?: $_POST;
        log_message('info', 'Tosla subscription callback: ' . json_encode($postData));

        $isSuccess = $this->gateway->isCallbackSuccessful($postData);
        $orderId = (string)($postData['OrderId'] ?? $postData['orderId'] ?? '');
        $bankMsg = (string)($postData['BankResponseMessage'] ?? $postData['Message'] ?? ($isSuccess ? 'Ödeme Başarılı' : 'Ödeme Onaylanamadı'));
        $transactionId = (string)($postData['TransactionId'] ?? $postData['transactionId'] ?? $orderId);

        // Parse orderId format: SUB_{PLAN}_{TENANTID}_{TIME}
        if ($isSuccess && !empty($orderId)) {
            $parts = explode('_', $orderId);
            $planKey = strtolower($parts[1] ?? 'starter');
            $tenantId = !empty($parts[2]) ? (int)$parts[2] : null;

            $plan = PlatformBillingConfig::PLANS[$planKey] ?? PlatformBillingConfig::PLANS['starter'];
            $this->activateSubscription($tenantId, $planKey, 'monthly', $plan['monthly_price'], $orderId, $transactionId);
        }

        html_vars([
            'page_title'            => 'BooKi — Abonelik Ödeme Sonucu',
            'payment_status'        => $isSuccess ? 'succeeded' : 'failed',
            'payment_error_message' => $isSuccess ? null : $bankMsg,
            'transaction_ref'       => $transactionId,
            'amount'                => isset($postData['Amount']) ? ((float)$postData['Amount'] / 100) : null,
            'currency'              => 'TRY',
            'gateway'               => 'Tosla İşim POS',
            'redirect_url'          => site_url('onboarding')
        ]);

        $this->load->view('pages/payment_callback_status');
    }

    /**
     * 3D Secure Callback for AI Conversation Add-ons.
     */
    public function tosla_addon_callback(): void
    {
        $postData = $this->input->post(null, true) ?: $_POST;
        log_message('info', 'Tosla AI addon callback: ' . json_encode($postData));

        $isSuccess = $this->gateway->isCallbackSuccessful($postData);
        $orderId = (string)($postData['OrderId'] ?? $postData['orderId'] ?? '');
        $bankMsg = (string)($postData['BankResponseMessage'] ?? $postData['Message'] ?? ($isSuccess ? 'Ek Paket Satın Alındı' : 'Ödeme Onaylanamadı'));
        $transactionId = (string)($postData['TransactionId'] ?? $postData['transactionId'] ?? $orderId);

        html_vars([
            'page_title'            => 'BooKi — AI Paket Ödeme Sonucu',
            'payment_status'        => $isSuccess ? 'succeeded' : 'failed',
            'payment_error_message' => $isSuccess ? null : $bankMsg,
            'transaction_ref'       => $transactionId,
            'amount'                => isset($postData['Amount']) ? ((float)$postData['Amount'] / 100) : null,
            'currency'              => 'TRY',
            'gateway'               => 'Tosla İşim POS',
            'redirect_url'          => site_url('dashboard')
        ]);

        $this->load->view('pages/payment_callback_status');
    }

    /**
     * Helper to activate a SaaS subscription in the database.
     */
    private function activateSubscription(
        ?int $tenantId,
        string $planKey,
        string $period,
        float $amount,
        string $orderId,
        ?string $transactionId = null
    ): void {
        try {
            $duration = ($period === 'annual') ? '+1 year' : '+1 month';
            $expiresAt = date('Y-m-d H:i:s', strtotime($duration));

            if ($tenantId && $this->db->table_exists('ea_tenants')) {
                $this->db->where('id', $tenantId)->update('ea_tenants', [
                    'plan'                    => $planKey,
                    'billing_cycle'           => ($period === 'annual') ? 'yearly' : 'monthly',
                    'mrr_amount'              => $amount,
                    'onboarding_status'       => 'active',
                    'onboarding_completed_at' => date('Y-m-d H:i:s'),
                    'license_expires_at'      => $expiresAt,
                    'updated_at'              => date('Y-m-d H:i:s')
                ]);
            }

            // Record transaction in ea_payment_transactions if table exists
            if ($this->db->table_exists('ea_payment_transactions')) {
                $this->db->insert('ea_payment_transactions', [
                    'intent_id'               => $orderId,
                    'provider_transaction_id' => $transactionId ?: $orderId,
                    'gateway'                 => 'tosla',
                    'status'                  => 'succeeded',
                    'amount'                  => $amount,
                    'currency'                => 'TRY',
                    'created_at'              => date('Y-m-d H:i:s'),
                    'updated_at'              => date('Y-m-d H:i:s')
                ]);
            }
        } catch (Throwable $e) {
            log_message('error', 'Failed to activate tenant subscription: ' . $e->getMessage());
        }
    }
}
