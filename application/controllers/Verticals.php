<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Multi-Vertical Enterprise Operations Controller
 * ---------------------------------------------------------------------------- */

class Verticals extends EA_Controller
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

        $this->load->view('pages/vertical_sports_matches', [
            'matches' => $matches,
            'stations' => $stations,
            'checkins' => $checkins,
            'customers' => $this->customers_model->get(null, 100, null, 'first_name ASC'),
        ]);
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
            ->select('cr.*, c.first_name as patient_first_name, c.last_name as patient_last_name, p.first_name as doc_first_name, p.last_name as doc_last_name')
            ->from('clinical_records cr')
            ->join('users c', 'c.id = cr.id_users_customer', 'left')
            ->join('users p', 'p.id = cr.id_users_provider', 'left')
            ->order_by('cr.created_at DESC')
            ->limit(50)
            ->get()
            ->result_array();

        $patients = $this->customers_model->get(null, 100, null, 'first_name ASC');
        $providers = $this->db->get_where('users', ['id_roles' => 2])->result_array();

        $this->load->view('pages/vertical_clinical_records', [
            'records' => $records,
            'patients' => $patients,
            'providers' => $providers,
        ]);
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

        $this->load->view('pages/vertical_vehicles_dvi', [
            'vehicles' => $vehicles,
            'work_orders' => $work_orders,
            'customers' => $this->customers_model->get(null, 100, null, 'first_name ASC'),
        ]);
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

        $this->load->view('pages/vertical_experience_waivers', [
            'waivers' => $waivers,
            'signatures' => $signatures,
            'tickets' => $tickets,
        ]);
    }
}
