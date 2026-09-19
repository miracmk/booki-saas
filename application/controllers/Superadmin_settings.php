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
        ]);

        $this->load->view('pages/superadmin_settings');
    }

    public function save(): void
    {
        try {
            method('post');

            check('google_client_id', 'string|null');
            check('google_client_secret', 'string|null');
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

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
