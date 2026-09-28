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
 * ---------------------------------------------------------------------------- */

class Billing_checkout extends App_Controller
{
    private ToslaPaymentGatewayAdapter $gateway;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();

        // Platform Master POS: Tosla İşim
        $this->gateway = new ToslaPaymentGatewayAdapter([
            'client_id'  => '1000006967',
            'api_user'   => 'apiUser3041794',
            'api_pass'   => 'QJMGN0AX9E',
            'is_sandbox' => false
        ]);
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

            $post = $this->input->post(null, true) ?: json_decode(file_get_contents('php://input'), true);

            $planKey = $post['plan_key'] ?? '';
            $period  = $post['period'] ?? 'monthly';

            if (!isset(PlatformBillingConfig::PLANS[$planKey])) {
                throw new InvalidArgumentException("Geçersiz paket seçimi: {$planKey}");
            }

            $plan = PlatformBillingConfig::PLANS[$planKey];
            $amount = ($period === 'annual') ? $plan['annual_total_price'] : $plan['monthly_price'];
            $tenantId = session('user_id') ?: ($post['tenant_id'] ?? null);

            $orderId = 'SUB_' . strtoupper($planKey) . '_' . ($tenantId ?: '0') . '_' . time();

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
                'card_holder_name' => $post['card_holder_name'] ?? '',
                'card_number'      => $post['card_number'] ?? '',
                'expire_month'     => $post['expire_month'] ?? '',
                'expire_year'      => $post['expire_year'] ?? '',
                'cvv'              => $post['cvv'] ?? ''
            ];

            // Tosla pre-auth or direct payment
            $paymentResult = $this->gateway->holdPreAuth($amount, 'TRY', $cardPayload, $metadata);

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

            $post = $this->input->post(null, true) ?: json_decode(file_get_contents('php://input'), true);

            $addonKey = $post['addon_key'] ?? '';
            if (!isset(PlatformBillingConfig::AI_CONVERSATION_ADDONS[$addonKey])) {
                throw new InvalidArgumentException("Geçersiz AI paketi: {$addonKey}");
            }

            $addon = PlatformBillingConfig::AI_CONVERSATION_ADDONS[$addonKey];
            $amount = $addon['price'];
            $tenantId = session('user_id') ?: ($post['tenant_id'] ?? null);

            $orderId = 'AI_' . strtoupper($addonKey) . '_' . ($tenantId ?: '0') . '_' . time();

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
                'card_holder_name' => $post['card_holder_name'] ?? '',
                'card_number'      => $post['card_number'] ?? '',
                'expire_month'     => $post['expire_month'] ?? '',
                'expire_year'      => $post['expire_year'] ?? '',
                'cvv'              => $post['cvv'] ?? ''
            ];

            $paymentResult = $this->gateway->holdPreAuth($amount, 'TRY', $cardPayload, $metadata);

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
}
