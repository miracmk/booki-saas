<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Inventory Consumables, Recipe & Session Cost Accounting Engine
 *
 * Manages service recipes (BOM), per-session consumables tracking (both standard
 * and extra items), atomic stock deductions, and unit economics (cost of consumables
 * and gross profit margin per appointment/service).
 * ---------------------------------------------------------------------------- */

class Inventory_consumables_model extends App_Model
{
    /**
     * Get consumable recipe rules for a service with product cost and unit details.
     */
    public function get_recipes_for_service(int $service_id): array
    {
        $rows = $this->db
            ->select('sc.*, p.name as product_name, p.sku, p.stock_quantity, COALESCE(p.cost_price, 0.00) as cost, COALESCE(p.unit, sc.unit, "adet") as product_unit')
            ->from('service_consumables sc')
            ->join('products p', 'p.id = sc.id_products', 'left')
            ->where('sc.id_services', $service_id)
            ->order_by('sc.id', 'ASC')
            ->get()
            ->result_array();

        foreach ($rows as &$r) {
            $r['cost'] = (float) $r['cost'];
            $r['quantity_used'] = (float) $r['quantity_used'];
            $r['stock_quantity'] = (float) ($r['stock_quantity'] ?? 0);
            $r['line_total_cost'] = round($r['quantity_used'] * $r['cost'], 2);
        }

        return $rows;
    }

    /**
     * Get recipe summary including total estimated consumable cost and gross margin.
     */
    public function get_service_recipe_summary(int $service_id): array
    {
        $service = $this->db->get_where('services', ['id' => $service_id])->row_array();
        if (!$service) {
            return [];
        }

        $recipes = $this->get_recipes_for_service($service_id);
        $total_consumable_cost = 0.0;

        foreach ($recipes as $rec) {
            $total_consumable_cost += (float) ($rec['line_total_cost'] ?? 0.0);
        }

        $service_price = (float) ($service['price'] ?? 0.0);
        $gross_profit = $service_price - $total_consumable_cost;
        $gross_margin_percent = $service_price > 0 ? round(($gross_profit / $service_price) * 100, 1) : 0.0;

        return [
            'service_id' => $service_id,
            'service_name' => $service['name'] ?? '',
            'service_price' => $service_price,
            'total_consumable_cost' => round($total_consumable_cost, 2),
            'gross_profit' => round($gross_profit, 2),
            'gross_margin_percent' => $gross_margin_percent,
            'item_count' => count($recipes),
            'recipes' => $recipes,
        ];
    }

