<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Online Appointment Scheduler
 *
 * @package     KiReservation
 * @author      Ki Software
 * @copyright   Copyright (c) Ki Software
 * @license     Proprietary - see LICENSE file
 * @link        https://kisoftware.com
 * ---------------------------------------------------------------------------- */

/**
 * Services controller.
 *
 * Handles the services related operations.
 *
 * @package Controllers
 */
class Services extends EA_Controller
{
    public array $allowed_service_fields = [
        'id',
        'name',
        'duration',
        'price',
        'currency',
        'description',
        'color',
        'location',
        'slot_interval',
        'attendants_number',
        'is_private',
        'id_service_categories',
        'providers',
    ];
    public array $optional_service_fields = [
        'id_service_categories' => null,
    ];

    /**
     * Services constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('services_model');
        $this->load->model('providers_model');
        $this->load->model('roles_model');
        $this->load->model('inventory_consumables_model');

        $this->load->library('accounts');
        $this->load->library('timezones');
        $this->load->library('webhooks_client');
    }

    /**
     * Render the backend services page.
     *
     * On this page admin users will be able to manage services, which are eventually selected by customers during the
     * booking process.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('services')]);

        $user_id = session('user_id');

        if (cannot('view', PRIV_SERVICES)) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        $role_slug = session('role_slug');

        $providers = $this->providers_model->get();
        $products = $this->db->table_exists('products') ? $this->db->order_by('name', 'ASC')->get('products')->result_array() : [];

        script_vars([
            'user_id' => $user_id,
            'role_slug' => $role_slug,
            'event_minimum_duration' => EVENT_MINIMUM_DURATION,
            'providers' => filter_sensitive_users_data($providers),
            'products' => $products,
        ]);

        html_vars([
            'page_title' => lang('services'),
            'active_menu' => PRIV_SERVICES,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'timezones' => $this->timezones->to_array(),
            'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
            'providers' => filter_sensitive_users_data($providers),
            'products' => $products,
        ]);

        $this->load->view('pages/services');
    }

    /**
     * Filter services by the provided keyword.
     */
    public function search(): void
    {
        try {
            method('post');

            if (cannot('view', PRIV_SERVICES)) {
                abort(403, 'Forbidden');
            }

            check('keyword', 'string|null');
            check('order_by', 'string|null');
            check('limit', 'numeric|null');
            check('offset', 'numeric|null');

            $keyword = request('keyword', '');

            $order_by = request('order_by', 'update_datetime DESC');

            $limit = request('limit', 1000);

            $offset = (int) request('offset', '0');

            $services = $this->services_model->search($keyword, $limit, $offset, $order_by);

            // Include provider IDs for each service
            foreach ($services as &$service) {
                $service['providers'] = $this->services_model->get_provider_ids($service['id']);
            }

            json_response($services);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Store a new service.
     */
    public function store(): void
    {
        try {
            method('post');

            if (cannot('add', PRIV_SERVICES)) {
                abort(403, 'Forbidden');
            }

            check('service', 'array');

            $service = request('service');

            $this->services_model->only($service, $this->allowed_service_fields);

            $this->services_model->optional($service, $this->optional_service_fields);

            $service_id = $this->services_model->save($service);

            $service = $this->services_model->find($service_id);

            $this->webhooks_client->trigger(WEBHOOK_SERVICE_SAVE, $service);

            json_response([
                'success' => true,
                'id' => $service_id,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Find a service.
     */
    public function find(): void
    {
        try {
            method('get');

            if (cannot('view', PRIV_SERVICES)) {
                abort(403, 'Forbidden');
            }

            check('service_id', 'numeric');

            $service_id = request('service_id');

            // Validate service_id is a positive integer
            if (empty($service_id) || !filter_var($service_id, FILTER_VALIDATE_INT) || $service_id <= 0) {
                throw new InvalidArgumentException('Invalid service ID provided.');
            }

            $service = $this->services_model->find($service_id);

            json_response($service);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Update a service.
     */
    public function update(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SERVICES)) {
                abort(403, 'Forbidden');
            }

            check('service', 'array');

            $service = request('service');

            $this->services_model->only($service, $this->allowed_service_fields);

            $this->services_model->optional($service, $this->optional_service_fields);

            $service_id = $this->services_model->save($service);

            $service = $this->services_model->find($service_id);

            $this->webhooks_client->trigger(WEBHOOK_SERVICE_SAVE, $service);

            json_response([
                'success' => true,
                'id' => $service_id,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Remove a service.
     */
    public function destroy(): void
    {
        try {
            method('post');

            if (cannot('delete', PRIV_SERVICES)) {
                abort(403, 'Forbidden');
            }

            check('service_id', 'numeric');

            $service_id = request('service_id');

            // Validate service_id is a positive integer
            if (empty($service_id) || !filter_var($service_id, FILTER_VALIDATE_INT) || $service_id <= 0) {
                throw new InvalidArgumentException('Invalid service ID provided.');
            }

            $service = $this->services_model->find($service_id);

            $this->services_model->delete($service_id);

            $this->webhooks_client->trigger(WEBHOOK_SERVICE_DELETE, $service);

            json_response([
                'success' => true,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Get add-ons for a service.
     */
    public function get_addons(int $service_id): void
    {
        try {
            method('get');
            $addons = $this->services_model->get_addons($service_id);
            json_response($addons);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Save an add-on.
     */
    public function save_addon(): void
    {
        try {
            method('post');
            if (cannot('edit', PRIV_SERVICES)) {
                abort(403, 'Forbidden');
            }
            $data = json_decode($this->input->raw_input_stream, true) ?: $this->input->post();
            $id = $this->services_model->save_addon($data);
            json_response(['success' => true, 'id' => $id]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Delete an add-on.
     */
    public function delete_addon(int $addon_id): void
    {
        try {
            method('post');
            if (cannot('edit', PRIV_SERVICES)) {
                abort(403, 'Forbidden');
            }
            $this->services_model->delete_addon($addon_id);
            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Get consumable recipes for a service.
     */
    public function get_consumables(int $service_id): void
    {
        try {
            method('get');
            $recipes = $this->inventory_consumables_model->get_recipes_for_service($service_id);
            json_response($recipes);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Save consumable recipe for a service.
     */
    public function save_consumable(): void
    {
        try {
            method('post');
            if (cannot('edit', PRIV_SERVICES)) {
                abort(403, 'Forbidden');
            }
            $data = json_decode($this->input->raw_input_stream, true) ?: $this->input->post();
            $id = $this->inventory_consumables_model->save_recipe($data);
            json_response(['success' => true, 'id' => $id]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Delete consumable recipe.
     */
    public function delete_consumable(int $recipe_id): void
    {
        try {
            method('post');
            if (cannot('edit', PRIV_SERVICES)) {
                abort(403, 'Forbidden');
            }
            $this->inventory_consumables_model->delete_recipe($recipe_id);
            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Get required resources for a service.
     */
    public function get_resources(int $service_id): void
    {
        try {
            method('get');
            $resources = $this->services_model->get_required_resources($service_id);
            json_response($resources);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Save required resource for a service.
     */
    public function save_resource(): void
    {
        try {
            method('post');
            if (cannot('edit', PRIV_SERVICES)) {
                abort(403, 'Forbidden');
            }
            $data = json_decode($this->input->raw_input_stream, true) ?: $this->input->post();
            $id = $this->services_model->save_required_resource($data);
            json_response(['success' => true, 'id' => $id]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Delete required resource.
     */
    public function delete_resource(int $resource_id): void
    {
        try {
            method('post');
            if (cannot('edit', PRIV_SERVICES)) {
                abort(403, 'Forbidden');
            }
            $this->services_model->delete_required_resource($resource_id);
            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
