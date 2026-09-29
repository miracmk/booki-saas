<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Multi-Vertical Enterprise Suite API v1 Controller
 * ---------------------------------------------------------------------------- */

class Verticals_api_v1 extends App_Controller
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
            $card_id = request('card_id') ?: request('id');
            $amount = (float) request('amount');
            $notes = request('notes') ?: request('note');
            $appointment_id = request('appointment_id');
            $adisyon_id = request('adisyon_id');

            if ($amount <= 0) {
                json_response(['success' => false, 'error' => 'Düşülecek tutar 0\'dan büyük olmalıdır.'], 400);
                return;
            }

            $card = null;
            if ($card_id) {
                $card = $this->db->get_where('gift_cards', ['id' => (int) $card_id])->row_array();
            }
            if (!$card && !empty($code)) {
                $card = $this->db->get_where('gift_cards', ['code' => strtoupper(trim($code))])->row_array();
            }

            if (!$card) {
                json_response(['success' => false, 'error' => 'Hediye kartı bulunamadı.'], 404);
                return;
            }

            // Expiry date check
            if (!empty($card['expires_at']) && $card['expires_at'] < date('Y-m-d')) {
                $this->db->where('id', $card['id'])->update('gift_cards', [
                    'status' => 'expired',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                json_response(['success' => false, 'error' => 'Hediye kartının kullanım süresi dolmuştur.'], 400);
                return;
            }

            if ($card['status'] !== 'active') {
                json_response(['success' => false, 'error' => 'Hediye kartı aktif değil (Durum: ' . $card['status'] . ').'], 400);
                return;
            }

            $current_balance = (float) $card['current_balance'];
            if ($current_balance < $amount) {
                json_response([
                    'success' => false,
                    'error' => 'Yetersiz bakiye. Mevcut bakiye: ' . number_format($current_balance, 2) . ' TL',
                    'current_balance' => $current_balance,
                ], 400);
                return;
            }

            $new_balance = round($current_balance - $amount, 2);
            $new_status = ($new_balance <= 0.001) ? 'depleted' : 'active';
            $now = date('Y-m-d H:i:s');

            $this->db->trans_start();

            $this->db->where('id', $card['id'])->update('gift_cards', [
                'current_balance' => $new_balance,
                'status' => $new_status,
                'updated_at' => $now,
            ]);

            if ($this->db->table_exists('gift_card_transactions')) {
                $this->db->insert('gift_card_transactions', [
                    'id_gift_cards' => $card['id'],
                    'amount' => $amount,
                    'notes' => $notes,
                    'created_at' => $now,
                ]);
            }

            if ($this->db->table_exists('gift_card_redemptions')) {
                $redemption_data = [
                    'id_gift_cards' => $card['id'],
                    'id_appointments' => $appointment_id ? (int) $appointment_id : null,
                    'id_adisyons' => $adisyon_id ? (int) $adisyon_id : null,
                    'redeemed_amount' => $amount,
                    'redeemed_at' => $now,
                ];
                if ($this->db->field_exists('notes', 'gift_card_redemptions')) {
                    $redemption_data['notes'] = $notes;
                }
                $this->db->insert('gift_card_redemptions', $redemption_data);
            }

            $this->db->trans_complete();

            json_response([
                'success' => true,
                'message' => 'Bakiye başarıyla düşüldü.',
                'new_balance' => $new_balance,
                'card_id' => (int) $card['id'],
                'status' => $new_status,
            ], 200);
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
            $session_role = session('role_slug');
            $session_user_id = (int) session('user_id');

            if (!$session_user_id) {
                json_response(['error' => 'Kimlik doğrulama gereklidir.'], 401);
                return;
            }

            if ($session_role === DB_SLUG_CUSTOMER && $session_user_id !== $customer_id) {
                json_response(['error' => 'Diğer misafirlerin tercihlerini düzenleme yetkiniz yoktur.'], 403);
                return;
            }

            $data = request();

            // Extract hospitality preferences if present
            $preferences = $data['preferences'] ?? $data;
            $pillow = '';
            $floor = '';
            $dietary = '';
            $notes = '';

            if (is_array($preferences)) {
                $pillow = trim((string) ($preferences['pillow_type'] ?? ($preferences['pillow_choice'] ?? ($data['pillow_type'] ?? ($data['pillow_choice'] ?? '')))));
                $floor = trim((string) ($preferences['floor_preference'] ?? ($data['floor_preference'] ?? '')));
                $dietary = trim((string) ($preferences['dietary'] ?? ($preferences['dietary_allergies'] ?? ($preferences['dietary_restrictions'] ?? ($data['dietary'] ?? ($data['dietary_allergies'] ?? ''))))));
                $notes = trim((string) ($preferences['notes'] ?? ($preferences['special_notes'] ?? ($data['notes'] ?? ($data['special_notes'] ?? '')))));

                $customer = $this->db->get_where('users', ['id' => $customer_id])->row_array();
                if ($customer) {
                    $existing_notes = $customer['notes'] ?? '';
                    $meta = [];
                    if (!empty($existing_notes) && str_starts_with(trim($existing_notes), '{')) {
                        $meta = json_decode($existing_notes, true) ?: [];
                    }
                    $meta['hospitality_preferences'] = [
                        'pillow_type' => $pillow,
                        'floor_preference' => $floor,
                        'dietary' => $dietary,
                        'notes' => $notes,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                    $meta['pillow_type'] = $pillow;
                    $meta['floor_preference'] = $floor;
                    $meta['dietary'] = $dietary;
                    $meta['notes'] = $notes;

                    $this->db->update('users', [
                        'notes' => json_encode($meta, JSON_UNESCAPED_UNICODE),
                    ], ['id' => $customer_id]);
                }
            }

            $res = [];
            if (method_exists($this->restaurant_model, 'save_guest_preferences')) {
                $res = $this->restaurant_model->save_guest_preferences($customer_id, $data);
            }

            json_response([
                'success' => true,
                'message' => 'Misafir tercihleri kaydedildi.',
                'customer_id' => $customer_id,
                'preferences' => [
                    'pillow_type' => $pillow,
                    'floor_preference' => $floor,
                    'dietary' => $dietary,
                    'notes' => $notes,
                ],
                'data' => $res,
            ]);
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
            $session_role = session('role_slug');
            $session_user_id = (int) session('user_id');

            if (!$session_user_id) {
                json_response(['error' => 'Kimlik doğrulama gereklidir.'], 401);
                return;
            }

            $data = request();

            if (empty($data['title']) || empty($data['start_datetime']) || empty($data['end_datetime'])) {
                json_response(['error' => 'Maç başlığı, başlangıç ve bitiş zamanı zorunludur.'], 400);
                return;
            }

            if (strtotime($data['start_datetime']) >= strtotime($data['end_datetime'])) {
                json_response(['error' => 'Bitiş zamanı başlangıç zamanından sonra olmalıdır.'], 400);
                return;
            }

            if (isset($data['max_players']) && ((int) $data['max_players'] < 2 || (int) $data['max_players'] > 50)) {
                json_response(['error' => 'Maksimum oyuncu sayısı 2 ile 50 arasında olmalıdır.'], 400);
                return;
            }

            if ($session_role === DB_SLUG_CUSTOMER || empty($data['created_by_user_id'])) {
                $data['created_by_user_id'] = $session_user_id;
            }

            $id = $this->sports_matches_model->create_match($data);
            json_response(['success' => true, 'match_id' => $id], 201);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function join_sports_match(?int $match_id = null): void
    {
        try {
            $session_role = session('role_slug');
            $session_user_id = (int) session('user_id');

            if (!$session_user_id) {
                json_response(['error' => 'Kimlik doğrulama gereklidir.'], 401);
                return;
            }

            $match_id = $match_id ?: (int) request('match_id');
            $customer_id = (int) (request('customer_id') ?? request('id_users_customer') ?? $session_user_id);
            $team = request('team');
            $skill_level = request('skill_level');

            if (!$match_id || $match_id <= 0) {
                json_response(['error' => 'Geçerli bir maç ID belirtilmelidir.'], 400);
                return;
            }

            if (!$customer_id || $customer_id <= 0) {
                json_response(['error' => 'Geçerli bir müşteri seçilmelidir.'], 400);
                return;
            }

            if ($session_role === DB_SLUG_CUSTOMER && $customer_id !== $session_user_id) {
                json_response(['error' => 'Diğer kullanıcılar adına maça katılamazsınız.'], 403);
                return;
            }

            $res = $this->sports_matches_model->join_match($match_id, $customer_id, $team, $skill_level);
            json_response($res, ($res['success'] ?? false) ? 200 : 400);
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
            $session_role = session('role_slug');
            if ($session_role === DB_SLUG_CUSTOMER) {
                json_response(['error' => 'Bu işlem için yetkiniz bulunmamaktadır.'], 403);
                return;
            }

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
            $session_role = session('role_slug');
            $session_user_id = session('user_id');

            if ($session_role === DB_SLUG_CUSTOMER && (int) $session_user_id !== (int) $customer_id) {
                json_response(['error' => 'Diğer danışanların klinik kayıtlarına erişim yetkiniz yoktur.'], 403);
                return;
            }

            $include_confidential = ($session_role !== DB_SLUG_CUSTOMER);
            $records = $this->clinical_records_model->get_patient_history($customer_id, $include_confidential);
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
            $session_role = session('role_slug');
            if ($session_role === DB_SLUG_CUSTOMER) {
                json_response(['error' => 'Bu işlem için yetkiniz bulunmamaktadır.'], 403);
                return;
            }

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
            $session_role = session('role_slug');
            $session_user_id = session('user_id');

            if ($session_role === DB_SLUG_CUSTOMER) {
                $appt = $this->db->get_where('appointments', ['id' => $appointment_id])->row_array();
                if (!$appt) {
                    json_response(['error' => 'Randevu bulunamadı.'], 404);
                    return;
                }
                if ((int) $appt['id_users_customer'] !== (int) $session_user_id) {
                    json_response(['error' => 'Bu randevuya ait telehealth bağlantısına erişim yetkiniz yoktur.'], 403);
                    return;
                }
            }

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
            $session_role = session('role_slug');
            $session_user_id = (int) session('user_id');

            if (!$session_user_id) {
                json_response(['error' => 'Kimlik doğrulama gereklidir.'], 401);
                return;
            }

            $data = request();

            if ($session_role === DB_SLUG_CUSTOMER) {
                if (!empty($data['id_users_customer']) && (int) $data['id_users_customer'] !== $session_user_id) {
                    json_response(['error' => 'Diğer müşteriler adına araç kaydedemezsiniz.'], 403);
                    return;
                }
                $data['id_users_customer'] = $session_user_id;
            }

            $customer_id = !empty($data['id_users_customer']) ? (int) $data['id_users_customer'] : (!empty($data['customer_id']) ? (int) $data['customer_id'] : null);
            $plate_number = trim($data['plate_number'] ?? '');
            $brand = trim($data['brand'] ?? '');
            $model = trim($data['model'] ?? '');

            if (!$customer_id || empty($plate_number) || empty($brand) || empty($model)) {
                json_response(['error' => 'Plaka, marka, model ve araç sahibi seçimi zorunludur.'], 400);
                return;
            }

            $data['id_users_customer'] = $customer_id;
            $data['plate_number'] = $plate_number;
            $data['brand'] = $brand;
            $data['model'] = $model;

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
            $session_role = session('role_slug');
            $session_user_id = (int) session('user_id');

            if ($session_role === DB_SLUG_CUSTOMER && (int) $session_user_id !== (int) $customer_id) {
                json_response(['error' => 'Diğer müşterilerin araçlarına erişim yetkiniz yoktur.'], 403);
                return;
            }

            $vehicles = $this->vehicles_model->get_by_customer($customer_id);
            json_response(['vehicles' => $vehicles]);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 500);
        }
    }

    public function save_vehicle_inspection(): void
    {
        try {
            $session_role = session('role_slug');
            $session_user_id = (int) session('user_id');

            if (!$session_user_id) {
                json_response(['error' => 'Kimlik doğrulama gereklidir.'], 401);
                return;
            }

            if ($session_role === DB_SLUG_CUSTOMER) {
                json_response(['error' => 'Ekspertiz kaydetme yetkiniz bulunmamaktadır.'], 403);
                return;
            }

            $data = request();
            $vehicle_id = !empty($data['id_vehicles']) ? (int) $data['id_vehicles'] : (!empty($data['vehicle_id']) ? (int) $data['vehicle_id'] : null);

            if (!$vehicle_id) {
                json_response(['error' => 'Ekspertiz için araç seçimi zorunludur.'], 400);
                return;
            }

            $vehicle = $this->db->get_where('customer_vehicles', ['id' => $vehicle_id])->row_array();
            if (!$vehicle) {
                json_response(['error' => 'Seçilen araç bulunamadı.'], 404);
                return;
            }

            $data['id_vehicles'] = $vehicle_id;

            if (empty($data['inspector_id']) && $session_user_id) {
                $data['inspector_id'] = $session_user_id;
            }

            $inspection = $this->work_orders_model->save_inspection($data);
            json_response(['success' => true, 'inspection' => $inspection], 201);
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
            $session_role = session('role_slug');
            $session_user_id = (int) session('user_id');

            if (!$session_user_id) {
                json_response(['error' => 'Kimlik doğrulama gereklidir.'], 401);
                return;
            }

            if ($session_role === DB_SLUG_CUSTOMER) {
                json_response(['error' => 'İş emri oluşturma yetkiniz bulunmamaktadır.'], 403);
                return;
            }

            $data = request();
            $vehicle_id = !empty($data['id_vehicles']) ? (int) $data['id_vehicles'] : (!empty($data['vehicle_id']) ? (int) $data['vehicle_id'] : null);

            if (!$vehicle_id) {
                json_response(['error' => 'Araç seçimi zorunludur.'], 400);
                return;
            }

            $vehicle = $this->db->get_where('customer_vehicles', ['id' => $vehicle_id])->row_array();
            if (!$vehicle) {
                json_response(['error' => 'Seçilen araç bulunamadı.'], 404);
                return;
            }

            $data['id_vehicles'] = $vehicle_id;

            if (!empty($data['customer_complaint']) && empty($data['labor_items'])) {
                $data['labor_items'] = [['description' => $data['customer_complaint']]];
            }

            $wo = $this->work_orders_model->create_work_order($data);
            json_response(['success' => true, 'work_order' => $wo], 201);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function update_work_order_status(?int $work_order_id = null): void
    {
        try {
            $session_role = session('role_slug');
            $session_user_id = (int) session('user_id');

            if (!$session_user_id) {
                json_response(['error' => 'Kimlik doğrulama gereklidir.'], 401);
                return;
            }

            if ($session_role === DB_SLUG_CUSTOMER) {
                json_response(['error' => 'İş emri durumunu güncelleme yetkiniz bulunmamaktadır.'], 403);
                return;
            }

            $id = $work_order_id ?: (int) (request('work_order_id') ?: request('id'));
            if (!$id) {
                json_response(['error' => 'İş emri ID zorunludur.'], 400);
                return;
            }

            $status = trim((string) request('status'));
            if (empty($status)) {
                json_response(['error' => 'Yeni durum bilgisi zorunludur.'], 400);
                return;
            }

            $valid = [
                'created', 'inspected', 'estimate_pending', 'approved',
                'in_progress', 'parts_waiting', 'quality_check', 'ready', 'delivered'
            ];
            if (!in_array($status, $valid, true)) {
                json_response(['error' => 'Geçersiz iş emri durumu: ' . $status], 400);
                return;
            }

            $wo = $this->db->get_where('work_orders', ['id' => $id])->row_array();
            if (!$wo) {
                json_response(['error' => 'İş emri bulunamadı.'], 404);
                return;
            }

            $res = $this->work_orders_model->update_status($id, $status);
            json_response(['success' => $res, 'work_order_id' => $id, 'status' => $status]);
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
            $session_role = session('role_slug');
            $session_user_id = (int) session('user_id');

            if (!$session_user_id) {
                json_response(['error' => 'Kimlik doğrulama gereklidir.'], 401);
                return;
            }

            if ($session_role === DB_SLUG_CUSTOMER) {
                json_response(['error' => 'Feragatname şablonu oluşturma yetkiniz bulunmamaktadır.'], 403);
                return;
            }

            $data = request();
            if (empty($data['title']) || empty($data['content_html'])) {
                json_response(['error' => 'Feragatname başlığı ve içeriği zorunludur.'], 400);
                return;
            }

            $id = $this->digital_waivers_model->save_waiver($data);
            json_response(['success' => true, 'waiver_id' => $id], 201);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function sign_digital_waiver(): void
    {
        try {
            $session_role = session('role_slug');
            $session_user_id = (int) session('user_id');

            $data = request();
            $waiver_id = !empty($data['id_waivers']) ? (int) $data['id_waivers'] : (!empty($data['waiver_id']) ? (int) $data['waiver_id'] : 0);
            $signer_name = trim($data['signer_full_name'] ?? ($data['signer_name'] ?? ''));

            if (!$waiver_id || empty($signer_name)) {
                json_response(['error' => 'Feragatname ve imzalayan adı zorunludur.'], 400);
                return;
            }

            if ($session_role === DB_SLUG_CUSTOMER && empty($data['id_users_customer'])) {
                $data['id_users_customer'] = $session_user_id;
            }

            if (empty($data['signature_data'])) {
                $data['signature_data'] = 'DIGITAL_ACK_ACCEPTED_' . date('Y-m-d_H:i:s');
            }

            $data['id_waivers'] = $waiver_id;
            $data['signer_full_name'] = $signer_name;
            $data['ip_address'] = $this->input->ip_address() ?: '127.0.0.1';

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
            $session_role = session('role_slug');
            $session_user_id = (int) session('user_id');

            if (!$session_user_id) {
                json_response(['error' => 'Kimlik doğrulama gereklidir.'], 401);
                return;
            }

            if ($session_role === DB_SLUG_CUSTOMER) {
                json_response(['error' => 'Bilet kesme yetkiniz bulunmamaktadır.'], 403);
                return;
            }

            $data = request();
            $appointment_id = !empty($data['id_appointments']) ? (int) $data['id_appointments'] : (!empty($data['appointment_id']) ? (int) $data['appointment_id'] : null);
            $customer_id = (int) (!empty($data['id_users_customer']) ? $data['id_users_customer'] : ($data['customer_id'] ?? 0));
            $ticket_type = trim($data['ticket_type'] ?? ($data['seat_label'] ?? 'standard'));
            $price = isset($data['price']) && $data['price'] !== '' ? (float) $data['price'] : 0.00;

            if (!$customer_id) {
                json_response(['error' => 'Müşteri seçimi zorunludur.'], 400);
                return;
            }

            $seat_label = !empty($data['seat_or_slot_label']) ? trim($data['seat_or_slot_label']) : (!empty($ticket_type) ? trim($ticket_type) : null);

            $ticket = $this->event_tickets_model->issue_ticket($appointment_id, $customer_id, $seat_label, $ticket_type, $price);
            json_response([
                'success' => true,
                'ticket_id' => $ticket['id'],
                'ticket_code' => $ticket['ticket_code'],
                'ticket' => $ticket,
            ], 201);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function validate_event_ticket(): void
    {
        try {
            $session_role = session('role_slug');
            $session_user_id = (int) session('user_id');

            if (!$session_user_id) {
                json_response(['valid' => false, 'error' => 'Kimlik doğrulama gereklidir.'], 401);
                return;
            }

            if ($session_role === DB_SLUG_CUSTOMER) {
                json_response(['valid' => false, 'error' => 'Bilet doğrulama yetkiniz bulunmamaktadır.'], 403);
                return;
            }

            $ticket_code = request('ticket_code') ?? request('code');
            if (empty($ticket_code)) {
                json_response(['valid' => false, 'message' => 'Geçersiz bilet kodu.', 'error' => 'Bilet kodu zorunludur.'], 400);
                return;
            }

            $res = $this->event_tickets_model->validate_ticket($ticket_code);
            json_response($res);
        } catch (Throwable $e) {
            json_response(['valid' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /* -------------------------------------------------------------------------
     * 6. OTEL & KONAKLAMA (HOSPITALITY) - ODA DURUMU & FOLYO HARCAMALARI
     * ------------------------------------------------------------------------- */

    public function update_room_status(): void
    {
        try {
            $session_role = session('role_slug');
            $session_user_id = (int) session('user_id');

            if (!$session_user_id) {
                json_response(['error' => 'Kimlik doğrulama gereklidir.'], 401);
                return;
            }

            if ($session_role === DB_SLUG_CUSTOMER) {
                json_response(['error' => 'Oda durumunu güncelleme yetkiniz bulunmamaktadır.'], 403);
                return;
            }

            $data = request();
            $room_id = (int) (!empty($data['room_id']) ? $data['room_id'] : ($data['station_id'] ?? ($data['id'] ?? 0)));
            $status = strtolower(trim((string) ($data['status'] ?? 'clean')));
            if ($status === 'available') {
                $status = 'clean';
            }

            $valid_statuses = ['clean', 'dirty', 'cleaning', 'occupied', 'maintenance'];
            if (!$room_id || !in_array($status, $valid_statuses, true)) {
                json_response(['error' => 'Geçersiz oda ID veya durum (clean, dirty, cleaning, occupied, maintenance olmalıdır).'], 400);
                return;
            }

            $station = $this->db->get_where('stations', ['id' => $room_id])->row_array();
            if (!$station) {
                json_response(['error' => 'Oda bulunamadı.'], 404);
                return;
            }

            $update = [
                'update_datetime' => date('Y-m-d H:i:s'),
            ];

            if ($this->db->field_exists('status', 'stations')) {
                $update['status'] = $status;
            }

            $notes = $station['notes'] ?? '';
            $meta = [];
            if (!empty($notes) && str_starts_with(trim($notes), '{')) {
                $meta = json_decode($notes, true) ?: [];
            }
            $meta['status'] = $status;
            $meta['updated_at'] = date('Y-m-d H:i:s');
            $update['notes'] = json_encode($meta, JSON_UNESCAPED_UNICODE);

            $this->db->update('stations', $update, ['id' => $room_id]);

            json_response([
                'success' => true,
                'room_id' => $room_id,
                'status' => $status,
                'message' => 'Oda durumu başarıyla güncellendi.',
            ]);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function add_room_charge(): void
    {
        try {
            $session_role = session('role_slug');
            $session_user_id = (int) session('user_id');

            if (!$session_user_id) {
                json_response(['error' => 'Kimlik doğrulama gereklidir.'], 401);
                return;
            }

            if ($session_role === DB_SLUG_CUSTOMER) {
                json_response(['error' => 'Oda harcaması ekleme yetkiniz bulunmamaktadır.'], 403);
                return;
            }

            $data = request();
            $room_id = !empty($data['room_id']) ? (int) $data['room_id'] : (!empty($data['station_id']) ? (int) $data['station_id'] : 0);
            $guest_id = !empty($data['guest_id']) ? (int) $data['guest_id'] : (!empty($data['customer_id']) ? (int) $data['customer_id'] : 0);
            $category = strtolower(trim((string) ($data['category'] ?? 'extra')));
            $item_name = trim((string) ($data['item_name'] ?? ''));
            $amount = isset($data['amount']) ? (float) $data['amount'] : 0.00;

            $valid_categories = ['minibar', 'spa', 'transfer', 'restaurant', 'extra'];
            if (!in_array($category, $valid_categories, true)) {
                $category = 'extra';
            }

            if (empty($item_name) || $amount <= 0 || (!$room_id && !$guest_id)) {
                json_response(['error' => 'Oda/misafir bilgisi, harcama kalemi açıklaması ve geçerli bir tutar zorunludur.'], 400);
                return;
            }

            $this->load->model('adisyons_model');

            // Find active appointment for room if guest_id is not provided
            $appointment = null;
            if ($room_id > 0) {
                $appointment = $this->db
                    ->where('id_stations', $room_id)
                    ->where('is_unavailability', 0)
                    ->where_not_in('status', ['cancelled'])
                    ->order_by('id DESC')
                    ->get('appointments')
                    ->row_array();

                if ($appointment && !$guest_id && !empty($appointment['id_users_customer'])) {
                    $guest_id = (int) $appointment['id_users_customer'];
                }
            }

            $adisyon = null;
            if ($appointment) {
                $adisyon = $this->db->get_where('adisyons', [
                    'id_appointments' => $appointment['id'],
                    'status' => 'open',
                ])->row_array();
            }
            if (!$adisyon && $guest_id > 0) {
                $adisyon = $this->db
                    ->where('id_users_customer', $guest_id)
                    ->where('status', 'open')
                    ->order_by('id DESC')
                    ->get('adisyons')
                    ->row_array();
            }
            if (!$adisyon && $room_id > 0 && $this->db->field_exists('id_stations', 'adisyons')) {
                $adisyon = $this->db
                    ->where('id_stations', $room_id)
                    ->where('status', 'open')
                    ->order_by('id DESC')
                    ->get('adisyons')
                    ->row_array();
            }

            $this->db->trans_start();
            $now = date('Y-m-d H:i:s');
            if (!$adisyon) {
                $adisyon_num = 'FOL-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
                $room_name = '';
                if ($room_id > 0) {
                    $st = $this->db->get_where('stations', ['id' => $room_id])->row_array();
                    $room_name = $st['name'] ?? ('Oda #' . $room_id);
                }
                $insert = [
                    'adisyon_number' => $adisyon_num,
                    'id_users_customer' => $guest_id > 0 ? $guest_id : null,
                    'id_appointments' => $appointment ? (int) $appointment['id'] : null,
                    'status' => 'open',
                    'payment_status' => 'unpaid',
                    'invoice_status' => 'uninvoiced',
                    'subtotal' => 0.00,
                    'tax_amount' => 0.00,
                    'total_amount' => 0.00,
                    'paid_amount' => 0.00,
                    'notes' => 'Otel Folyo - ' . ($room_name ?: ('Misafir #' . $guest_id)),
                    'opened_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                if ($this->db->field_exists('id_stations', 'adisyons')) {
                    $insert['id_stations'] = $room_id > 0 ? $room_id : null;
                }
                $this->db->insert('adisyons', $insert);
                $adisyon_id = (int) $this->db->insert_id();
            } else {
                $adisyon_id = (int) $adisyon['id'];
            }

            $charge_id = $this->adisyons_model->add_item($adisyon_id, [
                'item_type' => $category,
                'name' => $item_name,
                'unit_price' => $amount,
                'quantity' => 1.00,
                'tax_rate' => 0.00,
                'notes' => 'Oda Harcaması [' . strtoupper($category) . ']' . ($room_id > 0 ? ' - Oda #' . $room_id : ''),
            ]);

            $updated = $this->db->get_where('adisyons', ['id' => $adisyon_id])->row_array();
            $total_amount = (float) ($updated['total_amount'] ?? $amount);
            $this->db->trans_complete();

            json_response([
                'success' => true,
                'charge_id' => $charge_id,
                'total_amount' => $total_amount,
                'adisyon_id' => $adisyon_id,
                'category' => $category,
                'message' => 'Ekstra harcama folyoya kaydedildi.',
            ], 201);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    /* -------------------------------------------------------------------------
     * 8. EĞİTİM & ATÖLYE - KURS, CANLI YOKLAMA & ÖĞRENCİ GELİŞİM TAKİBİ
     * ------------------------------------------------------------------------- */

    public function save_attendance(): void
    {
        try {
            $session_role = session('role_slug');
            $session_user_id = (int) session('user_id');

            if (!$session_user_id) {
                json_response(['error' => 'Kimlik doğrulama gereklidir.'], 401);
                return;
            }

            if ($session_role === DB_SLUG_CUSTOMER) {
                json_response(['error' => 'Öğrenciler yoklama kaydedemez.'], 403);
                return;
            }

            $data = request();
            $session_id = (int) ($data['session_id'] ?? 0);
            $records = $data['records'] ?? [];

            if ($session_id <= 0) {
                json_response(['error' => 'Geçerli bir ders/oturum seçilmelidir.'], 400);
                return;
            }
            if (empty($records) || !is_array($records)) {
                json_response(['error' => 'Yoklama kayıtları boş olamaz.'], 400);
                return;
            }

            $this->load->library('migration');
            $this->migration->version(174);

            $now = date('Y-m-d H:i:s');
            $updated_count = 0;

            $this->db->trans_start();

            foreach ($records as $rec) {
                $student_id = (int) ($rec['student_id'] ?? 0);
                $status = in_array($rec['status'] ?? '', ['present', 'absent', 'excused'], true) ? $rec['status'] : 'present';
                $notes = !empty($rec['notes']) ? trim((string) $rec['notes']) : null;

                if ($student_id <= 0) {
                    continue;
                }

                $existing = $this->db->get_where('course_attendance', [
                    'id_appointments' => $session_id,
                    'id_users_customer' => $student_id,
                ])->row_array();

                if ($existing) {
                    $this->db->where('id', $existing['id'])->update('course_attendance', [
                        'status' => $status,
                        'notes' => $notes,
                        'updated_at' => $now,
                    ]);
                } else {
                    $this->db->insert('course_attendance', [
                        'id_appointments' => $session_id,
                        'id_users_customer' => $student_id,
                        'status' => $status,
                        'notes' => $notes,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                // If marked present, deduct 1 session from active package if available
                if ($status === 'present') {
                    $active_pkg = $this->db
                        ->where('id_users_customer', $student_id)
                        ->where('status', 'active')
                        ->where('used_sessions < total_sessions')
                        ->order_by('id ASC')
                        ->get('customer_packages')
                        ->row_array();

                    if ($active_pkg) {
                        $this->db->set('used_sessions', 'used_sessions + 1', false)
                            ->where('id', $active_pkg['id'])
                            ->update('customer_packages');
                    }
                }

                $updated_count++;
            }

            $this->db->trans_complete();

            json_response([
                'success' => true,
                'message' => 'Yoklama başarıyla kaydedildi.',
                'updated_count' => $updated_count,
            ], 200);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function save_student_grade(): void
    {
        try {
            $session_role = session('role_slug');
            $session_user_id = (int) session('user_id');

            if (!$session_user_id) {
                json_response(['error' => 'Kimlik doğrulama gereklidir.'], 401);
                return;
            }

            if ($session_role === DB_SLUG_CUSTOMER) {
                json_response(['error' => 'Öğrenciler not girişi yapamaz.'], 403);
                return;
            }

            $data = request();
            $student_id = (int) ($data['student_id'] ?? 0);
            $session_id = !empty($data['session_id']) ? (int) $data['session_id'] : null;
            $subject = trim((string) ($data['subject'] ?? ''));
            $grade_score = isset($data['grade_score']) ? (float) $data['grade_score'] : null;
            $feedback_notes = !empty($data['feedback_notes']) ? trim((string) $data['feedback_notes']) : null;

            if ($student_id <= 0) {
                json_response(['error' => 'Öğrenci seçilmelidir.'], 400);
                return;
            }
            if (empty($subject)) {
                json_response(['error' => 'Ders konusu veya ödev başlığı zorunludur.'], 400);
                return;
            }

            $this->load->library('migration');
            $this->migration->version(174);

            $now = date('Y-m-d H:i:s');
            $this->db->insert('student_evaluations', [
                'id_users_customer' => $student_id,
                'id_appointments' => $session_id,
                'subject' => $subject,
                'grade_score' => $grade_score,
                'feedback_notes' => $feedback_notes,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            json_response([
                'success' => true,
                'message' => 'Öğrenci değerlendirmesi başarıyla kaydedildi.',
                'evaluation_id' => $this->db->insert_id(),
            ], 201);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    /* -------------------------------------------------------------------------
     * 9. PROFESYONEL HİZMETLER (HUKUK, DANIŞMANLIK & GAYRİMENKUL)
     * ------------------------------------------------------------------------- */

    public function save_legal_case(): void
    {
        try {
            $session_role = session('role_slug');
            $session_user_id = (int) session('user_id');

            if (!$session_user_id) {
                json_response(['error' => 'Kimlik doğrulama gereklidir.'], 401);
                return;
            }

            if ($session_role === DB_SLUG_CUSTOMER) {
                json_response(['error' => 'Müvekkiller doğrudan dava kaydı açamaz.'], 403);
                return;
            }

            $post = request();
            $client_id = (int) ($post['id_users_client'] ?? 0);
            $case_number = trim((string) ($post['case_number'] ?? ''));
            $court_name = trim((string) ($post['court_name'] ?? ''));
            $case_subject = trim((string) ($post['case_subject'] ?? ''));
            $opposing_party = trim((string) ($post['opposing_party'] ?? ''));
            $case_type = trim((string) ($post['case_type'] ?? 'civil'));
            $hearing_datetime = !empty($post['hearing_datetime']) ? date('Y-m-d H:i:s', strtotime($post['hearing_datetime'])) : null;

            if ($client_id <= 0) {
                json_response(['error' => 'Müvekkil seçilmelidir.'], 400);
                return;
            }
            if (empty($case_number) || empty($court_name)) {
                json_response(['error' => 'Esas no ve mahkeme bilgisi zorunludur.'], 400);
                return;
            }
            if (empty($case_subject)) {
                json_response(['error' => 'Dava konusu zorunludur.'], 400);
                return;
            }

            $this->load->library('migration');
            $this->migration->version(176);

            $now = date('Y-m-d H:i:s');
            $this->db->insert('legal_cases', [
                'case_number' => $case_number,
                'court_name' => $court_name,
                'id_users_client' => $client_id,
                'opposing_party' => $opposing_party,
                'case_type' => $case_type,
                'case_subject' => $case_subject,
                'hearing_datetime' => $hearing_datetime,
                'status' => 'open',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            json_response([
                'success' => true,
                'message' => 'Dava dosyası başarıyla kaydedildi.',
                'case_id' => $this->db->insert_id(),
            ], 201);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function save_consulting_time_log(): void
    {
        try {
            $session_role = session('role_slug');
            $session_user_id = (int) session('user_id');

            if (!$session_user_id) {
                json_response(['error' => 'Kimlik doğrulama gereklidir.'], 401);
                return;
            }

            if ($session_role === DB_SLUG_CUSTOMER) {
                json_response(['error' => 'Müşteriler zaman kaydı oluşturamaz.'], 403);
                return;
            }

            $post = request();
            $client_id = (int) ($post['id_users_client'] ?? 0);
            $consultant_id = (int) ($post['id_users_consultant'] ?? 0);
            $project_name = trim((string) ($post['project_name'] ?? ''));
            $duration_minutes = (int) ($post['duration_minutes'] ?? 0);
            $hourly_rate = isset($post['hourly_rate']) ? (float) $post['hourly_rate'] : 0.00;
            $work_description = trim((string) ($post['work_description'] ?? ''));
            $is_billable = !empty($post['is_billable']) ? 1 : 0;

            if ($client_id <= 0 || $consultant_id <= 0) {
                json_response(['error' => 'Müşteri ve danışman seçilmelidir.'], 400);
                return;
            }
            if (empty($project_name)) {
                json_response(['error' => 'Proje veya konu başlığı zorunludur.'], 400);
                return;
            }
            if ($duration_minutes <= 0) {
                json_response(['error' => 'Süre 0 dakikadan büyük olmalıdır.'], 400);
                return;
            }

            $total_fee = round(($duration_minutes / 60) * $hourly_rate, 2);

            $this->load->library('migration');
            $this->migration->version(176);

            $now = date('Y-m-d H:i:s');
            $this->db->insert('consulting_time_logs', [
                'id_users_client' => $client_id,
                'id_users_consultant' => $consultant_id,
                'project_name' => $project_name,
                'duration_minutes' => $duration_minutes,
                'hourly_rate' => $hourly_rate,
                'total_fee' => $total_fee,
                'work_description' => $work_description,
                'is_billable' => $is_billable,
                'status' => 'logged',
                'created_at' => $now,
            ]);

            json_response([
                'success' => true,
                'message' => 'Zaman kaydı başarıyla oluşturuldu.',
                'log_id' => $this->db->insert_id(),
                'total_fee' => $total_fee,
            ], 201);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }

    public function save_real_estate_listing(): void
    {
        try {
            $session_role = session('role_slug');
            $session_user_id = (int) session('user_id');

            if (!$session_user_id) {
                json_response(['error' => 'Kimlik doğrulama gereklidir.'], 401);
                return;
            }

            if ($session_role === DB_SLUG_CUSTOMER) {
                json_response(['error' => 'Müşteriler ilan ekleyemez.'], 403);
                return;
            }

            $post = request();
            $title = trim((string) ($post['title'] ?? ''));
            $listing_type = in_array($post['listing_type'] ?? '', ['sale', 'rent']) ? $post['listing_type'] : 'sale';
            $property_type = trim((string) ($post['property_type'] ?? 'apartment'));
            $price = isset($post['price']) ? (float) $post['price'] : 0.00;
            $city = trim((string) ($post['city'] ?? 'İstanbul'));
            $district = trim((string) ($post['district'] ?? ''));
            $square_meters = !empty($post['square_meters']) ? (int) $post['square_meters'] : null;
            $agent_id = !empty($post['id_users_agent']) ? (int) $post['id_users_agent'] : null;

            if (empty($title)) {
                json_response(['error' => 'İlan başlığı zorunludur.'], 400);
                return;
            }
            if ($price <= 0) {
                json_response(['error' => 'Geçerli bir fiyat girilmelidir.'], 400);
                return;
            }
            if (empty($district)) {
                json_response(['error' => 'İlçe bilgisi zorunludur.'], 400);
                return;
            }

            $this->load->library('migration');
            $this->migration->version(176);

            $listing_code = 'LST-' . strtoupper(bin2hex(random_bytes(3)));
            $now = date('Y-m-d H:i:s');

            $insertData = [
                'title' => $title,
                'slug' => strtolower($listing_code),
                'listing_type' => $listing_type,
                'price' => $price,
                'city' => $city,
                'district' => $district,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($this->db->field_exists('listing_code', 'real_estate_listings')) {
                $insertData['listing_code'] = $listing_code;
            }
            if ($this->db->field_exists('property_type', 'real_estate_listings')) {
                $insertData['property_type'] = $property_type;
            }
            if ($this->db->field_exists('square_meters', 'real_estate_listings')) {
                $insertData['square_meters'] = $square_meters;
            }
            if ($this->db->field_exists('gross_m2', 'real_estate_listings')) {
                $insertData['gross_m2'] = $square_meters ?: 0;
            }
            if ($this->db->field_exists('id_users_agent', 'real_estate_listings')) {
                $insertData['id_users_agent'] = $agent_id;
            }
            if ($this->db->field_exists('agent_user_id', 'real_estate_listings')) {
                $insertData['agent_user_id'] = $agent_id;
            }

            $this->db->insert('real_estate_listings', $insertData);

            json_response([
                'success' => true,
                'message' => 'Gayrimenkul ilanı başarıyla kaydedildi.',
                'listing_id' => $this->db->insert_id(),
                'listing_code' => $listing_code,
            ], 201);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 400);
        }
    }
}



