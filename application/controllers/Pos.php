<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - POS controller (Dalga 1, 2026-08-28).
 *
 * Handles order creation and checkout via the existing payment gateway
 * abstraction. Access: admin/secretary/provider (register access).
 * ---------------------------------------------------------------------------- */

class Pos extends EA_Controller
{
    /**
     * Pos constructor.
     */
    public function __construct()
    {
        parent::__construct();

        require_plan_feature(PRIV_POS);

        $this->load->model('orders_model');
        $this->load->model('customers_model');
        $this->load->model('products_model');
        $this->load->model('roles_model');

        $this->load->library('accounts');
    }

    /**
     * Render the POS register page.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('pos')]);

        $user_id = session('user_id');

        if (cannot('view', PRIV_POS)) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        $role_slug = session('role_slug');

        $this->load->model('payment_settings_model');
        $payment_settings = $this->payment_settings_model->get_settings();
        $active_gateway = $payment_settings['active_gateway'] ?? 'none';

        $gateway_names = [
            'iyzico' => 'İyzico Sanal POS',
            'stripe' => 'Stripe Payments',
            'odeal' => 'ÖdeAl Sanal POS',
            'garanti' => 'Garanti Sanal POS (VPAS)',
            'enpara' => 'Enpara Sanal POS (VPAS)',
            'paytr' => 'PayTR Sanal POS',
            'none' => 'Tanımsız / Manuel',
        ];

        html_vars([
            'page_title' => 'Satış Noktası (POS)',
            'active_menu' => PRIV_POS,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
            'active_gateway' => $active_gateway,
            'active_gateway_name' => $gateway_names[$active_gateway] ?? $active_gateway,
        ]);

        script_vars([
            'user_id' => $user_id,
            'role_slug' => $role_slug,
            'customers' => $this->customers_model->get(),
            'products' => $this->products_model->get(),
            'active_gateway' => $active_gateway,
            'active_gateway_name' => $gateway_names[$active_gateway] ?? $active_gateway,
            'supported_gateways' => $gateway_names,
        ]);

        $this->load->view('pages/pos', [
            'active_gateway' => $active_gateway,
            'active_gateway_name' => $gateway_names[$active_gateway] ?? $active_gateway,
        ]);
    }

    /**
     * List orders.
     */
    public function search(): void
    {
        try {
            method('post');

            if (cannot('view', PRIV_POS)) {
                abort(403, 'Forbidden');
            }

            check('limit', 'numeric|null');
            check('offset', 'numeric|null');

            $limit = request('limit', 1000);
            $offset = (int) request('offset', '0');

            $orders = $this->orders_model->get_all($limit, $offset);

            json_response($orders);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Create a new order.
     */
    public function store(): void
    {
        try {
            method('post');

            if (cannot('add', PRIV_POS)) {
                abort(403, 'Forbidden');
            }

            check('items', 'array');
            check('customer_id', 'numeric|null');

            $items = request('items');
            $customer_id = request('customer_id') ?: null;

            $order_id = $this->orders_model->create_with_items(
                ['id_users_customer' => $customer_id, 'sold_by' => session('user_id')],
                $items,
            );

            audit_log('order.create', 'order', $order_id, [
                'customer_id' => $customer_id,
                'item_count' => count($items),
            ]);

            json_response([
                'success' => true,
                'id' => $order_id,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Check out an order (create a payment intent via the active gateway).
     */
    public function checkout(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_POS)) {
                abort(403, 'Forbidden');
            }

            check('order_id', 'numeric');

            $order_id = (int) request('order_id');

            $result = $this->orders_model->checkout($order_id);

            audit_log('order.checkout', 'order', $order_id, [
                'gateway' => $result['gateway'],
            ]);

            json_response([
                'success' => true,
                'payment' => $result,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Void an order.
     */
    public function void(): void
    {
        try {
            method('post');

            if (cannot('delete', PRIV_POS)) {
                abort(403, 'Forbidden');
            }

            check('order_id', 'numeric');

            $order_id = (int) request('order_id');

            $this->orders_model->void($order_id);

            audit_log('order.void', 'order', $order_id, []);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
