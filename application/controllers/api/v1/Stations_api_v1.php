<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Stations (Tables / Treatment Rooms) API v1
 * ---------------------------------------------------------------------------- */

class Stations_api_v1 extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('stations_model');
        $this->load->library('api');

        $this->api->auth();
        $this->api->model('stations_model');
    }

    /**
     * GET /api/v1/stations
     */
    public function index(): void
    {
        try {
            $stations = $this->stations_model->get();
            json_response($stations);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * GET /api/v1/stations/:id
     */
    public function show(int $id): void
    {
        try {
            $station = $this->stations_model->find($id);
            if (!$station) {
                response('', 404);
                return;
            }
            json_response($station);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}

