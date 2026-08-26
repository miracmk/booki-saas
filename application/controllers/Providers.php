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
 * Providers controller.
 *
 * Handles the providers related operations.
 *
 * @package Controllers
 */
class Providers extends EA_Controller
{
    public array $allowed_provider_fields = [
        'id',
        'first_name',
        'last_name',
        'email',
        'alt_number',
        'phone_number',
        'address',
        'city',
        'state',
        'zip_code',
        'notes',
        'timezone',
        'language',
        'is_private',
        'ldap_dn',
        'id_roles',
        'stations', // Salon Flora customization - array, replaces the old single id_stations column
        'station_restriction_enabled', // Salon Flora customization - when set, "stations" narrows the service's own station list instead of being ignored
        'skills', // Ki Reservation (2026-08-26) - array of provider_skills IDs, see Skills_model
        'commission_type', // Salon Flora customization - default/fallback commission rate
        'commission_value', // Salon Flora customization
        'commission_overtime_bonus', // Salon Flora customization - one-time bonus over 1 hour (hourly type only)
        'service_commissions', // Salon Flora customization - per-service commission overrides
        'settings',
        'services',
    ];

    public array $optional_provider_fields = [
        'services' => [],
        'stations' => [], // Salon Flora customization
        'skills' => [], // Ki Reservation (2026-08-26)
        'commission_type' => 'percentage', // Salon Flora customization
        'commission_value' => 0, // Salon Flora customization
        'commission_overtime_bonus' => 0, // Salon Flora customization
        'service_commissions' => [], // Salon Flora customization
        'station_restriction_enabled' => false, // Salon Flora customization
    ];

    public array $allowed_provider_setting_fields = [
        'username',
        'password',
        'working_plan',
        'working_plan_exceptions',
        'notifications',
        'calendar_view',
    ];

    public array $optional_provider_setting_fields = [
        'working_plan' => null,
        'working_plan_exceptions' => '{}',
    ];

    public array $allowed_service_fields = ['id', 'name', 'duration', 'price', 'currency']; // Salon Flora customization: duration/price/currency, so the admin UI can label duration variants (e.g. "Klasik Masaj — 60 dk")

