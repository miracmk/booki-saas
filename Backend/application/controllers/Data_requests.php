<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Data Requests admin controller (Faz 30, KVKK/GDPR, 2026-08-28).
 *
 * Lets staff review and act on customer-initiated KVKK requests: export requests
 * are fully self-service (Customer_portal builds/emails them automatically), but
 * erasure requests always land here first for a human decision - anonymizing a
 * customer can conflict with VUK accounting retention requirements, so it is
 * never fully automatic. Reuses PRIV_CUSTOMERS (no new permission constant -
 * a data request is fundamentally about a customer record).
 * ---------------------------------------------------------------------------- */

class Data_requests extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('data_requests_model');
        $this->load->model('customers_model');
        $this->load->model('roles_model');

        $this->load->library('accounts');
    }

    /**
     * Render the data requests management page.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('data_requests')]);

        $user_id = session('user_id');

        if (cannot('view', PRIV_CUSTOMERS)) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        $role_slug = session('role_slug');

        html_vars([
            'page_title' => 'Veri Talepleri',
            'active_menu' => 'data_requests',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
        ]);

        script_vars([
            'user_id' => $user_id,
            'role_slug' => $role_slug,
        ]);

        $this->load->view('pages/data_requests');
    }

    /**
     * List data requests (optionally filtered), plus overall status counts.
     */
    public function search(): void
    {
        try {
            method('post');

            if (cannot('view', PRIV_CUSTOMERS)) {
                abort(403, 'Forbidden');
            }

            check('request_type', 'string|null');
            check('status', 'string|null');
            check('limit', 'numeric|null');
            check('offset', 'numeric|null');

            $request_type = request('request_type') ?: null;
            $status = request('status') ?: null;
            $limit = (int) request('limit', 50);
            $offset = (int) request('offset', 0);

            $requests = $this->data_requests_model->search_admin($request_type, $status, $limit, $offset);

            // Attach the customer's current (possibly already-anonymized) name for display.
            foreach ($requests as &$row) {
                $customer = $this->customers_model->find($row['id_users']);
                $row['customer_name'] = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
            }

            json_response([
                'counts' => $this->data_requests_model->count_by_status(),
                'requests' => $requests,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Approve a pending erasure request: anonymize the customer and mark the request completed.
     */
    public function approve_erasure(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_CUSTOMERS)) {
                abort(403, 'Forbidden');
            }

            check('request_id', 'numeric');

            $request_id = (int) request('request_id');
            $request = $this->data_requests_model->find($request_id);

            if (!$request || $request['request_type'] !== 'erasure') {
                throw new InvalidArgumentException('Silme talebi bulunamadı.');
            }

            if ($request['status'] !== 'pending') {
                throw new InvalidArgumentException('Bu talep zaten işleme alınmış.');
            }

            $this->customers_model->anonymize($request['id_users']);

            $this->data_requests_model->mark_completed($request_id, 'Onaylandı ve anonimleştirildi.');

            audit_log('data_request.approve_erasure', 'data_request', $request_id, [
                'customer_id' => $request['id_users'],
            ]);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Reject a pending erasure request (e.g. active invoices under VUK retention).
     */
    public function reject_erasure(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_CUSTOMERS)) {
                abort(403, 'Forbidden');
            }

            check('request_id', 'numeric');
            check('reason', 'string|null');

            $request_id = (int) request('request_id');
            $reason = (string) request('reason', 'Reddedildi.');

            $request = $this->data_requests_model->find($request_id);

            if (!$request || $request['request_type'] !== 'erasure') {
                throw new InvalidArgumentException('Silme talebi bulunamadı.');
            }

            if ($request['status'] !== 'pending') {
                throw new InvalidArgumentException('Bu talep zaten işleme alınmış.');
            }

            $this->data_requests_model->mark_failed($request_id, $reason);

            audit_log('data_request.reject_erasure', 'data_request', $request_id, [
                'customer_id' => $request['id_users'],
                'reason' => $reason,
            ]);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
