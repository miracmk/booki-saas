<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Invoices controller (Dalga 1, 2026-08-28).
 *
 * Handles invoice creation (from staff-selected billable items), listing,
 * issuing, marking paid, voiding. Payment collection against an invoice is
 * out of scope for this wave (see Invoices_model class docblock).
 * Access: admin/secretary only.
 * ---------------------------------------------------------------------------- */

class Invoices extends EA_Controller
{
    /**
     * Invoices constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('invoices_model');
        $this->load->model('customers_model');
        $this->load->model('roles_model');

        $this->load->library('accounts');
    }

    /**
     * Render the invoices management page.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('invoices')]);

        $user_id = session('user_id');

        if (cannot('view', PRIV_INVOICES)) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        $role_slug = session('role_slug');

        html_vars([
            'page_title' => 'Faturalar',
            'active_menu' => PRIV_INVOICES,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
        ]);

        script_vars([
            'user_id' => $user_id,
            'role_slug' => $role_slug,
            'customers' => $this->customers_model->get(),
        ]);

        $this->load->view('pages/invoices');
    }

    /**
     * List invoices.
     */
    public function search(): void
    {
        try {
            method('post');

            if (cannot('view', PRIV_INVOICES)) {
                abort(403, 'Forbidden');
            }

            check('limit', 'numeric|null');
            check('offset', 'numeric|null');

            $limit = request('limit', 1000);
            $offset = (int) request('offset', '0');

            $invoices = $this->invoices_model->get_all($limit, $offset);

            json_response($invoices);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Get billable (uninvoiced, completed) appointments for a customer, to populate the
     * invoice-creation UI.
     */
    public function billable_items(): void
    {
        try {
            method('post');

            if (cannot('add', PRIV_INVOICES)) {
                abort(403, 'Forbidden');
            }

            check('customer_id', 'numeric');

            $customer_id = (int) request('customer_id');

            $items = $this->invoices_model->get_billable_appointments($customer_id);

            json_response($items);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Create a new invoice from staff-selected items.
     */
    public function store(): void
    {
        try {
            method('post');

            if (cannot('add', PRIV_INVOICES)) {
                abort(403, 'Forbidden');
            }

            check('customer_id', 'numeric');
            check('items', 'array');

            $customer_id = (int) request('customer_id');
            $items = request('items');

            $invoice_id = $this->invoices_model->create_with_items(
                ['id_users_customer' => $customer_id, 'created_by' => session('user_id')],
                $items,
            );

            audit_log('invoice.create', 'invoice', $invoice_id, [
                'customer_id' => $customer_id,
                'item_count' => count($items),
            ]);

            json_response([
                'success' => true,
                'id' => $invoice_id,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * View an invoice with its line items.
     */
    public function find(): void
    {
        try {
            method('post');

            if (cannot('view', PRIV_INVOICES)) {
                abort(403, 'Forbidden');
            }

            check('invoice_id', 'numeric');

            $invoice_id = (int) request('invoice_id');

            json_response($this->invoices_model->find_with_items($invoice_id));
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Mark an invoice as issued.
     */
    public function issue(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_INVOICES)) {
                abort(403, 'Forbidden');
            }

            check('invoice_id', 'numeric');

            $invoice_id = (int) request('invoice_id');

            $this->invoices_model->issue($invoice_id);

            audit_log('invoice.issue', 'invoice', $invoice_id, []);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Mark an invoice as paid.
     */
    public function mark_paid(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_INVOICES)) {
                abort(403, 'Forbidden');
            }

            check('invoice_id', 'numeric');

            $invoice_id = (int) request('invoice_id');

            $this->invoices_model->mark_paid($invoice_id);

            audit_log('invoice.mark_paid', 'invoice', $invoice_id, []);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Void an invoice.
     */
    public function void(): void
    {
        try {
            method('post');

            if (cannot('delete', PRIV_INVOICES)) {
                abort(403, 'Forbidden');
            }

            check('invoice_id', 'numeric');

            $invoice_id = (int) request('invoice_id');

            $this->invoices_model->void($invoice_id);

            audit_log('invoice.void', 'invoice', $invoice_id, []);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
