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
 * BooKi customization (2026-08-26) - read-only admin dashboard for the Google Calendar
 * push-notification sync (see Google_sync::register_watch()/get_incremental_events(),
 * Google::webhook(), google_calendar_watch_channels/google_calendar_sync_log tables). Admin-only
 * (gated on PRIV_SYSTEM_SETTINGS, same posture as Audit_log/General Settings).
 */
class Google_sync_dashboard extends App_Controller
{
    /**
     * Google_sync_dashboard constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('roles_model');
        $this->load->model('providers_model');
        $this->load->library('accounts');
    }

    /**
     * Render the dashboard page.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('google_sync_dashboard')]);

        $user_id = session('user_id');

        if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        $role_slug = session('role_slug');

        $providers = $this->providers_model->get();

        $rows = [];

        foreach ($providers as $provider) {
            $google_sync_enabled = filter_var($provider['settings']['google_sync'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if (!$google_sync_enabled) {
                continue;
            }

            $channel = $this->db
                ->get_where('google_calendar_watch_channels', ['id_users_provider' => $provider['id']])
                ->row_array();

            $last_log = $this->db
                ->where('id_users_provider', $provider['id'])
                ->order_by('synced_at', 'DESC')
                ->limit(1)
                ->get('google_calendar_sync_log')
                ->row_array();

            $recent_error = $this->db
                ->where('id_users_provider', $provider['id'])
                ->where('status', 'failed')
                ->order_by('synced_at', 'DESC')
                ->limit(1)
                ->get('google_calendar_sync_log')
                ->row_array();

            $rows[] = [
                'provider_name' => trim($provider['first_name'] . ' ' . $provider['last_name']),
                'calendar_id' => $provider['settings']['google_calendar'] ?? null,
                'channel_active' => (bool) $channel,
                'channel_expiration' => $channel['expiration'] ?? null,
                'last_synced_at' => $last_log['synced_at'] ?? null,
                'last_trigger' => $last_log['trigger'] ?? null,
                'last_status' => $last_log['status'] ?? null,
                'last_event_count' => $last_log['event_count'] ?? null,
                'last_error' => $recent_error['error_message'] ?? null,
                'last_error_at' => $recent_error['synced_at'] ?? null,
            ];
        }

        html_vars([
            'page_title' => 'Google Takvim Senkron Durumu',
            'active_menu' => PRIV_SYSTEM_SETTINGS,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
            'rows' => $rows,
        ]);

        $this->load->view('pages/google_sync_dashboard');
    }
}
