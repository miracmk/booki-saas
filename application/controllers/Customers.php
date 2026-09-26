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
 * Customers controller.
 *
 * Handles the customers related operations.
 *
 * Salon Flora customization: this is a full override of the stock controller, whose search()/find() endpoints
 * returned complete customer records (surname, phone, email, address) to any role with PRIV_CUSTOMERS view access
 * - including providers, even though the frontend hides those fields from them
 * (Appointments_model::filter_customer_for_role(), applyCustomerPrivacyRestrictions() in appointments_modal.js).
 * That left the data reachable via a direct request to this endpoint regardless of what the UI showed. The only
 * changes from stock are marked below; everything else is untouched.
 *
 * @package Controllers
 */
class Customers extends App_Controller
{
    public array $allowed_customer_fields = [
        'id',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'address',
        'city',
        'state',
        'zip_code',
        'notes',
        // Salon Flora customization - CRM customer card (see migration 093): social_links is
        // encrypted JSON ({"whatsapp":...,"telegram":...,"instagram":...}), last_contact_channel is a
        // plain staff-set label.
        'social_links',
        'last_contact_channel',
        'timezone',
        'language',
        'custom_field_1',
        'custom_field_2',
        'custom_field_3',
        'custom_field_4',
        'custom_field_5',
        'ldap_dn',
    ];

    public array $optional_customer_fields = [
        //
    ];

