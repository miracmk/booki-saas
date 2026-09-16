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

            // Bu action iki AYRI form tarafından çağrılıyor (Google OAuth kartı + Platform SMTP/IMAP
            // kartı), her biri sadece kendi alanlarını POST ediyor. Bu yüzden bir alanı sadece
            // İSTEKTE GERÇEKTEN GÖNDERİLMİŞSE güncelle (request(...) !== null) - aksi halde diğer
            // formun submit'i bu formun alanlarını boşa yazardı.
            if (request('google_client_id') !== null) {
                master_setting('google_client_id', trim((string) request('google_client_id')));
            }

            $secret = trim((string) request('google_client_secret'));

            // Boş bırakılırsa mevcut secret'a dokunulmuyor (tekrar tekrar gösterilmiyor, sadece
            // değiştirilmek istendiğinde üzerine yazılıyor).
            if ($secret !== '') {
                master_setting('google_client_secret', $secret);
            }

            // Platform SMTP/IMAP - tenant kendi mail sunucusunu bağlamadıysa bu bilgiler kullanılır
            // (bkz. Email_messages::resolve_smtp_config()). Şifre alanları boşsa mevcut değere
            // dokunulmuyor, google_client_secret ile aynı desen.
            $plaintext_fields = ['platform_smtp_host', 'platform_smtp_port', 'platform_smtp_crypto',
                'platform_smtp_user', 'platform_smtp_from_name', 'platform_smtp_from_address',
                'platform_imap_host', 'platform_imap_port', 'platform_imap_crypto', 'platform_imap_user'];

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

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
