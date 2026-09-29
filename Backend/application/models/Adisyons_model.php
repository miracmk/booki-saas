<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Adisyons Model (Order Tabs, Service Tickets & Billing Operations)
 *
 * Sits between Appointments/Reservations and Payments/Invoices.
 * ---------------------------------------------------------------------------- */

class Adisyons_model extends App_Model
{
    protected array $casts = [
        'id' => 'integer',
        'id_users_customer' => 'integer',
        'id_appointments' => 'integer',
        'id_restaurant_tables' => 'integer',
        'id_users_staff' => 'integer',
        'id_invoices' => 'integer',
        'subtotal' => 'float',
        'discount_amount' => 'float',
        'discount_percent' => 'float',
        'tax_amount' => 'float',
        'total_amount' => 'float',
        'paid_amount' => 'float',
        'tip_amount' => 'float',
    ];

    /**
     * Create or retrieve open adisyon for an appointment.
     */
    public function get_or_create_for_appointment(int $appointment_id): array
    {
        $existing = $this->db->get_where('adisyons', [
            'id_appointments' => $appointment_id,
            'status' => 'open',
        ])->row_array();

        if ($existing) {
            $this->cast($existing);
            $this->apply_appointment_deposit_if_needed((int) $existing['id'], $appointment_id);
            return $this->find((int) $existing['id']);
        }

        // Fetch appointment details to initialize adisyon
        $appointment = $this->db
            ->select('a.*, s.name as service_name, s.price as service_price')
            ->from('appointments a')
            ->join('services s', 's.id = a.id_services', 'left')
            ->where('a.id', $appointment_id)
            ->get()
            ->row_array();

        if (!$appointment) {
            throw new InvalidArgumentException('Appointment not found: ' . $appointment_id);
        }

        $this->db->trans_start();
        try {
            $adisyon_number = $this->generate_adisyon_number();
            $now = date('Y-m-d H:i:s');
            $service_price = (float) ($appointment['service_price'] ?? 0);
            $tax_amount = round($service_price * 0.20, 2);
            $total_amount = round($service_price, 2);

            $this->db->insert('adisyons', [
                'adisyon_number' => $adisyon_number,
                'id_users_customer' => !empty($appointment['id_users_customer']) ? (int) $appointment['id_users_customer'] : null,
                'id_appointments' => $appointment_id,
                'id_users_staff' => !empty($appointment['id_users_provider']) ? (int) $appointment['id_users_provider'] : null,
                'status' => 'open',
                'payment_status' => 'unpaid',
                'invoice_status' => 'uninvoiced',
                'subtotal' => $service_price,
                'discount_amount' => 0.00,
                'discount_percent' => 0.00,
                'tax_amount' => $tax_amount,
                'total_amount' => $total_amount,
                'paid_amount' => 0.00,
                'tip_amount' => 0.00,
                'opened_at' => $now,
                'created_at' => $now,
            ]);
            $adisyon_id = $this->db->insert_id();

            // Add the main service as first line item
            $this->db->insert('adisyon_items', [
                'id_adisyons' => $adisyon_id,
                'item_type' => 'service',
                'id_services' => $appointment['id_services'],
                'name' => $appointment['service_name'] ?: 'Hizmet',
                'unit_price' => $service_price,
                'quantity' => 1.00,
                'discount_amount' => 0.00,
                'tax_rate' => 20.00,
                'tax_amount' => $tax_amount,
                'total_amount' => $total_amount,
                'id_users_staff' => $appointment['id_users_provider'],
                'created_at' => $now,
            ]);

            // Query appointment details & apply deposit if deposit_status === 'paid' and deposit_amount > 0
            $this->apply_appointment_deposit_if_needed($adisyon_id, $appointment_id, $appointment);

            $this->db->trans_complete();
            return $this->find($adisyon_id);
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            throw new RuntimeException('Could not create adisyon: ' . $e->getMessage());
        }
    }