    /**
     * Save consumable rule for a service (e.g. Diş Dolgusu -> 1 çift eldiven, 1 hasta önlüğü, 0.25 tüp dolgu).
     */
    public function save_recipe(array $data): int
    {
        if (empty($data['id_services']) || empty($data['id_products']) || empty($data['quantity_used'])) {
            throw new InvalidArgumentException('Hizmet, ürün ve miktar zorunludur.');
        }

        // Fetch product to inherit default unit if not provided
        $unit = !empty($data['unit']) ? $data['unit'] : null;
        if (empty($unit)) {
            $prod = $this->db->get_where('products', ['id' => $data['id_products']])->row_array();
            $unit = $prod['unit'] ?? 'adet';
        }

        $clean = [
            'id_services' => (int) $data['id_services'],
            'id_products' => (int) $data['id_products'],
            'quantity_used' => (float) $data['quantity_used'],
            'unit' => (string) $unit,
            'notes' => isset($data['notes']) ? trim((string) $data['notes']) : null,
        ];

        if (empty($data['id'])) {
            $clean['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('service_consumables', $clean);
            return $this->db->insert_id();
        } else {
            $id = (int) $data['id'];
            $this->db->update('service_consumables', $clean, ['id' => $id]);
            return $id;
        }
    }

    /**
     * Delete consumable rule.
     */
    public function delete_recipe(int $recipe_id): void
    {
        $this->db->delete('service_consumables', ['id' => $recipe_id]);
    }

    // =========================================================================
    // APPOINTMENT CONSUMABLES & PER-SESSION TRACKING
    // =========================================================================

    /**
     * Get all consumables logged for a specific appointment session.
     * If none exist yet, automatically generates preview/initial items from the service recipe.
     */
    public function get_appointment_consumables(int $appointment_id, bool $auto_populate = true): array
    {
        $items = $this->db
            ->select('ac.*, p.name as product_name, p.sku, p.stock_quantity, COALESCE(p.cost_price, 0.00) as current_product_cost, COALESCE(p.unit, ac.unit, "adet") as product_unit')
            ->from('appointment_consumables ac')
            ->join('products p', 'p.id = ac.id_products', 'left')
            ->where('ac.id_appointments', $appointment_id)
            ->order_by('ac.is_extra ASC, ac.id ASC')
            ->get()
            ->result_array();

        // If no appointment consumables found and auto-populate requested, check service recipes
        if (empty($items) && $auto_populate) {
            $appt = $this->db->get_where('appointments', ['id' => $appointment_id])->row_array();
            if ($appt && !empty($appt['id_services'])) {
                $this->populate_appointment_consumables_from_recipe($appointment_id, (int) $appt['id_services']);

                // Re-fetch now populated items
                $items = $this->db
                    ->select('ac.*, p.name as product_name, p.sku, p.stock_quantity, COALESCE(p.cost_price, 0.00) as current_product_cost, COALESCE(p.unit, ac.unit, "adet") as product_unit')
                    ->from('appointment_consumables ac')
                    ->join('products p', 'p.id = ac.id_products', 'left')
                    ->where('ac.id_appointments', $appointment_id)
                    ->order_by('ac.is_extra ASC, ac.id ASC')
                    ->get()
                    ->result_array();
            }
        }

        foreach ($items as &$it) {
            $it['quantity_used'] = (float) $it['quantity_used'];
            $it['unit_cost'] = (float) $it['unit_cost'];
            $it['total_cost'] = (float) $it['total_cost'];
            $it['is_extra'] = (int) $it['is_extra'];
            $it['stock_quantity'] = (float) ($it['stock_quantity'] ?? 0);
        }

        return $items;
    }

    /**
     * Copy recipe items from a service into appointment_consumables for a specific appointment.
     */
    public function populate_appointment_consumables_from_recipe(int $appointment_id, int $service_id): void
    {
        $recipes = $this->get_recipes_for_service($service_id);
        if (empty($recipes)) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        foreach ($recipes as $rec) {
            // Check if already populated for this product
            $exists = $this->db
                ->get_where('appointment_consumables', [
                    'id_appointments' => $appointment_id,
                    'id_products' => $rec['id_products'],
                    'is_extra' => 0,
                ])
                ->num_rows();

            if ($exists === 0) {
                $qty = (float) $rec['quantity_used'];
                $unit_cost = (float) ($rec['cost'] ?? 0.00);
                $total_cost = round($qty * $unit_cost, 2);

                $this->db->insert('appointment_consumables', [
                    'id_appointments' => $appointment_id,
                    'id_products' => (int) $rec['id_products'],
                    'quantity_used' => $qty,
                    'unit' => $rec['unit'] ?? $rec['product_unit'] ?? 'adet',
                    'unit_cost' => $unit_cost,
                    'total_cost' => $total_cost,
                    'is_extra' => 0,
                    'notes' => $rec['notes'] ?? 'Standart Hizmet Reçetesi',
                    'created_at' => $now,
                ]);
            }
        }

        $this->recalculate_appointment_costs($appointment_id);
    }

    /**
     * Save or update an appointment consumable item (e.g. extra hair dye, extra anesthetic).
     */
    public function save_appointment_consumable(array $data): int
    {
        if (empty($data['id_appointments']) || empty($data['id_products']) || !isset($data['quantity_used'])) {
            throw new InvalidArgumentException('Randevu ID, ürün ve miktar zorunludur.');
        }

        $appointment_id = (int) $data['id_appointments'];
        $product_id = (int) $data['id_products'];
        $qty = (float) $data['quantity_used'];

        // Get product details for cost and unit if not provided
        $product = $this->db->get_where('products', ['id' => $product_id])->row_array();
        $unit_cost = isset($data['unit_cost']) ? (float) $data['unit_cost'] : (float) ($product['cost_price'] ?? 0.00);
        $unit = $data['unit'] ?? $product['unit'] ?? 'adet';
        $total_cost = round($qty * $unit_cost, 2);

        $payload = [
            'id_appointments' => $appointment_id,
            'id_products' => $product_id,
            'quantity_used' => $qty,
            'unit' => $unit,
            'unit_cost' => $unit_cost,
            'total_cost' => $total_cost,
            'is_extra' => !empty($data['is_extra']) ? 1 : 0,
            'notes' => $data['notes'] ?? null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (empty($data['id'])) {
            $payload['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('appointment_consumables', $payload);
            $id = $this->db->insert_id();
        } else {
            $id = (int) $data['id'];
            $this->db->update('appointment_consumables', $payload, ['id' => $id]);
        }

        $this->recalculate_appointment_costs($appointment_id);

        return $id;
    }

    /**
     * Delete an appointment consumable entry.
     */
    public function delete_appointment_consumable(int $id): void
    {
        $row = $this->db->get_where('appointment_consumables', ['id' => $id])->row_array();
        if ($row) {
            $appointment_id = (int) $row['id_appointments'];
            $this->db->delete('appointment_consumables', ['id' => $id]);
            $this->recalculate_appointment_costs($appointment_id);
        }
    }

    /**
     * Recalculate total consumables cost and gross profit for an appointment.
     */
    public function recalculate_appointment_costs(int $appointment_id): array
    {
        $appt = $this->db->get_where('appointments', ['id' => $appointment_id])->row_array();
        if (!$appt) {
            return ['consumables_cost' => 0.0, 'gross_profit' => 0.0];
        }

        $sum_row = $this->db
            ->select('SUM(total_cost) as total_consumables_cost')
            ->from('appointment_consumables')
            ->where('id_appointments', $appointment_id)
            ->get()
            ->row_array();

        $consumables_cost = round((float) ($sum_row['total_consumables_cost'] ?? 0.0), 2);

        // Get service price or custom price
        $service_price = 0.0;
        if (!empty($appt['id_services'])) {
            $svc = $this->db->get_where('services', ['id' => $appt['id_services']])->row_array();
            $service_price = (float) ($svc['price'] ?? 0.0);
        }

        $gross_profit = round($service_price - $consumables_cost, 2);

        $this->db->update('appointments', [
            'consumables_cost' => $consumables_cost,
            'gross_profit' => $gross_profit,
        ], ['id' => $appointment_id]);

        return [
            'consumables_cost' => $consumables_cost,
            'gross_profit' => $gross_profit,
        ];
    }

    // =========================================================================
    // ATOMIC STOCK DEDUCTION & REVERT LOGIC
    // =========================================================================

    /**
     * Deduct stock for an appointment session.
     * Guaranteed idempotent: If consumables have already been deducted for this appointment, it safely exits.
     */
    public function deduct_for_appointment(int $appointment_id): void
    {
        $appt = $this->db->get_where('appointments', ['id' => $appointment_id])->row_array();
        if (!$appt) {
            return;
        }

        // Idempotency check: already deducted?
        if (!empty($appt['consumables_deducted'])) {
            return;
        }

        // Fetch consumables for this appointment (with auto-populate from recipe)
        $items = $this->get_appointment_consumables($appointment_id, true);
        if (empty($items)) {
            // Even if no consumables, mark as processed so we don't re-run
            $this->db->update('appointments', ['consumables_deducted' => 1], ['id' => $appointment_id]);
            return;
        }

        $this->db->trans_start();
        $now = date('Y-m-d H:i:s');
        $total_cost = 0.0;

        foreach ($items as $item) {
            $product_id = (int) $item['id_products'];
            $qty = (float) $item['quantity_used'];
            $unit_cost = (float) $item['unit_cost'];
            $line_cost = (float) $item['total_cost'];
            $total_cost += $line_cost;

            $notes = $item['is_extra']
                ? 'Seans Ekstra Sarfiyatı (Randevu #' . $appointment_id . ')'
                : 'Seans Standart Sarfiyatı (Randevu #' . $appointment_id . ')';

            // Insert stock movement
            $movement_data = [
                'id_products' => $product_id,
                'movement_type' => 'service_consumption',
                'quantity' => -$qty,
                'quantity_delta' => (int) (-$qty),
                'unit_cost' => $unit_cost,
                'unit_price' => $unit_cost,
                'id_appointments' => $appointment_id,
                'notes' => $notes,
                'created_at' => $now,
            ];

            // Filter columns to what actually exists in stock_movements
            $fields = $this->db->list_fields('stock_movements');
            $insert_fields = [];
            foreach ($movement_data as $k => $v) {
                if (in_array($k, $fields, true)) {
                    $insert_fields[$k] = $v;
                }
            }
            $this->db->insert('stock_movements', $insert_fields);

            // Deduct product stock quantity
            $this->db->set('stock_quantity', 'stock_quantity - ' . $qty, false);
            $this->db->where('id', $product_id);
            $this->db->update('products');
        }

        // Calculate service price and gross profit
        $service_price = 0.0;
        if (!empty($appt['id_services'])) {
            $svc = $this->db->get_where('services', ['id' => $appt['id_services']])->row_array();
            $service_price = (float) ($svc['price'] ?? 0.0);
        }
        $gross_profit = round($service_price - $total_cost, 2);

        // Mark appointment as deducted
        $this->db->update('appointments', [
            'consumables_deducted' => 1,
            'consumables_cost' => round($total_cost, 2),
            'gross_profit' => $gross_profit,
        ], ['id' => $appointment_id]);

        $this->db->trans_complete();
    }

    /**
     * Revert stock deduction if an appointment is cancelled or refunded.
     */
    public function revert_for_appointment(int $appointment_id): void
    {
        $appt = $this->db->get_where('appointments', ['id' => $appointment_id])->row_array();
        if (!$appt || empty($appt['consumables_deducted'])) {
            return;
        }

        $items = $this->get_appointment_consumables($appointment_id, false);
        if (empty($items)) {
            $this->db->update('appointments', ['consumables_deducted' => 0], ['id' => $appointment_id]);
            return;
        }

        $this->db->trans_start();
        $now = date('Y-m-d H:i:s');

        foreach ($items as $item) {
            $product_id = (int) $item['id_products'];
            $qty = (float) $item['quantity_used'];
            $unit_cost = (float) $item['unit_cost'];

            // Insert reversal movement
            $movement_data = [
                'id_products' => $product_id,
                'movement_type' => 'adjustment',
                'quantity' => $qty,
                'quantity_delta' => (int) $qty,
                'unit_cost' => $unit_cost,
                'unit_price' => $unit_cost,
                'id_appointments' => $appointment_id,
                'notes' => 'İptal Edilen Randevu Sarfiyat İadesi (Randevu #' . $appointment_id . ')',
                'created_at' => $now,
            ];

            $fields = $this->db->list_fields('stock_movements');
            $insert_fields = [];
            foreach ($movement_data as $k => $v) {
                if (in_array($k, $fields, true)) {
                    $insert_fields[$k] = $v;
                }
            }
            $this->db->insert('stock_movements', $insert_fields);

            // Restore product stock
            $this->db->set('stock_quantity', 'stock_quantity + ' . $qty, false);
            $this->db->where('id', $product_id);
            $this->db->update('products');
        }

        $this->db->update('appointments', [
            'consumables_deducted' => 0,
        ], ['id' => $appointment_id]);

        $this->db->trans_complete();
    }

    /**
     * Automatically deduct stock consumables upon service completion (backwards compatible with Adisyons).
     */
    public function deduct_for_service(int $service_id, ?int $appointment_id = null, ?int $adisyon_id = null): void
    {
        if ($appointment_id) {
            $this->deduct_for_appointment($appointment_id);
            return;
        }

        $recipes = $this->get_recipes_for_service($service_id);
        if (empty($recipes)) {
            return;
        }

        $this->db->trans_start();
        $now = date('Y-m-d H:i:s');

        foreach ($recipes as $rec) {
            $product_id = (int) $rec['id_products'];
            $qty = (float) $rec['quantity_used'];
            $cost = (float) ($rec['cost'] ?? 0.00);

            $movement_data = [
                'id_products' => $product_id,
                'movement_type' => 'service_consumption',
                'quantity' => -$qty,
                'quantity_delta' => (int) (-$qty),
                'unit_cost' => $cost,
                'unit_price' => $cost,
                'id_appointments' => $appointment_id,
                'id_adisyons' => $adisyon_id,
                'notes' => 'Hizmet Sarfiyatı (Service #' . $service_id . ')',
                'created_at' => $now,
            ];

            $fields = $this->db->list_fields('stock_movements');
            $insert_fields = [];
            foreach ($movement_data as $k => $v) {
                if (in_array($k, $fields, true)) {
                    $insert_fields[$k] = $v;
                }
            }
            $this->db->insert('stock_movements', $insert_fields);

            // Deduct product stock quantity
            $this->db->set('stock_quantity', 'stock_quantity - ' . $qty, false);
            $this->db->where('id', $product_id);
            $this->db->update('products');
        }

        $this->db->trans_complete();
    }

    /**
     * Deduct stock for direct retail product sale.
     */
    public function deduct_for_product_sale(int $product_id, float $quantity, ?int $adisyon_id = null): void
    {
        $product = $this->db->get_where('products', ['id' => $product_id])->row_array();
        if (!$product) {
            return;
        }

        $this->db->trans_start();
        $cost = (float) ($product['cost_price'] ?? 0.00);

        $movement_data = [
            'id_products' => $product_id,
            'movement_type' => 'sale',
            'quantity' => -$quantity,
            'quantity_delta' => (int) (-$quantity),
            'unit_cost' => $cost,
            'unit_price' => (float) ($product['sale_price'] ?? 0.00),
            'id_adisyons' => $adisyon_id,
            'notes' => 'Ürün Satışı',
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $fields = $this->db->list_fields('stock_movements');
        $insert_fields = [];
        foreach ($movement_data as $k => $v) {
            if (in_array($k, $fields, true)) {
                $insert_fields[$k] = $v;
            }
        }
        $this->db->insert('stock_movements', $insert_fields);

        $this->db->set('stock_quantity', 'stock_quantity - ' . $quantity, false);
        $this->db->where('id', $product_id);
        $this->db->update('products');

        $this->db->trans_complete();
    }

    /**
     * Get stock movements log with filtering.
     */
    public function get_stock_movements(?int $product_id = null, int $limit = 100): array
    {
        $this->db
            ->select('sm.*, p.name as product_name, p.sku, COALESCE(p.unit, "adet") as product_unit')
            ->from('stock_movements sm')
            ->join('products p', 'p.id = sm.id_products', 'left');

        if ($product_id) {
            $this->db->where('sm.id_products', $product_id);
        }

        return $this->db
            ->order_by('sm.created_at DESC, sm.id DESC')
            ->limit($limit)
            ->get()
            ->result_array();
    }

    /**
     * Get low stock alert products.
     */
    public function get_critical_stock(): array
    {
        return $this->db
            ->select('*')
            ->from('products')
            ->where('stock_quantity <= low_stock_threshold', null, false)
            ->where('is_active', 1)
            ->order_by('stock_quantity ASC')
            ->get()
            ->result_array();
    }

    // =========================================================================
    // COMPREHENSIVE CONSUMABLES & MARGIN REPORTING
    // =========================================================================

    /**
     * Generate detailed consumables and profit margin report across appointments.
     */
    public function get_consumables_report(?string $start_date = null, ?string $end_date = null, ?int $service_id = null): array
    {
        $start_date = $start_date ?: date('Y-m-01');
        $end_date = $end_date ?: date('Y-m-d 23:59:59');

        // 1. Material consumption summary
        $this->db
            ->select('ac.id_products, p.name as product_name, p.sku, COALESCE(p.unit, ac.unit, "adet") as unit, SUM(ac.quantity_used) as total_quantity, SUM(ac.total_cost) as total_spend, COUNT(DISTINCT ac.id_appointments) as session_count')
            ->from('appointment_consumables ac')
            ->join('appointments a', 'a.id = ac.id_appointments', 'inner')
            ->join('products p', 'p.id = ac.id_products', 'left')
            ->where('a.start_datetime >=', $start_date)
            ->where('a.start_datetime <=', $end_date);

        if ($service_id) {
            $this->db->where('a.id_services', $service_id);
        }

        $items = $this->db
            ->group_by('ac.id_products, p.name, p.sku, unit')
            ->order_by('total_spend DESC')
            ->get()
            ->result_array();

        $total_consumable_spend = 0.0;
        foreach ($items as &$it) {
            $it['total_quantity'] = (float) $it['total_quantity'];
            $it['total_spend'] = (float) $it['total_spend'];
            $it['session_count'] = (int) $it['session_count'];
            $total_consumable_spend += $it['total_spend'];
        }

        // 2. Service profitability summary
        $this->db
            ->select('s.id as service_id, s.name as service_name, s.price as service_price, COUNT(a.id) as total_appointments, SUM(COALESCE(a.consumables_cost, 0)) as total_consumables_cost, SUM(COALESCE(s.price, 0)) as total_revenue, SUM(COALESCE(a.gross_profit, s.price)) as total_gross_profit')
            ->from('appointments a')
            ->join('services s', 's.id = a.id_services', 'inner')
            ->where('a.start_datetime >=', $start_date)
            ->where('a.start_datetime <=', $end_date)
            ->where_not_in('a.status', ['Cancelled', 'İptal']);

        if ($service_id) {
            $this->db->where('a.id_services', $service_id);
        }

        $services_summary = $this->db
            ->group_by('s.id, s.name, s.price')
            ->order_by('total_revenue DESC')
            ->get()
            ->result_array();

        $total_revenue = 0.0;
        $total_profit = 0.0;
        foreach ($services_summary as &$svc) {
            $svc['total_appointments'] = (int) $svc['total_appointments'];
            $svc['total_revenue'] = (float) $svc['total_revenue'];
            $svc['total_consumables_cost'] = (float) $svc['total_consumables_cost'];
            $svc['total_gross_profit'] = (float) $svc['total_gross_profit'];
            $svc['margin_percent'] = $svc['total_revenue'] > 0
                ? round(($svc['total_gross_profit'] / $svc['total_revenue']) * 100, 1)
                : 0.0;

            $total_revenue += $svc['total_revenue'];
            $total_profit += $svc['total_gross_profit'];
        }

        $overall_margin = $total_revenue > 0 ? round(($total_profit / $total_revenue) * 100, 1) : 0.0;

        return [
            'period' => [
                'start_date' => $start_date,
                'end_date' => $end_date,
            ],
            'total_consumable_spend' => round($total_consumable_spend, 2),
            'total_revenue' => round($total_revenue, 2),
            'total_gross_profit' => round($total_profit, 2),
            'overall_margin_percent' => $overall_margin,
            'consumed_products' => $items,
            'service_profitability' => $services_summary,
            'critical_stock' => $this->get_critical_stock(),
        ];
    }
}
