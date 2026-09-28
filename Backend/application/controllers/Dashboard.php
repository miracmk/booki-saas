<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Dashboard controller (Command Center, 2026-09-10).
 *
 * The backend's new landing page (replaces "land straight on the calendar").
 * Server-rendered: today's KPIs (appointment count, revenue, occupancy),
 * today's appointment timeline, and the waitlist count. "Aktif Seanslar" and
 * "Dikkat Gerektirenler" (payment-missing / overdue) are populated client-side
 * by dashboard.js reusing the EXISTING calendar/get_active_sessions endpoint
 * and App.Utils.SessionStatus helpers (same ones the calendar's own active
 * sessions widget already uses) - no duplicated business logic.
 * ---------------------------------------------------------------------------- */

class Dashboard extends App_Controller
{
    private const CANCELLED_LIKE_STATUSES = ['Cancelled', 'Draft'];

    /**
     * Dashboard constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('roles_model');
        $this->load->model('reports_model');
        $this->load->model('providers_model');
        $this->load->model('waitlist_model');
        $this->load->model('appointments_model');

        $this->load->library('accounts');
    }

    /**
     * Render the dashboard.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('dashboard')]);

        $user_id = session('user_id');
        if (!$user_id) {
            redirect('login');
            return;
        }

        $this->load->library('permission_service');
        if (!$this->permission_service->can('view', 'dashboard', (int) $user_id) && cannot('view', PRIV_APPOINTMENTS)) {
            abort(403, 'Forbidden');
        }

        $role_slug = session('role_slug');

        $today = date('Y-m-d');

        $provider_filter = $role_slug === DB_SLUG_PROVIDER ? (int) $user_id : null;

        $summary = $this->build_today_summary($today, $provider_filter);

        html_vars([
            'page_title' => 'Dashboard',
            'active_menu' => 'dashboard',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
            'today_label' => $this->turkish_date_label($today),
            'summary' => $summary,
        ]);

        script_vars([
            'user_id' => $user_id,
            'role_slug' => $role_slug,
            'today' => $today,
            'csrf_token' => $this->security->get_csrf_hash(),
            'routes' => [
                'ai_approve' => site_url('ai_agent/approve'),
                'ai_reject' => site_url('ai_agent/reject'),
            ],
        ]);

        $this->load->view('pages/dashboard');
    }

    /**
     * Gather every number the KPI row + timeline + attention panel need for "today".
     *
     * @param string $date 'YYYY-MM-DD'
     * @param int|null $provider_id Restrict to a single provider (a logged-in provider only ever sees their own day)
     */
    private function build_today_summary(string $date, ?int $provider_id): array
    {
        // Today's full appointment list (every status, not just completed ones) - powers the timeline.
        $this->db
            ->select(
                'appointments.id, appointments.start_datetime, appointments.end_datetime, appointments.status, ' .
                    'appointments.payment_status, ' .
                    'customers.first_name AS customer_first_name, customers.last_name AS customer_last_name, ' .
                    'providers.first_name AS provider_first_name, providers.last_name AS provider_last_name, ' .
                    'services.name AS service_name, stations.name AS station_name',
            )
            ->from('appointments')
            ->join('users AS providers', 'providers.id = appointments.id_users_provider', 'inner')
            ->join('users AS customers', 'customers.id = appointments.id_users_customer', 'left')
            ->join('services', 'services.id = appointments.id_services', 'inner')
            ->join('stations', 'stations.id = appointments.id_stations', 'left')
            ->where('appointments.start_datetime >=', $date . ' 00:00:00')
            ->where('appointments.start_datetime <=', $date . ' 23:59:59')
            ->where_not_in('appointments.status', self::CANCELLED_LIKE_STATUSES);

        if ($provider_id) {
            $this->db->where('appointments.id_users_provider', $provider_id);
        }

        $today_appointments = $this->db->order_by('appointments.start_datetime', 'asc')->get()->result_array();

        $waiting_count = $provider_id ? 0 : count($this->waitlist_model->get_waiting());

        $active_sessions_count = count($this->appointments_model->get_active_sessions($provider_id, null));

        // Revenue (collected + still-pending) - reuses the exact same completed-session query as Reports.
        $filters = $provider_id ? ['provider_id' => $provider_id] : [];
        $revenue_rows = $this->reports_model->get_revenue_rows($date, $date, $filters);

        $collected = 0.0;
        $pending = 0.0;
        $payment_missing_count = 0;

        foreach ($revenue_rows as $row) {
            if ($row['payment_status'] === PAYMENT_STATUS_COLLECTED) {
                $collected += (float) ($row['payment_amount'] ?? 0);
            } elseif ($row['payment_status'] === PAYMENT_STATUS_NOT_COLLECTED) {
                $pending += (float) ($row['payment_balance_amount'] ?? 0);
                $payment_missing_count++;
            }
        }

        // Occupancy - scheduled minutes today (every non-cancelled appointment, not only completed ones)
        // over each provider's available working minutes today.
        $provider_ids = $provider_id ? [$provider_id] : array_column($this->providers_model->get_available_providers(), 'id');

        $available_minutes = 0;
        foreach ($provider_ids as $pid) {
            $available_minutes += $this->reports_model->calculate_available_minutes((int) $pid, $date, $date);
        }

        $booked_minutes = 0;
        foreach ($today_appointments as $row) {
            $booked_minutes += (strtotime($row['end_datetime']) - strtotime($row['start_datetime'])) / 60;
        }

        $occupancy_pct = $available_minutes > 0 ? round(min($booked_minutes / $available_minutes, 1) * 100, 1) : null;

        // AI Agent pending approval requests
        $ai_pending_items = [];
        if ($this->db->table_exists('ai_agent_pending_changes')) {
            $ai_pending_items = $this->db
                ->where('status', 'pending')
                ->order_by('created_at', 'desc')
                ->limit(10)
                ->get('ai_agent_pending_changes')
                ->result_array();
        }
        $ai_pending_count = count($ai_pending_items);

        // Sektöre özel modül istatistikleri
        $open_adisyons_count = 0;
        if ($this->db->table_exists('adisyons')) {
            $open_adisyons_count = $this->db->where('status', 'open')->count_all_results('adisyons');
        }

        $occupied_tables_count = 0;
        $total_tables_count = 0;
        if ($this->db->table_exists('restaurant_tables')) {
            $total_tables_count = $this->db->count_all_results('restaurant_tables');
            $occupied_tables_count = $this->db->where('status', 'occupied')->count_all_results('restaurant_tables');
        }

        $industry_code = current_industry_code();
        $industry_info = current_industry_info();
        $industry_config = industry_dashboard_config($industry_code, (string) session('role_slug'));
        $terminology = [
            'customer_label' => industry_term('customer_label', 'Müşteri'),
            'provider_label' => industry_term('provider_label', 'Personel / Uzman'),
            'service_label' => industry_term('service_label', 'Hizmet'),
            'station_label' => industry_term('station_label', 'İstasyon / Alan'),
            'appointment_label' => industry_term('appointment_label', 'Randevu'),
        ];

        return [
            'industry_code' => $industry_code,
            'industry_info' => $industry_info,
            'industry_config' => $industry_config,
            'terminology' => $terminology,
            'open_adisyons_count' => $open_adisyons_count,
            'occupied_tables_count' => $occupied_tables_count,
            'total_tables_count' => $total_tables_count,
            'appointment_count' => count($today_appointments),
            'appointments' => array_map(static function (array $row): array {
                return [
                    'id' => (int) $row['id'],
                    'start_time' => date('H:i', strtotime($row['start_datetime'])),
                    'customer_name' => trim(($row['customer_first_name'] ?? '') . ' ' . ($row['customer_last_name'] ?? '')) ?: 'Müsait Değil',
                    'provider_name' => trim($row['provider_first_name'] . ' ' . $row['provider_last_name']),
                    'service_name' => $row['service_name'],
                    'station_name' => $row['station_name'] ?: '—',
                    'status' => $row['status'],
                ];
            }, $today_appointments),
            'revenue_collected' => $collected,
            'revenue_pending' => $pending,
            'payment_missing_count' => $payment_missing_count,
            'active_sessions_count' => $active_sessions_count,
            'waiting_count' => $waiting_count,
            'occupancy_pct' => $occupancy_pct,
            'ai_pending_count' => $ai_pending_count,
            'ai_pending_items' => $ai_pending_items,
        ];
    }

    /**
     * "Perşembe, 10 Eylül 2026" style label for the hero date, Turkish locale, no ICU dependency.
     */
    private function turkish_date_label(string $date): string
    {
        $days = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
        $months = [
            1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan', 5 => 'Mayıs', 6 => 'Haziran',
            7 => 'Temmuz', 8 => 'Ağustos', 9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık',
        ];

        $ts = strtotime($date);

        return $days[(int) date('w', $ts)] . ', ' . date('j', $ts) . ' ' . $months[(int) date('n', $ts)] . ' ' . date('Y', $ts);
    }
}
