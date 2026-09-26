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
 * Accounting Settings controller.
 *
 * Handles OAuth2 connection/disconnection for accounting systems (Paraşüt).
 *
 * @package Controllers
 */
class Accounting_settings extends App_Controller
{
    /**
     * Accounting_settings constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('accounting_settings_model');
        $this->load->library('accounting/parasut_connector');
    }

    /**
     * Display accounting settings and connection status page.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('accounting_settings')]);

        $user_id = session('user_id');

        if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        $connection = $this->accounting_settings_model->get_connection();
        $is_connected = $this->parasut_connector->is_connected();

        html_vars([
            'page_title' => lang('accounting_settings'),
            'active_menu' => PRIV_SYSTEM_SETTINGS,
            'user_display_name' => session('user_display_name'),
            'connection' => $connection,
            'is_connected' => $is_connected,
        ]);

        $this->load->view('pages/accounting_settings');
    }

    /**
     * Redirect to Paraşüt OAuth2 authorization endpoint.
     */
    public function connect(): void
    {
        method('get');

        if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
            abort(403, 'Forbidden');
        }

        try {
            $client_id = getenv('PARASUT_CLIENT_ID');

            if (empty($client_id)) {
                throw new RuntimeException('PARASUT_CLIENT_ID environment variable not set.');
            }

            $oauth_url = 'https://app.parasut.com/oauth/authorize?' . http_build_query([
                'client_id' => $client_id,
                'redirect_uri' => site_url('accounting_settings/callback'),
                'response_type' => 'code',
                'scope' => 'invoices:create invoices:read',
            ]);

            redirect($oauth_url);
        } catch (Throwable $e) {
            log_message('error', 'Accounting_settings::connect() failed: ' . $e->getMessage());
            session()->set_flashdata('error', 'Failed to initiate Paraşüt connection.');
            redirect('accounting_settings');
        }
    }

    /**
     * Handle Paraşüt OAuth2 callback.
     *
     * Exchanges authorization code for access/refresh tokens.
     */
    public function callback(): void
    {
        method('get');

        if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
            abort(403, 'Forbidden');
        }

        try {
            $authorization_code = $this->input->get('code');
            $state = $this->input->get('state');

            if (empty($authorization_code)) {
                $error = $this->input->get('error');
                throw new RuntimeException('OAuth2 callback error: ' . ($error ?? 'unknown'));
            }

            // Exchange authorization code for tokens
            $this->parasut_connector->connect([
                'authorization_code' => $authorization_code,
            ]);

            session()->set_flashdata('success', 'Paraşüt connection established successfully.');
        } catch (Throwable $e) {
            log_message('error', 'Accounting_settings::callback() failed: ' . $e->getMessage());
            session()->set_flashdata('error', 'Failed to connect to Paraşüt: ' . $e->getMessage());
        }

        redirect('accounting_settings');
    }

    /**
     * Disconnect from Paraşüt (revoke stored credentials).
     */
    public function disconnect(): void
    {
        method('post');

        if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
            abort(403, 'Forbidden');
        }

        try {
            $this->accounting_settings_model->delete_connection('parasut');

            session()->set_flashdata('success', 'Paraşüt connection removed.');
        } catch (Throwable $e) {
            log_message('error', 'Accounting_settings::disconnect() failed: ' . $e->getMessage());
            session()->set_flashdata('error', 'Failed to disconnect from Paraşüt.');
        }

        redirect('accounting_settings');
    }
}
