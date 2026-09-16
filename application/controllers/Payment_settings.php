<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Payment settings controller (2026-08-27).
 *
 * Admin-facing payment gateway configuration page (select active gateway,
 * enter API credentials, configure deposit requirements).
 * ---------------------------------------------------------------------------- */

class Payment_settings extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('payment_settings_model');
    }

    /**
     * Render the payment settings configuration page.
     */
    public function index(): void
    {
        method('get');

        if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
            abort(403, 'Forbidden');
        }

        $settings = $this->payment_settings_model->get_settings();

        // Prepare settings for the view (mask sensitive fields)
        $display_settings = $settings;

        foreach (['iyzico_api_key', 'iyzico_secret_key', 'paytr_merchant_id', 'paytr_merchant_key',
                  'paytr_merchant_salt', 'stripe_publishable_key', 'stripe_secret_key', 'webhook_secret'] as $field) {
            if (!empty($display_settings[$field])) {
                $display_settings[$field . '_set'] = true;
                $display_settings[$field] = null;
            } else {
                $display_settings[$field . '_set'] = false;
            }
        }

        html_vars([
            'page_title' => 'Ödeme Ayarları',
            'active_menu' => PRIV_SYSTEM_SETTINGS,
            'settings' => $display_settings,
        ]);

        $this->load->view('pages/payment_settings');
    }

    /**
     * Save payment settings configuration.
     */
    public function save_settings(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            check('active_gateway', 'string|null');
            check('require_deposit', 'bool|null');
            check('deposit_type', 'string|null');
            check('deposit_value', 'numeric|null');
            check('is_sandbox', 'bool|null');

            // Gather all possible gateway settings
            check('iyzico_api_key', 'string|null');
            check('iyzico_secret_key', 'string|null');
            check('paytr_merchant_id', 'string|null');
            check('paytr_merchant_key', 'string|null');
            check('paytr_merchant_salt', 'string|null');
            check('stripe_publishable_key', 'string|null');
            check('stripe_secret_key', 'string|null');

            $data = [
                'active_gateway' => request('active_gateway', 'none'),
                'require_deposit' => filter_var(request('require_deposit', false), FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                'deposit_type' => request('deposit_type') ?: null,
                'deposit_value' => request('deposit_value') ? (float) request('deposit_value') : null,
                'is_sandbox' => filter_var(request('is_sandbox', true), FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
            ];

            // Only include gateway-specific fields if they're provided (non-empty)
            foreach (['iyzico_api_key', 'iyzico_secret_key', 'paytr_merchant_id', 'paytr_merchant_key',
                      'paytr_merchant_salt', 'stripe_publishable_key', 'stripe_secret_key'] as $field) {
                $value = request($field) ?: null;

                if ($value) {
                    $data[$field] = $value;
                }
            }

            // Validate deposit settings if enabled
            if ($data['require_deposit'] == 1) {
                if (empty($data['deposit_type'])) {
                    throw new InvalidArgumentException('Deposit type is required when deposits are enabled.');
                }

                if ($data['deposit_type'] === 'fixed' || $data['deposit_type'] === 'percentage') {
                    if ($data['deposit_value'] === null || $data['deposit_value'] <= 0) {
                        throw new InvalidArgumentException('Deposit value must be greater than 0.');
                    }

                    if ($data['deposit_type'] === 'percentage' && $data['deposit_value'] > 100) {
                        throw new InvalidArgumentException('Deposit percentage cannot exceed 100.');
                    }
                } else {
                    throw new InvalidArgumentException('Invalid deposit type.');
                }
            }

            // Validate active_gateway
            if (!in_array($data['active_gateway'], ['none', 'iyzico', 'paytr', 'stripe'], true)) {
                throw new InvalidArgumentException('Invalid payment gateway selection.');
            }

            $this->payment_settings_model->save_settings($data);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
