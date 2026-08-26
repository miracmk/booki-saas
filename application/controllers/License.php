<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Online Appointment Scheduler
 *
 * @package     KiReservation
 * @author      Ki Software
 * @copyright   Copyright (c) Ki Software
 * @license     Proprietary - see LICENSE file
 * @link        https://kisoftware.com
 * ---------------------------------------------------------------------------- */

/**
 * Ki Reservation customization (2026-08-24) - view/update the self-hosted license key. Admin-only.
 */
class License extends EA_Controller
{
    /**
     * License constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('roles_model');
        $this->load->library('accounts');
        $this->load->library('licensing');
    }

    /**
     * Render the license status page.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('license')]);

        $user_id = session('user_id');
        $role_slug = session('role_slug');

        if ($role_slug !== DB_SLUG_ADMIN) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        html_vars([
            'page_title' => 'Lisans',
            'active_menu' => PRIV_SYSTEM_SETTINGS,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
            'license_status' => $this->licensing->status(),
            'license_key' => (string) setting('license_key'),
        ]);

        $this->load->view('pages/license');
    }

    /**
     * Save a new license key.
     */
    public function save(): void
    {
        try {
            method('post');

            if (session('role_slug') !== DB_SLUG_ADMIN) {
                abort(403, 'Forbidden');
            }

            check('license_key', 'string');

            $license_key = trim((string) request('license_key'));

            setting(['license_key' => $license_key]);

            audit_log('license.update', null, null, ['status_after' => $this->licensing->status()['state']]);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
