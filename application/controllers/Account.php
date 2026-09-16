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
 * Account controller.
 *
 * Handles current account related operations.
 *
 * @package Controllers
 */
class Account extends EA_Controller
{
    public array $allowed_user_fields = [
        'id',
        'first_name',
        'last_name',
        'email',
        'mobile_number',
        'phone_number',
        'address',
        'city',
        'state',
        'zip_code',
        'notes',
        'timezone',
        'language',
        'settings',
    ];

    public array $optional_user_fields = [
        //
    ];

    public array $allowed_user_setting_fields = ['username', 'password', 'notifications', 'calendar_view'];

    public array $optional_user_setting_fields = [
        //
    ];

    /**
     * Account constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('appointments_model');
        $this->load->model('customers_model');
        $this->load->model('services_model');
        $this->load->model('providers_model');
        $this->load->model('roles_model');
        $this->load->model('settings_model');

        $this->load->library('accounts');
        $this->load->library('google_sync');
        $this->load->library('notifications');
        $this->load->library('synchronization');
        $this->load->library('timezones');
    }

    /**
     * Render the settings page.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('account')]);

        $user_id = session('user_id');

        if (cannot('view', PRIV_USER_SETTINGS)) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        $account = $this->users_model->find($user_id);

        script_vars([
            'account' => filter_sensitive_user_data($account),
        ]);

        html_vars([
            'page_title' => lang('settings'),
            'active_menu' => PRIV_SYSTEM_SETTINGS,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'grouped_timezones' => $this->timezones->to_grouped_array(),
        ]);

        $this->load->view('pages/account');
    }

    /**
     * Save general settings.
     */
    public function save(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_USER_SETTINGS)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            check('account', 'array');

            $account = request('account');

            $account['id'] = session('user_id');

            $this->users_model->only($account, $this->allowed_user_fields);

            $this->users_model->optional($account, $this->optional_user_fields);

            $this->users_model->only($account['settings'], $this->allowed_user_setting_fields);

            $this->users_model->optional($account['settings'], $this->optional_user_setting_fields);

            if (empty($account['password'])) {
                unset($account['password']);
            }

            $this->users_model->save($account);

            session([
                'user_email' => $account['email'],
                'username' => $account['settings']['username'],
                'timezone' => $account['timezone'],
                'language' => $account['language'],
            ]);

            response();
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Make sure the username is valid and unique in the database.
     */
    public function validate_username(): void
    {
        try {
            method('post');

            check('username', 'string');
            check('user_id', 'numeric|null');

            $username = request('username');

            $user_id = request('user_id');

            $is_valid = $this->users_model->validate_username($username, $user_id);

            json_response([
                'is_valid' => $is_valid,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Generate a new TOTP secret for enrollment.
     */
    public function totp_setup(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_USER_SETTINGS)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            $user_id = session('user_id');

            $result = $this->accounts->generate_totp_secret($user_id);

            json_response([
                'secret' => $result['secret'],
                'otpauth_uri' => $result['otpauth_uri'],
                'plain_uri' => $result['plain_uri'],
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Enable TOTP after verifying the code.
     */
    public function totp_enable(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_USER_SETTINGS)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            check('code', 'string');

            $user_id = session('user_id');
            $code = request('code');

            $result = $this->accounts->enable_totp($user_id, $code);

            if ($result['success']) {
                audit_log('auth.totp_enabled', 'user', $user_id);

                json_response([
                    'success' => true,
                    'backup_codes' => $result['backup_codes'],
                ]);
            } else {
                json_response([
                    'success' => false,
                    'error' => 'Failed to enable TOTP',
                ], 400);
            }
        } catch (InvalidArgumentException $e) {
            json_response([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Disable TOTP after password re-verification.
     */
    public function totp_disable(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_USER_SETTINGS)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            check('password', 'string');

            $user_id = session('user_id');
            $password = request('password');

            // Get the user settings to get the username
            $user_settings = $this->db
                ->get_where('user_settings', ['id_users' => $user_id])
                ->row_array();

            if (empty($user_settings)) {
                throw new RuntimeException('User settings not found.');
            }

            $username = $user_settings['username'];

            // Re-verify the password
            $verified_user = $this->accounts->check_login($username, $password);

            if (empty($verified_user)) {
                json_response([
                    'success' => false,
                    'error' => 'Invalid password',
                ], 403);
                return;
            }

            // Password is correct, disable TOTP
            $this->accounts->disable_totp($user_id);

            audit_log('auth.totp_disabled', 'user', $user_id);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Regenerate backup codes after TOTP verification.
     */
    public function totp_regenerate_backup_codes(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_USER_SETTINGS)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            check('code', 'string');

            $user_id = session('user_id');
            $code = request('code');

            // Verify the TOTP code
            if (!$this->accounts->verify_totp_code($user_id, $code)) {
                json_response([
                    'success' => false,
                    'error' => 'Invalid TOTP code',
                ], 400);
                return;
            }

            // Generate new backup codes
            $backup_codes = [];
            $backup_codes_hashes = [];

            for ($i = 0; $i < 10; $i++) {
                $generated_code = bin2hex(random_bytes(5));
                $backup_codes[] = $generated_code;
                $backup_codes_hashes[] = hash('sha256', $generated_code);
            }

            // Update the backup codes in the database
            $this->db->update(
                'user_settings',
                ['totp_backup_codes' => json_encode($backup_codes_hashes)],
                ['id_users' => $user_id],
            );

            json_response([
                'success' => true,
                'backup_codes' => $backup_codes,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
