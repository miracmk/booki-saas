<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Orders (POS) Model
 *
 * A point-of-sale style basket (orders + order_items), checked out through
 * the existing payment gateway abstraction. checkout() is a NEW consumer of
 * Payment_gateway_factory and Payment_transactions_model - it adds no new
 * methods to either and never changes an existing method's signature (see
 * migration 121: payment_transactions gained a widened `type` enum and two
 * new nullable columns, nothing else).
 * ---------------------------------------------------------------------------- */

class Orders_model extends EA_Model
{
    protected array $casts = [
        'id' => 'integer',
        'id_users_customer' => 'integer',
        'subtotal' => 'float',
        'tax_total' => 'float',
        'total' => 'float',
        'sold_by' => 'integer',
    ];

    /**
     * Create a new order with its line items in a single transaction. Subtotal/total are computed
     * from the items, never trusted from the caller.
     *
     * @param array $order ['id_users_customer'?, 'currency'?, 'sold_by'?]
     * @param array $items Each: ['item_type', 'id_reference'?, 'description', 'quantity'?, 'unit_price']
     * @return int The new order ID.
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function create_with_items(array $order, array $items): int
    {
        if (empty($items)) {
            throw new InvalidArgumentException('Sipariş en az bir kalem içermelidir.');
        }

        $subtotal = 0;

        foreach ($items as $item) {
            if (empty($item['description']) || !isset($item['unit_price'])) {
                throw new InvalidArgumentException('Her kalem açıklama ve birim fiyat içermelidir.');
            }

            $quantity = (float) ($item['quantity'] ?? 1);
            $subtotal += $quantity * (float) $item['unit_price'];
        }

        $subtotal = round($subtotal, 2);

        $this->db->trans_start();

        try {
            $order_id = $this->db->insert('orders', [
                'id_users_customer' => $order['id_users_customer'] ?? null,
                'status' => 'open',
                'subtotal' => $subtotal,
                'tax_total' => 0,
                'total' => $subtotal,
                'currency' => $order['currency'] ?? 'TRY',
                'sold_by' => $order['sold_by'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
            ]) ? $this->db->insert_id() : null;

            if (!$order_id) {
                throw new RuntimeException('Could not insert order.');
            }

            foreach ($items as $item) {
                $quantity = (float) ($item['quantity'] ?? 1);
                $unit_price = (float) $item['unit_price'];

                $this->db->insert('order_items', [
                    'id_orders' => $order_id,
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
            throw new RuntimeException('Could not create order: ' . $e->getMessage());
        }

        return $order_id;
    }

    /**
     * Find an order with its line items.
     *
     * @param int $order_id Order ID.
     * @return array ['order' => array, 'items' => array]
     * @throws InvalidArgumentException
     */
    public function find_with_items(int $order_id): array
    {
        $order = $this->db->get_where('orders', ['id' => $order_id])->row_array();

        if (!$order) {
            throw new InvalidArgumentException('The provided order ID was not found in the database: ' . $order_id);
        }

        $this->cast($order);

        $items = $this->db->get_where('order_items', ['id_orders' => $order_id])->result_array();

        return ['order' => $order, 'items' => $items];
    }

    /**
     * Get all orders (POS register / admin page), joined with customer names.
     *
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @return array Returns an array of orders.
     */
    public function get_all(?int $limit = null, ?int $offset = null): array
    {
        $orders = $this->db
            ->select('o.*, u.first_name as customer_first_name, u.last_name as customer_last_name')
            ->from('orders o')
            ->join('users u', 'u.id = o.id_users_customer', 'left')
            ->order_by('o.created_at', 'DESC')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->result_array();

        foreach ($orders as &$order) {
            $this->cast($order);
        }

        return $orders;
    }

    /**
     * Void an order (before payment).
     *
     * @param int $order_id Order ID.
     */
    public function void(int $order_id): void
    {
        $this->db->update(
            'orders',
            ['status' => 'cancelled', 'updated_at' => date('Y-m-d H:i:s')],
            ['id' => $order_id],
        );
    }

    /**
     * Update just the status column (used by Payment_webhooks.php when a linked
     * payment_transactions row's status changes - see that controller's new conditional branch).
     * A separate, narrow method rather than routing through save()/validate(), so this can be
     * called from a webhook context without pulling in the full order-creation validation.
     *
     * @param int $order_id Order ID.
     * @param string $status New status: open, paid, cancelled, refunded.
     */
    public function update_status(int $order_id, string $status): void
    {
        $this->db->update(
            'orders',
            ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')],
            ['id' => $order_id],
        );
    }

    /**
     * Check out an order: create a payment intent via the existing gateway abstraction and record
     * a payment_transactions row (type='pos_sale'). Consumes Payment_gateway_factory and
     * Payment_transactions_model::save() exactly as Appointment_booking_service/Booking.php already
     * do for deposits - no new methods added to either, no signatures changed.
     *
     * @param int $order_id Order ID.
     * @return array ['transaction_id', 'intent_id', 'checkout_form', 'gateway']
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function checkout(int $order_id): array
    {
        $this->load->model('payment_settings_model');
        $this->load->model('payment_transactions_model');

        $order = $this->db->get_where('orders', ['id' => $order_id])->row_array();

        if (!$order) {
            throw new InvalidArgumentException('The provided order ID was not found in the database: ' . $order_id);
        }

        if ($order['status'] !== 'open') {
            throw new InvalidArgumentException('Only an open order can be checked out.');
        }

        $payment_settings = $this->payment_settings_model->get_settings();

        if (empty($payment_settings['active_gateway']) || $payment_settings['active_gateway'] === 'none') {
            throw new RuntimeException('Aktif bir ödeme sağlayıcısı yapılandırılmamış.');
        }

        $payment_gateway = Payment_gateway_factory::make($payment_settings);

        if ($payment_gateway === null) {
            throw new RuntimeException('Ödeme sağlayıcısı başlatılamadı.');
        }

        $intent_response = $payment_gateway->create_payment_intent(
            (float) $order['total'],
            $order['currency'],
            [
                'order_id' => $order_id,
                'customer_id' => $order['id_users_customer'],
            ],
        );

        $transaction_id = $this->payment_transactions_model->save([
            'id_orders' => $order_id,
            'id_users' => $order['id_users_customer'],
            'gateway' => $payment_settings['active_gateway'],
            'intent_id' => $intent_response['intent_id'] ?? null,
            'amount' => (float) $order['total'],
            'currency' => $order['currency'],
            'status' => 'pending',
            'type' => 'pos_sale',
            'raw_response' => $intent_response['raw_response'] ?? null,
        ]);

        return [
            'transaction_id' => $transaction_id,
            'intent_id' => $intent_response['intent_id'] ?? null,
            'checkout_form' => $intent_response['checkout_form'] ?? null,
            'gateway' => $payment_settings['active_gateway'],
        ];
    }

    /**
     * Faz 30 (KVKK export) - a customer's POS orders. id_users_customer is nullable (walk-in sales),
     * so this deliberately never matches walk-in orders (which have no customer to export to).
     *
     * @param int $customer_id
     * @return array
     */
    public function get_for_customer(int $customer_id): array
    {
        $orders = $this->db->where('id_users_customer', $customer_id)
            ->order_by('created_at', 'DESC')
            ->get('orders')->result_array();

        foreach ($orders as &$order) {
            $this->cast($order);
        }

        return $orders;
    }
}
