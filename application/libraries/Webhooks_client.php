<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Open Source Web Scheduler
 *
 * @package     KiReservation
 * @author      Ki Software
 * @copyright   Copyright (c) 2013 - 2020, Ki Software
 * @license     http://opensource.org/licenses/Proprietary - Ki Software License
 * @link        http://kisoftware.com
 * @since       v1.4.0
 * ---------------------------------------------------------------------------- */

use GuzzleHttp\Client;

/**
 * Webhooks client library.
 *
 * Handles the webhook HTTP related functionality.
 *
 * @package Libraries
 */
class Webhooks_client
{
    /**
     * @var EA_Controller|CI_Controller
     */
    protected EA_Controller|CI_Controller $CI;

    /**
     * Webhook client constructor.
     */
    public function __construct()
    {
        $this->CI = &get_instance();

        $this->CI->load->model('providers_model');
        $this->CI->load->model('secretaries_model');
        $this->CI->load->model('secretaries_model');
        $this->CI->load->model('admins_model');
        $this->CI->load->model('appointments_model');
        $this->CI->load->model('settings_model');
        $this->CI->load->model('webhooks_model');
        // BooKi (Dalga 2) - queue gate for call(), see do_call()/handle_queued_delivery().
        // MUST be loaded here - call() references $this->CI->queue->enabled(), which would fatal on
        // every webhook trigger if this library were never loaded. Same missing-load bug found and
        // fixed in Notifications.php's constructor - see that comment for the full explanation.
        $this->CI->load->library('queue');
    }

    /**
     * Trigger the registered webhooks for the provided action.
     *
     * @param string $action Webhook action.
     * @param array $payload Payload data.
     *
     * @return void|null
     */
    public function trigger(string $action, array $payload)
    {
        $webhooks = $this->CI->webhooks_model->get();

        foreach ($webhooks as $webhook) {
            $actions = array_filter(array_map('trim', explode(',', (string) $webhook['actions'])));

            if (in_array($action, $actions, true)) {
                $this->call($webhook, $action, $payload);
            }
        }
    }

    /**
     * Call the provided webhook.
     *
     * @param array $webhook
     * @param string $action
     * @param array $payload
     */
    private function call(array $webhook, string $action, array $payload): void
    {
        // Queue if enabled; fall through to synchronous call if queue is disabled or push fails.
        if ($this->CI->queue->enabled()) {
            if ($this->CI->queue->push(
                'webhook',
                'webhooks.deliver',
                [
                    'webhook_id' => $webhook['id'] ?? null,
                    'action' => $action,
                    'payload' => $payload,
                ],
                [
                    'reference_type' => 'webhook',
                    'reference_id' => $webhook['id'] ?? null,
                ],
            ) !== null) {
                return;
            }
        }

        $this->do_call($webhook, $action, $payload);
    }

    /**
     * Internal helper: perform the actual webhook call (no queue check).
     *
     * @param array $webhook
     * @param string $action
     * @param array $payload
     */
    private function do_call(array $webhook, string $action, array $payload): void
    {
        try {
            $client = new Client();

            $headers = [];

            if (!empty($webhook['secret_header']) && !empty($webhook['secret_token'])) {
                $headers[$webhook['secret_header']] = $webhook['secret_token'];
            }

            $response = $client->post($webhook['url'], [
                'verify' => $webhook['is_ssl_verified'],
                'headers' => $headers,
                'json' => [
                    'action' => $action,
                    'payload' => $payload,
                ],
            ]);

            // echo $response->getBody()->getContents(); // Use this for quick debugging
        } catch (Throwable $e) {
            log_message(
                'error',
                'Webhooks Client - The webhook (' .
                    ($webhook['id'] ?? null) .
                    ') request received an unexpected exception: ' .
                    $e->getMessage(),
            );
            log_message('error', $e->getTraceAsString());
        }
    }

    /**
     * Queued handler for webhook deliveries (called by Job_dispatcher).
     * Re-fetches the webhook configuration and delivers the payload.
     *
     * @param EA_Controller|CI_Controller $CI
     * @param array $payload Must contain 'webhook_id', 'action', 'payload'
     */
    public function handle_queued_delivery($CI, array $payload): void
    {
        try {
            $webhook_id = $payload['webhook_id'] ?? null;
            $action = $payload['action'] ?? '';
            $webhook_payload = $payload['payload'] ?? [];

            if (!$webhook_id) {
                log_message('warning', 'Webhooks_client::handle_queued_delivery() - webhook_id is missing');
                return;
            }

            // Re-fetch the webhook configuration
            $webhook = $CI->webhooks_model->find($webhook_id);
            if (!$webhook) {
                log_message('warning', 'Webhooks_client::handle_queued_delivery() - Webhook not found: ' . $webhook_id);
                return;
            }

            $this->do_call($webhook, $action, $webhook_payload);
        } catch (Throwable $e) {
            log_message('error', 'Webhooks_client::handle_queued_delivery() failed: ' . $e->getMessage());
            log_message('error', $e->getTraceAsString());
        }
    }
}
