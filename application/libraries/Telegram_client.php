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

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Salon Flora customization - native Telegram Bot API client (2026-08-25). No Composio/n8n dependency -
 * talks to https://api.telegram.org directly with the bot token stored in settings ('telegram_bot_token').
 *
 * @package Libraries
 */
class Telegram_client
{
    /**
     * @var EA_Controller|CI_Controller
     */
    protected EA_Controller|CI_Controller $CI;

    public function __construct()
    {
        $this->CI = &get_instance();

        $this->CI->load->model('settings_model');
    }

    /**
     * @return string|null The configured bot token, or null if Telegram hasn't been set up yet.
     */
    private function token(): ?string
    {
        $token = setting('telegram_bot_token');

        return !empty($token) ? $token : null;
    }

    /**
     * @param string $method Telegram Bot API method name (e.g. 'sendMessage').
     * @param array $params
     *
     * @return array|null Decoded JSON response, or null if the bot isn't configured or the call failed.
     */
    private function call(string $method, array $params = []): ?array
    {
        $token = $this->token();

        if (!$token) {
            return null;
        }

        try {
            $client = new Client();

            $response = $client->post('https://api.telegram.org/bot' . $token . '/' . $method, [
                'json' => $params,
                'timeout' => 10,
            ]);

            $decoded = json_decode((string) $response->getBody(), true);

            return is_array($decoded) ? $decoded : null;
        } catch (GuzzleException|Throwable $e) {
            log_message('error', 'Telegram_client - ' . $method . ' request failed: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Send a text message to a chat. Silently no-ops (returns false) if Telegram isn't configured -
     * callers treat Telegram as a best-effort extra channel, never a hard dependency.
     *
     * @param string $chat_id
     * @param string $text
     *
     * @return bool Whether the message was sent successfully.
     */
    public function send_message(string $chat_id, string $text): bool
    {
        if (empty($chat_id)) {
            return false;
        }

        $result = $this->call('sendMessage', [
            'chat_id' => $chat_id,
            'text' => $text,
        ]);

        return (bool) ($result['ok'] ?? false);
    }

    /**
     * Verify the bot token and fetch the bot's own username (needed to build t.me/<username>?start=...
     * deep links).
     *
     * @return array|null {'id', 'username', 'first_name'} or null if the token is missing/invalid.
     */
    public function get_me(): ?array
    {
        $result = $this->call('getMe');

        return $result['ok'] ?? false ? $result['result'] : null;
    }

    /**
     * Point Telegram's webhook at this installation, so incoming messages reach Telegram::webhook().
     *
     * @param string $url Public HTTPS URL of the webhook endpoint.
     * @param string $secret_token Sent back by Telegram on every call as the
     *   X-Telegram-Bot-Api-Secret-Token header, so the endpoint can verify the request actually came
     *   from Telegram.
     *
     * @return bool
     */
    public function set_webhook(string $url, string $secret_token): bool
    {
        $result = $this->call('setWebhook', [
            'url' => $url,
            'secret_token' => $secret_token,
        ]);

        return (bool) ($result['ok'] ?? false);
    }
}
