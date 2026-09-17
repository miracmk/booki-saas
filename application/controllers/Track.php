<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Public Telemetry & Attribution Tracking Controller
 *
 * Receives ad click visits, heatmap interactions, and conversion signals
 * from public booking pages and marketing landing pages.
 * ---------------------------------------------------------------------------- */

class Track extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('traffic_attributions_model');
    }

    /**
     * POST → Track ad click or initial visit.
     */
    public function visit(): void
    {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        if (!is_array($data)) {
            $data = $this->input->post();
        }

        if (empty($data['session_id'])) {
            $data['session_id'] = $this->input->cookie('booki_session_id') ?: bin2hex(random_bytes(16));
        }

        $data['ip_address'] = $this->input->ip_address();
        $data['user_agent'] = $this->input->user_agent();
        if (empty($data['click_timestamp'])) {
            $data['click_timestamp'] = date('Y-m-d H:i:s');
        }

        $id = $this->traffic_attributions_model->record_visit($data);

        json_response(['success' => true, 'id' => $id, 'session_id' => $data['session_id']]);
    }

    /**
     * POST → Track heatmap clicks, scroll depth, and form clues.
     */
    public function interaction(): void
    {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        if (!is_array($data)) {
            $data = $this->input->post();
        }

        $sessionId = $data['session_id'] ?? $this->input->cookie('booki_session_id');

        if (empty($sessionId)) {
            json_response(['success' => false, 'message' => 'Session ID required'], 400);
            return;
        }

        $heatmapData = $data['heatmap_data'] ?? [];
        $clues = $data['clues'] ?? [];

        $success = $this->traffic_attributions_model->record_interaction(
            (string) $sessionId,
            (array) $heatmapData,
            (array) $clues
        );

        json_response(['success' => $success]);
    }
}
