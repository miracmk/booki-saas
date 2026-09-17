<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Invoices Model
 *
 * An invoice aggregates charges (appointments, packages, products,
 * memberships) into one billing document for a customer. This model only
 * READS from appointments/customer_packages/products/customer_memberships
 * when assembling candidate line items (get_billable_appointments()) - it
 * never writes to them, and it never writes to payment_transactions either
 * (payment collection against an invoice is a later wave's concern; an
 * invoice can exist in draft/issued/paid status purely as a billing record).
 * ---------------------------------------------------------------------------- */

class Invoices_model extends EA_Model
{
    protected array $casts = [
        'id' => 'integer',
        'id_users_customer' => 'integer',
        'subtotal' => 'float',
        'tax_total' => 'float',
        'total' => 'float',
        'created_by' => 'integer',
    ];

    /**
     * Create a new invoice with its line items in a single transaction. Subtotal/total are
     * computed from the items, never trusted from the caller.
     *
     * @param array $invoice ['id_users_customer', 'currency'?, 'due_at'?, 'notes'?, 'created_by'?]
     * @param array $items Each: ['item_type', 'id_reference'?, 'description', 'quantity'?, 'unit_price']
     * @return int The new invoice ID.
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function create_with_items(array $invoice, array $items): int
    {
        if (empty($invoice['id_users_customer'])) {
            throw new InvalidArgumentException('Fatura için müşteri gereklidir.');
        }

        if (empty($items)) {
            throw new InvalidArgumentException('Fatura en az bir kalem içermelidir.');
        }

        $subtotal = 0;

        foreach ($items as $item) {
            if (empty($item['description']) || !isset($item['unit_price'])) {
                throw new InvalidArgumentException('Her kalem açıklama ve birim fiyat içermelidir.');
            }

            $quantity = (float) ($item['quantity'] ?? 1);
            $subtotal += $quantity * (float) $item['unit_price'];
        }

        $tax_total = 0;
        foreach ($items as $item) {
            $tax_rate = isset($item['tax_rate']) ? (float) $item['tax_rate'] : 20.0;
            $line_total = round((float) ($item['quantity'] ?? 1) * (float) $item['unit_price'], 2);
            $tax_total += round($line_total * ($tax_rate / 100), 2);
        }

        $tax_total = round($tax_total, 2);
        $total = round($subtotal + $tax_total, 2);

        $this->db->trans_start();

        try {
            $invoice_id = $this->db->insert('invoices', [
                'invoice_number' => $this->generate_invoice_number(),
                'id_users_customer' => !empty($invoice['id_users_customer']) ? (int) $invoice['id_users_customer'] : null,
                'status' => 'draft',
                'subtotal' => $subtotal,
                'tax_total' => $tax_total,
                'total' => $total,
                'currency' => $invoice['currency'] ?? 'TRY',
                'due_at' => $invoice['due_at'] ?? null,
                'notes' => $invoice['notes'] ?? null,
                'created_by' => $invoice['created_by'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
            ]) ? $this->db->insert_id() : null;

            if (!$invoice_id) {
                throw new RuntimeException('Could not insert invoice.');
            }

            foreach ($items as $item) {
                $quantity = (float) ($item['quantity'] ?? 1);
                $unit_price = (float) $item['unit_price'];

                $this->db->insert('invoice_items', [
                    'id_invoices' => $invoice_id,
                    'item_type' => $item['item_type'] ?? 'product',
                    'id_reference' => $item['id_reference'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $quantity,
                    'unit_price' => $unit_price,
                    'line_total' => round($quantity * $unit_price, 2),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $this->db->trans_complete();
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            throw new RuntimeException('Could not create invoice: ' . $e->getMessage());
        }

        return $invoice_id;
    }

    /**
     * Generate the next sequential invoice number for the current year (e.g. "INV-2026-000123").
     * Wrapped by the caller's transaction (create_with_items()) so the count+insert is atomic
     * enough for this wave's expected volume; a genuine high-concurrency counter (e.g.
     * INSERT ... ON DUPLICATE KEY for a per-year sequence row) is a fair follow-up if needed.
     *
     * @return string
     */
    private function generate_invoice_number(): string
    {
        $year = date('Y');

        $count = $this->db
            ->where("invoice_number LIKE 'INV-{$year}-%'")
            ->count_all_results('invoices');

        return sprintf('INV-%s-%06d', $year, $count + 1);
    }

    /**
     * Find an invoice with its line items.
     *
     * @param int $invoice_id Invoice ID.
     * @return array ['invoice' => array, 'items' => array]
     * @throws InvalidArgumentException
     */
    public function find_with_items(int $invoice_id): array
    {
        $invoice = $this->db->get_where('invoices', ['id' => $invoice_id])->row_array();

        if (!$invoice) {
            throw new InvalidArgumentException('The provided invoice ID was not found in the database: ' . $invoice_id);
        }

        $this->cast($invoice);

        $items = $this->db->get_where('invoice_items', ['id_invoices' => $invoice_id])->result_array();

        return ['invoice' => $invoice, 'items' => $items];
    }

