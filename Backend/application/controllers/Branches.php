<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Branches controller
 *
 * Handles branch (physical location) related operations. Provides CRUD
 * functionality for managing branches in a single tenant. Restricted to
 * admin-level access.
 * ---------------------------------------------------------------------------- */

class Branches extends App_Controller
{
    public array $allowed_branch_fields = ['id', 'name', 'address', 'phone', 'is_default', 'is_active'];

    /**
     * Branches constructor.
     */
    public function __construct()
    {
        parent::__construct();

        require_plan_feature(PRIV_BRANCHES);

        $this->load->model('branches_model');
        $this->load->model('roles_model');

        $this->load->library('accounts');
    }

    /**
     * Render the backend branches management page.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('branches')]);

        $user_id = session('user_id');

        if (cannot('view', PRIV_BRANCHES)) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        $role_slug = session('role_slug');

        $branches = $this->branches_model->get(null, null, null, 'is_default DESC, name ASC');

        html_vars([
            'page_title' => lang('branches') ?: 'Şubeler & Lokasyonlar',
            'active_menu' => PRIV_BRANCHES,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
            'branches' => $branches,
        ]);

        script_vars([
            'user_id' => $user_id,
            'role_slug' => $role_slug,
        ]);

        $this->load->view('pages/branches');
    }

    /**
     * Filter branches by the provided keyword.
     */
    public function search(): void
    {
        try {
            method('post');

            if (cannot('view', PRIV_BRANCHES)) {
                abort(403, 'Forbidden');
            }

            check('keyword', 'string|null');
            check('limit', 'numeric|null');
            check('offset', 'numeric|null');

            $keyword = request('keyword', '');

            $limit = request('limit', 1000);

            $offset = (int) request('offset', '0');

            $branches = $this->branches_model->search($keyword, $limit, $offset);

            json_response($branches);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Store a new branch.
     */
    public function store(): void
    {
        try {
            method('post');

            if (cannot('add', PRIV_BRANCHES)) {
                abort(403, 'Forbidden');
            }

            check('branch', 'array');

            $branch = request('branch');

            unset($branch['id']);

            $this->branches_model->only($branch, $this->allowed_branch_fields);

            $branch_id = $this->branches_model->save($branch);

            json_response([
                'success' => true,
                'id' => $branch_id,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Find a branch.
     */
    public function find(): void
    {
        try {
            method('post');

            if (cannot('view', PRIV_BRANCHES)) {
                abort(403, 'Forbidden');
            }

            check('branch_id', 'numeric');

            $branch_id = request('branch_id');

            if (empty($branch_id) || !filter_var($branch_id, FILTER_VALIDATE_INT) || $branch_id <= 0) {
                throw new InvalidArgumentException('Invalid branch ID provided.');
            }

            $branch = $this->branches_model->find((int) $branch_id);

            json_response($branch);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Update a branch.
     */
    public function update(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_BRANCHES)) {
                abort(403, 'Forbidden');
            }

            check('branch', 'array');

            $branch = request('branch');

            $this->branches_model->only($branch, $this->allowed_branch_fields);

            $branch_id = $this->branches_model->save($branch);

            json_response([
                'success' => true,
                'id' => $branch_id,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Remove a branch.
     */
    public function destroy(): void
    {
        try {
            method('post');

            if (cannot('delete', PRIV_BRANCHES)) {
                abort(403, 'Forbidden');
            }

            check('branch_id', 'numeric');

            $branch_id = request('branch_id');

            if (empty($branch_id) || !filter_var($branch_id, FILTER_VALIDATE_INT) || $branch_id <= 0) {
                throw new InvalidArgumentException('Invalid branch ID provided.');
            }

            $this->branches_model->delete((int) $branch_id);

            json_response([
                'success' => true,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
