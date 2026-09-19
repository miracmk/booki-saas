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
 * SaaS super-admin login (admin-bookiapp.kibusiness.co) - Ki Software's own staff, credentials in
 * the master `master_admins` table, completely separate from any tenant's users. Every controller in
 * this "Superadmin*" family is let through EA_Controller::resolve_tenant()'s host check while staying
 * on the master DB - see that method's docblock. Uses its OWN session key ('superadmin_id') rather
 * than 'user_id' so it can never be confused with (or accidentally satisfy) a tenant login check
 * elsewhere in the shared codebase.
 */
class Superadmin_auth extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(): void
    {
        method('get');

        if (session('superadmin_id')) {
            redirect('superadmin_tenants');
            return;
        }

        html_vars([
            'page_title' => 'BooKi - Admin',
            'csrf_token' => $this->security->get_csrf_hash(),
        ]);

        $this->load->view('pages/superadmin_login');
    }

    public function validate(): void
    {
        try {
            method('post');

            $this->apply_login_rate_limit();

            check('username', 'string');
            check('password', 'string');

            $username = trim((string) request('username'));
            $password = (string) request('password');

            if ($username === '' || $password === '') {
                throw new InvalidArgumentException('Kullanıcı adı ve şifre gerekli.');
            }

            $admin = $this->db->get_where('master_admins', ['username' => $username])->row_array();

            if (!$admin || !verify_password($admin['salt'], $password, $admin['password'])) {
                usleep(random_int(100000, 300000));

                json_response(['success' => false, 'message' => 'Geçersiz kimlik bilgileri.']);
                return;
            }

            $this->session->sess_regenerate(true);

            session([
                'superadmin_id' => (int) $admin['id'],
                'superadmin_username' => $admin['username'],
            ]);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function logout(): void
    {
        $this->session->sess_destroy();
        redirect('superadmin_auth');
    }

    private function apply_login_rate_limit(): void
    {
        try {
            $this->load->driver('cache', ['adapter' => 'file']);

            if (!isset($this->cache) || !is_object($this->cache)) {
                return;
            }

            $ip = $this->input->ip_address();
            $cache_key = 'superadmin_login_attempts_' . str_replace([':', '.'], '_', $ip);

            $attempts = $this->cache->get($cache_key);

            if ($attempts === false) {
                $this->cache->save($cache_key, 1, 300);
                return;
            }

            $this->cache->save($cache_key, $attempts + 1, 300);

            if ($attempts >= 5) {
                throw new RuntimeException('Çok fazla deneme yapıldı, birkaç dakika sonra tekrar deneyin.');
            }
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Throwable $e) {
            log_message('error', 'Cache error in superadmin login rate limiting: ' . $e->getMessage());
        }
    }
}
