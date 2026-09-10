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
 * Login controller.
 *
 * Handles the login page functionality.
 *
 * @package Controllers
 */
class Login extends EA_Controller
{
    /**
     * Login constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('users_model');
        $this->load->model('roles_model');

        $this->load->library('accounts');
        $this->load->library('ldap_client');
        $this->load->library('email_messages');
        $this->load->library('timezones');

        script_vars([
            'dest_url' => session('dest_url', site_url('dashboard')),
        ]);
    }

    /**
     * Render the login page.
     */
    public function index(): void
    {
        method('get');

        if (session('user_id')) {
            redirect('dashboard');
            return;
        }

        html_vars([
            'page_title' => lang('login'),
            'base_url' => config('base_url'),
            'dest_url' => session('dest_url', site_url('dashboard')),
            'company_name' => setting('company_name'),
            'require_captcha' => setting('require_captcha'),
            'altcha_enabled' => setting('altcha_enabled'),
        ]);

        $this->load->view('pages/login');
    }

    /**
     * Validate the provided credentials and start a new session if the validation was successful.
     */
    public function validate(): void
    {
        try {
            method('post');

            // Apply stricter rate limiting for login attempts (5 attempts per 5 minutes)
            $this->apply_login_rate_limit();

            check('username', 'string');
            check('password', 'string');
            check('captcha', 'string|null');

            $require_captcha = (bool) setting('require_captcha');

            // Validate CAPTCHA or ALTCHA
            if ($require_captcha) {
                $altcha_enabled = setting('altcha_enabled') === '1';

                if ($altcha_enabled) {
                    check('altcha_payload', 'string|null');
                    $altcha_payload = request('altcha_payload');

                    $this->load->library('altcha_client');

                    if (!$this->altcha_client->verify($altcha_payload)) {
                        json_response([
                            'success' => false,
                            'altcha_verification' => false,
                        ]);
                        return;
                    }
                } else {
                    $captcha = request('captcha');
                    $captcha_phrase = session('captcha_phrase');

                    if (strtoupper($captcha_phrase) !== strtoupper($captcha)) {
                        json_response([
                            'success' => false,
                            'captcha_verification' => false,
                        ]);
                        return;
                    }
                }
            }

            $username = request('username');

            if (empty($username)) {
                throw new InvalidArgumentException('No username value provided.');
            }

            // Validate username format to prevent injection
            if (!preg_match('/^[a-zA-Z0-9_@.\-]+$/', $username) || strlen($username) > 255) {
                throw new InvalidArgumentException(lang('invalid_credentials_provided'));
            }

            $password = request('password');

            if (empty($password)) {
                throw new InvalidArgumentException('No password value provided.');
            }

            // Password length check
            if (strlen($password) > MAX_PASSWORD_LENGTH) {
                throw new InvalidArgumentException(lang('invalid_credentials_provided'));
            }

            $user_data = $this->accounts->check_login($username, $password);

            if (empty($user_data)) {
                $user_data = $this->ldap_client->check_login($username, $password);
            }

            if (empty($user_data)) {
                // Log failed login attempt
                log_message(
                    'info',
                    'Failed login attempt for username: ' . $username . ' from IP: ' . $this->input->ip_address(),
                );

                // Ki Reservation customization - audit trail for failed authentication attempts.
                audit_log('auth.login_failed', null, null, ['username' => $username]);

                // Use constant time response to prevent username enumeration
                usleep(random_int(100000, 300000)); // 100-300ms delay

                json_response([
                    'success' => false,
                    'message' => lang('invalid_credentials_provided'),
                ]);

                return;
            }

            // Check if user has TOTP enabled
            if ($this->accounts->totp_is_enabled((int) $user_data['user_id'])) {
                $pending_token = $this->accounts->create_totp_challenge((int) $user_data['user_id'], $this->input->ip_address());
                audit_log('auth.totp_challenge', 'user', (int) $user_data['user_id']);
                json_response(['success' => true, 'requires_totp' => true, 'pending_token' => $pending_token]);
                return;
            }

            $this->session->sess_regenerate(true); // Regenerate session ID and delete old session

            session($user_data); // Save data in the session.

            log_message('info', 'Successful login for user: ' . $username . ' from IP: ' . $this->input->ip_address());

            // Ki Reservation customization - audit trail for successful authentication.
            audit_log('auth.login_success', 'user', (int) $user_data['user_id']);

            // Ki Reservation (2026-08-26) - "Müşteri Paneli": a customer has no access to /calendar
            // (their role has zero permissions there) - login.js's default dest_url fallback assumes
            // staff, so tell it explicitly where a customer belongs instead.
            json_response([
                'success' => true,
                'redirect_url' => $user_data['role_slug'] === DB_SLUG_CUSTOMER ? site_url('customer_portal') : null,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Verify a TOTP code after a user has passed the initial login stage.
     */
    public function verify_totp(): void
    {
        try {
            method('post');

            // Apply stricter rate limiting for TOTP attempts
            $this->apply_login_rate_limit();

            check('pending_token', 'string');
            check('code', 'string');

            $pending_token = request('pending_token');
            $code = request('code');

            // Validate the challenge token
            $challenge = $this->accounts->validate_totp_challenge($pending_token);

            if (empty($challenge)) {
                json_response(['success' => false, 'error' => 'expired'], 400);
                return;
            }

            $user_id = $challenge['id_users'];

            // Try to verify the TOTP code first
            $code_valid = $this->accounts->verify_totp_code($user_id, $code);

            // If TOTP code fails, try backup code
            if (!$code_valid) {
                $code_valid = $this->accounts->verify_backup_code($user_id, $code);
            }

            if (!$code_valid) {
                // Log failed TOTP verification
                audit_log('auth.totp_failed', 'user', $user_id);

                // Increment attempts
                $this->accounts->increment_totp_challenge_attempts($pending_token);

                // Add a delay to prevent brute force
                usleep(random_int(100000, 300000)); // 100-300ms delay

                json_response(['success' => false, 'error' => 'invalid_code'], 400);
                return;
            }

            // TOTP code is valid, consume the challenge token
            $this->accounts->consume_totp_challenge($pending_token);

            audit_log('auth.totp_success', 'user', $user_id);

            // Re-fetch user data and establish session (same as validate() does)
            $user = $this->users_model->find($user_id);
            $user_settings = $this->db
                ->get_where('user_settings', ['id_users' => $user_id])
                ->row_array();

            $role = $this->roles_model->find($user['id_roles']);
            $default_timezone = $this->timezones->get_default_timezone();

            $user_data = [
                'user_id' => $user['id'],
                'user_email' => $user['email'],
                'username' => $user_settings['username'] ?? '',
                'timezone' => !empty($user['timezone']) ? $user['timezone'] : $default_timezone,
                'language' => !empty($user['language']) ? $user['language'] : Config::LANGUAGE,
                'role_slug' => $role['slug'],
            ];

            // Establish the session
            $this->session->sess_regenerate(true);
            session($user_data);

            // Ki Reservation - keep audit_log parity with validate()'s non-MFA success path, so a
            // query for 'auth.login_success' finds every completed login regardless of whether it
            // went through a TOTP challenge.
            audit_log('auth.login_success', 'user', $user_id);

            log_message('info', 'Successful TOTP verification for user: ' . $user_settings['username'] . ' from IP: ' . $this->input->ip_address());

            // Return the same response as validate()
            json_response([
                'success' => true,
                'redirect_url' => $user_data['role_slug'] === DB_SLUG_CUSTOMER ? site_url('customer_portal') : null,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Apply rate limiting specifically for login attempts.
     *
     * @throws RuntimeException If rate limit is exceeded.
     */
    private function apply_login_rate_limit(): void
    {
        try {
            $this->load->driver('cache', ['adapter' => 'file']);

            if (!isset($this->cache) || !is_object($this->cache)) {
                log_message('debug', 'Cache driver not available, skipping rate limit check.');
                return;
            }

            $ip = $this->input->ip_address();
            $cache_key = 'login_attempts_' . str_replace([':', '.'], '_', $ip);

            $attempts = $this->cache->get($cache_key);

            if ($attempts === false) {
                $this->cache->save($cache_key, 1, 300); // 5 minutes
                return;
            }
            $this->cache->save($cache_key, $attempts + 1, 300);

            if ($attempts >= 5) {
                log_message('error', 'Login rate limit exceeded for IP: ' . $ip);
                throw new RuntimeException('Too many login attempts. Please try again in a few minutes.');
            }
        } catch (RuntimeException $e) {
            // Re-throw rate limit exceptions
            throw $e;
        } catch (Throwable $e) {
            // Log cache errors but don't block login
            log_message('error', 'Cache error in login rate limiting: ' . $e->getMessage());
        }
    }
}
