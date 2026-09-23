<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Online Appointment Scheduler
 *
 * @package     KiReservation
 * @author      Ki Software
 * @copyright   Copyright (c) Ki Software
 * @license     Proprietary - see LICENSE file
 * @link        https://kisoftware.com
 * ---------------------------------------------------------------------------- */

/**
 * SaaS admin panel (admin-bookiapp.kibusiness.co) - platform-wide settings.
 * Manages Google OAuth, Platform SMTP/IMAP, and Universal AI/LLM Provider API Keys.
 */
class Superadmin_settings extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!session('superadmin_id')) {
            redirect('superadmin_auth');
            exit();
        }
    }

    public function index(): void
    {
        method('get');

        html_vars([
            'page_title' => 'BooKi - Platform Ayarları',
            'csrf_token' => $this->security->get_csrf_hash(),
            'superadmin_username' => session('superadmin_username'),
            'google_client_id' => master_setting('google_client_id') ?? '',
            'google_client_secret_set' => !empty(master_setting('google_client_secret')),
            'meta_app_id' => master_setting('meta_app_id') ?? '',
            'meta_app_secret_set' => !empty(master_setting('meta_app_secret')),
            'meta_webhook_verify_token' => master_setting('meta_webhook_verify_token') ?? 'bookiapp_meta_webhook_secret_2026',
            'platform_smtp_host' => master_setting('platform_smtp_host') ?? '',
            'platform_smtp_port' => master_setting('platform_smtp_port') ?? '',
            'platform_smtp_crypto' => master_setting('platform_smtp_crypto') ?? 'tls',
            'platform_smtp_user' => master_setting('platform_smtp_user') ?? '',
            'platform_smtp_pass_set' => !empty(master_setting('platform_smtp_pass')),
            'platform_smtp_from_name' => master_setting('platform_smtp_from_name') ?? '',
            'platform_smtp_from_address' => master_setting('platform_smtp_from_address') ?? '',
            'platform_imap_host' => master_setting('platform_imap_host') ?? '',
            'platform_imap_port' => master_setting('platform_imap_port') ?? '',
            'platform_imap_crypto' => master_setting('platform_imap_crypto') ?? 'ssl',
            'platform_imap_user' => master_setting('platform_imap_user') ?? '',
            'platform_imap_pass_set' => !empty(master_setting('platform_imap_pass')),

            // AI / LLM Gateway Master Settings
            'ai_provider' => master_setting('ai_provider') ?? 'auto',
            'google_ai_key_set' => !empty(master_setting('google_ai_key')) || !empty(getenv('GEMINI_API_KEY')),
            'ai_model_google' => master_setting('ai_model_google') ?? 'gemini-1.5-flash',
            'groq_api_key_set' => !empty(master_setting('groq_api_key')) || !empty(getenv('GROQ_API_KEY')),
            'ai_model_groq' => master_setting('ai_model_groq') ?? 'llama-3.3-70b-versatile',
            'openrouter_api_key_set' => !empty(master_setting('openrouter_api_key')) || !empty(getenv('OPENROUTER_API_KEY')),
            'ai_model_openrouter' => master_setting('ai_model_openrouter') ?? 'google/gemini-2.0-flash-exp:free',
            'openai_api_key_set' => !empty(master_setting('openai_api_key')) || !empty(getenv('OPENAI_API_KEY')),
            'ai_model_openai' => master_setting('ai_model_openai') ?? 'gpt-4o-mini',
            'anthropic_api_key_set' => !empty(master_setting('anthropic_api_key')) || !empty(getenv('ANTHROPIC_API_KEY')),
            'ai_model_anthropic' => master_setting('ai_model_anthropic') ?? 'claude-3-5-haiku-20241022',

            // BooKi Marketplace & Sectoral Commissions
            'marketplace_commission_rate' => master_setting('marketplace_commission_rate') ?? '5.00',
            'sector_commission_rates' => get_all_sector_commission_rates(),
            'wallet_stats' => $this->get_wallet_stats(),
        ]);

        $this->load->view('pages/superadmin_settings');
    }

    /**
     * Retrieve aggregate stats across all tenant wallets from master DB.
     */
    protected function get_wallet_stats(): array
    {
        $stats = [
            'total_balance' => 0.00,
            'total_earned' => 0.00,
            'total_commission' => 0.00,
            'active_wallets' => 0,
        ];

        try {
            $master_db = $this->load->database('default', true);
            if ($master_db && $master_db->table_exists('tenant_wallets')) {
                $row = $master_db->select('SUM(balance) AS total_balance, SUM(total_earned) AS total_earned, SUM(total_commission) AS total_commission, COUNT(*) AS active_wallets')
                    ->get('tenant_wallets')
                    ->row_array();

                if ($row) {
                    $stats['total_balance'] = (float) ($row['total_balance'] ?? 0);
                    $stats['total_earned'] = (float) ($row['total_earned'] ?? 0);
                    $stats['total_commission'] = (float) ($row['total_commission'] ?? 0);
                    $stats['active_wallets'] = (int) ($row['active_wallets'] ?? 0);
                }
            }
        } catch (Throwable $e) {
            log_message('error', 'Superadmin_settings::get_wallet_stats: ' . $e->getMessage());
        }

        return $stats;
    }

    public function save(): void
    {
        try {
            method('post');

            check('google_client_id', 'string|null');
            check('google_client_secret', 'string|null');
            check('meta_app_id', 'string|null');
            check('meta_app_secret', 'string|null');
            check('meta_webhook_verify_token', 'string|null');
            check('platform_smtp_host', 'string|null');
            check('platform_smtp_port', 'string|null');
            check('platform_smtp_crypto', 'string|null');
            check('platform_smtp_user', 'string|null');
            check('platform_smtp_pass', 'string|null');
            check('platform_smtp_from_name', 'string|null');
            check('platform_smtp_from_address', 'string|null');
            check('platform_imap_host', 'string|null');
            check('platform_imap_port', 'string|null');
            check('platform_imap_crypto', 'string|null');
            check('platform_imap_user', 'string|null');
            check('platform_imap_pass', 'string|null');

            // AI / LLM Fields
            check('ai_provider', 'string|null');
            check('google_ai_key', 'string|null');
            check('ai_model_google', 'string|null');
            check('groq_api_key', 'string|null');
            check('ai_model_groq', 'string|null');
            check('openrouter_api_key', 'string|null');
            check('ai_model_openrouter', 'string|null');
            check('openai_api_key', 'string|null');
            check('ai_model_openai', 'string|null');
            check('anthropic_api_key', 'string|null');
            check('ai_model_anthropic', 'string|null');

            if (request('google_client_id') !== null) {
                master_setting('google_client_id', trim((string) request('google_client_id')));
            }

            $secret = trim((string) request('google_client_secret'));
            if ($secret !== '') {
                master_setting('google_client_secret', $secret);
            }

            if (request('meta_app_id') !== null) {
                master_setting('meta_app_id', trim((string) request('meta_app_id')));
            }

            $meta_sec = trim((string) request('meta_app_secret'));
            if ($meta_sec !== '') {
                master_setting('meta_app_secret', $meta_sec);
            }

            if (request('meta_webhook_verify_token') !== null) {
                master_setting('meta_webhook_verify_token', trim((string) request('meta_webhook_verify_token')));
            }

            $plaintext_fields = [
                'platform_smtp_host', 'platform_smtp_port', 'platform_smtp_crypto',
                'platform_smtp_user', 'platform_smtp_from_name', 'platform_smtp_from_address',
                'platform_imap_host', 'platform_imap_port', 'platform_imap_crypto', 'platform_imap_user',
                'ai_provider', 'ai_model_google', 'ai_model_groq', 'ai_model_openrouter',
                'ai_model_openai', 'ai_model_anthropic'
            ];

            foreach ($plaintext_fields as $field) {
                if (request($field) !== null) {
                    master_setting($field, trim((string) request($field)));
                }
            }

            $smtp_pass = trim((string) request('platform_smtp_pass'));
            if ($smtp_pass !== '') {
                master_setting('platform_smtp_pass', $smtp_pass);
            }

            $imap_pass = trim((string) request('platform_imap_pass'));
            if ($imap_pass !== '') {
                master_setting('platform_imap_pass', $imap_pass);
            }

            // Save API Keys (only when not empty)
            $api_key_fields = [
                'google_ai_key',
                'groq_api_key',
                'openrouter_api_key',
                'openai_api_key',
                'anthropic_api_key',
            ];

            foreach ($api_key_fields as $key_field) {
                $key_val = trim((string) request($key_field));
                if ($key_val !== '') {
                    master_setting($key_field, $key_val);
                    // Also maintain backward-compatible aliases if applicable
                    if ($key_field === 'google_ai_key') {
                        master_setting('gemini_api_key', $key_val);
                    }
                }
            }

            // BooKi Marketplace Commissions
            if (request('marketplace_commission_rate') !== null) {
                $gen_rate = max(0, min(100, (float) request('marketplace_commission_rate')));
                master_setting('marketplace_commission_rate', number_format($gen_rate, 2, '.', ''));
            }

            if (request('sector_rates') !== null && is_array(request('sector_rates'))) {
                $cleaned_rates = [];
                foreach (request('sector_rates') as $sec_key => $sec_rate) {
                    $cleaned_rates[preg_replace('/[^a-z0-9_]/', '', (string)$sec_key)] = max(0, min(100, (float)$sec_rate));
                }
                master_setting('marketplace_sector_commission_rates', json_encode($cleaned_rates));
            }

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Process daily settlements: calculates cleared payouts for tenants with positive balance,
     * logs settlement records in wallet_ledger, and resets/transfers the balance.
     */
    public function process_daily_settlements(): void
    {
        try {
            method('post');

            $master_db = $this->load->database('default', true);
            if (!$master_db || !$master_db->table_exists('tenant_wallets')) {
                throw new RuntimeException('Cüzdan tablosu bulunamadı.');
            }

            $wallets = $master_db->select('tw.*, t.company_name, t.subdomain, t.iban, t.bank_name')
                ->from('tenant_wallets tw')
                ->join('tenants t', 't.id = tw.id_tenants', 'left')
                ->where('tw.balance >', 0)
                ->get()
                ->result_array();

            $processed_count = 0;
            $total_settled_amount = 0.00;
            $batch_ref = 'STLM-' . date('Ymd-His');

            foreach ($wallets as $wallet) {
                $payout_amount = (float) $wallet['balance'];
                if ($payout_amount <= 0) {
                    continue;
                }

                $tenant_name = $wallet['company_name'] ?: $wallet['subdomain'];

                // Deduct from wallet balance
                $master_db->where('id', $wallet['id'])
                    ->update('tenant_wallets', [
                        'balance' => 0.00,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                // Record settlement entry in ledger
                $master_db->insert('wallet_ledger', [
                    'id_tenants' => $wallet['id_tenants'],
                    'type' => 'settlement',
                    'amount' => -$payout_amount,
                    'currency' => 'TRY',
                    'reference_id' => $batch_ref . '-T' . $wallet['id_tenants'],
                    'description' => 'Günlük Hakediş Transferi (' . $tenant_name . ' - ' . ($wallet['iban'] ? 'IBAN: ' . $wallet['iban'] : 'Banka Transferi') . ')',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                $processed_count++;
                $total_settled_amount += $payout_amount;
            }

            json_response([
                'success' => true,
                'message' => "Günlük hakediş işlemi tamamlandı. {$processed_count} işletmeye toplam ₺" . number_format($total_settled_amount, 2) . " tutarında ödeme emri oluşturuldu.",
                'processed_count' => $processed_count,
                'total_settled_amount' => $total_settled_amount,
                'batch_ref' => $batch_ref,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
