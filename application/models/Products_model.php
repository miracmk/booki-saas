<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Products Model
 *
 * Handles database operations for inventory/product management.
 * ---------------------------------------------------------------------------- */

class Products_model extends EA_Model
{
    protected array $casts = [
        'id' => 'integer',
        'sale_price' => 'float',
        'cost_price' => 'float',
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Save (insert or update) a product.
     *
     * @param array $product Associative array with the product data.
     * @return int Returns the product ID.
     * @throws InvalidArgumentException
     */
    public function save(array $product): int
    {
        $this->validate($product);

        if (empty($product['id'])) {
            $product_id = $this->insert($product);
        } else {
            $product_id = $this->update($product);
        }

        return $product_id;
    }

    /**
     * Validate the product data.
     *
     * @param array $product Associative array with the product data.
     * @throws InvalidArgumentException
     */
    public function validate(array $product): void
    {
        if (!empty($product['id'])) {
            $count = $this->db->get_where('products', ['id' => $product['id']])->num_rows();

            if (!$count) {
                throw new InvalidArgumentException(
                    'The provided product ID does not exist in the database: ' . $product['id'],
                );
            }
        }

        if (empty($product['name']) || !isset($product['sale_price'])) {
            throw new InvalidArgumentException('Not all required fields are provided: ' . print_r($product, true));
        }

        if ((float) $product['sale_price'] < 0) {
            throw new InvalidArgumentException('Sale price cannot be negative.');
        }
    }

    /**
     * Insert a new product into the database.
     *
     * @param array $product Associative array with the product data.
     * @return int Returns the product ID.
     * @throws RuntimeException
     */
    protected function insert(array $product): int
    {
        $product['created_at'] = date('Y-m-d H:i:s');

        if (!$this->db->insert('products', $product)) {
            throw new RuntimeException('Could not insert product.');
        }

        return $this->db->insert_id();
    }

    /**
     * Update an existing product.
     *
     * @param array $product Associative array with the product data.
     * @return int Returns the product ID.
     * @throws RuntimeException
     */
    protected function update(array $product): int
    {
        $product['updated_at'] = date('Y-m-d H:i:s');

        if (!$this->db->update('products', $product, ['id' => $product['id']])) {
            throw new RuntimeException('Could not update product.');
        }

        return $product['id'];
    }

    /**
     * Remove an existing product from the database.
     *
     * @param int $product_id Product ID.
     */
    public function delete(int $product_id): void
    {
        $this->db->delete('products', ['id' => $product_id]);
    }

    /**
     * Get a specific product from the database.
     *
     * @param int $product_id The ID of the record to be returned.
     * @return array Returns an array with the product data.
     * @throws InvalidArgumentException
     */
    public function find(int $product_id): array
    {
        $product = $this->db->get_where('products', ['id' => $product_id])->row_array();

        if (!$product) {
            throw new InvalidArgumentException('The provided product ID was not found in the database: ' . $product_id);
        }

        $this->cast($product);

        return $product;
    }

    /**
     * Get all products that match the provided criteria.
     *
     * @param array|string|null $where Where conditions
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @param string|null $order_by Order by.
     * @return array Returns an array of products.
     */
    public function get(
        array|string|null $where = null,
        ?int $limit = null,
        ?int $offset = null,
        ?string $order_by = null,
    ): array {
        if ($where !== null) {
            $this->db->where($where);
        }

        if ($order_by !== null) {
            $this->db->order_by($this->quote_order_by($order_by));
        }

        $products = $this->db->get('products', $limit, $offset)->result_array();

        foreach ($products as &$product) {
            $this->cast($product);
        }

        return $products;
    }

    /**
     * Search products by keyword.
     *
     * @param string $keyword Search keyword.
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @return array Returns an array of products.
     */
    public function search(string $keyword, ?int $limit = null, ?int $offset = null): array
    {
        $products = $this->db
            ->select()
            ->from('products')
            ->group_start()
            ->like('name', $keyword)
            ->or_like('sku', $keyword)
            ->group_end()
            ->where('is_active', true)
            ->limit($limit)
            ->offset($offset)
            ->order_by('name ASC')
            ->get()
            ->result_array();

        foreach ($products as &$product) {
            $this->cast($product);
        }

        return $products;
    }

    /**
     * Get all active products as options for dropdowns.
     *
     * @return array Returns an array of options with 'value' and 'label' keys.
     */
    public function to_options(): array
    {
        $products = $this->db
            ->select('id, name, stock_quantity')
            ->from('products')
            ->where('is_active', true)
            ->order_by('name ASC')
            ->get()
            ->result_array();

        $options = [];

        foreach ($products as $product) {
            $options[] = [
                'value' => (int) $product['id'],
                'label' => $product['name'],
                'stock' => (int) $product['stock_quantity'],
            ];
        }

        return $options;
    }

    /**
     * Adjust stock for a product (with movement tracking).
     *
     * @param int $product_id Product ID.
     * @param int $delta Quantity delta (positive or negative).
     * @param string $movement_type sale, restock, adjustment, waste.
     * @param int|null $appointment_id Related appointment ID.
     * @param int|null $customer_id Related customer ID.
     * @param float|null $unit_price Price at time of transaction.
     * @param int $recorded_by User ID who recorded this.
     * @throws RuntimeException
     */
    public function adjust_stock(
        int $product_id,
        int $delta,
        string $movement_type,
        ?int $appointment_id = null,
        ?int $customer_id = null,
        ?float $unit_price = null,
        int $recorded_by = 0,
    ): void {
        $this->db->trans_start();

        try {
            // Validate product exists
            $product = $this->find($product_id);

            // Update stock quantity
            $new_quantity = (int) $product['stock_quantity'] + $delta;

            $this->db->update('products', ['stock_quantity' => $new_quantity, 'updated_at' => date('Y-m-d H:i:s')], ['id' => $product_id]);

            // Record the movement
            $this->db->insert('stock_movements', [
                'id_products' => $product_id,
                'movement_type' => $movement_type,
                'quantity_delta' => $delta,
                'id_appointments' => $appointment_id,
                'id_users_customer' => $customer_id,
                'unit_price' => $unit_price,
                'recorded_by' => $recorded_by ?: null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $this->db->trans_complete();
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            throw new RuntimeException('Could not adjust stock: ' . $e->getMessage());
        }
    }

    /**
     * Sell a product (deduct from stock).
     *
     * @param int $product_id Product ID.
     * @param int $quantity Quantity to sell.
     * @param int $appointment_id Related appointment ID.
     * @param int $customer_id Customer ID.
     * @param int $recorded_by User ID who recorded this.
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function sell(int $product_id, int $quantity, int $appointment_id, int $customer_id, int $recorded_by): void
    {
        $product = $this->find($product_id);

        if ((int) $product['stock_quantity'] < $quantity) {
            throw new InvalidArgumentException(
                'Insufficient stock for product "' . $product['name'] . '". Available: ' . $product['stock_quantity'] . ', Requested: ' . $quantity,
            );
        }

        $this->adjust_stock(
            $product_id,
            -$quantity,
            'sale',
            $appointment_id,
            $customer_id,
            (float) $product['sale_price'],
            $recorded_by,
        );
    }

    /**
     * Get all products below the low stock threshold.
     *
     * @return array Returns an array of low-stock products.
     */
    public function get_low_stock(): array
    {
        $products = $this->db
            ->select()
            ->from('products')
            ->where('is_active', true)
            ->where('stock_quantity <=', $this->db->raw('low_stock_threshold'))
            ->order_by('stock_quantity ASC')
            ->get()
            ->result_array();

        foreach ($products as &$product) {
            $this->cast($product);
        }

        return $products;
    }

    /**
     * Get stock movements for a product (audit trail).
     *
     * @param int $product_id Product ID.
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @return array Returns an array of stock movements.
     */
    public function get_movements(int $product_id, ?int $limit = null, ?int $offset = null): array
    {
        $movements = $this->db
            ->select()
            ->from('stock_movements')
            ->where('id_products', $product_id)
            ->order_by('created_at DESC')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->result_array();

        foreach ($movements as &$movement) {
            $this->cast($movement);
        }

        return $movements;
    }
}
