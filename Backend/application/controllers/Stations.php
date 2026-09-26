<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Salon Flora customization - Stations controller.
 *
 * Handles the stations (physical treatment room) related operations. Mirrors
 * the structure of Services.php.
 * ---------------------------------------------------------------------------- */

class Stations extends App_Controller
{
    public array $allowed_station_fields = ['id', 'name', 'notes', 'is_active', 'display_order']; // display_order: BooKi (2026-09-17), İlk Müsaitlik room ranking

    /**
     * Stations constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('stations_model');
        $this->load->model('roles_model');
        $this->load->model('services_model');
        $this->load->model('service_categories_model');

        $this->load->library('accounts');
    }

    /**
     * Render the backend stations page.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('stations')]);

        $user_id = session('user_id');

        if (cannot('view', PRIV_STATIONS)) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        $role_slug = session('role_slug');

        html_vars([
            'page_title' => 'İstasyonlar',
            'active_menu' => PRIV_STATIONS,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
        ]);

        script_vars([
            'user_id' => $user_id,
            'role_slug' => $role_slug,
            // Salon Flora customization - for the "which services can this station host" checkbox list.
            'services' => $this->services_model->get(),
            'service_categories' => $this->service_categories_model->get(),
        ]);

        $this->load->view('pages/stations');
    }

    /**
     * Filter stations by the provided keyword.
     */
    public function search(): void
    {
        try {
            method('post');

            if (cannot('view', PRIV_STATIONS)) {
                abort(403, 'Forbidden');
            }

            check('keyword', 'string|null');
            check('limit', 'numeric|null');
            check('offset', 'numeric|null');

            $keyword = request('keyword', '');

            $limit = request('limit', 1000);

            $offset = (int) request('offset', '0');

            $stations = $this->stations_model->search($keyword, $limit, $offset);

            json_response($stations);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Store a new station.
     */
    public function store(): void
    {
        try {
            method('post');

            if (cannot('add', PRIV_STATIONS)) {
                abort(403, 'Forbidden');
            }

            check('station', 'array');

            $station = request('station');

            unset($station['id']);

            // Salon Flora customization - 'services' is a separate many-to-many relationship, not a column on
            // the stations table - pull it out before only() would otherwise strip it.
            $services = $station['services'] ?? [];
            unset($station['services']);

            $this->stations_model->only($station, $this->allowed_station_fields);

            $station['services'] = $services;

            $station_id = $this->stations_model->save($station);

            json_response([
                'success' => true,
                'id' => $station_id,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Find a station.
     */
    public function find(): void
    {
        try {
            method('post');

            if (cannot('view', PRIV_STATIONS)) {
                abort(403, 'Forbidden');
            }

            check('station_id', 'numeric');

            $station_id = request('station_id');

            if (empty($station_id) || !filter_var($station_id, FILTER_VALIDATE_INT) || $station_id <= 0) {
                throw new InvalidArgumentException('Invalid station ID provided.');
            }

            $station = $this->stations_model->find((int) $station_id);

            $station['provider_ids'] = $this->stations_model->get_provider_ids((int) $station_id);

            json_response($station);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Update a station.
     */
    public function update(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_STATIONS)) {
                abort(403, 'Forbidden');
            }

            check('station', 'array');

            $station = request('station');

            // Salon Flora customization - see the identical comment in store().
            $services = $station['services'] ?? [];
            unset($station['services']);

            $this->stations_model->only($station, $this->allowed_station_fields);

            $station['services'] = $services;

            $station_id = $this->stations_model->save($station);

            json_response([
                'success' => true,
                'id' => $station_id,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Remove a station.
     */
    public function destroy(): void
    {
        try {
            method('post');

            if (cannot('delete', PRIV_STATIONS)) {
                abort(403, 'Forbidden');
            }

            check('station_id', 'numeric');

            $station_id = request('station_id');

            if (empty($station_id) || !filter_var($station_id, FILTER_VALIDATE_INT) || $station_id <= 0) {
                throw new InvalidArgumentException('Invalid station ID provided.');
            }

            $this->stations_model->delete((int) $station_id);

            json_response([
                'success' => true,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
