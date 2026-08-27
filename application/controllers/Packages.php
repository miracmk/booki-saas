<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Packages controller.
 *
 * Handles CRUD operations for multi-session customer packages.
 * Access: admin/secretary only.
 * ---------------------------------------------------------------------------- */

class Packages extends EA_Controller
{
    public array $allowed_package_fields = [
        'id',
        'id_users_customer',
        'id_services',
        'total_sessions',
        'used_sessions',
        'unit_price',
        'purchased_at',
        'expires_at',
        'status',
        'sold_by',
    ];

    /**
     * Packages constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('packages_model');
        $this->load->model('customers_model');
        $this->load->model('services_model');
        $this->load->model('roles_model');

        $this->load->library('accounts');
    }

    /**
     * Render the packages management page.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('packages')]);

        $user_id = session('user_id');

        if (cannot('view', PRIV_PACKAGES)) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        $role_slug = session('role_slug');

        html_vars([
            'page_title' => 'Paketler',
            'active_menu' => PRIV_PACKAGES,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
        ]);

        script_vars([
            'user_id' => $user_id,
            'role_slug' => $role_slug,
            'customers' => $this->customers_model->get(),
            'services' => $this->services_model->get(),
        ]);

        $this->load->view('pages/packages');
    }

    /**
     * Filter packages by the provided keyword.
     */
    public function search(): void
    {
        try {
            method('post');

            if (cannot('view', PRIV_PACKAGES)) {
                abort(403, 'Forbidden');
            }

            check('keyword', 'string|null');
            check('limit', 'numeric|null');
            check('offset', 'numeric|null');

            $keyword = request('keyword', '');
            $limit = request('limit', 1000);
            $offset = (int) request('offset', '0');

            $packages = $this->packages_model->search($keyword, $limit, $offset);

            json_response($packages);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Store a new package.
     */
    public function store(): void
    {
        try {
            method('post');

            if (cannot('add', PRIV_PACKAGES)) {
                abort(403, 'Forbidden');
            }

            check('package', 'array');

            $package = request('package');
            unset($package['id']);

            // Set the user who created this package
            $package['sold_by'] = session('user_id');

            $this->packages_model->only($package, $this->allowed_package_fields);

            $package_id = $this->packages_model->save($package);

            // Audit log
            audit_log('package.create', 'package', $package_id, [
                'customer_id' => $package['id_users_customer'],
                'service_id' => $package['id_services'],
                'total_sessions' => $package['total_sessions'],
            ]);

            json_response([
                'success' => true,
                'id' => $package_id,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Find a package.
     */
    public function find(): void
    {
        try {
            method('post');

            if (cannot('view', PRIV_PACKAGES)) {
                abort(403, 'Forbidden');
            }

            check('package_id', 'numeric');

            $package_id = request('package_id');

            if (empty($package_id) || !filter_var($package_id, FILTER_VALIDATE_INT) || $package_id <= 0) {
                throw new InvalidArgumentException('Invalid package ID provided.');
            }

            $package = $this->packages_model->find((int) $package_id);

            json_response($package);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Update a package.
     */
    public function update(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_PACKAGES)) {
                abort(403, 'Forbidden');
            }

            check('package', 'array');

            $package = request('package');

            $this->packages_model->only($package, $this->allowed_package_fields);

            $package_id = $this->packages_model->save($package);

            // Audit log
            audit_log('package.update', 'package', $package_id, [
                'status' => $package['status'] ?? null,
            ]);

            json_response([
                'success' => true,
                'id' => $package_id,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Remove a package.
     */
    public function destroy(): void
    {
        try {
            method('post');

            if (cannot('delete', PRIV_PACKAGES)) {
                abort(403, 'Forbidden');
            }

            check('package_id', 'numeric');

            $package_id = request('package_id');

            if (empty($package_id) || !filter_var($package_id, FILTER_VALIDATE_INT) || $package_id <= 0) {
                throw new InvalidArgumentException('Invalid package ID provided.');
            }

            // Audit log before deletion
            $package = $this->packages_model->find((int) $package_id);
            audit_log('package.delete', 'package', $package_id, [
                'customer_id' => $package['id_users_customer'],
            ]);

            $this->packages_model->delete((int) $package_id);

            json_response([
                'success' => true,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