    /**
     * Apply online appointment deposit to adisyon if paid and not yet recorded.
     */
    public function apply_appointment_deposit_if_needed(int $adisyon_id, int $appointment_id, ?array $appointment = null): void
    {
        if ($appointment === null) {
            $appointment = $this->db->get_where('appointments', ['id' => $appointment_id])->row_array();
        }

        if (!$appointment) {
            return;
        }

        $deposit_status = strtolower((string) ($appointment['deposit_status'] ?? ''));
        $deposit_amount = (float) ($appointment['deposit_amount'] ?? 0);

        if ($deposit_status === 'paid' && $deposit_amount > 0) {
            // Check if this deposit has already been recorded in adisyon_payments for this $adisyon_id
            $existing_payment = $this->db
                ->where('id_adisyons', $adisyon_id)
                ->like('notes', 'Online Randevu Kaporası')
                ->get('adisyon_payments')
                ->row_array();

            if (!$existing_payment) {
                $now = date('Y-m-d H:i:s');
                $this->db->insert('adisyon_payments', [
                    'id_adisyons' => $adisyon_id,
                    'payment_method' => 'card',
                    'amount' => $deposit_amount,
                    'notes' => 'Online Randevu Kaporası',
                    'created_at' => $now,
                ]);

                // Recalculate/update adisyon paid_amount = (current paid_amount + deposit_amount)
                $current_adisyon = $this->db->get_where('adisyons', ['id' => $adisyon_id])->row_array();
                if ($current_adisyon) {
                    $new_paid = round((float) ($current_adisyon['paid_amount'] ?? 0) + $deposit_amount, 2);
                    $total_amount = (float) ($current_adisyon['total_amount'] ?? 0);
                    $payment_status = ($new_paid >= $total_amount) ? 'paid' : 'partially_paid';

                    $this->db->where('id', $adisyon_id)->update('adisyons', [
                        'paid_amount' => $new_paid,
                        'payment_status' => $payment_status,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    /**
     * Create or retrieve open adisyon for a restaurant table.
     */
    public function get_or_create_for_table(int $table_id, ?int $customer_id = null, ?int $server_id = null): array
    {
        $existing = $this->db->get_where('adisyons', [
            'id_restaurant_tables' => $table_id,
            'status' => 'open',
        ])->row_array();

        if ($existing) {
            $this->cast($existing);
            return $existing;
        }

        $now = date('Y-m-d H:i:s');
        $adisyon_number = $this->generate_adisyon_number();

        $this->db->trans_start();
        try {
            $this->db->insert('adisyons', [
                'adisyon_number' => $adisyon_number,
                'id_users_customer' => $customer_id,
                'id_restaurant_tables' => $table_id,
                'id_users_staff' => $server_id,
                'status' => 'open',
                'payment_status' => 'unpaid',
                'invoice_status' => 'uninvoiced',
                'subtotal' => 0.00,
                'discount_amount' => 0.00,
                'discount_percent' => 0.00,
                'tax_amount' => 0.00,
                'total_amount' => 0.00,
                'paid_amount' => 0.00,
                'tip_amount' => 0.00,
                'opened_at' => $now,
                'created_at' => $now,
            ]);
            $adisyon_id = $this->db->insert_id();

            // Link table to this adisyon
            $this->db->update('restaurant_tables', [
                'current_id_adisyons' => $adisyon_id,
                'status' => 'seated',
                'seated_at' => $now,
                'id_users_server' => $server_id,
            ], ['id' => $table_id]);

            $this->db->trans_complete();
            return $this->find($adisyon_id);
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            throw new RuntimeException('Could not create table adisyon: ' . $e->getMessage());
        }
    }

    /**
     * Find adisyon by ID with items and payments.
     */
    public function find(int $adisyon_id): array
    {
        $adisyon = $this->db
            ->select('a.*, 
                      c.first_name as customer_first_name, c.last_name as customer_last_name, c.phone_number as customer_phone, c.email as customer_email,
                      u.first_name as staff_first_name, u.last_name as staff_last_name,
                      rt.table_number, rt.name as table_name, rt.section as table_section,
                      inv.invoice_number')
            ->from('adisyons a')
            ->join('users c', 'c.id = a.id_users_customer', 'left')
            ->join('users u', 'u.id = a.id_users_staff', 'left')
            ->join('restaurant_tables rt', 'rt.id = a.id_restaurant_tables', 'left')
            ->join('invoices inv', 'inv.id = a.id_invoices', 'left')
            ->where('a.id', $adisyon_id)
            ->get()
            ->row_array();

        if (!$adisyon) {
            throw new InvalidArgumentException('Adisyon not found: ' . $adisyon_id);
        }

        $this->cast($adisyon);

        if (!empty($adisyon['customer_phone']) && function_exists('sf_pii_is_encrypted') && sf_pii_is_encrypted($adisyon['customer_phone'])) {
            $adisyon['customer_phone'] = sf_pii_decrypt($adisyon['customer_phone']);
        }
        if (!empty($adisyon['customer_email']) && function_exists('sf_pii_is_encrypted') && sf_pii_is_encrypted($adisyon['customer_email'])) {
            $adisyon['customer_email'] = sf_pii_decrypt($adisyon['customer_email']);
        }

        // Fetch items
        $adisyon['items'] = $this->db
            ->select('ai.*, s.name as service_name, p.name as product_name, u.first_name as staff_first_name, u.last_name as staff_last_name')
            ->from('adisyon_items ai')
            ->join('services s', 's.id = ai.id_services', 'left')
            ->join('products p', 'p.id = ai.id_products', 'left')
            ->join('users u', 'u.id = ai.id_users_staff', 'left')
            ->where('ai.id_adisyons', $adisyon_id)
            ->order_by('ai.id ASC')
            ->get()
            ->result_array();

        // Fetch payments
        $adisyon['payments'] = $this->db
            ->select('ap.*, u.first_name as receiver_first_name, u.last_name as receiver_last_name')
            ->from('adisyon_payments ap')
            ->join('users u', 'u.id = ap.received_by', 'left')
            ->where('ap.id_adisyons', $adisyon_id)
            ->order_by('ap.created_at ASC')
            ->get()
            ->result_array();

        return $adisyon;
    }

    /**
     * Add item to an open adisyon.
     */
    public function add_item(int $adisyon_id, array $item): int
    {
        $adisyon = $this->db->get_where('adisyons', ['id' => $adisyon_id])->row_array();
        if (!$adisyon || $adisyon['status'] === 'closed') {
            throw new InvalidArgumentException('Cannot add item to closed/invalid adisyon');
        }

        $unit_price = (float) ($item['unit_price'] ?? 0.00);
        $quantity = (float) ($item['quantity'] ?? 1.00);
        $discount_amount = (float) ($item['discount_amount'] ?? 0.00);
        $tax_rate = (float) ($item['tax_rate'] ?? 20.00);
        $line_subtotal = round(($unit_price * $quantity) - $discount_amount, 2);
        $tax_amount = round($line_subtotal * ($tax_rate / 100), 2);
        $total_amount = $line_subtotal;

        $this->db->trans_start();
        try {
            $this->db->insert('adisyon_items', [
                'id_adisyons' => $adisyon_id,
                'item_type' => $item['item_type'] ?? 'product',
                'id_services' => !empty($item['id_services']) ? (int) $item['id_services'] : null,
                'id_products' => !empty($item['id_products']) ? (int) $item['id_products'] : null,
                'id_service_addons' => !empty($item['id_service_addons']) ? (int) $item['id_service_addons'] : null,
                'name' => $item['name'] ?? 'Ürün/Hizmet',
                'unit_price' => $unit_price,
                'quantity' => $quantity,
                'discount_amount' => $discount_amount,
                'tax_rate' => $tax_rate,
                'tax_amount' => $tax_amount,
                'total_amount' => $total_amount,
                'id_users_staff' => !empty($item['id_users_staff']) ? (int) $item['id_users_staff'] : null,
                'id_customer_packages' => !empty($item['id_customer_packages']) ? (int) $item['id_customer_packages'] : null,
                'id_customer_memberships' => !empty($item['id_customer_memberships']) ? (int) $item['id_customer_memberships'] : null,
                'notes' => $item['notes'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $item_id = $this->db->insert_id();

            // Recompute adisyon totals
            $this->recompute_totals($adisyon_id);

            $this->db->trans_complete();
            return $item_id;
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            throw new RuntimeException('Could not add item: ' . $e->getMessage());
        }
    }

    /**
     * Remove item from an open adisyon.
     */
    public function remove_item(int $item_id): void
    {
        $item = $this->db->get_where('adisyon_items', ['id' => $item_id])->row_array();
        if (!$item) {
            return;
        }

        $this->db->trans_start();
        $this->db->delete('adisyon_items', ['id' => $item_id]);
        $this->recompute_totals((int) $item['id_adisyons']);
        $this->db->trans_complete();
    }

    /**
     * Apply general discount to adisyon.
     */
    public function apply_discount(int $adisyon_id, float $amount, float $percent = 0.0): void
    {
        $this->db->update('adisyons', [
            'discount_amount' => $amount,
            'discount_percent' => $percent,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $adisyon_id]);

        $this->recompute_totals($adisyon_id);
    }

    /**
     * Record payment against an adisyon (split or single).
     */
    public function record_payment(int $adisyon_id, array $payment_data): int
    {
        $adisyon = $this->find($adisyon_id);
        $amount = (float) ($payment_data['amount'] ?? 0.00);
        $method = $payment_data['payment_method'] ?? 'cash';
        $now = date('Y-m-d H:i:s');

        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than 0');
        }

        $this->db->trans_start();
        try {
            $this->db->insert('adisyon_payments', [
                'id_adisyons' => $adisyon_id,
                'payment_method' => $method,
                'amount' => $amount,
                'id_customer_packages' => !empty($payment_data['id_customer_packages']) ? (int) $payment_data['id_customer_packages'] : null,
                'id_customer_memberships' => !empty($payment_data['id_customer_memberships']) ? (int) $payment_data['id_customer_memberships'] : null,
                'notes' => $payment_data['notes'] ?? null,
                'received_by' => $payment_data['received_by'] ?? null,
                'created_at' => $now,
            ]);
            $payment_id = $this->db->insert_id();

            // Check if paid with package -> consume package session
            if ($method === 'package' && !empty($payment_data['id_customer_packages']) && !empty($adisyon['id_appointments'])) {
                $this->load->model('packages_model');
                $this->packages_model->consume_session((int) $payment_data['id_customer_packages'], (int) $adisyon['id_appointments']);
            }

            // Check if paid with membership -> consume membership session
            if ($method === 'membership' && !empty($payment_data['id_customer_memberships']) && !empty($adisyon['id_appointments'])) {
                $this->load->model('customer_memberships_model');
                $this->customer_memberships_model->consume_session((int) $payment_data['id_customer_memberships'], (int) $adisyon['id_appointments']);
            }

            // Check if paid with gift card -> redeem balance
            if ($method === 'gift_card' && !empty($payment_data['gift_card_code'])) {
                $this->load->model('gift_cards_model');
                $redeem_res = $this->gift_cards_model->redeem(
                    $payment_data['gift_card_code'],
                    $amount,
                    !empty($adisyon['id_appointments']) ? (int) $adisyon['id_appointments'] : null,
                    $adisyon_id
                );
                if (empty($redeem_res['success'])) {
                    throw new RuntimeException($redeem_res['message'] ?? 'Hediye kartı tahsilatı gerçekleştirilemedi.');
                }
            }

            // If cash payment and cash register is active, register cash in
            if ($method === 'cash') {
                $open_register = $this->db->get_where('cash_registers', ['status' => 'open'])->row_array();
                if ($open_register) {
                    $this->db->set('current_balance', 'current_balance + ' . $amount, false);
                    $this->db->set('total_cash_in', 'total_cash_in + ' . $amount, false);
                    $this->db->where('id', $open_register['id']);
                    $this->db->update('cash_registers');
                }
            }

            // Recompute paid amount and statuses
            $total_paid = (float) $this->db
                ->select_sum('amount')
                ->where('id_adisyons', $adisyon_id)
                ->get('adisyon_payments')
                ->row()->amount;

            $status = ($total_paid >= $adisyon['total_amount']) ? 'paid' : 'partially_paid';

            $this->db->update('adisyons', [
                'paid_amount' => $total_paid,
                'payment_status' => $status,
                'updated_at' => $now,
            ], ['id' => $adisyon_id]);

            $this->db->trans_complete();
            return $payment_id;
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            throw new RuntimeException('Could not record payment: ' . $e->getMessage());
        }
    }

    /**
     * Close adisyon (finalize visit/order).
     */
    public function close(int $adisyon_id): void
    {
        $adisyon = $this->find($adisyon_id);
        $now = date('Y-m-d H:i:s');

        $this->db->trans_start();
        try {
            $this->db->update('adisyons', [
                'status' => 'closed',
                'closed_at' => $now,
                'updated_at' => $now,
            ], ['id' => $adisyon_id]);

            // Release restaurant table if linked
            if (!empty($adisyon['id_restaurant_tables'])) {
                $this->db->update('restaurant_tables', [
                    'current_id_adisyons' => null,
                    'status' => 'cleaning',
                ], ['id' => $adisyon['id_restaurant_tables']]);
            }

            // Trigger consumable deduction for services in adisyon
            $this->load->model('inventory_consumables_model');
            foreach ($adisyon['items'] as $item) {
                if ($item['item_type'] === 'service' && !empty($item['id_services'])) {
                    $this->inventory_consumables_model->deduct_for_service(
                        (int) $item['id_services'],
                        $adisyon['id_appointments'],
                        $adisyon_id
                    );
                } elseif ($item['item_type'] === 'product' && !empty($item['id_products'])) {
                    $this->inventory_consumables_model->deduct_for_product_sale(
                        (int) $item['id_products'],
                        (float) $item['quantity'],
                        $adisyon_id
                    );
                }
            }

            // Calculate staff commissions
            $this->load->model('staff_commissions_model');
            $this->staff_commissions_model->calculate_for_adisyon($adisyon_id);

            $this->db->trans_complete();
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            throw new RuntimeException('Could not close adisyon: ' . $e->getMessage());
        }
    }

    /**
     * Convert adisyon to Invoice with optional ERP synchronization.
     */
    public function convert_to_invoice(int $adisyon_id, bool $send_to_erp = false, ?string $erp_provider = null): array
    {
        $adisyon = $this->find($adisyon_id);
        
        $customer_id = !empty($adisyon['id_users_customer']) ? (int) $adisyon['id_users_customer'] : null;
        if (!$customer_id) {
            // Find or fallback to first active customer or create guest customer
            $first_cust = $this->db->get_where('users', ['id_roles' => 3])->row_array();
            if ($first_cust) {
                $customer_id = (int) $first_cust['id'];
                $this->db->update('adisyons', ['id_users_customer' => $customer_id], ['id' => $adisyon_id]);
            } else {
                throw new InvalidArgumentException('Fatura oluşturmak için adisyonda kayıtlı bir müşteri olmalıdır.');
            }
        }

        $this->load->model('invoices_model');
        $items = [];
        foreach ($adisyon['items'] as $it) {
            $items[] = [
                'item_type' => $it['item_type'] ?: 'service',
                'id_reference' => $it['id_services'] ?: $it['id_products'],
                'description' => $it['name'],
                'quantity' => (float) $it['quantity'],
                'unit_price' => (float) $it['unit_price'],
                'tax_rate' => (float) $it['tax_rate'],
            ];
        }

        if (empty($items)) {
            $items[] = [
                'item_type' => 'service',
                'description' => 'Adisyon Hizmet Bedeli',
                'quantity' => 1.0,
                'unit_price' => (float) $adisyon['total_amount'],
                'tax_rate' => 20.0,
            ];
        }

        $invoice_id = $this->invoices_model->create_with_items([
            'id_users_customer' => $customer_id,
            'notes' => 'Adisyon #' . $adisyon['adisyon_number'] . ' üzerinden oluşturuldu.',
        ], $items);

        $this->db->update('adisyons', [
            'id_invoices' => $invoice_id,
            'invoice_status' => 'invoiced',
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $adisyon_id]);

        $erp_result = null;
        $erp_synced = false;
        $erp_message = null;

        if ($send_to_erp) {
            try {
                $this->load->library('accounting/erp_manager');
                $erp_result = $this->erp_manager->sync_invoice($invoice_id, $erp_provider);
                $erp_synced = true;
                $erp_message = $erp_result['message'] ?? 'ERP sistemine aktarıldı.';
            } catch (Throwable $e) {
                $erp_synced = false;
                $erp_message = 'ERP aktarımı sırasında hata: ' . $e->getMessage();
            }
        }

        return [
            'success' => true,
            'invoice_id' => $invoice_id,
            'adisyon_id' => $adisyon_id,
            'adisyon_number' => $adisyon['adisyon_number'],
            'erp_synced' => $erp_synced,
            'erp_message' => $erp_message,
            'erp_result' => $erp_result,
        ];
    }

    /**
     * Bulk convert multiple adisyons to invoices.
     */
    public function bulk_convert_to_invoices(array $adisyon_ids, bool $send_to_erp = false, ?string $erp_provider = null): array
    {
        $results = [];
        $created_count = 0;
        $erp_synced_count = 0;
        $errors = [];

        foreach ($adisyon_ids as $adisyon_id) {
            $id = (int) $adisyon_id;
            if (!$id) continue;

            try {
                $res = $this->convert_to_invoice($id, $send_to_erp, $erp_provider);
                $results[] = $res;
                $created_count++;
                if (!empty($res['erp_synced'])) {
                    $erp_synced_count++;
                }
            } catch (Throwable $e) {
                $errors[] = [
                    'adisyon_id' => $id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => $created_count > 0,
            'total_processed' => count($adisyon_ids),
            'created_count' => $created_count,
            'erp_synced_count' => $erp_synced_count,
            'results' => $results,
            'errors' => $errors,
        ];
    }

    /**
     * Recompute and store subtotal, taxes, and total.
     */
    public function recompute_totals(int $adisyon_id): void
    {
        $items = $this->db->get_where('adisyon_items', ['id_adisyons' => $adisyon_id])->result_array();
        $subtotal = 0.00;
        $tax_total = 0.00;

        foreach ($items as $it) {
            $line_total = (float) $it['total_amount'];
            $subtotal += $line_total;
            $tax_total += (float) $it['tax_amount'];
        }

        $adisyon = $this->db->get_where('adisyons', ['id' => $adisyon_id])->row_array();
        $discount = (float) ($adisyon['discount_amount'] ?? 0.00);
        $total = max(0.00, round($subtotal - $discount, 2));

        $this->db->update('adisyons', [
            'subtotal' => round($subtotal, 2),
            'tax_amount' => round($tax_total, 2),
            'total_amount' => $total,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $adisyon_id]);
    }

    /**
     * Generate unique adisyon number (e.g. AD-202609-0001).
     */
    protected function generate_adisyon_number(): string
    {
        $prefix = 'AD-' . date('Ym') . '-';
        $last = $this->db
            ->select('adisyon_number')
            ->like('adisyon_number', $prefix, 'after')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('adisyons')
            ->row_array();

        if ($last) {
            $num = (int) substr($last['adisyon_number'], strlen($prefix)) + 1;
        } else {
            $num = 1;
        }

        return $prefix . str_pad((string) $num, 4, '0', STR_PAD_LEFT);
    }
}
