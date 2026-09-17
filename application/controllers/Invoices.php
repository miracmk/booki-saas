<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Invoices controller (Dalga 1, 2026-08-28).
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

        require_plan_feature(PRIV_INVOICES);

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

        $this->load->library('accounting/erp_manager');

        html_vars([
            'page_title' => 'Faturalar',
            'active_menu' => PRIV_INVOICES,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
            'erp_providers' => Erp_manager::PROVIDERS,
        ]);

        script_vars([
            'user_id' => $user_id,
            'role_slug' => $role_slug,
            'customers' => $this->customers_model->get(),
            'erp_providers' => Erp_manager::PROVIDERS,
        ]);

        $this->load->view('pages/invoices', [
            'erp_providers' => Erp_manager::PROVIDERS,
        ]);
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

    /**
     * Synchronize an invoice to an external ERP system (Paraşüt, BizimHesap, Logo, Mikro).
     */
    public function sync_erp(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_INVOICES)) {
                abort(403, 'Forbidden');
            }

            check('invoice_id', 'numeric');

            $invoice_id = (int) request('invoice_id');
            $provider = request('provider') ?: null;

            $this->load->library('accounting/erp_manager');
            $result = $this->erp_manager->sync_invoice($invoice_id, $provider);

            audit_log('invoice.sync_erp', 'invoice', $invoice_id, [
                'provider' => $result['provider'],
                'external_id' => $result['external_id'],
            ]);

            json_response([
                'success' => true,
                'message' => $result['message'],
                'data' => $result,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Printable e-Arşiv / e-Fatura preview layout.
     */
    public function print_view(int $invoice_id = 0): void
    {
        method('get');

        if (cannot('view', PRIV_INVOICES)) {
            abort(403, 'Forbidden');
        }

        $invoiceData = $this->invoices_model->find_with_items($invoice_id);
        $invoice = $invoiceData['invoice'];
        $items = $invoiceData['items'];

        $customer = null;
        if (!empty($invoice['id_users_customer'])) {
            $customer = $this->db->get_where('users', ['id' => $invoice['id_users_customer']])->row_array();
        }

        $company_name = $this->db->get_where('settings', ['name' => 'company_name'])->row_array()['value'] ?? 'BooKi İşletmesi';

        $this->load->view('pages/invoice_print', [
            'invoice' => $invoice,
            'items' => $items,
            'customer' => $customer,
            'company_name' => $company_name,
        ]);
    }

    /**
     * BooKi (2026-09-12) - "Muhasebe Dışa Aktar": a generic, one-row-per-invoice CSV that
     * Turkish accounting/ERP software (Logo, Mikro, Netsis, Zirve, İşBaşı, ETA, Paraşüt, KolayBi, ...)
     * can import by hand today. This is NOT a live push integration to any of those systems - most of
     * them (the classic on-premise ones) have no public cloud API a SaaS could call directly; Paraşüt
     * and KolayBi do have real REST APIs and are the natural next-phase candidates for a genuine push
     * connector once API credentials are available (out of scope for this pass).
     */
    public function export_csv(): void
    {
        try {
            method('get');

            if (cannot('view', PRIV_INVOICES)) {
                abort(403, 'Forbidden');
            }

            check('start_date', 'date');
            check('end_date', 'date');

            $start_date = request('start_date');
            $end_date = request('end_date');

            if ($end_date < $start_date) {
                throw new InvalidArgumentException('Bitiş tarihi başlangıç tarihinden önce olamaz.');
            }

            $invoices = $this->invoices_model->get_for_export($start_date, $end_date);

            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="muhasebe_' . $start_date . '_' . $end_date . '.csv"');
            header('Pragma: no-cache');
            header('Expires: 0');

            $output = fopen('php://output', 'w');

            // UTF-8 BOM + ';' delimiter so Excel (Turkish locale, ',' is the decimal separator) opens
            // Turkish characters and columns correctly without a manual "import as UTF-8" step - same
            // convention as Reports::export_csv().
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, [
                'Tarih', 'Fatura No', 'Müşteri', 'Vergi/TC No', 'Açıklama',
                'Ara Toplam', 'KDV Tutarı', 'Toplam', 'Para Birimi', 'Durum',
            ], ';');

            $money = fn($value) => number_format((float) $value, 2, ',', '');
            $status_label = [
                'issued' => 'Kesildi',
                'paid' => 'Ödendi',
                'partially_paid' => 'Kısmi Ödendi',
            ];

            foreach ($invoices as $invoice) {
                $customer_name = trim(($invoice['customer_first_name'] ?? '') . ' ' . ($invoice['customer_last_name'] ?? '')) ?: '-';
                $descriptions = implode('; ', array_column($invoice['item_descriptions'], 'description'));

                fputcsv($output, [
                    (new DateTime($invoice['created_at']))->format('d.m.Y'),
                    $invoice['invoice_number'],
                    $customer_name,
                    // BooKi (2026-09-12) - customers have no tax-ID/TC-kimlik field today; left
                    // blank rather than guessed. Add one to Customers_model if real e-Fatura-grade export
                    // is needed later.
                    '',
                    $descriptions ?: '-',
                    $money($invoice['subtotal']),
                    $money($invoice['tax_total']),
                    $money($invoice['total']),
                    $invoice['currency'],
                    $status_label[$invoice['status']] ?? $invoice['status'],
                ], ';');
            }

            fclose($output);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
