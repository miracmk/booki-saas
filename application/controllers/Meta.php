<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Unified Meta (Facebook, Instagram, WhatsApp, Ads & LeadGen) Controller
 *
 * Central endpoint for Meta Graph API / Marketing API / WhatsApp Cloud API:
 * 1. Webhook Verification (GET /meta/webhook):
 *    Validates hub.challenge against platform master verify token.
 * 2. Event Ingestion (POST /meta/webhook):
 *    Receives webhooks for Lead Ads (leadgen), Ad Accounts, WhatsApp & Instagram.
 * 3. Central OAuth Relay (/meta/oauth_callback):
 *    Handles multi-tenant OAuth login & Meta Business account linking.
 * ---------------------------------------------------------------------------- */

class Meta extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('tenant_helper');
    }

    /**
     * Unified Meta Webhook endpoint (/meta/webhook)
     */
    public function webhook(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if ($method === 'GET') {
            $this->webhook_verify();
            return;
        }

        if ($method === 'POST') {
            $this->webhook_receive();
            return;
        }

        abort(405, 'Method Not Allowed');
    }

    /**
     * Webhook verification challenge (GET).
     */
    private function webhook_verify(): void
    {
        try {
            $mode = request('hub_mode') ?? (request('hub.mode') ?? ($_GET['hub_mode'] ?? ($_GET['hub.mode'] ?? null)));
            $token = request('hub_verify_token') ?? (request('hub.verify_token') ?? ($_GET['hub_verify_token'] ?? ($_GET['hub.verify_token'] ?? null)));
            $challenge = request('hub_challenge') ?? (request('hub.challenge') ?? ($_GET['hub_challenge'] ?? ($_GET['hub.challenge'] ?? null)));

            if (empty($mode) || empty($challenge)) {
                parse_str($_SERVER['QUERY_STRING'] ?? '', $qs);
                $mode = $mode ?: ($qs['hub_mode'] ?? ($qs['hub.mode'] ?? null));
                $token = $token ?: ($qs['hub_verify_token'] ?? ($qs['hub.verify_token'] ?? null));
                $challenge = $challenge ?: ($qs['hub_challenge'] ?? ($qs['hub.challenge'] ?? null));
            }

            if (empty($mode) || empty($token) || empty($challenge)) {
                log_message('error', 'Meta::webhook_verify - Missing hub parameters');
                abort(403, 'Forbidden: Missing Parameters');
            }

            // Expected token from master_setting, env, or platform default
            $master_token = master_setting('meta_webhook_verify_token')
                ?: (getenv('META_WEBHOOK_VERIFY_TOKEN') ?: 'bookiapp_meta_webhook_secret_2026');

            if ($mode === 'subscribe' && hash_equals($master_token, (string) $token)) {
                $this->output
                    ->set_status_header(200)
                    ->set_content_type('text/plain', 'UTF-8')
                    ->set_output((string) $challenge);
                return;
            }

            log_message('error', 'Meta::webhook_verify - Token mismatch. Provided: ' . $token);
            abort(403, 'Forbidden: Token Mismatch');
        } catch (Throwable $e) {
            log_message('error', 'Meta::webhook_verify exception: ' . $e->getMessage());
            abort(403, 'Forbidden');
        }
    }

    /**
     * Webhook payload receiver (POST).
     */
    private function webhook_receive(): void
    {
        try {
            $raw_input = file_get_contents('php://input');
            $payload = json_decode($raw_input, true) ?: [];

            if (empty($payload)) {
                $this->output->set_status_header(200)->set_output('EVENT_RECEIVED');
                return;
            }

            // Verify Meta HMAC signature if App Secret is configured
            $app_secret = master_setting('meta_app_secret') ?: (getenv('META_APP_SECRET') ?: '');
            $signature_header = $this->input->get_request_header('X-Hub-Signature-256')
                ?? ($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? null);

            if (!empty($app_secret) && !empty($signature_header)) {
                $expected = 'sha256=' . hash_hmac('sha256', $raw_input, $app_secret);
                if (!hash_equals($expected, (string) $signature_header)) {
                    log_message('error', 'Meta::webhook_receive - Invalid X-Hub-Signature-256');
                    abort(403, 'Invalid Meta signature');
                }
            }

            $object = $payload['object'] ?? '';

            // Handle Lead Generation Webhooks
            if ($object === 'page') {
                $this->handle_leadgen_events($payload);
            }

            $this->output->set_status_header(200)->set_output('EVENT_RECEIVED');
        } catch (Throwable $e) {
            log_message('error', 'Meta::webhook_receive exception: ' . $e->getMessage());
            $this->output->set_status_header(200)->set_output('EVENT_RECEIVED');
        }
    }

    /**
     * Process Meta Lead Ads events
     */
    private function handle_leadgen_events(array $payload): void
    {
        $entries = $payload['entry'] ?? [];
        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];
            foreach ($changes as $change) {
                if (($change['field'] ?? '') === 'leadgen') {
                    $value = $change['value'] ?? [];
                    log_message('info', 'Meta LeadGen event received: ' . json_encode($value));
                }
            }
        }
    }

    /**
     * Central OAuth Callback Relay (/meta/oauth_callback)
     */
    public function oauth_callback(): void
    {
        $code = request('code');
        $state = request('state');

        if (empty($code)) {
            $error_desc = request('error_description') ?? 'Authorization was not granted.';
            show_error('Meta OAuth failed: ' . $error_desc, 400);
            return;
        }

        // Redirect or relay to originating tenant
        redirect('superadmin_settings');
    }
}