    /**
     * Get all invoices (admin management page), joined with customer names.
     *
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @return array Returns an array of invoices.
     */
    public function get_all(?int $limit = null, ?int $offset = null): array
    {
        $invoices = $this->db
            ->select('i.*, u.first_name as customer_first_name, u.last_name as customer_last_name')
            ->from('invoices i')
            ->join('users u', 'u.id = i.id_users_customer', 'left')
            ->order_by('i.created_at', 'DESC')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->result_array();

        foreach ($invoices as &$invoice) {
            $this->cast($invoice);
        }

        return $invoices;
    }

    /**
     * Get all invoices for a customer.
     *
     * @param int $customer_id Customer ID.
     * @return array Returns an array of invoices.
     */
    public function get_for_customer(int $customer_id): array
    {
        $invoices = $this->db
            ->where('id_users_customer', $customer_id)
            ->order_by('created_at', 'DESC')
            ->get('invoices')
            ->result_array();

        foreach ($invoices as &$invoice) {
            $this->cast($invoice);
        }

        return $invoices;
    }

    /**
     * BooKi (2026-09-12) - finalized invoices (issued/paid/partially_paid - draft and void
     * excluded, matching what an accounting system should actually receive) in a date range, joined with
     * customer name and each invoice's item descriptions concatenated (one row per invoice, not per line
     * item - most accounting import formats expect one row per document). Used by
     * Invoices::export_csv().
     *
     * @param string $date_from 'Y-m-d'
     * @param string $date_to 'Y-m-d'
     * @return array
     */
    public function get_for_export(string $date_from, string $date_to): array
    {
        $invoices = $this->db
            ->select('i.*, u.first_name as customer_first_name, u.last_name as customer_last_name')
            ->from('invoices i')
            ->join('users u', 'u.id = i.id_users_customer', 'left')
            ->where_in('i.status', ['issued', 'paid', 'partially_paid'])
            ->where('DATE(i.created_at) >=', $date_from)
            ->where('DATE(i.created_at) <=', $date_to)
            ->order_by('i.created_at', 'ASC')
            ->get()
            ->result_array();

        foreach ($invoices as &$invoice) {
            $this->cast($invoice);

            $invoice['item_descriptions'] = $this->db
                ->select('description')
                ->where('id_invoices', $invoice['id'])
                ->get('invoice_items')
                ->result_array();
        }

        return $invoices;
    }

    /**
     * Mark an invoice as issued (draft -> issued, sets issued_at).
     *
     * @param int $invoice_id Invoice ID.
     */
    public function issue(int $invoice_id): void
    {
        $this->db->update(
            'invoices',
            ['status' => 'issued', 'issued_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            ['id' => $invoice_id],
        );
    }

    /**
     * Mark an invoice as paid.
     *
     * @param int $invoice_id Invoice ID.
     */
    public function mark_paid(int $invoice_id): void
    {
        $this->db->update(
            'invoices',
            ['status' => 'paid', 'updated_at' => date('Y-m-d H:i:s')],
            ['id' => $invoice_id],
        );
    }

    /**
     * Void an invoice.
     *
     * @param int $invoice_id Invoice ID.
     */
    public function void(int $invoice_id): void
    {
        $this->db->update(
            'invoices',
            ['status' => 'void', 'updated_at' => date('Y-m-d H:i:s')],
            ['id' => $invoice_id],
        );
    }

    /**
     * Find completed appointments for a customer that could be billed on an invoice (have an
     * actual check-out and are not already referenced by any invoice_items row) - candidate line
     * items for the invoice-creation UI. Read-only: never writes to appointments.
     *
     * Uses Appointments_model::compute_effective_billing() (the same real-duration/price_override
     * calculation already used for reports/commissions) so the invoiced amount matches what the
     * business actually charges, not a naive flat service price.
     *
     * @param int $customer_id Customer ID.
     * @return array Each: ['id_appointments', 'description', 'unit_price']
     */
    public function get_billable_appointments(int $customer_id): array
    {
        $this->load->model('appointments_model');
        $this->load->model('services_model');

        $appointments = $this->db
            ->select('a.*')
            ->from('appointments a')
            ->where('a.id_users_customer', $customer_id)
            ->where('a.actual_end_datetime IS NOT NULL')
            ->where(
                'a.id NOT IN (SELECT id_reference FROM ' .
                    $this->db->dbprefix('invoice_items') .
                    " WHERE item_type = 'appointment' AND id_reference IS NOT NULL)",
                null,
                false,
            )
            ->order_by('a.start_datetime', 'DESC')
            ->limit(50)
            ->get()
            ->result_array();

        $billable = [];

        foreach ($appointments as $appointment) {
            $service = $this->services_model->find((int) $appointment['id_services']);
            $billing = $this->appointments_model->compute_effective_billing($service, $appointment);

            $billable[] = [
                'id_appointments' => (int) $appointment['id'],
                'description' => $service['name'] . ' (' . substr($appointment['start_datetime'], 0, 10) . ')',
                'unit_price' => $billing['price'],
            ];
        }

        return $billable;
    }
}
