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
 * General settings controller.
 *
 * Handles general settings related operations.
 *
 * @package Controllers
 */
class General_settings extends EA_Controller
{
    /**
     * Calendar constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('settings_model');

        $this->load->library('accounts');
        $this->load->library('timezones');
    }

    /**
     * Render the settings page.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('general_settings')]);

        $user_id = session('user_id');

        if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        $role_slug = session('role_slug');

        $available_theme_files = glob(__DIR__ . '/../../assets/css/themes/*.min.css');

        $available_themes = array_map(function ($available_theme_file) {
            return str_replace('.min.css', '', basename($available_theme_file));
        }, $available_theme_files);

        script_vars([
            'user_id' => $user_id,
            'role_slug' => $role_slug,
            'timezones' => $this->timezones->to_array(),
            'general_settings' => filter_sensitive_settings($this->settings_model->get()),
        ]);

        html_vars([
            'page_title' => lang('settings'),
            'active_menu' => PRIV_SYSTEM_SETTINGS,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'grouped_timezones' => $this->timezones->to_grouped_array(),
            'available_themes' => $available_themes,
        ]);

        $this->load->view('pages/general_settings');
    }

    /**
     * Allowed setting names that can be modified via this controller.
     */
    private array $allowed_settings = [
        'company_name',
        'company_email',
        'company_link',
        'company_logo',
        'white_label_enabled',
        'company_color',
        'company_working_plan',
        'book_advance_timeout',
        'default_timezone',
        'default_language',
        'theme',
        'date_format',
        'time_format',
        'first_weekday',
        'require_phone_number',
        'display_cookie_notice',
        'cookie_notice_content',
        'display_terms_and_conditions',
        'terms_and_conditions_content',
        'display_privacy_policy',
        'privacy_policy_content',
        'marketplace_opt_in',
        'marketplace_category',
        'marketplace_city',
        'marketplace_district',
        'marketplace_neighborhood',
        'marketplace_short_description',
        'marketplace_cover_image_url',
        'marketplace_price_range',
        'industry_code',
    ];

    /**
     * Save general settings.
     */
    public function save(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            check('general_settings', 'array|null');

            $settings = request('general_settings', []);

            // Validate settings is an array
            if (!is_array($settings)) {
                throw new InvalidArgumentException('Invalid settings data format.');
            }

            foreach ($settings as $setting) {
                // Validate each setting has required fields
                if (!isset($setting['name']) || !is_string($setting['name'])) {
                    continue;
                }

                // Only allow whitelisted settings to be modified
                if (!in_array($setting['name'], $this->allowed_settings, true)) {
                    log_message('error', 'Attempt to modify unauthorized setting: ' . $setting['name']);
                    continue;
                }

                $existing_setting = $this->settings_model->query()->where('name', $setting['name'])->get()->row_array();

                if (!empty($existing_setting)) {
                    $setting['id'] = $existing_setting['id'];
                }

                $this->settings_model->save($setting);
            }

            // Sync to master DB tenants table if multi-tenant context exists
            $tenant = tenant_context();
            if ($tenant && !empty($tenant['id'])) {
                $master = $this->load->database('default', true);
                if ($master && ($master->table_exists($master->dbprefix('tenants')) || $master->table_exists('tenants'))) {
                    $master_sync = [
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];

                    if ($master->field_exists('company_name', 'tenants')) {
                        $c_name = setting('company_name');
                        if ($c_name !== null && $c_name !== '') {
                            $master_sync['company_name'] = $c_name;
                        }
                    }
                    if (setting('marketplace_opt_in') !== null) {
                        $master_sync['marketplace_opt_in'] = setting('marketplace_opt_in') === '1' ? 1 : 0;
                    }
                    if (setting('marketplace_category') !== null) {
                        $master_sync['category'] = setting('marketplace_category');
                    }
                    if (setting('marketplace_city') !== null) {
                        $master_sync['city'] = setting('marketplace_city');
                    }
                    if (setting('marketplace_district') !== null) {
                        $master_sync['district'] = setting('marketplace_district');
                    }
                    if (setting('marketplace_neighborhood') !== null) {
                        $master_sync['neighborhood'] = setting('marketplace_neighborhood');
                    }
                    if (setting('marketplace_short_description') !== null) {
                        $master_sync['short_description'] = setting('marketplace_short_description');
                    }
                    if (setting('marketplace_cover_image_url') !== null) {
                        $master_sync['cover_image_url'] = setting('marketplace_cover_image_url');
                    }
                    if ($master->field_exists('price_range', 'tenants') && setting('marketplace_price_range') !== null) {
                        $master_sync['price_range'] = setting('marketplace_price_range');
                    }

                    $master->where('id', (int) $tenant['id'])->update('tenants', $master_sync);
                }
            }

            response();
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
