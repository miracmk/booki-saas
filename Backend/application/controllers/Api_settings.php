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
 * API settings controller.
 *
 * Handles API settings related operations.
 *
 * @package Controllers
 */
class Api_settings extends App_Controller
{
    /**
     * Api_settings constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('settings_model');

        $this->load->library('accounts');
    }

    /**
     * Render the settings page.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('api_settings')]);

        $user_id = session('user_id');

        if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        $role_slug = session('role_slug');

        $tenant = tenant_context();
        $subdomain = $tenant['subdomain'] ?? '';
        $agent_api_key = setting('agent_api_key');
        if (empty($agent_api_key) && can('edit', PRIV_SYSTEM_SETTINGS)) {
            $agent_api_key = bin2hex(random_bytes(32));
            $this->settings_model->set_setting('agent_api_key', $agent_api_key);
        }

        $app_domain = getenv('TENANT_APP_DOMAIN') ?: 'bookiapp.kibusiness.co';
        $mcp_url = 'https://' . $app_domain . '/mcp?tenant=' . urlencode($subdomain);

        script_vars([
            'user_id' => $user_id,
            'role_slug' => $role_slug,
            'api_settings' => $this->settings_model->get('name like "api_%"'),
            'mcp_url' => $mcp_url,
            'agent_api_key' => $agent_api_key,
            'tenant_subdomain' => $subdomain,
        ]);

        html_vars([
            'page_title' => lang('api'),
            'active_menu' => PRIV_SYSTEM_SETTINGS,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'mcp_url' => $mcp_url,
            'agent_api_key' => $agent_api_key,
            'tenant_subdomain' => $subdomain,
        ]);

        $this->load->view('pages/api_settings');
    }

    /**
     * Generate / rotate agent API key for MCP and external agents.
     */
    public function generate_agent_key(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                throw new RuntimeException('Forbidden');
            }

            $new_key = bin2hex(random_bytes(32));
            $this->settings_model->set_setting('agent_api_key', $new_key);

            json_response(['success' => true, 'agent_api_key' => $new_key]);
        } catch (Throwable $e) {
            json_exception($e);
        }
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

            check('api_settings', 'array|null');

            $settings = request('api_settings', []);

            foreach ($settings as $setting) {
                $existing_setting = $this->settings_model->query()->where('name', $setting['name'])->get()->row_array();

                if (!empty($existing_setting)) {
                    $setting['id'] = $existing_setting['id'];
                }

                $this->settings_model->save($setting);
            }

            response();
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
