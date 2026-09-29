<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Multi-Vertical Enterprise Operations Controller
 * ---------------------------------------------------------------------------- */

class Verticals extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->library('accounts');
        $this->load->model('roles_model');
        $this->load->model('gift_cards_model');
        $this->load->model('restaurant_model');
        $this->load->model('sports_matches_model');
        $this->load->model('clinical_records_model');
        $this->load->model('vehicles_model');
        $this->load->model('work_orders_model');
        $this->load->model('digital_waivers_model');
        $this->load->model('event_tickets_model');
        $this->load->model('customers_model');
        $this->load->model('appointments_model');
    }

    private function require_auth(string $dest_url): int
    {
        session(['dest_url' => site_url($dest_url)]);
        $user_id = (int) session('user_id');
        if (!$user_id) {
            redirect('login');
            exit;
        }
        if (session('role_slug') === DB_SLUG_CUSTOMER) {
            redirect('customer_portal');
            exit;
        }
        return $user_id;
    }

    /* -------------------------------------------------------------------------
     * 1. GÜZELLİK & SPA - HEDİYE KARTLARI & KAPORA YÖNETİMİ
     * ------------------------------------------------------------------------- */
    public function gift_cards(): void
    {
        $user_id = $this->require_auth('verticals/gift_cards');

        html_vars([
            'page_title' => 'Hediye Kartları & Kapora Yönetimi',
            'active_menu' => 'verticals_gift_cards',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug(session('role_slug')),
        ]);

        $cards = $this->db->order_by('created_at DESC')->get('gift_cards', 100)->result_array();
        $deposits = $this->db
            ->select('a.id, a.start_datetime, a.deposit_amount, a.deposit_status, a.deposit_paid_at, c.first_name, c.last_name, s.name as service_name')
            ->from('appointments a')
            ->join('users c', 'c.id = a.id_users_customer', 'left')
            ->join('services s', 's.id = a.id_services', 'left')
            ->where('a.deposit_amount >', 0)
            ->order_by('a.start_datetime DESC')
            ->get()
            ->result_array();

        $this->load->model('payment_settings_model');
        $payment_settings = $this->payment_settings_model->get_settings();

        $this->load->view('pages/vertical_gift_cards', [
            'cards' => $cards,
            'deposits' => $deposits,
            'customers' => $this->customers_model->get(null, 100, null, 'first_name ASC'),
            'payment_settings' => $payment_settings,
        ]);
    }

    /**
     * Tenant-controlled toggle & configuration for appointment deposit (kapora).
     */
    public function save_deposit_settings(): void
    {
        try {
            method('post');
            if (cannot('edit', PRIV_SYSTEM_SETTINGS) && !in_array(session('role_slug'), ['admin', 'provider'], true)) {
                abort(403, 'Forbidden');
            }

            $require_deposit = filter_var($this->input->post('require_deposit'), FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            $deposit_type = $this->input->post('deposit_type') ?: 'fixed';
            $deposit_value = (float) $this->input->post('deposit_value');

            if ($require_deposit === 1) {
                if (!in_array($deposit_type, ['fixed', 'percentage'], true)) {
                    throw new InvalidArgumentException('Geçersiz kapora türü (Sabit veya Yüzde olmalıdır).');
                }
                if ($deposit_value <= 0) {
                    throw new InvalidArgumentException('Kapora değeri 0\'dan büyük olmalıdır.');
                }
                if ($deposit_type === 'percentage' && $deposit_value > 100) {
                    throw new InvalidArgumentException('Yüzdelik kapora %100\'den büyük olamaz.');
                }
            }

            $this->load->model('payment_settings_model');
            $this->payment_settings_model->save_settings([
                'require_deposit' => $require_deposit,
                'deposit_type' => $deposit_type,
                'deposit_value' => $deposit_value,
            ]);

            json_response([
                'success' => true,
                'message' => $require_deposit ? 'Kapora güvence sistemi aktifleştirildi.' : 'Kapora sistemi devre dışı bırakıldı.',
                'settings' => [
                    'require_deposit' => $require_deposit,
                    'deposit_type' => $deposit_type,
                    'deposit_value' => $deposit_value,
                ]
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Redeem gift card balance.
     */
    public function redeem_gift_card(): void
    {
        $this->ensure_authenticated();

        try {
            $raw = file_get_contents('php://input');
            $post = !empty($raw) ? json_decode($raw, true) : $this->input->post();
            if (!is_array($post)) {
                $post = $this->input->post() ?: [];
            }

            $card_id = $post['card_id'] ?? $post['id'] ?? null;
            $code = $post['code'] ?? null;
            $amount = (float) ($post['amount'] ?? 0.00);
            $notes = $post['notes'] ?? $post['note'] ?? null;
            $appointment_id = !empty($post['appointment_id']) ? (int) $post['appointment_id'] : null;
            $adisyon_id = !empty($post['adisyon_id']) ? (int) $post['adisyon_id'] : null;

            if ($amount <= 0) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'success' => false,
                        'message' => 'Düşülecek tutar 0\'dan büyük olmalıdır.'
                    ]));
                return;
            }

            // Fetch gift card record
            $card = null;
            if ($card_id) {
                $card = $this->db->get_where('gift_cards', ['id' => (int) $card_id])->row_array();
            }
            if (!$card && !empty($code)) {
                $card = $this->db->get_where('gift_cards', ['code' => strtoupper(trim($code))])->row_array();
            }

            if (!$card) {
                $this->output
                    ->set_status_header(404)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'success' => false,
                        'message' => 'Hediye kartı bulunamadı.'
                    ]));
                return;
            }

            // Expiry date check
            if (!empty($card['expires_at']) && $card['expires_at'] < date('Y-m-d')) {
                $this->db->where('id', $card['id'])->update('gift_cards', [
                    'status' => 'expired',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'success' => false,
                        'message' => 'Hediye kartının kullanım süresi dolmuştur.'
                    ]));
                return;
            }

            if ($card['status'] !== 'active') {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'success' => false,
                        'message' => 'Hediye kartı aktif değil (Durum: ' . $card['status'] . ').'
                    ]));
                return;
            }

            $current_balance = (float) $card['current_balance'];
            if ($current_balance < $amount) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'success' => false,
                        'message' => 'Yetersiz bakiye. Mevcut bakiye: ' . number_format($current_balance, 2) . ' TL',
                        'current_balance' => $current_balance,
                    ]));
                return;
            }

            $new_balance = round($current_balance - $amount, 2);
            $new_status = ($new_balance <= 0.001) ? 'depleted' : 'active';
            $now = date('Y-m-d H:i:s');

            $this->db->trans_start();

            // Deduct balance
            $this->db->where('id', $card['id'])->update('gift_cards', [
                'current_balance' => $new_balance,
                'status' => $new_status,
                'updated_at' => $now,
            ]);

            // Insert transaction log into gift_card_transactions (or equivalent table) if exists
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
                    'id_appointments' => $appointment_id,
                    'id_adisyons' => $adisyon_id,
                    'redeemed_amount' => $amount,
                    'redeemed_at' => $now,
                ];
                if ($this->db->field_exists('notes', 'gift_card_redemptions')) {
                    $redemption_data['notes'] = $notes;
                }
                $this->db->insert('gift_card_redemptions', $redemption_data);
            }

            $this->db->trans_complete();

            if ($this->db->trans_status() === false) {
                $this->output
                    ->set_status_header(500)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'success' => false,
                        'message' => 'Hediye kartı işlemi gerçekleştirilirken bir hata oluştu.'
                    ]));
                return;
            }

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => true,
                    'message' => 'Bakiye başarıyla düşüldü.',
                    'new_balance' => $new_balance,
                    'card_id' => (int) $card['id'],
                    'status' => $new_status,
                ]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => $e->getMessage(),
                ]));
        }
    }

    protected function ensure_authenticated(): void
    {
        if (!session('user_id')) {
            $this->output
                ->set_status_header(401)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
            if (ENVIRONMENT === 'testing') {
                throw new RuntimeException('Unauthorized', 401);
            }
            exit;
        }

        if (session('role_slug') === DB_SLUG_CUSTOMER) {
            $this->output
                ->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => 'Forbidden']));
            if (ENVIRONMENT === 'testing') {
                throw new RuntimeException('Forbidden', 403);
            }
            exit;
        }
    }

    /* -------------------------------------------------------------------------
     * 2. RESTORAN & KAFE - KDS (KITCHEN DISPLAY SYSTEM)
     * ------------------------------------------------------------------------- */
    public function kds(): void
    {
        $user_id = $this->require_auth('verticals/kds');

        html_vars([
            'page_title' => 'Mutfak & Bar Ekranı (KDS)',
            'active_menu' => 'verticals_kds',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug(session('role_slug')),
        ]);

        $orders = $this->restaurant_model->get_active_kitchen_orders('all');

        $this->load->view('pages/vertical_kds', [
            'orders' => $orders,
        ]);
    }

    /* -------------------------------------------------------------------------
     * 3. SPOR & KORT & HALI SAHA - MAÇLAR & TURNİKE GEÇİŞ
     * ------------------------------------------------------------------------- */
    public function sports(): void
    {
        $user_id = $this->require_auth('verticals/sports');

        html_vars([
            'page_title' => 'Kortlar, Açık Maçlar & Turnike Geçiş Paneli',
            'active_menu' => 'verticals_sports',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug(session('role_slug')),
        ]);

        $matches = $this->sports_matches_model->get_open_matches();
        $stations = $this->db->get_where('stations', ['is_active' => 1])->result_array();
        $checkins = $this->db->order_by('entry_timestamp DESC')->get('checkin_logs', 30)->result_array();
        $customers = $this->customers_model->get();

        $this->load->view('pages/vertical_sports_matches', [
            'matches' => $matches,
            'stations' => $stations,
            'checkins' => $checkins,
            'customers' => $customers,
        ]);
    }

    public function create_sports_match(): void
    {
        $this->ensure_authenticated();

        try {
            $raw = file_get_contents('php://input');
            $data = !empty($raw) ? json_decode($raw, true) : null;
            if (!is_array($data)) {
                $data = request() ?: ($this->input->post() ?: []);
            }

            if (empty($data['created_by_user_id'])) {
                $data['created_by_user_id'] = (int) session('user_id');
            }

            $match_id = $this->sports_matches_model->create_match($data);

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => true,
                    'match_id' => $match_id,
                ]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'error' => $e->getMessage(),
                ]));
        }
    }

    public function join_sports_match(): void
    {
        $this->ensure_authenticated();

        try {
            $raw = file_get_contents('php://input');
            $data = !empty($raw) ? json_decode($raw, true) : null;
            if (!is_array($data)) {
                $data = request() ?: ($this->input->post() ?: []);
            }

            $match_id = (int) ($data['match_id'] ?? request('match_id'));
            $customer_id = (int) ($data['id_users_customer'] ?? $data['customer_id'] ?? request('id_users_customer') ?? request('customer_id'));
            $team = $data['team'] ?? request('team');
            $skill_level = $data['skill_level'] ?? request('skill_level');

            if (!$match_id || !$customer_id) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'success' => false,
                        'message' => 'Maç ID ve Oyuncu/Müşteri seçimi zorunludur.',
                    ]));
                return;
            }

            $res = $this->sports_matches_model->join_match($match_id, $customer_id, $team, $skill_level);

            $this->output
                ->set_status_header(($res['success'] ?? false) ? 200 : 400)
                ->set_content_type('application/json')
                ->set_output(json_encode($res));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => $e->getMessage(),
                ]));
        }
    }

    public function verify_turnstile(): void
    {
        $this->ensure_authenticated();

        try {
            $raw = file_get_contents('php://input');
            $data = !empty($raw) ? json_decode($raw, true) : null;
            if (!is_array($data)) {
                $data = request() ?: ($this->input->post() ?: []);
            }

            $token = $data['token'] ?? $data['access_token'] ?? request('token') ?? request('access_token');
            $gate_id = $data['gate_id'] ?? request('gate_id');

            if (empty($token)) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'access_granted' => false,
                        'relay_trigger' => 0,
                        'reason' => 'Access token zorunludur.',
                    ]));
                return;
            }

            $res = $this->sports_matches_model->verify_turnstile_access((string) $token, $gate_id);

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode($res));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'access_granted' => false,
                    'relay_trigger' => 0,
                    'error' => $e->getMessage(),
                ]));
        }
    }

    /* -------------------------------------------------------------------------
     * 4. SAĞLIK & KLİNİK - EHR / SOAP KLİNİK DOSYASI & TELEHEALTH
     * ------------------------------------------------------------------------- */
    public function clinic(): void
    {
        $user_id = $this->require_auth('verticals/clinic');

        html_vars([
            'page_title' => 'Klinik Kayıtları, SOAP Notları & Telehealth',
            'active_menu' => 'verticals_clinic',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug(session('role_slug')),
        ]);

        $records = $this->db
            ->select('cr.id, cr.id_users_customer, cr.id_appointments, cr.id_users_provider, cr.record_type, cr.subjective, cr.objective, cr.assessment, cr.plan, cr.is_confidential, cr.created_at, cr.updated_at, c.first_name as patient_first_name, c.last_name as patient_last_name, c.phone_number as patient_phone, p.first_name as doc_first_name, p.last_name as doc_last_name, p.first_name as provider_first_name, p.last_name as provider_last_name, a.start_datetime as appointment_date')
            ->from('clinical_records cr')
            ->join('users c', 'c.id = cr.id_users_customer', 'left')
            ->join('users p', 'p.id = cr.id_users_provider', 'left')
            ->join('appointments a', 'a.id = cr.id_appointments', 'left')
            ->order_by('cr.created_at DESC')
            ->limit(50)
            ->get()
            ->result_array();

        $patients = $this->customers_model->get(null, 100, null, 'first_name ASC');
        $providers = $this->db->get_where('users', ['id_roles' => 2])->result_array();
        $appointments = $this->db
            ->select('a.id, a.id_users_customer, a.id_users_provider, a.start_datetime, a.end_datetime, s.name as service_name, c.first_name as customer_first_name, c.last_name as customer_last_name')
            ->from('appointments a')
            ->join('services s', 's.id = a.id_services', 'left')
            ->join('users c', 'c.id = a.id_users_customer', 'left')
            ->where('a.is_unavailability', 0)
            ->order_by('a.start_datetime DESC')
            ->limit(100)
            ->get()
            ->result_array();

        $this->load->view('pages/vertical_clinical_records', [
            'records' => $records,
            'patients' => $patients,
            'providers' => $providers,
            'appointments' => $appointments,
        ]);
    }

    public function add_clinical_record(): void
    {
        $this->ensure_authenticated();

        try {
            $raw = file_get_contents('php://input');
            $data = !empty($raw) ? json_decode($raw, true) : null;
            if (!is_array($data)) {
                $data = $this->input->post() ?: [];
            }

            $customer_id = !empty($data['id_users_customer']) ? (int) $data['id_users_customer'] : (!empty($data['customer_id']) ? (int) $data['customer_id'] : null);
            $provider_id = !empty($data['id_users_provider']) ? (int) $data['id_users_provider'] : (!empty($data['provider_id']) ? (int) $data['provider_id'] : null);

            if (!$customer_id || !$provider_id) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'success' => false,
                        'message' => 'Danışan/hasta ve hekim/uzman seçimi zorunludur.'
                    ]));
                return;
            }

            $data['id_users_customer'] = $customer_id;
            $data['id_users_provider'] = $provider_id;

            $this->load->model('clinical_records_model');
            $record_id = $this->clinical_records_model->add_record($data);

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => true,
                    'message' => 'Klinik dosya başarıyla kaydedildi.',
                    'record_id' => $record_id,
                ]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => $e->getMessage(),
                ]));
        }
    }

    public function get_patient_history(int $customer_id): void
    {
        $this->ensure_authenticated();

        try {
            $this->load->model('clinical_records_model');
            $records = $this->clinical_records_model->get_patient_history($customer_id);
            $insurance = $this->clinical_records_model->get_patient_insurance($customer_id);

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => true,
                    'customer_id' => $customer_id,
                    'clinical_records' => $records,
                    'insurance' => $insurance,
                ]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => $e->getMessage(),
                ]));
        }
    }

    public function save_patient_insurance(): void
    {
        $this->ensure_authenticated();

        try {
            $raw = file_get_contents('php://input');
            $data = !empty($raw) ? json_decode($raw, true) : null;
            if (!is_array($data)) {
                $data = $this->input->post() ?: [];
            }

            $customer_id = !empty($data['customer_id']) ? (int) $data['customer_id'] : (!empty($data['id_users_customer']) ? (int) $data['id_users_customer'] : null);

            if (!$customer_id) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'success' => false,
                        'message' => 'Hasta / Danışan seçimi zorunludur.'
                    ]));
                return;
            }

            $this->load->model('clinical_records_model');
            $id = $this->clinical_records_model->save_patient_insurance($customer_id, $data);

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => true,
                    'message' => 'Sigorta bilgisi başarıyla kaydedildi.',
                    'insurance_id' => $id,
                ]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => $e->getMessage(),
                ]));
        }
    }

    public function get_telehealth_link(int $appointment_id): void
    {
        $this->ensure_authenticated();

        try {
            $this->load->model('clinical_records_model');
            $link = $this->clinical_records_model->get_or_create_telehealth_link($appointment_id);

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => true,
                    'appointment_id' => $appointment_id,
                    'telehealth_url' => $link,
                ]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => $e->getMessage(),
                ]));
        }
    }

    /* -------------------------------------------------------------------------
     * 5. OTOMOTİV & SERVİS - ARAÇ 360, DVI EKSPERTİZ & İŞ EMİRLERİ
     * ------------------------------------------------------------------------- */
    public function automotive(): void
    {
        $user_id = $this->require_auth('verticals/automotive');

        html_vars([
            'page_title' => 'Oto Servis, Araç Sicili & DVI Ekspertiz',
            'active_menu' => 'verticals_automotive',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug(session('role_slug')),
        ]);

        $vehicles = $this->db
            ->select('v.*, c.first_name as owner_first_name, c.last_name as owner_last_name, c.phone_number')
            ->from('customer_vehicles v')
            ->join('users c', 'c.id = v.id_users_customer', 'left')
            ->order_by('v.created_at DESC')
            ->limit(50)
            ->get()
            ->result_array();

        $work_orders = $this->db
            ->select('wo.*, v.plate_number, v.brand, v.model')
            ->from('work_orders wo')
            ->join('customer_vehicles v', 'v.id = wo.id_vehicles', 'left')
            ->order_by('wo.created_at DESC')
            ->limit(50)
            ->get()
            ->result_array();

        $data = [
            'vehicles' => $vehicles,
            'work_orders' => $work_orders,
            'customers' => $this->customers_model->get(null, 100, null, 'first_name ASC'),
            'technicians' => $this->db->get_where('users', ['role_slug !=' => 'customer'])->result_array(),
        ];

        $this->load->view('pages/vertical_vehicles_dvi', $data);
    }

    public function add_vehicle(): void
    {
        $this->ensure_authenticated();

        try {
            $raw = file_get_contents('php://input');
            $data = !empty($raw) ? json_decode($raw, true) : null;
            if (!is_array($data)) {
                $data = $this->input->post() ?: [];
            }

            $customer_id = !empty($data['id_users_customer']) ? (int) $data['id_users_customer'] : (!empty($data['customer_id']) ? (int) $data['customer_id'] : null);
            $plate_number = trim($data['plate_number'] ?? '');
            $brand = trim($data['brand'] ?? '');
            $model = trim($data['model'] ?? '');

            if (!$customer_id || empty($plate_number) || empty($brand) || empty($model)) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'success' => false,
                        'message' => 'Plaka, marka, model ve araç sahibi (müşteri) seçimi zorunludur.',
                    ]));
                return;
            }

            $data['id_users_customer'] = $customer_id;
            $data['plate_number'] = $plate_number;
            $data['brand'] = $brand;
            $data['model'] = $model;

            $this->load->model('vehicles_model');
            $vehicle_id = $this->vehicles_model->add_vehicle($data);

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => true,
                    'vehicle_id' => $vehicle_id,
                    'message' => 'Araç başarıyla kaydedildi.',
                ]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => $e->getMessage(),
                ]));
        }
    }

    public function create_work_order(): void
    {
        $this->ensure_authenticated();

        try {
            $raw = file_get_contents('php://input');
            $data = !empty($raw) ? json_decode($raw, true) : null;
            if (!is_array($data)) {
                $data = $this->input->post() ?: [];
            }

            $vehicle_id = !empty($data['id_vehicles']) ? (int) $data['id_vehicles'] : (!empty($data['vehicle_id']) ? (int) $data['vehicle_id'] : null);

            if (!$vehicle_id) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'success' => false,
                        'message' => 'İş emri için araç seçimi zorunludur.',
                    ]));
                return;
            }

            $data['id_vehicles'] = $vehicle_id;

            if (!empty($data['customer_complaint']) && empty($data['labor_items'])) {
                $data['labor_items'] = [['description' => $data['customer_complaint']]];
            }

            $this->load->model('work_orders_model');
            $wo = $this->work_orders_model->create_work_order($data);

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => true,
                    'work_order' => $wo,
                    'message' => 'İş emri başarıyla başlatıldı.',
                ]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => $e->getMessage(),
                ]));
        }
    }

    public function update_work_order_status(): void
    {
        $this->ensure_authenticated();

        try {
            $raw = file_get_contents('php://input');
            $data = !empty($raw) ? json_decode($raw, true) : null;
            if (!is_array($data)) {
                $data = $this->input->post() ?: [];
            }

            $id = !empty($data['id']) ? (int) $data['id'] : (!empty($data['work_order_id']) ? (int) $data['work_order_id'] : 0);
            $status = trim((string) ($data['status'] ?? ''));

            if (!$id || empty($status)) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'success' => false,
                        'message' => 'İş emri ID ve güncellenecek durum bilgisi zorunludur.',
                    ]));
                return;
            }

            $this->load->model('work_orders_model');
            $this->work_orders_model->update_status($id, $status);

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => true,
                    'work_order_id' => $id,
                    'status' => $status,
                    'message' => 'İş emri durumu güncellendi.',
                ]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => $e->getMessage(),
                ]));
        }
    }

    public function save_vehicle_inspection(): void
    {
        $this->ensure_authenticated();

        try {
            $raw = file_get_contents('php://input');
            $data = !empty($raw) ? json_decode($raw, true) : null;
            if (!is_array($data)) {
                $data = $this->input->post() ?: [];
            }

            $vehicle_id = !empty($data['id_vehicles']) ? (int) $data['id_vehicles'] : (!empty($data['vehicle_id']) ? (int) $data['vehicle_id'] : null);

            if (!$vehicle_id) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'success' => false,
                        'message' => 'Ekspertiz için araç seçimi zorunludur.',
                    ]));
                return;
            }

            $data['id_vehicles'] = $vehicle_id;

            if (empty($data['inspector_id'])) {
                $data['inspector_id'] = (int) (session('user_id') ?: 1);
            }

            $this->load->model('work_orders_model');
            $inspection = $this->work_orders_model->save_inspection($data);

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => true,
                    'inspection' => $inspection,
                    'token' => $inspection['customer_shared_token'] ?? null,
                    'customer_shared_token' => $inspection['customer_shared_token'] ?? null,
                    'message' => 'DVI ekspertiz raporu başarıyla kaydedildi.',
                    'report_url' => site_url('api/v1/verticals/automotive/inspections/' . ($inspection['customer_shared_token'] ?? '')),
                ]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => $e->getMessage(),
                ]));
        }
    }

    /* -------------------------------------------------------------------------
     * 6. DENEYİM & ETKİNLİK - DİJİTAL FERAGATNAME & BİLETLEME
     * ------------------------------------------------------------------------- */
    public function experience(): void
    {
        $user_id = $this->require_auth('verticals/experience');

        html_vars([
            'page_title' => 'Deneyimler, Dijital Feragatname & Biletleme',
            'active_menu' => 'verticals_experience',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug(session('role_slug')),
        ]);

        $waivers = $this->db->get('digital_waivers')->result_array();
        $signatures = $this->db
            ->select('ws.*, w.title as waiver_title')
            ->from('waiver_signatures ws')
            ->join('digital_waivers w', 'w.id = ws.id_waivers', 'left')
            ->order_by('ws.signed_at DESC')
            ->limit(50)
            ->get()
            ->result_array();

        $tickets = $this->db
            ->select('t.*, c.first_name, c.last_name, a.start_datetime')
            ->from('event_tickets t')
            ->join('users c', 'c.id = t.id_users_customer', 'left')
            ->join('appointments a', 'a.id = t.id_appointments', 'left')
            ->order_by('t.created_at DESC')
            ->limit(50)
            ->get()
            ->result_array();

        $customers = $this->customers_model->get(null, 100, null, 'first_name ASC');
        $appointments = $this->db
            ->select('a.*, s.name as service_name, c.first_name, c.last_name')
            ->from('appointments a')
            ->join('services s', 's.id = a.id_services', 'left')
            ->join('users c', 'c.id = a.id_users_customer', 'left')
            ->where('a.is_unavailability', 0)
            ->order_by('a.start_datetime DESC')
            ->limit(100)
            ->get()
            ->result_array();

        $this->load->view('pages/vertical_experience_waivers', [
            'waivers' => $waivers,
            'signatures' => $signatures,
            'tickets' => $tickets,
            'customers' => $customers,
            'appointments' => $appointments,
        ]);
    }

    public function save_digital_waiver(): void
    {
        $this->ensure_authenticated();

        try {
            $raw = file_get_contents('php://input');
            $data = !empty($raw) ? json_decode($raw, true) : null;
            if (!is_array($data)) {
                $data = $this->input->post() ?: [];
            }

            if (empty($data['title']) || empty($data['content_html'])) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'success' => false,
                        'message' => 'Feragatname başlığı ve içerik metni zorunludur.',
                    ]));
                return;
            }

            $data['is_mandatory'] = !empty($data['is_mandatory']) ? 1 : 0;
            $waiver_id = $this->digital_waivers_model->save_waiver($data);

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => true,
                    'waiver_id' => $waiver_id,
                    'message' => 'Feragatname şablonu başarıyla kaydedildi.',
                ]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => $e->getMessage(),
                ]));
        }
    }

    public function sign_digital_waiver(): void
    {
        $this->ensure_authenticated();

        try {
            $raw = file_get_contents('php://input');
            $data = !empty($raw) ? json_decode($raw, true) : null;
            if (!is_array($data)) {
                $data = $this->input->post() ?: [];
            }

            $waiver_id = !empty($data['id_waivers']) ? (int) $data['id_waivers'] : (!empty($data['waiver_id']) ? (int) $data['waiver_id'] : null);
            $signer_name = trim($data['signer_full_name'] ?? '');

            if (!$waiver_id || empty($signer_name)) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'success' => false,
                        'message' => 'Feragatname şablonu ve imzacı adı zorunludur.',
                    ]));
                return;
            }

            $data['id_waivers'] = $waiver_id;
            $data['signer_full_name'] = $signer_name;
            $data['ip_address'] = $this->input->ip_address() ?: '127.0.0.1';

            if (empty($data['signature_data'])) {
                $data['signature_data'] = 'DIGITAL_ACK_ACCEPTED_' . date('Y-m-d_H:i:s');
            }

            $sig_id = $this->digital_waivers_model->sign_waiver($data);

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => true,
                    'signature_id' => $sig_id,
                    'message' => 'Feragatname başarıyla imzalandı.',
                ]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => $e->getMessage(),
                ]));
        }
    }

    public function issue_event_ticket(): void
    {
        $this->ensure_authenticated();

        try {
            $raw = file_get_contents('php://input');
            $data = !empty($raw) ? json_decode($raw, true) : null;
            if (!is_array($data)) {
                $data = $this->input->post() ?: [];
            }

            $customer_id = (int) (!empty($data['id_users_customer']) ? $data['id_users_customer'] : ($data['customer_id'] ?? 0));
            $appointment_id = !empty($data['id_appointments']) ? (int) $data['id_appointments'] : (!empty($data['appointment_id']) ? (int) $data['appointment_id'] : null);
            $ticket_type = trim($data['ticket_type'] ?? ($data['seat_label'] ?? 'standard'));
            $price = isset($data['price']) && $data['price'] !== '' ? (float) $data['price'] : 0.00;

            if (!$customer_id) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'success' => false,
                        'message' => 'Bilet kesilecek danışan/müşteri seçimi zorunludur.',
                    ]));
                return;
            }

            do {
                $ticket_code = 'TKT-' . strtoupper(bin2hex(random_bytes(4)));
                $exists = $this->db->get_where('event_tickets', ['ticket_code' => $ticket_code])->num_rows() > 0;
            } while ($exists);

            $seat_label = !empty($data['seat_or_slot_label']) ? trim($data['seat_or_slot_label']) : (!empty($ticket_type) ? trim($ticket_type) : null);

            $ticket_record = [
                'ticket_code' => $ticket_code,
                'id_appointments' => $appointment_id,
                'id_users_customer' => $customer_id,
                'seat_or_slot_label' => $seat_label,
                'ticket_type' => $ticket_type,
                'price' => $price,
                'status' => 'valid',
                'used_at' => null,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            $this->db->insert('event_tickets', $ticket_record);
            $ticket_id = (int) $this->db->insert_id();
            $ticket_record['id'] = $ticket_id;

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => true,
                    'ticket_id' => $ticket_id,
                    'ticket_code' => $ticket_code,
                    'ticket' => $ticket_record,
                    'message' => 'Bilet başarıyla üretildi.',
                ]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => $e->getMessage(),
                ]));
        }
    }

    public function validate_event_ticket(): void
    {
        $this->ensure_authenticated();

        try {
            $raw = file_get_contents('php://input');
            $data = !empty($raw) ? json_decode($raw, true) : null;
            if (!is_array($data)) {
                $data = $this->input->post() ?: [];
            }

            $ticket_code = strtoupper(trim($data['ticket_code'] ?? ($data['code'] ?? '')));
            if (empty($ticket_code)) {
                $this->output
                    ->set_status_header(200)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'valid' => false,
                        'message' => 'Geçersiz bilet kodu.',
                    ]));
                return;
            }

            $ticket = $this->db
                ->select('t.*, a.start_datetime, a.end_datetime, s.name as service_name, c.first_name, c.last_name, c.phone_number')
                ->from('event_tickets t')
                ->join('appointments a', 'a.id = t.id_appointments', 'left')
                ->join('services s', 's.id = a.id_services', 'left')
                ->join('users c', 'c.id = t.id_users_customer', 'left')
                ->where('t.ticket_code', $ticket_code)
                ->get()
                ->row_array();

            if (!$ticket) {
                $this->output
                    ->set_status_header(200)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'valid' => false,
                        'message' => 'Geçersiz bilet kodu.',
                    ]));
                return;
            }

            if ($ticket['status'] === 'used') {
                $this->output
                    ->set_status_header(200)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'valid' => false,
                        'message' => 'Bu bilet daha önce kullanılmış.',
                        'ticket' => $ticket,
                    ]));
                return;
            }

            if ($ticket['status'] === 'cancelled') {
                $this->output
                    ->set_status_header(200)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'valid' => false,
                        'message' => 'Bu bilet iptal edilmiştir.',
                        'ticket' => $ticket,
                    ]));
                return;
            }

            $now = date('Y-m-d H:i:s');
            $this->db->update('event_tickets', [
                'status' => 'used',
                'used_at' => $now,
            ], ['id' => $ticket['id']]);

            $ticket['status'] = 'used';
            $ticket['used_at'] = $now;

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'valid' => true,
                    'message' => 'Bilet geçerli! Giriş onaylandı.',
                    'ticket' => $ticket,
                ]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'valid' => false,
                    'message' => $e->getMessage(),
                ]));
        }
    }

    /* -------------------------------------------------------------------------
     * 6. OTEL, BUTİK KONAKLAMA, BUNGALOV & PANSİYON - ODA YÖNETİMİ & KAT HİZMETLERİ
     * ------------------------------------------------------------------------- */
    public function hospitality(): void
    {
        $user_id = $this->require_auth('verticals/hospitality');

        html_vars([
            'page_title' => 'Otel, Butik Konaklama & Kat Hizmetleri (Housekeeping)',
            'active_menu' => 'verticals_hospitality',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug(session('role_slug')),
        ]);

        $today_start = date('Y-m-d 00:00:00');
        $today_end = date('Y-m-d 23:59:59');

        // Fetch stations (rooms/units)
        $stations = $this->db->order_by('display_order ASC, name ASC')->get('stations')->result_array();

        // Active appointments (current guest check-in: today between start and end datetime)
        $active_appts = $this->db
            ->select('a.*, c.first_name as guest_first_name, c.last_name as guest_last_name, c.phone_number as guest_phone, c.email as guest_email, s.name as service_name')
            ->from('appointments a')
            ->join('users c', 'c.id = a.id_users_customer', 'left')
            ->join('services s', 's.id = a.id_services', 'left')
            ->where('a.id_stations IS NOT NULL')
            ->where('a.is_unavailability', 0)
            ->where('a.start_datetime <=', $today_end)
            ->where('a.end_datetime >=', $today_start)
            ->where_not_in('a.status', ['cancelled'])
            ->get()
            ->result_array();

        $appts_by_station = [];
        foreach ($active_appts as $appt) {
            $st_id = (int) $appt['id_stations'];
            if (!isset($appts_by_station[$st_id])) {
                $appts_by_station[$st_id] = $appt;
            }
        }

        $rooms = [];
        foreach ($stations as $st) {
            $roomId = (int) $st['id'];
            $roomStatus = $st['status'] ?? null;
            $metadata = [];
            if (!empty($st['notes']) && str_starts_with(trim($st['notes']), '{')) {
                $metadata = json_decode($st['notes'], true) ?: [];
            }

            if (empty($roomStatus)) {
                $roomStatus = $metadata['status'] ?? null;
            }

            $activeAppt = $appts_by_station[$roomId] ?? null;

            if (empty($roomStatus)) {
                $roomStatus = $activeAppt ? 'occupied' : 'clean';
            }

            $capacity = $st['capacity'] ?? ($metadata['capacity'] ?? 2);
            $notes = $st['notes'] ?? ($metadata['description'] ?? '');

            $guest_name = '';
            $guest_id = null;
            $checkout_date = '';
            if ($activeAppt) {
                $guest_name = trim(($activeAppt['guest_first_name'] ?? '') . ' ' . ($activeAppt['guest_last_name'] ?? ''));
                $guest_id = (int) $activeAppt['id_users_customer'];
                $checkout_date = date('d.m.Y H:i', strtotime($activeAppt['end_datetime']));
            }

            $rooms[] = [
                'id' => $roomId,
                'name' => $st['name'],
                'room_number' => preg_replace('/[^0-9]/', '', $st['name']) ?: (string) $roomId,
                'type' => $st['name'],
                'status' => $roomStatus,
                'capacity' => (int) $capacity,
                'floor' => 'Ana Bina',
                'guest_name' => $guest_name,
                'guest_id' => $guest_id,
                'checkout_date' => $checkout_date,
                'notes' => $notes,
                'description' => $notes,
                'active_guest' => $activeAppt ? [
                    'appointment_id' => (int) $activeAppt['id'],
                    'customer_id' => $guest_id,
                    'name' => $guest_name,
                    'phone' => $activeAppt['guest_phone'] ?? '',
                    'email' => $activeAppt['guest_email'] ?? '',
                    'start_datetime' => $activeAppt['start_datetime'],
                    'end_datetime' => $activeAppt['end_datetime'],
                    'service_name' => $activeAppt['service_name'] ?? 'Konaklama',
                ] : null,
            ];
        }

        // Fetch open folios / adisyons linked to rooms or guests
        $folios = [];
        $charges = [];
        if ($this->db->table_exists('adisyons')) {
            $this->db
                ->select('a.*, c.first_name, c.last_name, c.phone_number, c.email,
                          app.id_stations, s.name as station_name')
                ->from('adisyons a')
                ->join('users c', 'c.id = a.id_users_customer', 'left')
                ->join('appointments app', 'app.id = a.id_appointments', 'left')
                ->join('stations s', 's.id = app.id_stations', 'left')
                ->where('a.status', 'open')
                ->order_by('a.opened_at DESC');

            $folios = $this->db->get()->result_array();

            foreach ($folios as &$folio) {
                $folio_id = (int) $folio['id'];
                $folio['items'] = $this->db->get_where('adisyon_items', ['id_adisyons' => $folio_id])->result_array();
                $folio['guest_name'] = trim(($folio['first_name'] ?? '') . ' ' . ($folio['last_name'] ?? '')) ?: 'Misafir';
                $folio['room_name'] = $folio['station_name'] ?: 'Oda';

                foreach ($folio['items'] as $item) {
                    $charges[] = [
                        'id' => (int) $item['id'],
                        'adisyon_id' => $folio_id,
                        'room_number' => $folio['room_name'],
                        'room_name' => $folio['room_name'],
                        'guest_name' => $folio['guest_name'],
                        'category' => !empty($item['notes']) ? $item['notes'] : ($item['item_type'] ?? 'Ekstra'),
                        'item_name' => $item['name'] ?: 'Ekstra Harcama',
                        'amount' => (float) ($item['total_amount'] ?? $item['unit_price']),
                        'created_at' => $item['created_at'],
                    ];
                }
            }
            unset($folio);
        }

        // Fetch preferences
        $preferences = [];
        if ($this->db->table_exists('restaurant_guest_preferences')) {
            $rawPrefs = $this->db
                ->select('rgp.*, c.first_name, c.last_name, c.notes as customer_notes')
                ->from('restaurant_guest_preferences rgp')
                ->join('users c', 'c.id = rgp.id_users_customer', 'left')
                ->order_by('rgp.updated_at', 'DESC')
                ->limit(50)
                ->get()
                ->result_array();

            foreach ($rawPrefs as $rp) {
                $cNotes = [];
                if (!empty($rp['customer_notes']) && str_starts_with(trim($rp['customer_notes']), '{')) {
                    $cNotes = json_decode($rp['customer_notes'], true) ?: [];
                }
                $preferences[] = [
                    'id' => (int) $rp['id'],
                    'customer_id' => (int) $rp['id_users_customer'],
                    'guest_name' => trim(($rp['first_name'] ?? '') . ' ' . ($rp['last_name'] ?? '')) ?: 'Misafir',
                    'vip_level' => $rp['vip_level'] ?: 'Standart',
                    'pillow_choice' => $cNotes['pillow_type'] ?? 'Ortopedik',
                    'floor_preference' => $cNotes['floor_preference'] ?? ($rp['seating_preference'] ?: 'Yüksek Kat'),
                    'dietary_allergies' => $cNotes['dietary'] ?? ($rp['dietary_restrictions'] ?: 'Yok'),
                    'special_notes' => $cNotes['notes'] ?? ($rp['special_notes'] ?: ''),
                ];
            }
        }

        $customers = $this->customers_model->get(null, 150, null, 'first_name ASC');

        $this->load->view('pages/vertical_hospitality', [
            'rooms' => $rooms,
            'folios' => $folios,
            'charges' => $charges,
            'preferences' => $preferences,
            'customers' => $customers,
            'stations' => $stations,
        ]);
    }

    public function update_room_status(): void
    {
        $this->ensure_authenticated();

        try {
            $raw = file_get_contents('php://input');
            $data = !empty($raw) ? json_decode($raw, true) : null;
            if (!is_array($data)) {
                $data = $this->input->post() ?: [];
            }

            $room_id = (int) (!empty($data['room_id']) ? $data['room_id'] : ($data['station_id'] ?? ($data['id'] ?? 0)));
            $status = strtolower(trim((string) ($data['status'] ?? 'clean')));
            if ($status === 'available') {
                $status = 'clean';
            }

            $valid_statuses = ['clean', 'dirty', 'cleaning', 'occupied', 'maintenance'];
            if (!$room_id || !in_array($status, $valid_statuses, true)) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'success' => false,
                        'message' => 'Geçersiz oda ID veya durum (clean, dirty, cleaning, occupied, maintenance olmalıdır).',
                    ]));
                return;
            }

            $station = $this->db->get_where('stations', ['id' => $room_id])->row_array();
            if (!$station) {
                $this->output
                    ->set_status_header(404)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'success' => false,
                        'message' => 'Oda bulunamadı.',
                    ]));
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

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => true,
                    'room_id' => $room_id,
                    'status' => $status,
                    'message' => 'Oda durumu başarıyla güncellendi.',
                ]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => $e->getMessage(),
                ]));
        }
    }

    public function add_room_charge(): void
    {
        $this->ensure_authenticated();

        try {
            $raw = file_get_contents('php://input');
            $data = !empty($raw) ? json_decode($raw, true) : null;
            if (!is_array($data)) {
                $data = $this->input->post() ?: [];
            }

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
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'success' => false,
                        'message' => 'Oda/misafir bilgisi, harcama kalemi açıklaması ve geçerli bir tutar (0\'dan büyük) zorunludur.',
                    ]));
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

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => true,
                    'charge_id' => $charge_id,
                    'total_amount' => $total_amount,
                    'adisyon_id' => $adisyon_id,
                    'category' => $category,
                    'message' => 'Ekstra harcama folyoya kaydedildi.',
                ]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => $e->getMessage(),
                ]));
        }
    }

    public function save_guest_preferences(): void
    {
        $this->ensure_authenticated();

        try {
            $raw = file_get_contents('php://input');
            $data = !empty($raw) ? json_decode($raw, true) : null;
            if (!is_array($data)) {
                $data = $this->input->post() ?: [];
            }

            $customer_id = (int) (!empty($data['customer_id']) ? $data['customer_id'] : ($data['id_users_customer'] ?? 0));
            if (!$customer_id) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'success' => false,
                        'message' => 'Geçerli bir misafir seçimi zorunludur.',
                    ]));
                return;
            }

            $customer = $this->db->get_where('users', ['id' => $customer_id])->row_array();
            if (!$customer) {
                $this->output
                    ->set_status_header(404)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'success' => false,
                        'message' => 'Misafir bulunamadı.',
                    ]));
                return;
            }

            $preferences = $data['preferences'] ?? $data;
            if (!is_array($preferences)) {
                $preferences = [];
            }

            $pillow = trim((string) ($preferences['pillow_type'] ?? ($preferences['pillow_choice'] ?? ($data['pillow_type'] ?? ($data['pillow_choice'] ?? '')))));
            $floor = trim((string) ($preferences['floor_preference'] ?? ($data['floor_preference'] ?? '')));
            $dietary = trim((string) ($preferences['dietary'] ?? ($preferences['dietary_allergies'] ?? ($preferences['dietary_restrictions'] ?? ($data['dietary'] ?? ($data['dietary_allergies'] ?? ''))))));
            $notes = trim((string) ($preferences['notes'] ?? ($preferences['special_notes'] ?? ($data['notes'] ?? ($data['special_notes'] ?? '')))));
            $vip = trim((string) ($preferences['vip_level'] ?? ($data['vip_level'] ?? 'regular')));

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

            if ($this->db->table_exists('restaurant_guest_preferences')) {
                $existing = $this->db->get_where('restaurant_guest_preferences', ['id_users_customer' => $customer_id])->row_array();
                $prefData = [
                    'id_users_customer' => $customer_id,
                    'vip_level' => $vip ?: 'regular',
                    'dietary_restrictions' => $dietary,
                    'seating_preference' => $floor,
                    'special_notes' => $notes,
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                if ($existing) {
                    $this->db->update('restaurant_guest_preferences', $prefData, ['id' => $existing['id']]);
                } else {
                    $prefData['created_at'] = date('Y-m-d H:i:s');
                    $this->db->insert('restaurant_guest_preferences', $prefData);
                }
            }

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => true,
                    'message' => 'Misafir tercihleri kaydedildi.',
                    'preferences' => [
                        'customer_id' => $customer_id,
                        'pillow_type' => $pillow,
                        'floor_preference' => $floor,
                        'dietary' => $dietary,
                        'notes' => $notes,
                    ],
                ]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => $e->getMessage(),
                ]));
        }
    }

    /* -------------------------------------------------------------------------
     * 8. EĞİTİM & ATÖLYE - KURS, CANLI YOKLAMA & ÖĞRENCİ GELİŞİM TAKİBİ
     * ------------------------------------------------------------------------- */

    /**
     * Education / Workshops dashboard.
     */
    public function education(): void
    {
        $user_id = $this->require_auth('verticals/education');

        html_vars([
            'page_title' => 'Eğitim, Atölye & Kurs Yönetimi (Yoklama & Öğrenci Takip)',
            'active_menu' => 'verticals_education',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug(session('role_slug')),
        ]);

        // Auto-run migration 174 if needed
        $this->load->library('migration');
        $this->migration->version(174);

        // Fetch classes / sessions (appointments joined with services, instructors, stations)
        $sessions = $this->db
            ->select('a.*, s.name as service_name, s.duration as service_duration, u.first_name as provider_name, st.name as station_name')
            ->from('appointments a')
            ->join('services s', 's.id = a.id_services', 'left')
            ->join('users u', 'u.id = a.id_users_provider', 'left')
            ->join('stations st', 'st.id = a.id_stations', 'left')
            ->where('a.is_unavailability', 0)
            ->where_not_in('a.status', ['cancelled'])
            ->order_by('a.start_datetime DESC')
            ->limit(30)
            ->get()
            ->result_array();

        // Fetch students (customers)
        $students = $this->db
            ->select('id, first_name, last_name, phone_number, email')
            ->from('users')
            ->where('role_slug', 'customer')
            ->order_by('first_name ASC, last_name ASC')
            ->limit(100)
            ->get()
            ->result_array();

        // Fetch recent attendance
        $attendance = [];
        if ($this->db->table_exists('course_attendance')) {
            $attendance = $this->db
                ->select('ca.*, CONCAT(u.first_name, " ", u.last_name) as student_name, s.name as service_name')
                ->from('course_attendance ca')
                ->join('users u', 'u.id = ca.id_users_customer', 'left')
                ->join('appointments a', 'a.id = ca.id_appointments', 'left')
                ->join('services s', 's.id = a.id_services', 'left')
                ->order_by('ca.created_at DESC')
                ->limit(50)
                ->get()
                ->result_array();
        }

        // Fetch student evaluations / grades
        $evaluations = [];
        if ($this->db->table_exists('student_evaluations')) {
            $evaluations = $this->db
                ->select('se.*, CONCAT(u.first_name, " ", u.last_name) as student_name')
                ->from('student_evaluations se')
                ->join('users u', 'u.id = se.id_users_customer', 'left')
                ->order_by('se.created_at DESC')
                ->limit(30)
                ->get()
                ->result_array();
        }

        $this->load->view('pages/vertical_education', [
            'sessions' => $sessions,
            'students' => $students,
            'attendance' => $attendance,
            'evaluations' => $evaluations,
        ]);
    }

    /**
     * Save course attendance and optionally deduct sessions.
     */
    public function save_attendance(): void
    {
        $this->ensure_authenticated();

        if (session('role_slug') === 'customer') {
            $this->output
                ->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Öğrenciler yoklama kaydedemez.']));
            return;
        }

        try {
            $raw = file_get_contents('php://input');
            $post = !empty($raw) ? json_decode($raw, true) : $this->input->post();
            if (!is_array($post)) {
                $post = $this->input->post() ?: [];
            }

            $session_id = (int) ($post['session_id'] ?? 0);
            $records = $post['records'] ?? [];

            if ($session_id <= 0) {
                throw new InvalidArgumentException('Geçerli bir ders/oturum seçilmelidir.');
            }
            if (empty($records) || !is_array($records)) {
                throw new InvalidArgumentException('Yoklama kayıtları boş olamaz.');
            }

            // Ensure table exists
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

                // Check existing attendance for this session and student
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

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => true,
                    'message' => 'Yoklama başarıyla kaydedildi.',
                    'updated_count' => $updated_count,
                ]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => $e->getMessage(),
                ]));
        }
    }

    /**
     * Save student evaluation / grade.
     */
    public function save_student_grade(): void
    {
        $this->ensure_authenticated();

        if (session('role_slug') === 'customer') {
            $this->output
                ->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Öğrenciler not girişi yapamaz.']));
            return;
        }

        try {
            $raw = file_get_contents('php://input');
            $post = !empty($raw) ? json_decode($raw, true) : $this->input->post();
            if (!is_array($post)) {
                $post = $this->input->post() ?: [];
            }

            $student_id = (int) ($post['student_id'] ?? 0);
            $session_id = !empty($post['session_id']) ? (int) $post['session_id'] : null;
            $subject = trim((string) ($post['subject'] ?? ''));
            $grade_score = isset($post['grade_score']) ? (float) $post['grade_score'] : null;
            $feedback_notes = !empty($post['feedback_notes']) ? trim((string) $post['feedback_notes']) : null;

            if ($student_id <= 0) {
                throw new InvalidArgumentException('Öğrenci seçilmelidir.');
            }
            if (empty($subject)) {
                throw new InvalidArgumentException('Ders konusu veya ödev başlığı zorunludur.');
            }

            // Ensure table exists
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

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => true,
                    'message' => 'Öğrenci değerlendirmesi başarıyla kaydedildi.',
                    'evaluation_id' => $this->db->insert_id(),
                ]));
        } catch (Throwable $e) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => $e->getMessage(),
                ]));
        }
    }
}


