<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Multi-Vertical Enterprise Suite API v1 Controller
 * ---------------------------------------------------------------------------- */

class Verticals_api_v1 extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->library('api');

        // Allow public access to certain token-based endpoints (DVI public inspection, token verification)
        $method = $this->router->method;
        $public_methods = ['public_inspection_report', 'approve_inspection', 'verify_turnstile'];
        if (!in_array($method, $public_methods, true)) {
            $this->api->auth();
        }

        $this->load->model('gift_cards_model');
        $this->load->model('restaurant_model');
        $this->load->model('sports_matches_model');
        $this->load->model('clinical_records_model');
        $this->load->model('vehicles_model');
        $this->load->model('work_orders_model');
        $this->load->model('digital_waivers_model');
        $this->load->model('event_tickets_model');
    }

    /* -------------------------------------------------------------------------
     * 1. GÜZELLİK & SPA - HEDİYE KARTI & KAPORA (DEPOSIT)
     * ------------------------------------------------------------------------- */

    public function issue_gift_card(): void
    {
        try {
            $data = request();
            $card = $this->gift_cards_model->issue_card($data);
            json_response($card, 201);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function get_gift_card(string $code): void
    {
        try {
            $card = $this->gift_cards_model->get_by_code($code);
            if (!$card) {
                json_response(['error' => 'Hediye kartı bulunamadı.'], 404);
                return;
            }
            json_response($card);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 500);
        }
    }

    public function redeem_gift_card(): void
    {
        try {
            $code = request('code');
            $amount = (float) request('amount');
            $appointment_id = request('appointment_id');
            $adisyon_id = request('adisyon_id');

            $res = $this->gift_cards_model->redeem($code, $amount, $appointment_id ? (int)$appointment_id : null, $adisyon_id ? (int)$adisyon_id : null);
            json_response($res, $res['success'] ? 200 : 400);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function record_deposit(): void
    {
        try {
            $appointment_id = (int) request('appointment_id');
            $amount = (float) request('amount');
            $tx_id = request('transaction_id');

            $res = $this->gift_cards_model->record_appointment_deposit($appointment_id, $amount, $tx_id);
            json_response(['success' => $res, 'appointment_id' => $appointment_id, 'deposit_amount' => $amount]);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function update_deposit_status(): void
    {
        try {
            $appointment_id = (int) request('appointment_id');
            $status = request('status');

            $res = $this->gift_cards_model->update_deposit_status($appointment_id, $status);
            json_response(['success' => $res, 'appointment_id' => $appointment_id, 'status' => $status]);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    /* -------------------------------------------------------------------------
     * 2. RESTORAN & KAFE - GUEST INTELLIGENCE & KDS
     * ------------------------------------------------------------------------- */

    public function get_guest_preferences(int $customer_id): void
    {
        try {
            $prefs = $this->restaurant_model->get_guest_preferences($customer_id);
            json_response($prefs);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 500);
        }
    }

    public function save_guest_preferences(int $customer_id): void
    {
        try {
            $data = request();
            $res = $this->restaurant_model->save_guest_preferences($customer_id, $data);
            json_response($res);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function get_kitchen_orders(): void
    {
        try {
            $station = request('station');
            $orders = $this->restaurant_model->get_active_kitchen_orders($station);
            json_response(['orders' => $orders]);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 500);
        }
    }

    public function create_kitchen_order(): void
    {
        try {
            $data = request();
            $id = $this->restaurant_model->create_kitchen_order($data);
            json_response(['success' => true, 'kitchen_order_id' => $id], 201);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function update_kitchen_order_status(): void
    {
        try {
            $order_id = (int) request('order_id');
            $status = request('status');
            $res = $this->restaurant_model->update_kitchen_order_status($order_id, $status);
            json_response(['success' => $res, 'order_id' => $order_id, 'status' => $status]);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    /* -------------------------------------------------------------------------
     * 3. SPOR / KORT / FITNESS - MATCHMAKING & TURNİKE
     * ------------------------------------------------------------------------- */

    public function get_sports_matches(): void
    {
        try {
            $sport_type = request('sport_type');
            $matches = $this->sports_matches_model->get_open_matches($sport_type);
            json_response(['matches' => $matches]);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 500);
        }
    }

    public function create_sports_match(): void
    {
        try {
            $data = request();
            $id = $this->sports_matches_model->create_match($data);
            json_response(['success' => true, 'match_id' => $id], 201);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function join_sports_match(int $match_id): void
    {
        try {
            $customer_id = (int) request('customer_id');
            $team = request('team');
            $skill_level = request('skill_level');

            $res = $this->sports_matches_model->join_match($match_id, $customer_id, $team, $skill_level);
            json_response($res, $res['success'] ? 200 : 400);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function verify_turnstile(): void
    {
        try {
            $token = request('access_token') ?? request('token');
            $gate_id = request('gate_id');

            if (empty($token)) {
                json_response(['access_granted' => false, 'relay_trigger' => 0, 'reason' => 'Access token zorunludur.'], 400);
                return;
            }

            $res = $this->sports_matches_model->verify_turnstile_access($token, $gate_id);
            json_response($res);
        } catch (Throwable $e) {
            json_response(['access_granted' => false, 'relay_trigger' => 0, 'error' => $e->getMessage()], 500);
        }
    }

    /* -------------------------------------------------------------------------
     * 4. SAĞLIK / KLİNİK - EHR / SOAP NOTLARI & TELEHEALTH
     * ------------------------------------------------------------------------- */

    public function add_clinical_record(): void
    {
        try {
            $data = request();
            $id = $this->clinical_records_model->add_record($data);
            json_response(['success' => true, 'record_id' => $id], 201);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function get_patient_clinical_history(int $customer_id): void
    {
        try {
            $records = $this->clinical_records_model->get_patient_history($customer_id);
            $insurance = $this->clinical_records_model->get_patient_insurance($customer_id);
            json_response([
                'customer_id' => $customer_id,
                'clinical_records' => $records,
                'insurance' => $insurance,
            ]);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 500);
        }
    }

    public function save_patient_insurance(int $customer_id): void
    {
        try {
            $data = request();
            $id = $this->clinical_records_model->save_patient_insurance($customer_id, $data);
            json_response(['success' => true, 'insurance_id' => $id]);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function get_telehealth_link(int $appointment_id): void
    {
        try {
            $link = $this->clinical_records_model->get_or_create_telehealth_link($appointment_id);
            json_response(['appointment_id' => $appointment_id, 'telehealth_url' => $link]);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    /* -------------------------------------------------------------------------
     * 5. OTOMOTİV / SERVİS / EKSPERTİZ - ARAÇLAR, DVI & İŞ EMİRLERİ
     * ------------------------------------------------------------------------- */

    public function add_vehicle(): void
    {
        try {
            $data = request();
            $id = $this->vehicles_model->add_vehicle($data);
            json_response(['success' => true, 'vehicle_id' => $id], 201);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function get_vehicle_by_plate(string $plate): void
    {
        try {
            $vehicle = $this->vehicles_model->get_by_plate($plate);
            if (!$vehicle) {
                json_response(['error' => 'Araç bulunamadı.'], 404);
                return;
            }
            json_response($vehicle);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 500);
        }
    }

    public function get_customer_vehicles(int $customer_id): void
    {
        try {
            $vehicles = $this->vehicles_model->get_by_customer($customer_id);
            json_response(['vehicles' => $vehicles]);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 500);
        }
    }

    public function save_vehicle_inspection(): void
    {
        try {
            $data = request();
            $inspection = $this->work_orders_model->save_inspection($data);
            json_response($inspection, 201);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function public_inspection_report(string $token): void
    {
        try {
            $inspection = $this->work_orders_model->get_inspection_by_token($token);
            if (!$inspection) {
                json_response(['error' => 'Ekspertiz raporu bulunamadı.'], 404);
                return;
            }
            json_response($inspection);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 500);
        }
    }

    public function approve_inspection(string $token): void
    {
        try {
            $res = $this->work_orders_model->approve_inspection_by_token($token);
            json_response(['success' => $res, 'message' => 'Ekspertiz ve onay kaydı başarıyla alındı.']);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function create_work_order(): void
    {
        try {
            $data = request();
            $wo = $this->work_orders_model->create_work_order($data);
            json_response($wo, 201);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function update_work_order_status(int $work_order_id): void
    {
        try {
            $status = request('status');
            $res = $this->work_orders_model->update_status($work_order_id, $status);
            json_response(['success' => $res, 'work_order_id' => $work_order_id, 'status' => $status]);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    /* -------------------------------------------------------------------------
     * 6. DENEYİM & EĞLENCE - DİJİTAL FERAGATNAME & BİLETLEME & ADD-ONS
     * ------------------------------------------------------------------------- */

    public function save_digital_waiver(): void
    {
        try {
            $data = request();
            $id = $this->digital_waivers_model->save_waiver($data);
            json_response(['success' => true, 'waiver_id' => $id]);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function sign_digital_waiver(): void
    {
        try {
            $data = request();
            $id = $this->digital_waivers_model->sign_waiver($data);
            json_response(['success' => true, 'signature_id' => $id], 201);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function add_booking_addon(int $appointment_id): void
    {
        try {
            $data = request();
            $id = $this->digital_waivers_model->add_booking_addon($appointment_id, $data);
            json_response(['success' => true, 'addon_id' => $id], 201);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function issue_event_ticket(): void
    {
        try {
            $appointment_id = (int) request('appointment_id');
            $customer_id = (int) request('customer_id');
            $seat_label = request('seat_label');

            $ticket = $this->event_tickets_model->issue_ticket($appointment_id, $customer_id, $seat_label);
            json_response($ticket, 201);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function validate_event_ticket(): void
    {
        try {
            $ticket_code = request('ticket_code') ?? request('code');
            if (empty($ticket_code)) {
                json_response(['valid' => false, 'error' => 'Bilet kodu zorunludur.'], 400);
                return;
            }

            $res = $this->event_tickets_model->validate_ticket($ticket_code);
            json_response($res);
        } catch (Throwable $e) {
            json_response(['valid' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
