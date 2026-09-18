<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Inventory Consumables & Stock Movement Engine
 * ---------------------------------------------------------------------------- */

class Inventory_consumables_model extends EA_Model
{
    /**
     * Get consumable recipe rules for a service.
     */
    public function get_recipes_for_service(int $service_id): array
    {
        return $this->db
            ->select('sc.*, p.name as product_name, p.sku, p.stock_quantity, p.cost')
            ->from('service_consumables sc')
            ->join('products p', 'p.id = sc.id_products', 'left')
            ->where('sc.id_services', $service_id)
            ->get()
            ->result_array();
    }

    /**
     * Save consumable rule for a service (e.g. Laser -> 1 pair gloves, 5ml gel, 1 sheet).
     */
    public function save_recipe(array $data): int
    {
        if (empty($data['id_services']) || empty($data['id_products']) || empty($data['quantity_used'])) {
            throw new InvalidArgumentException('Hizmet, ürün ve miktar zorunludur.');
        }

        if (empty($data['id'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('service_consumables', $data);
            return $this->db->insert_id();
        } else {
            $this->db->update('service_consumables', $data, ['id' => $data['id']]);
            return (int) $data['id'];
        }
    }

    /**
     * Delete consumable rule.
     */
    public function delete_recipe(int $recipe_id): void
    {
        $this->db->delete('service_consumables', ['id' => $recipe_id]);
    }

    /**
     * Automatically deduct stock consumables upon service completion.
     */
    public function deduct_for_service(int $service_id, ?int $appointment_id = null, ?int $adisyon_id = null): void
    {
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

            // Record stock movement
            $this->db->insert('stock_movements', [
                'id_products' => $product_id,
                'movement_type' => 'service_consumption',
                'quantity' => -$qty,
                'unit_cost' => $cost,
                'id_appointments' => $appointment_id,
                'id_adisyons' => $adisyon_id,
                'notes' => 'Hizmet Sarfiyatı (Service #' . $service_id . ')',
                'created_at' => $now,
            ]);

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
        $this->db->insert('stock_movements', [
            'id_products' => $product_id,
            'movement_type' => 'sale',
            'quantity' => -$quantity,
            'unit_cost' => (float) ($product['cost'] ?? 0.00),
            'id_adisyons' => $adisyon_id,
            'notes' => 'Ürün Satışı',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

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
            ->select('sm.*, p.name as product_name, p.sku')
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
            ->where('stock_quantity <= min_stock')
            ->where('is_active', 1)
            ->order_by('stock_quantity ASC')
            ->get()
            ->result_array();
    }
}
