<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Waitlist controller (Dalga 1, 2026-08-28).
 *
 * Handles CRUD + join operations for the customer waitlist.
 * Access: admin/secretary manage the list; providers can view their own.
 * ---------------------------------------------------------------------------- */

class Waitlist extends EA_Controller
{
    public array $allowed_entry_fields = [
        'id',
        'id_users_customer',
        'id_services',
        'id_users_provider',
        'requested_date',
        'requested_time_window_start',
        'requested_time_window_end',
        'notify_channel',
        'status',
    ];

    /**
     * Waitlist constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('waitlist_model');
        $this->load->model('customers_model');
        $this->load->model('services_model');
        $this->load->model('providers_model');
        $this->load->model('roles_model');

        $this->load->library('accounts');
        $this->load->library('waitlist_service');
    }

    /**
     * Render the waitlist management page.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('waitlist')]);

        $user_id = session('user_id');

        if (cannot('view', PRIV_WAITLIST)) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        $role_slug = session('role_slug');

        html_vars([
            'page_title' => 'Bekleme Listesi',
            'active_menu' => PRIV_WAITLIST,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
        ]);

        script_vars([
            'user_id' => $user_id,
            'role_slug' => $role_slug,
            'customers' => $this->customers_model->get(),
            'services' => $this->services_model->get(),
            'providers' => $this->providers_model->get_available_providers(),
        ]);

        $this->load->view('pages/waitlist');
    }

    /**
     * List currently-waiting entries.
     */
    public function search(): void
    {
        try {
            method('post');

            if (cannot('view', PRIV_WAITLIST)) {
                abort(403, 'Forbidden');
            }

            check('limit', 'numeric|null');
            check('offset', 'numeric|null');

            $limit = request('limit', 1000);
            $offset = (int) request('offset', '0');

            $this->waitlist_model->expire_stale();

            $entries = $this->waitlist_model->get_waiting($limit, $offset);

            json_response($entries);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Join the waitlist.
     */
    public function store(): void
    {
        try {
            method('post');

            if (cannot('add', PRIV_WAITLIST)) {
                abort(403, 'Forbidden');
            }

            check('entry', 'array');

            $entry = request('entry');
            unset($entry['id']);

            $this->waitlist_model->only($entry, $this->allowed_entry_fields);

            $entry_id = $this->waitlist_service->join($entry);

            audit_log('waitlist.join', 'waitlist_entry', $entry_id, [
                'customer_id' => $entry['id_users_customer'],
                'service_id' => $entry['id_services'],
            ]);

            json_response([
                'success' => true,
                'id' => $entry_id,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Remove/cancel a waitlist entry.
     */
    public function destroy(): void
    {
        try {
            method('post');

            if (cannot('delete', PRIV_WAITLIST)) {
                abort(403, 'Forbidden');
            }

            check('entry_id', 'numeric');

            $entry_id = request('entry_id');

            if (empty($entry_id) || !filter_var($entry_id, FILTER_VALIDATE_INT) || $entry_id <= 0) {
                throw new InvalidArgumentException('Invalid waitlist entry ID provided.');
            }

            $entry = $this->waitlist_model->find((int) $entry_id);

            audit_log('waitlist.cancel', 'waitlist_entry', $entry_id, [
                'customer_id' => $entry['id_users_customer'],
            ]);

            $this->waitlist_model->delete((int) $entry_id);

            json_response([
                'success' => true,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Proactively notify a waiting customer.
     */
    public function notify(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_WAITLIST)) {
                abort(403, 'Forbidden');
            }

            check('entry_id', 'numeric');

            $entry_id = (int) request('entry_id');
            $slot_datetime = request('slot_datetime') ?: null;
            $channel = request('channel') ?: 'both';

            if ($entry_id <= 0) {
                throw new InvalidArgumentException('Geçersiz bekleme listesi IDsi.');
            }

            $result = $this->waitlist_service->proactively_notify_customer($entry_id, $slot_datetime, $channel);

            audit_log('waitlist.notify', 'waitlist_entry', $entry_id, [
                'channel' => $channel,
                'slot_datetime' => $slot_datetime,
            ]);

            json_response([
                'success' => true,
                'message' => 'Müşteriye ön bilgilendirme başarıyla iletildi.',
                'result' => $result,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
