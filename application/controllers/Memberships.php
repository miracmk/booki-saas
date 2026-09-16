<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Memberships controller (Dalga 1, 2026-08-28).
 *
 * Handles CRUD for membership plans (definitions) and customer memberships
 * (subscription instances), plus the explicit renew() action - see
 * Customer_memberships_model's class docblock for why renewal is
 * staff-recorded rather than an automatic gateway charge in this wave.
 * Access: admin/secretary only.
 * ---------------------------------------------------------------------------- */

class Memberships extends EA_Controller
{
    public array $allowed_plan_fields = [
        'id',
        'name',
        'id_services',
        'billing_period',
        'price',
        'sessions_per_period',
        'is_active',
    ];

    public array $allowed_membership_fields = [
        'id',
        'id_users_customer',
        'id_membership_plans',
        'current_period_start',
        'current_period_end',
        'auto_renew',
    ];

    /**
     * Memberships constructor.
     */
    public function __construct()
    {
        parent::__construct();

        require_plan_feature(PRIV_MEMBERSHIPS);

        $this->load->model('memberships_model');
        $this->load->model('customer_memberships_model');
        $this->load->model('customers_model');
        $this->load->model('services_model');
        $this->load->model('roles_model');

        $this->load->library('accounts');
    }

    /**
     * Render the memberships management page.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('memberships')]);

        $user_id = session('user_id');

        if (cannot('view', PRIV_MEMBERSHIPS)) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        $role_slug = session('role_slug');

        html_vars([
            'page_title' => 'Üyelikler',
            'active_menu' => PRIV_MEMBERSHIPS,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
        ]);

        script_vars([
            'user_id' => $user_id,
            'role_slug' => $role_slug,
            'customers' => $this->customers_model->get(),
            'services' => $this->services_model->get(),
            'plans' => $this->memberships_model->get(),
        ]);

        $this->load->view('pages/memberships');
    }

    /**
     * List customer memberships.
     */
    public function search(): void
    {
        try {
            method('post');

            if (cannot('view', PRIV_MEMBERSHIPS)) {
                abort(403, 'Forbidden');
            }

            check('limit', 'numeric|null');
            check('offset', 'numeric|null');

            $limit = request('limit', 1000);
            $offset = (int) request('offset', '0');

            $memberships = $this->customer_memberships_model->get_all($limit, $offset);

            json_response($memberships);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Create a new plan.
     */
    public function store_plan(): void
    {
        try {
            method('post');

            if (cannot('add', PRIV_MEMBERSHIPS)) {
                abort(403, 'Forbidden');
            }

            check('plan', 'array');

            $plan = request('plan');
            unset($plan['id']);

            $this->memberships_model->only($plan, $this->allowed_plan_fields);

            $plan_id = $this->memberships_model->save($plan);

            audit_log('membership_plan.create', 'membership_plan', $plan_id, [
                'name' => $plan['name'] ?? null,
                'service_id' => $plan['id_services'] ?? null,
            ]);

            json_response([
                'success' => true,
                'id' => $plan_id,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Sell a plan to a customer (create a new customer membership).
     */
    public function store(): void
    {
        try {
            method('post');

            if (cannot('add', PRIV_MEMBERSHIPS)) {
                abort(403, 'Forbidden');
            }

            check('membership', 'array');

            $membership = request('membership');
            unset($membership['id']);

            if (empty($membership['id_users_customer']) || empty($membership['id_membership_plans'])) {
                throw new InvalidArgumentException('Müşteri ve üyelik planı gereklidir.');
            }

            $plan = $this->memberships_model->find((int) $membership['id_membership_plans']);

            $period_start = date('Y-m-d H:i:s');
            $months = match ($plan['billing_period']) {
                'quarterly' => 3,
                'yearly' => 12,
                default => 1,
            };
            $period_end = date('Y-m-d H:i:s', strtotime('+' . $months . ' months', strtotime($period_start)));

            $membership['current_period_start'] = $period_start;
            $membership['current_period_end'] = $period_end;
            $membership['sold_by'] = session('user_id');

            $this->customer_memberships_model->only($membership, $this->allowed_membership_fields);
            $membership['sold_by'] = session('user_id');

            $membership_id = $this->customer_memberships_model->save($membership);

            audit_log('membership.sell', 'customer_membership', $membership_id, [
                'customer_id' => $membership['id_users_customer'],
                'plan_id' => $membership['id_membership_plans'],
            ]);

            json_response([
                'success' => true,
                'id' => $membership_id,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Explicitly renew a membership (staff-recorded).
     */
    public function renew(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_MEMBERSHIPS)) {
                abort(403, 'Forbidden');
            }

            check('membership_id', 'numeric');

            $membership_id = (int) request('membership_id');

            $this->customer_memberships_model->renew($membership_id, session('user_id'));

            audit_log('membership.renew', 'customer_membership', $membership_id, []);

            json_response([
                'success' => true,
                'membership' => $this->customer_memberships_model->find($membership_id),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Cancel a membership.
     */
    public function cancel(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_MEMBERSHIPS)) {
                abort(403, 'Forbidden');
            }

            check('membership_id', 'numeric');

            $membership_id = (int) request('membership_id');

            $this->customer_memberships_model->save([
                'id' => $membership_id,
                'status' => 'cancelled',
                'cancelled_at' => date('Y-m-d H:i:s'),
            ]);

            audit_log('membership.cancel', 'customer_membership', $membership_id, []);

            json_response([
                'success' => true,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
