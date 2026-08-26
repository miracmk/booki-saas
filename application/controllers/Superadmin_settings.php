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
 * SaaS admin panel (reservationadmin.kibusiness.co) - platform-wide settings. First (and so far only)
 * use: the shared "Ki Business" Google OAuth Client ID/Secret every tenant's Google Calendar
 * connection falls back to (see master_setting(), Google_sync::get_client_id()/get_client_secret()).
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
            'page_title' => 'Ki Reservation - Platform Ayarları',
            'csrf_token' => $this->security->get_csrf_hash(),
            'superadmin_username' => session('superadmin_username'),
            'google_client_id' => master_setting('google_client_id') ?? '',
            'google_client_secret_set' => !empty(master_setting('google_client_secret')),
        ]);

        $this->load->view('pages/superadmin_settings');
    }

    public function save(): void
    {
        try {
            method('post');

            check('google_client_id', 'string|null');
            check('google_client_secret', 'string|null');

            master_setting('google_client_id', trim((string) request('google_client_id')));

            $secret = trim((string) request('google_client_secret'));

            // Boş bırakılırsa mevcut secret'a dokunulmuyor (tekrar tekrar gösterilmiyor, sadece
            // değiştirilmek istendiğinde üzerine yazılıyor).
            if ($secret !== '') {
                master_setting('google_client_secret', $secret);
            }

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