    /**
     * Customers constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('appointments_model');
        $this->load->model('customers_model');
        $this->load->model('secretaries_model');
        $this->load->model('roles_model');
        $this->load->model('providers_model'); // BooKi (2026-08-26)
        $this->load->model('skills_model'); // BooKi (2026-08-26)
        $this->load->model('user_notification_preferences_model');

        $this->load->library('accounts');
        $this->load->library('permissions');
        $this->load->library('timezones');
        $this->load->library('webhooks_client');
    }

    /**
     * Render the backend customers page.
     *
     * On this page admin users will be able to manage customers, which are eventually selected by customers during the
     * booking process.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('customers')]);

        $user_id = session('user_id');

        if (cannot('view', PRIV_CUSTOMERS)) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        $role_slug = session('role_slug');

        $date_format = setting('date_format');
        $time_format = setting('time_format');
        $require_first_name = setting('require_first_name');
        $require_last_name = setting('require_last_name');
        $require_email = setting('require_email');
        $require_phone_number = setting('require_phone_number');
        $require_address = setting('require_address');
        $require_city = setting('require_city');
        $require_zip_code = setting('require_zip_code');

        $secretary_providers = [];

        if ($role_slug === DB_SLUG_SECRETARY) {
            $secretary = $this->secretaries_model->find($user_id);

            $secretary_providers = $secretary['providers'];
        }

        // BooKi (2026-08-26) - {id, name, skills: [skill names]} for every provider that has
        // at least one skill, used by the customer insight panel to suggest a provider whose skill
        // matches the customer's favorite/most-requested service (see customers.js:renderInsights()).
        // Kept as a lightweight, best-effort SIGNAL (case-insensitive name matching, no service<->skill
        // foreign key) rather than an authoritative recommendation.
        $providers_with_skills = [];

        $skill_names_by_id = [];
        foreach ($this->skills_model->to_options() as $skill_option) {
            $skill_names_by_id[$skill_option['value']] = $skill_option['label'];
        }

        foreach ($this->providers_model->get() as $provider) {
            if (empty($provider['skills'])) {
                continue;
            }

            $skill_names = array_values(
                array_filter(array_map(static fn($id) => $skill_names_by_id[$id] ?? null, $provider['skills'])),
            );

            $providers_with_skills[] = [
                'id' => $provider['id'],
                'name' => trim($provider['first_name'] . ' ' . $provider['last_name']),
                'skills' => $skill_names,
            ];
        }

        script_vars([
            'user_id' => $user_id,
            'role_slug' => $role_slug,
            'date_format' => $date_format,
            'time_format' => $time_format,
            'timezones' => $this->timezones->to_array(),
            'secretary_providers' => $secretary_providers,
            'default_language' => setting('default_language'),
            'default_timezone' => setting('default_timezone'),
            'providers_with_skills' => $providers_with_skills, // BooKi (2026-08-26)
        ]);

        html_vars([
            'page_title' => lang('customers'),
            'active_menu' => PRIV_CUSTOMERS,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'timezones' => $this->timezones->to_array(),
            'grouped_timezones' => $this->timezones->to_grouped_array(),
            'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
            'require_first_name' => $require_first_name,
            'require_last_name' => $require_last_name,
            'require_email' => $require_email,
            'require_phone_number' => $require_phone_number,
            'require_address' => $require_address,
            'require_city' => $require_city,
            'require_zip_code' => $require_zip_code,
            'available_languages' => config('available_languages'),
        ]);

        $this->load->view('pages/customers');
    }

    /**
     * Find a customer.
     */
    public function find(): void
    {
        try {
            method('get');

            if (cannot('view', PRIV_CUSTOMERS)) {
                abort(403, 'Forbidden');
            }

            $user_id = session('user_id');

            check('customer_id', 'numeric');

            $customer_id = request('customer_id');

            // Validate customer_id is a positive integer
            if (empty($customer_id) || !filter_var($customer_id, FILTER_VALIDATE_INT) || $customer_id <= 0) {
                throw new InvalidArgumentException('Invalid customer ID provided.');
            }

            if (!$this->permissions->has_customer_access($user_id, $customer_id)) {
                abort(403, 'Forbidden');
            }

            $customer = $this->customers_model->find($customer_id);
            $customer['notification_preferences'] = $this->user_notification_preferences_model->get((int) $customer_id);

            // Salon Flora customization - see the class docblock.
            $customer = $this->appointments_model->filter_customer_for_role($customer, session('role_slug'));

            json_response($customer);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Get 360 Degree Customer CRM Context & Chronological Timeline.
     */
    public function get_360($customer_id = null): void
    {
        try {
            method('get');

            if (cannot('view', PRIV_CUSTOMERS)) {
                abort(403, 'Forbidden');
            }

            $customer_id = (int) ($customer_id ?: $this->input->get('customer_id'));
            if ($customer_id <= 0) {
                throw new InvalidArgumentException('Invalid customer ID');
            }

            $user_id = session('user_id');
            if (!$this->permissions->has_customer_access($user_id, $customer_id)) {
                abort(403, 'Forbidden');
            }

            $data = $this->customers_model->get_customer_360_timeline($customer_id);
            json_response($data);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Filter customers by the provided keyword.
     */
    public function search(): void
    {
        try {
            method('post');

            if (cannot('view', PRIV_CUSTOMERS)) {
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

            $customers = $this->customers_model->search($keyword, $limit, $offset, $order_by);

            $user_id = session('user_id');
            $role_slug = session('role_slug');

            $secretary_provider_ids = [];

            if ($role_slug === DB_SLUG_SECRETARY) {
                $secretary_provider_ids = $this->secretaries_model->find($user_id)['providers'];
            }

            // Batch eager-load appointments and dependencies to eliminate N+1 queries
            $customer_ids = array_values(array_filter(array_map('intval', array_column($customers, 'id'))));
            $appointments_by_customer = [];

            if (!empty($customer_ids)) {
                $all_appointments = $this->db
                    ->where_in('id_users_customer', $customer_ids)
                    ->order_by('start_datetime', 'DESC')
                    ->get('appointments')
                    ->result_array();

                $service_ids = array_values(array_unique(array_filter(array_column($all_appointments, 'id_services'))));
                $services_map = [];
                if (!empty($service_ids)) {
                    $services = $this->db->where_in('id', $service_ids)->get('services')->result_array();
                    foreach ($services as $s) {
                        $services_map[$s['id']] = $s;
                    }
                }

                $provider_ids = array_values(array_unique(array_filter(array_column($all_appointments, 'id_users_provider'))));
                $providers_map = [];
                if (!empty($provider_ids)) {
                    $providers = $this->db->where_in('id', $provider_ids)->get('users')->result_array();
                    foreach ($providers as $p) {
                        $providers_map[$p['id']] = $p;
                    }
                }

                foreach ($all_appointments as $appt) {
                    $c_id = (int) $appt['id_users_customer'];
                    $appt['service'] = $services_map[$appt['id_services']] ?? null;
                    $appt['provider'] = $providers_map[$appt['id_users_provider']] ?? null;
                    $appointments_by_customer[$c_id][] = $appt;
                }
            }

            foreach ($customers as $index => &$customer) {
                if (!$this->permissions->has_customer_access($user_id, $customer['id'])) {
                    unset($customers[$index]);

                    continue;
                }

                $appointments = $appointments_by_customer[$customer['id']] ?? [];

                // If the current user is a provider, only include their own appointments.
                if ($role_slug === DB_SLUG_PROVIDER) {
                    $appointments = array_filter($appointments, function ($appointment) use ($user_id) {
                        return (int) $appointment['id_users_provider'] === (int) $user_id;
                    });

                    $appointments = array_values($appointments);
                }

                // If the current user is a secretary, only include appointments of their providers.
                if ($role_slug === DB_SLUG_SECRETARY) {
                    $appointments = array_filter($appointments, function ($appointment) use ($secretary_provider_ids) {
                        return in_array((int) $appointment['id_users_provider'], $secretary_provider_ids);
                    });

                    $appointments = array_values($appointments);
                }

                // Salon Flora customization - filter_customer_for_role() strips private fields for providers
                $customer = $this->appointments_model->filter_customer_for_role($customer, $role_slug);
                $customer['appointments'] = $appointments;
                $customer['notification_preferences'] = $this->user_notification_preferences_model->get((int) $customer['id']);
            }

            json_response(array_values($customers));
        } catch (Throwable $e) {
            json_exception($e);
        }
    }


    /**
     * Store a new customer.
     */
    public function store(): void
    {
        try {
            method('post');

            if (cannot('add', PRIV_CUSTOMERS)) {
                abort(403, 'Forbidden');
            }

            if (session('role_slug') !== DB_SLUG_ADMIN && setting('limit_customer_visibility')) {
                abort(403);
            }

            check('customer', 'array');

            $customer = request('customer');
            $notification_preferences = $customer['notification_preferences'] ?? null;
            unset($customer['notification_preferences']);

            $this->customers_model->only($customer, $this->allowed_customer_fields);

            $this->customers_model->optional($customer, $this->optional_customer_fields);

            $customer_id = $this->customers_model->save($customer);

            if (is_array($notification_preferences)) {
                $this->user_notification_preferences_model->save($customer_id, $notification_preferences);
            }

            $customer = $this->customers_model->find($customer_id);

            $this->webhooks_client->trigger(WEBHOOK_CUSTOMER_SAVE, $customer);

            // Salon Flora customization - real-time Google Sheets sync (see Google_sheets_writer).
            $this->load->library('google_sheets_writer');
            $this->google_sheets_writer->sync_record('customers', (int) $customer_id, 'upsert');

            json_response([
                'success' => true,
                'id' => $customer_id,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Update a customer.
     */
    public function update(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_CUSTOMERS)) {
                abort(403, 'Forbidden');
            }

            $user_id = session('user_id');

            check('customer', 'array');

            $customer = request('customer');
            $notification_preferences = $customer['notification_preferences'] ?? null;
            unset($customer['notification_preferences']);

            if (!$this->permissions->has_customer_access($user_id, $customer['id'])) {
                abort(403, 'Forbidden');
            }

            $this->customers_model->only($customer, $this->allowed_customer_fields);

            $this->customers_model->optional($customer, $this->optional_customer_fields);

            $customer_id = $this->customers_model->save($customer);

            if (is_array($notification_preferences)) {
                $this->user_notification_preferences_model->save((int) $customer_id, $notification_preferences);
            }

            $customer = $this->customers_model->find($customer_id);

            $this->webhooks_client->trigger(WEBHOOK_CUSTOMER_SAVE, $customer);

            // Salon Flora customization - real-time Google Sheets sync (see Google_sheets_writer).
            $this->load->library('google_sheets_writer');
            $this->google_sheets_writer->sync_record('customers', (int) $customer_id, 'upsert');

            json_response([
                'success' => true,
                'id' => $customer_id,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Remove a customer.
     */
    public function destroy(): void
    {
        try {
            method('post');

            if (cannot('delete', PRIV_CUSTOMERS)) {
                abort(403, 'Forbidden');
            }

            $user_id = session('user_id');

            check('customer_id', 'numeric');

            $customer_id = request('customer_id');

            // Validate customer_id is a positive integer
            if (empty($customer_id) || !filter_var($customer_id, FILTER_VALIDATE_INT) || $customer_id <= 0) {
                throw new InvalidArgumentException('Invalid customer ID provided.');
            }

            if (!$this->permissions->has_customer_access($user_id, $customer_id)) {
                abort(403, 'Forbidden');
            }

            $customer = $this->customers_model->find($customer_id);

            $this->customers_model->delete($customer_id);
            $this->user_notification_preferences_model->delete((int) $customer_id);

            audit_log('customer.delete', 'customer', (int) $customer_id);

            $this->webhooks_client->trigger(WEBHOOK_CUSTOMER_DELETE, $customer);

            // Salon Flora customization - real-time Google Sheets sync (see Google_sheets_writer).
            $this->load->library('google_sheets_writer');
            $this->google_sheets_writer->sync_record('customers', (int) $customer_id, 'delete');

            json_response([
                'success' => true,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * BooKi (2026-08-26) - "Müşteri Paneli": grant (or change) a customer's own login
     * credentials, so they can access CustomerPortal.php going forward - separate from any other
     * customer field edit, since it touches `user_settings` rather than `users`.
     */
    public function set_login(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_CUSTOMERS)) {
                abort(403, 'Forbidden');
            }

            check('customer_id', 'numeric');
            check('username', 'string');
            check('password', 'string');

            $customer_id = (int) request('customer_id');

            if (!$this->permissions->has_customer_access(session('user_id'), $customer_id)) {
                abort(403, 'Forbidden');
            }

            $this->customers_model->set_login_credentials(
                $customer_id,
                (string) request('username'),
                (string) request('password'),
            );

            audit_log('customer.set_login', 'customer', $customer_id);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Salon Flora customization (2026-08-24, KVKK data retention) - "Unutulma Hakkı" (right to
     * erasure) as an in-place anonymization rather than a row delete, so the customer's appointment/
     * payment history (needed for accounting - see Customers_model::anonymize() docblock) survives.
     * Same permission/access model as destroy().
     */
    public function anonymize(): void
    {
        try {
            method('post');

            if (cannot('delete', PRIV_CUSTOMERS)) {
                abort(403, 'Forbidden');
            }

            $user_id = session('user_id');

            check('customer_id', 'numeric');

            $customer_id = request('customer_id');

            if (empty($customer_id) || !filter_var($customer_id, FILTER_VALIDATE_INT) || $customer_id <= 0) {
                throw new InvalidArgumentException('Invalid customer ID provided.');
            }

            if (!$this->permissions->has_customer_access($user_id, $customer_id)) {
                abort(403, 'Forbidden');
            }

            $this->customers_model->anonymize((int) $customer_id);

            audit_log('customer.anonymize', 'customer', (int) $customer_id, ['reason' => 'right_to_erasure']);

            json_response([
                'success' => true,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
