<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Unified Omnichannel Chat Portal & AI Copilot Controller
 *
 * Provides a modern, responsive WhatsApp Web-style inbox aggregating:
 * - BooKi AI Copilot (Pinned at the top with persistent memory)
 * - WhatsApp Business (Cloud API & QR Bridge)
 * - Instagram Direct Messages
 * - Telegram Bot
 * - Web Chat Widget
 *
 * Features:
 * - Channel logos & badges for every message and thread
 * - 1-Click Human Handoff / AI Autopilot toggle per conversation
 * - AI Draft Suggestion button ("🤖 AI Yanıtı Öner")
 * - Real-time filtering, search, and message dispatch
 * ---------------------------------------------------------------------------- */

class Chat_portal extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('chat_portal_model');
        $this->load->model('settings_model');
    }

    /**
     * Render the main Unified Chat Portal view.
     */
    public function index(): void
    {
        method('get');

        $user_id = (int) (session('user_id') ?: 1);
        if (!$user_id) {
            redirect('login');
            return;
        }

        $active_channel = $this->input->get('channel') ?: 'all';
        $search = $this->input->get('q') ?: '';

        $threads = $this->chat_portal_model->get_threads(
            $active_channel !== 'all' ? $active_channel : null,
            $search !== '' ? $search : null,
            $user_id
        );

        $initial_thread = !empty($threads) ? $threads[0] : null;
        $initial_messages = [];

        if ($initial_thread) {
            $initial_messages = $this->chat_portal_model->get_messages(
                $initial_thread['channel'],
                $initial_thread['thread_id'],
                $user_id
            );
        }

        $this->load->model('messaging_settings_model');
        $msg_settings = $this->messaging_settings_model->get_settings();
        $wa_mode = $msg_settings['whatsapp_mode'] ?? 'official';

        $bridge_status = 'disconnected';
        $bridge_session_name = 'BooKi Demo';
        try {
            if (!class_exists('Whatsapp_bridge', false)) {
                $this->load->library('whatsapp_bridge');
            }
            $bridge_url = !empty($msg_settings['whatsapp_bridge_url']) ? $msg_settings['whatsapp_bridge_url'] : (getenv('WA_BRIDGE_URL') ?: 'http://booki-wa:3000');
            $bridge_secret = !empty($msg_settings['whatsapp_bridge_secret']) ? $msg_settings['whatsapp_bridge_secret'] : (getenv('WA_BRIDGE_SECRET') ?: '');
            $bridge = new Whatsapp_bridge($bridge_url, $bridge_secret);
            $context = tenant_context();
            $tenant_key = Whatsapp_bridge::resolve_tenant_key($context['subdomain'] ?? 'salonflora');
            $session = $bridge->session_status($tenant_key);
            if (!empty($session) && !empty($session['status'])) {
                $bridge_status = $session['status'];
                $bridge_session_name = $session['name'] ?? $bridge_session_name;
            }
        } catch (Throwable $e) {
            log_message('error', 'Chat_portal bridge check error: ' . $e->getMessage());
        }

        $meta_configured = !empty($msg_settings['whatsapp_phone_number_id']) && !empty($msg_settings['whatsapp_access_token']);

        html_vars([
            'page_title' => 'Birleşik Chat Portalı & AI Copilot',
            'active_menu' => 'marketing',
            'active_channel' => $active_channel,
            'threads' => $threads,
            'initial_thread' => $initial_thread,
            'initial_messages' => $initial_messages,
            'csrf_token' => config_item('csrf_protection') ? $this->security->get_csrf_hash() : '',
            'wa_mode' => $wa_mode,
            'bridge_status' => $bridge_status,
            'bridge_session_name' => $bridge_session_name,
            'meta_configured' => $meta_configured,
        ]);

        script_vars([
            'chat_routes' => [
                'threads' => site_url('chat_portal/api_threads'),
                'messages' => site_url('chat_portal/api_messages'),
                'send' => site_url('chat_portal/api_send'),
                'toggle_handoff' => site_url('chat_portal/api_toggle_handoff'),
                'suggest' => site_url('chat_portal/api_suggest'),
                'switch_wa_mode' => site_url('chat_portal/api_switch_wa_mode'),
                'qr_status' => site_url('chat_portal/api_qr_status'),
                'qr_start' => site_url('chat_portal/api_qr_start'),
            ],
            'csrf_token' => config_item('csrf_protection') ? $this->security->get_csrf_hash() : '',
            'initial_channel' => $initial_thread['channel'] ?? 'ai',
            'initial_thread_id' => $initial_thread['thread_id'] ?? 'ai_copilot',
            'wa_mode' => $wa_mode,
            'bridge_status' => $bridge_status,
            'bridge_session_name' => $bridge_session_name,
            'meta_configured' => $meta_configured,
        ]);

        $this->load->view('pages/chat_portal');
    }

    /**
     * API: Get threads list.
     */
    public function api_threads(): void
    {
        try {
            method('get');
            $channel = $this->input->get('channel') ?: 'all';
            $search = $this->input->get('q') ?: '';
            $user_id = (int) (session('user_id') ?: 1);

            $threads = $this->chat_portal_model->get_threads(
                $channel !== 'all' ? $channel : null,
                $search !== '' ? $search : null,
                $user_id
            );

            json_response([
                'status' => 'success',
                'threads' => $threads,
                'count' => count($threads),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * API: Get messages for a thread.
     */
    public function api_messages(): void
    {
        try {
            method('get');
            $channel = (string) $this->input->get('channel');
            $thread_id = (string) $this->input->get('thread_id');
            $user_id = (int) (session('user_id') ?: 1);

            if (empty($channel) || empty($thread_id)) {
                throw new InvalidArgumentException('Kanal ve Thread ID gereklidir.');
            }

            $messages = $this->chat_portal_model->get_messages($channel, $thread_id, $user_id);
            $handoff_active = $this->chat_portal_model->is_handoff_active($channel, $thread_id);

            json_response([
                'status' => 'success',
                'channel' => $channel,
                'thread_id' => $thread_id,
                'handoff_active' => $handoff_active,
                'messages' => $messages,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * API: Send a message or query AI.
     */
    public function api_send(): void
    {
        try {
            method('post');
            check('channel', 'string');
            check('thread_id', 'string');
            check('message', 'string');

            $channel = (string) request('channel');
            $thread_id = (string) request('thread_id');
            $message = trim((string) request('message'));
            $user_id = (int) (session('user_id') ?: 1);

            if ($message === '') {
                throw new InvalidArgumentException('Mesaj boş olamaz.');
            }

            $res = $this->chat_portal_model->send_message($channel, $thread_id, $message, $user_id);

            json_response([
                'status' => 'success',
                'data' => $res,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * API: Toggle Handoff between Human and AI Autopilot.
     */
    public function api_toggle_handoff(): void
    {
        try {
            method('post');
            check('channel', 'string');
            check('thread_id', 'string');
            check('mode', 'string');

            $channel = (string) request('channel');
            $thread_id = (string) request('thread_id');
            $mode = (string) request('mode'); // 'human' or 'ai'
            $user_id = (int) (session('user_id') ?: 1);

            $res = $this->chat_portal_model->toggle_handoff($channel, $thread_id, $mode, $user_id);

            json_response($res);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * API: Generate AI response draft for agent review.
     */
    public function api_suggest(): void
    {
        try {
            method('post');
            check('channel', 'string');
            check('thread_id', 'string');

            $channel = (string) request('channel');
            $thread_id = (string) request('thread_id');

            $res = $this->chat_portal_model->get_ai_draft_suggestion($channel, $thread_id);

            json_response($res);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * API: Switch WhatsApp mode between 'official' and 'unofficial'
     */
    public function api_switch_wa_mode(): void
    {
        try {
            method('post');
            check('mode', 'string');
            $mode = (string) request('mode');
            if (!in_array($mode, ['official', 'unofficial'], true)) {
                throw new InvalidArgumentException('Geçersiz mod.');
            }
            $this->load->model('messaging_settings_model');
            $data = ['whatsapp_mode' => $mode];
            if ($mode === 'unofficial') {
                $data['whatsapp_unofficial_consent_at'] = date('Y-m-d H:i:s');
            }
            $this->messaging_settings_model->save_settings($data);
            json_response(['status' => 'success', 'mode' => $mode]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * API: Get WhatsApp QR bridge live status
     */
    public function api_qr_status(): void
    {
        try {
            method('get');
            $this->load->model('messaging_settings_model');
            $settings = $this->messaging_settings_model->get_settings();
            if (!class_exists('Whatsapp_bridge', false)) {
                $this->load->library('whatsapp_bridge');
            }
            $bridge_url = !empty($settings['whatsapp_bridge_url']) ? $settings['whatsapp_bridge_url'] : (getenv('WA_BRIDGE_URL') ?: 'http://booki-wa:3000');
            $bridge_secret = !empty($settings['whatsapp_bridge_secret']) ? $settings['whatsapp_bridge_secret'] : (getenv('WA_BRIDGE_SECRET') ?: '');
            $bridge = new Whatsapp_bridge($bridge_url, $bridge_secret);
            $context = tenant_context();
            $tenant_key = Whatsapp_bridge::resolve_tenant_key($context['subdomain'] ?? 'salonflora');
            $status = $bridge->session_status($tenant_key);
            json_response(['status' => 'success', 'session' => $status]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * API: Start WhatsApp QR session to generate fresh QR code
     */
    public function api_qr_start(): void
    {
        try {
            method('post');
            $this->load->model('messaging_settings_model');
            $settings = $this->messaging_settings_model->get_settings();
            if (!class_exists('Whatsapp_bridge', false)) {
                $this->load->library('whatsapp_bridge');
            }
            $bridge_url = !empty($settings['whatsapp_bridge_url']) ? $settings['whatsapp_bridge_url'] : (getenv('WA_BRIDGE_URL') ?: 'http://booki-wa:3000');
            $bridge_secret = !empty($settings['whatsapp_bridge_secret']) ? $settings['whatsapp_bridge_secret'] : (getenv('WA_BRIDGE_SECRET') ?: '');
            $bridge = new Whatsapp_bridge($bridge_url, $bridge_secret);
            $context = tenant_context();
            $tenant_key = Whatsapp_bridge::resolve_tenant_key($context['subdomain'] ?? 'salonflora');
            $res = $bridge->session_start($tenant_key);
            json_response(['status' => 'success', 'result' => $res]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
