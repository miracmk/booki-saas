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
        $this->load->model('legal_model');
        $this->load->model('consulting_model');
        $this->load->model('carwash_queue_model');
        $this->load->model('beauty_profiles_model');
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

        $vitals = $this->db->select('v.*, c.first_name, c.last_name')
            ->from('patient_vitals v')
            ->join('users c', 'c.id = v.id_users_patient', 'left')
            ->order_by('v.recorded_at DESC')
            ->limit(50)
            ->get()
            ->result_array();

        $prescriptions = $this->db->select('p.*, c.first_name as patient_first_name, c.last_name as patient_last_name, d.first_name as doc_first_name, d.last_name as doc_last_name')
            ->from('patient_prescriptions p')
            ->join('users c', 'c.id = p.id_users_patient', 'left')
            ->join('users d', 'd.id = p.id_users_doctor', 'left')
            ->order_by('p.prescribed_at DESC')
            ->limit(50)
            ->get()
            ->result_array();

        $allergies = $this->db->select('a.*, c.first_name, c.last_name')
            ->from('patient_allergies a')
            ->join('users c', 'c.id = a.id_users_patient', 'left')
            ->order_by('a.severity DESC, a.created_at DESC')
            ->limit(50)
            ->get()
            ->result_array();

        $lab_orders = $this->db->select('l.*, c.first_name, c.last_name')
            ->from('clinical_lab_orders l')
            ->join('users c', 'c.id = l.id_users_patient', 'left')
            ->order_by('l.created_at DESC')
            ->limit(50)
            ->get()
            ->result_array();

        $this->load->view('pages/vertical_clinical_records', [
            'records' => $records,
            'vitals' => $vitals,
            'prescriptions' => $prescriptions,
            'allergies' => $allergies,
            'lab_orders' => $lab_orders,
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

    /* -------------------------------------------------------------------------
     * 7. HUKUK & AVUKATLIK SUITE (LEGAL MATTERS, HEARINGS, BILLABLE TIMERS)
     * ------------------------------------------------------------------------- */
    public function legal(): void
    {
        $user_id = $this->require_auth('verticals/legal');

        html_vars([
            'page_title' => 'Hukuk Bürosu, Dava & Duruşma Yönetimi',
            'active_menu' => 'verticals_legal',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug(session('role_slug')),
        ]);

        $matters = $this->legal_model->get_matters();
        $hearings = $this->legal_model->get_hearings(null, false);
        $time_entries = $this->legal_model->get_time_entries();
        $clients = $this->customers_model->get(null, 200, null, 'first_name ASC');
        $attorneys = $this->db->get_where('users', ['id_roles' => 2])->result_array();

        $this->load->view('pages/vertical_legal', [
            'matters' => $matters,
            'hearings' => $hearings,
            'time_entries' => $time_entries,
            'clients' => $clients,
            'attorneys' => $attorneys,
        ]);
    }

    public function save_legal_matter(): void
    {
        try {
            method('post');
            $raw = json_decode($this->input->raw_input_stream, true) ?: $this->input->post();
            $id = $this->legal_model->save_matter($raw);
            json_response(['success' => true, 'id' => $id, 'message' => 'Dava dosyası başarıyla kaydedildi.']);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function save_legal_hearing(): void
    {
        try {
            method('post');
            $raw = json_decode($this->input->raw_input_stream, true) ?: $this->input->post();
            $id = $this->legal_model->save_hearing($raw);
            json_response(['success' => true, 'id' => $id, 'message' => 'Duruşma randevusu kaydedildi.']);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function save_legal_time_entry(): void
    {
        try {
            method('post');
            $raw = json_decode($this->input->raw_input_stream, true) ?: $this->input->post();
            $raw['id_users_attorney'] = $raw['id_users_attorney'] ?? session('user_id');
            $id = $this->legal_model->save_time_entry($raw);
            json_response(['success' => true, 'id' => $id, 'message' => 'Zaman kaydı dosyaya işlendi.']);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function check_legal_conflict(): void
    {
        try {
            method('get');
            $keyword = (string) $this->input->get('keyword');
            $results = $this->legal_model->check_conflict($keyword);
            json_response($results);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function delete_legal_matter(int $id): void
    {
        try {
            method('post');
            $this->legal_model->delete_matter($id);
            json_response(['success' => true, 'message' => 'Dava dosyası silindi.']);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /* -------------------------------------------------------------------------
     * 8. DANIŞMANLIK & STRATEJİ SUITE (PROJECTS, MILESTONES, TIMESHEETS)
     * ------------------------------------------------------------------------- */
    public function consulting(): void
    {
        $user_id = $this->require_auth('verticals/consulting');

        html_vars([
            'page_title' => 'Danışmanlık & Stratejik Proje Yönetimi',
            'active_menu' => 'verticals_consulting',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug(session('role_slug')),
        ]);

        $projects = $this->consulting_model->get_projects();
        $timesheets = $this->consulting_model->get_timesheets();
        $clients = $this->customers_model->get(null, 200, null, 'first_name ASC');
        $consultants = $this->db->get_where('users', ['id_roles' => 2])->result_array();

        $this->load->view('pages/vertical_consulting', [
            'projects' => $projects,
            'timesheets' => $timesheets,
            'clients' => $clients,
            'consultants' => $consultants,
        ]);
    }

    public function save_consulting_project(): void
    {
        try {
            method('post');
            $raw = json_decode($this->input->raw_input_stream, true) ?: $this->input->post();
            $id = $this->consulting_model->save_project($raw);
            json_response(['success' => true, 'id' => $id, 'message' => 'Proje başarıyla oluşturuldu.']);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function save_consulting_milestone(): void
    {
        try {
            method('post');
            $raw = json_decode($this->input->raw_input_stream, true) ?: $this->input->post();
            $id = $this->consulting_model->save_milestone($raw);
            json_response(['success' => true, 'id' => $id, 'message' => 'Kilometre taşı kaydedildi.']);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function save_consulting_timesheet(): void
    {
        try {
            method('post');
            $raw = json_decode($this->input->raw_input_stream, true) ?: $this->input->post();
            $raw['id_users_consultant'] = $raw['id_users_consultant'] ?? session('user_id');
            $id = $this->consulting_model->save_timesheet($raw);
            json_response(['success' => true, 'id' => $id, 'message' => 'Efor kaydı işlendi.']);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /* -------------------------------------------------------------------------
     * 9. OTO YIKAMA TV BEKLEME EKRANI & KUYRUK (CAR WASH LIVE TV & READY NOTIFY)
     * ------------------------------------------------------------------------- */
    public function carwash_tv(): void
    {
        $queue = $this->carwash_queue_model->get_active_queue();
        $this->load->view('pages/vertical_carwash_tv', [
            'queue' => $queue,
        ]);
    }

    public function save_carwash_queue(): void
    {
        try {
            method('post');
            $raw = json_decode($this->input->raw_input_stream, true) ?: $this->input->post();
            $id = $this->carwash_queue_model->add_to_queue($raw);
            json_response(['success' => true, 'id' => $id, 'message' => 'Araç peron sırasına eklendi.']);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function notify_carwash_ready(int $id): void
    {
        try {
            method('post');
            $result = $this->carwash_queue_model->notify_customer_ready($id);
            json_response($result);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /* -------------------------------------------------------------------------
     * 10. SAĞLIK & EMR KLİNİK AJAX HANDLERS (VITALS, PRESCRIPTIONS, LABS)
     * ------------------------------------------------------------------------- */
    public function save_patient_vitals(): void
    {
        try {
            method('post');
            $raw = json_decode($this->input->raw_input_stream, true) ?: $this->input->post();
            $id = $this->clinical_records_model->save_patient_vitals($raw);
            json_response(['success' => true, 'id' => $id, 'message' => 'Hayati bulgu ölçümleri kaydedildi.']);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function save_patient_prescription(): void
    {
        try {
            method('post');
            $raw = json_decode($this->input->raw_input_stream, true) ?: $this->input->post();
            $id = $this->clinical_records_model->save_patient_prescription($raw);
            json_response(['success' => true, 'id' => $id, 'message' => 'Reçete kaydedildi.']);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function save_patient_allergy(): void
    {
        try {
            method('post');
            $raw = json_decode($this->input->raw_input_stream, true) ?: $this->input->post();
            $id = $this->clinical_records_model->save_patient_allergy($raw);
            json_response(['success' => true, 'id' => $id, 'message' => 'Alerji uyarısı kaydedildi.']);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function save_clinical_lab(): void
    {
        try {
            method('post');
            $raw = json_decode($this->input->raw_input_stream, true) ?: $this->input->post();
            $id = $this->clinical_records_model->save_clinical_lab_order($raw);
            json_response(['success' => true, 'id' => $id, 'message' => 'Laboratuvar tetkik istemi kaydedildi.']);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /* -------------------------------------------------------------------------
     * 11. GÜZELLİK FORMÜLLERİ & BAHŞİŞ (TIPS)
     * ------------------------------------------------------------------------- */
    public function save_beauty_profile(): void
    {
        try {
            method('post');
            $raw = json_decode($this->input->raw_input_stream, true) ?: $this->input->post();
            $customerId = (int) ($raw['id_users_customer'] ?? 0);
            $id = $this->beauty_profiles_model->save_profile($customerId, $raw);
            json_response(['success' => true, 'id' => $id, 'message' => 'Güzellik ve formül profili kaydedildi.']);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    public function save_appointment_tip(): void
    {
        try {
            method('post');
            $raw = json_decode($this->input->raw_input_stream, true) ?: $this->input->post();
            $id = $this->beauty_profiles_model->save_tip($raw);
            json_response(['success' => true, 'id' => $id, 'message' => 'Bahşiş kaydedildi ve personele dağıtıldı.']);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}

