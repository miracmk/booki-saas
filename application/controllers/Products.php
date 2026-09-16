<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Products controller.
 *
 * Handles CRUD operations for inventory/product management.
 * Access: admin/secretary only.
 * ---------------------------------------------------------------------------- */

class Products extends EA_Controller
{
    public array $allowed_product_fields = [
        'id',
        'name',
        'sku',
        'sale_price',
        'cost_price',
        'stock_quantity',
        'low_stock_threshold',
        'is_active',
    ];

    /**
     * Products constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('products_model');
        $this->load->model('roles_model');

        $this->load->library('accounts');
    }

    /**
     * Render the products management page.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('products')]);

        $user_id = session('user_id');

        if (cannot('view', PRIV_PRODUCTS)) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        $role_slug = session('role_slug');

        html_vars([
            'page_title' => 'Ürünler',
            'active_menu' => PRIV_PRODUCTS,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
        ]);

        script_vars([
            'user_id' => $user_id,
            'role_slug' => $role_slug,
        ]);

        $this->load->view('pages/products');
    }

    /**
     * Filter products by the provided keyword.
     */
    public function search(): void
    {
        try {
            method('post');

            if (cannot('view', PRIV_PRODUCTS)) {
                abort(403, 'Forbidden');
            }

            check('keyword', 'string|null');
            check('limit', 'numeric|null');
            check('offset', 'numeric|null');

            $keyword = request('keyword', '');
            $limit = request('limit', 1000);
            $offset = (int) request('offset', '0');

            $products = $this->products_model->search($keyword, $limit, $offset);

            json_response($products);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Store a new product.
     */
    public function store(): void
    {
        try {
            method('post');

            if (cannot('add', PRIV_PRODUCTS)) {
                abort(403, 'Forbidden');
            }

            check('product', 'array');

            $product = request('product');
            unset($product['id']);

            $this->products_model->only($product, $this->allowed_product_fields);

            $product_id = $this->products_model->save($product);

            // Audit log
            audit_log('product.create', 'product', $product_id, [
                'name' => $product['name'],
                'sku' => $product['sku'] ?? null,
            ]);

            json_response([
                'success' => true,
                'id' => $product_id,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Find a product.
     */
    public function find(): void
    {
        try {
            method('post');

            if (cannot('view', PRIV_PRODUCTS)) {
                abort(403, 'Forbidden');
            }

            check('product_id', 'numeric');

            $product_id = request('product_id');

            if (empty($product_id) || !filter_var($product_id, FILTER_VALIDATE_INT) || $product_id <= 0) {
                throw new InvalidArgumentException('Invalid product ID provided.');
            }

            $product = $this->products_model->find((int) $product_id);

            // Get recent movements for this product
            $product['recent_movements'] = $this->products_model->get_movements((int) $product_id, 10);

            json_response($product);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Update a product.
     */
    public function update(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_PRODUCTS)) {
                abort(403, 'Forbidden');
            }

            check('product', 'array');

            $product = request('product');

            $this->products_model->only($product, $this->allowed_product_fields);

            $product_id = $this->products_model->save($product);

            // Audit log
            audit_log('product.update', 'product', $product_id, [
                'name' => $product['name'],
            ]);

            json_response([
                'success' => true,
                'id' => $product_id,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Remove a product.
     */
    public function destroy(): void
    {
        try {
            method('post');

            if (cannot('delete', PRIV_PRODUCTS)) {
                abort(403, 'Forbidden');
            }

            check('product_id', 'numeric');

            $product_id = request('product_id');

            if (empty($product_id) || !filter_var($product_id, FILTER_VALIDATE_INT) || $product_id <= 0) {
                throw new InvalidArgumentException('Invalid product ID provided.');
            }

            // Audit log before deletion
            $product = $this->products_model->find((int) $product_id);
            audit_log('product.delete', 'product', $product_id, [
                'name' => $product['name'],
            ]);

            $this->products_model->delete((int) $product_id);

            json_response([
                'success' => true,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Sell a product (manual stock adjustment for appointment).
     */
    public function sell(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_PRODUCTS)) {
                abort(403, 'Forbidden');
            }

            check('product_id', 'numeric');
            check('quantity', 'numeric');
            check('appointment_id', 'numeric');
            check('customer_id', 'numeric');

            $product_id = (int) request('product_id');
            $quantity = (int) request('quantity');
            $appointment_id = (int) request('appointment_id');
            $customer_id = (int) request('customer_id');

            $this->products_model->sell(
                $product_id,
                $quantity,
                $appointment_id,
                $customer_id,
                (int) session('user_id'),
            );

            // Audit log
            audit_log('product.sell', 'product', $product_id, [
                'quantity' => $quantity,
                'appointment_id' => $appointment_id,
            ]);

            json_response([
                'success' => true,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
