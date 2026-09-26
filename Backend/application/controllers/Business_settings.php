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
 * Business logic controller.
 *
 * Handles general settings related operations.
 *
 * @package Controllers
 */
class Business_settings extends App_Controller
{
    public array $allowed_setting_fields = ['id', 'name', 'value'];

    public array $optional_setting_fields = [];

    /**
     * Business_logic constructor.
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

        session(['dest_url' => site_url('business_settings')]);

        $user_id = session('user_id');

        if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        $role_slug = session('role_slug');

        script_vars([
            'user_id' => $user_id,
            'role_slug' => $role_slug,
            'business_settings' => filter_sensitive_settings($this->settings_model->get()),
            'first_weekday' => setting('first_weekday'),
            'time_format' => setting('time_format'),
        ]);

        html_vars([
            'page_title' => lang('settings'),
            'active_menu' => PRIV_SYSTEM_SETTINGS,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
        ]);

        $this->load->view('pages/business_settings');
    }

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

            check('business_settings', 'array|null');

            $settings = request('business_settings', []);

            foreach ($settings as $setting) {
                if (empty($setting['name'])) {
                    continue;
                }

                $existing_setting = $this->settings_model->query()->where('name', $setting['name'])->get()->row_array();

                if (!empty($existing_setting)) {
                    $setting['id'] = $existing_setting['id'];
                }

                $this->settings_model->only($setting, $this->allowed_setting_fields);

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
                    if ($master->field_exists('phone_number', 'tenants')) {
                        $c_phone = setting('company_phone') ?: setting('phone_number');
                        if ($c_phone !== null) {
                            $master_sync['phone_number'] = $c_phone;
                        }
                    }
                    if ($master->field_exists('address', 'tenants')) {
                        $c_addr = setting('company_address') ?: setting('address');
                        if ($c_addr !== null) {
                            $master_sync['address'] = $c_addr;
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

    /**
     * Apply global working plan to all providers.
     */
    public function apply_global_working_plan(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            check('working_plan', 'json');

            $working_plan = request('working_plan');

            $providers = $this->providers_model->get();

            foreach ($providers as $provider) {
                $this->providers_model->set_setting($provider['id'], 'working_plan', $working_plan);
            }

            response();
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