    /**
     * Providers constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('providers_model');
        $this->load->model('services_model');
        $this->load->model('roles_model');
        $this->load->model('stations_model'); // Salon Flora customization
        $this->load->model('skills_model'); // Ki Reservation (2026-08-26)

        $this->load->library('accounts');
        $this->load->library('timezones');
        $this->load->library('webhooks_client');

        $this->optional_provider_setting_fields['working_plan'] = setting('company_working_plan');
    }

    /**
     * Render the backend providers page.
     *
     * On this page admin users will be able to manage providers, which are eventually selected by customers during the
     * booking process.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('providers')]);

        $user_id = session('user_id');

        if (cannot('view', PRIV_USERS)) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        $role_slug = session('role_slug');

        $services = $this->services_model->get();

        foreach ($services as &$service) {
            $this->services_model->only($service, $this->allowed_service_fields);
        }

        script_vars([
            'user_id' => $user_id,
            'role_slug' => $role_slug,
            'company_working_plan' => setting('company_working_plan'),
            'date_format' => setting('date_format'),
            'time_format' => setting('time_format'),
            'first_weekday' => setting('first_weekday'),
            'min_password_length' => MIN_PASSWORD_LENGTH,
            'timezones' => $this->timezones->to_array(),
            'services' => $services,
            'default_language' => setting('default_language'),
            'default_timezone' => setting('default_timezone'),
            'stations' => $this->stations_model->to_options(), // Salon Flora customization
            'skills' => $this->skills_model->to_options(), // Ki Reservation (2026-08-26)
        ]);

        html_vars([
            'page_title' => lang('providers'),
            'active_menu' => PRIV_USERS,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'grouped_timezones' => $this->timezones->to_grouped_array(),
            'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
            'services' => $this->services_model->get(),
            'stations' => $this->stations_model->to_options(), // Salon Flora customization
            'skills' => $this->skills_model->to_options(), // Ki Reservation (2026-08-26)
        ]);

        $this->load->view('pages/providers');
    }

    /**
     * Filter providers by the provided keyword.
     */
    public function search(): void
    {
        try {
            method('post');

            if (cannot('view', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            check('keyword', 'string|null');
            check('order_by', 'string|null');
            check('limit', 'numeric|null');
            check('offset', 'numeric|null');

            $keyword = request('keyword', '');

            $order_by = request('order_by', 'update_datetime DESC');

            $limit = request('limit', 1000);

            $offset = (int) request('offset', '0');

            $providers = $this->providers_model->search($keyword, $limit, $offset, $order_by);

            json_response($providers);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Store a new provider.
     */
    public function store(): void
    {
        try {
            method('post');

            if (cannot('add', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            check('provider', 'array');

            $provider = request('provider');

            $this->providers_model->only($provider, $this->allowed_provider_fields);

            $this->providers_model->only($provider['settings'], $this->allowed_provider_setting_fields);

            $this->providers_model->optional($provider, $this->optional_provider_fields);

            $this->providers_model->optional($provider['settings'], $this->optional_provider_setting_fields);

            if (!in_array($provider['commission_type'] ?? 'percentage', ['percentage', 'fixed', 'hourly'], true)) {
                throw new InvalidArgumentException('Geçersiz komisyon tipi.');
            }

            if ((float) ($provider['commission_value'] ?? 0) < 0) {
                throw new InvalidArgumentException('Komisyon değeri negatif olamaz.');
            }

            if ((float) ($provider['commission_overtime_bonus'] ?? 0) < 0) {
                throw new InvalidArgumentException('Ek komisyon negatif olamaz.');
            }

            $provider_id = $this->providers_model->save($provider);

            $provider = $this->providers_model->find($provider_id);

            $this->webhooks_client->trigger(WEBHOOK_PROVIDER_SAVE, $provider);

            // Salon Flora customization - real-time Google Sheets sync (see Google_sheets_writer).
            $this->load->library('google_sheets_writer');
            $this->google_sheets_writer->sync_record('providers', (int) $provider_id, 'upsert');

            json_response([
                'success' => true,
                'id' => $provider_id,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Find a provider.
     */
    public function find(): void
    {
        try {
            method('get');

            if (cannot('view', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            check('provider_id', 'numeric');

            $provider_id = request('provider_id');

            // Validate provider_id is a positive integer
            if (empty($provider_id) || !filter_var($provider_id, FILTER_VALIDATE_INT) || $provider_id <= 0) {
                throw new InvalidArgumentException('Invalid provider ID provided.');
            }

            $provider = $this->providers_model->find($provider_id);

            json_response($provider);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Update a provider.
     */
    public function update(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            check('provider', 'array');

            $provider = request('provider');

            $this->providers_model->only($provider, $this->allowed_provider_fields);

            $this->providers_model->only($provider['settings'], $this->allowed_provider_setting_fields);

            $this->providers_model->optional($provider, $this->optional_provider_fields);

            $this->providers_model->optional($provider['settings'], $this->optional_provider_setting_fields);

            if (!in_array($provider['commission_type'] ?? 'percentage', ['percentage', 'fixed', 'hourly'], true)) {
                throw new InvalidArgumentException('Geçersiz komisyon tipi.');
            }

            if ((float) ($provider['commission_value'] ?? 0) < 0) {
                throw new InvalidArgumentException('Komisyon değeri negatif olamaz.');
            }

            if ((float) ($provider['commission_overtime_bonus'] ?? 0) < 0) {
                throw new InvalidArgumentException('Ek komisyon negatif olamaz.');
            }

            $provider_id = $this->providers_model->save($provider);

            $provider = $this->providers_model->find($provider_id);

            $this->webhooks_client->trigger(WEBHOOK_PROVIDER_SAVE, $provider);

            // Salon Flora customization - real-time Google Sheets sync (see Google_sheets_writer).
            $this->load->library('google_sheets_writer');
            $this->google_sheets_writer->sync_record('providers', (int) $provider_id, 'upsert');

            json_response([
                'success' => true,
                'id' => $provider_id,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Remove a provider.
     */
    public function destroy(): void
    {
        try {
            method('post');

            if (cannot('delete', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            check('provider_id', 'numeric');

            $provider_id = request('provider_id');

            // Validate provider_id is a positive integer
            if (empty($provider_id) || !filter_var($provider_id, FILTER_VALIDATE_INT) || $provider_id <= 0) {
                throw new InvalidArgumentException('Invalid provider ID provided.');
            }

            $provider = $this->providers_model->find($provider_id);

            $this->providers_model->delete($provider_id);

            audit_log('provider.delete', 'provider', (int) $provider_id);

            $this->webhooks_client->trigger(WEBHOOK_PROVIDER_DELETE, $provider);

            // Salon Flora customization - real-time Google Sheets sync (see Google_sheets_writer).
            $this->load->library('google_sheets_writer');
            $this->google_sheets_writer->sync_record('providers', (int) $provider_id, 'delete');

            json_response([
                'success' => true,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Salon Flora customization (2026-08-24, KVKK data retention) - "Unutulma Hakkı" for an
     * (ex-)provider, as an in-place anonymization rather than a row delete (see
     * Providers_model::anonymize() docblock).
     */
    public function anonymize(): void
    {
        try {
            method('post');

            if (cannot('delete', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            check('provider_id', 'numeric');

            $provider_id = request('provider_id');

            if (empty($provider_id) || !filter_var($provider_id, FILTER_VALIDATE_INT) || $provider_id <= 0) {
                throw new InvalidArgumentException('Invalid provider ID provided.');
            }

            $this->providers_model->anonymize((int) $provider_id);

            audit_log('provider.anonymize', 'provider', (int) $provider_id, ['reason' => 'right_to_erasure']);

            json_response([
                'success' => true,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Ki Reservation (2026-08-26) - find or create a skill by name and return it, so an admin can add
     * a new skill inline while editing a provider (see the "Yeni yetenek ekle" input in providers.js)
     * without a dedicated catalog management page.
     */
    public function create_skill(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_USERS)) {
                abort(403, 'Forbidden');
            }

            check('name', 'string');

            $name = trim((string) request('name'));

            $skill = $this->skills_model->find_or_create_by_name($name);

            json_response([
                'success' => true,
                'id' => (int) $skill['id'],
                'name' => $skill['name'],
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
