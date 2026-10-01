<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Salon Flora customization - Reports controller.
 *
 * Handles the daily revenue report. A day's revenue is calculated from
 * appointments that were checked out (actual_end_datetime is set) or, as a
 * fallback for appointments that were never explicitly checked out, whose
 * status is the "Tamamlandı" (Completed) option, joined against the
 * service's price at the time of the report (there is no price snapshot
 * stored on the appointment itself).
 * ---------------------------------------------------------------------------- */

class Reports extends App_Controller
{
    private const COMPLETED_STATUS = 'Tamamlandı';

    /**
     * Reports constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('roles_model');
        $this->load->model('appointments_model'); // Salon Flora customization - compute_effective_billing()
        $this->load->model('reports_model'); // Faz 3.6 - revenue calculation extraction
        $this->load->model('providers_model'); // Faz 3.6 - utilization report

        $this->load->library('accounts');
    }

    /**
     * Render the backend reports page.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('reports')]);

        $user_id = session('user_id');

        if (cannot('view', PRIV_REPORTS)) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        $role_slug = session('role_slug');

        html_vars([
            'page_title' => 'Raporlar',
            'active_menu' => PRIV_REPORTS,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'privileges' => $this->roles_model->get_permissions_by_slug($role_slug),
            'report_field_catalog' => self::field_catalog(), // Salon Flora customization
        ]);

        script_vars([
            'user_id' => $user_id,
            'role_slug' => $role_slug,
            'report_field_catalog' => self::field_catalog(), // Salon Flora customization
        ]);

        $this->load->view('pages/reports');
    }

    /**
     * Get the daily revenue report for a given date.
     */
    public function get_daily_revenue(): void
    {
        try {
            method('post|get');

            if (cannot('view', PRIV_REPORTS)) {
                abort(403, 'Forbidden');
            }

            check('date', 'date|null');

            $date = request('date') ?: date('Y-m-d');

            // Faz 3.6 - Extracted revenue calculation to Reports_model for reuse
            $rows = $this->reports_model->get_revenue_rows($date, $date);

            $by_provider = [];

            $grand_total = 0.0;

            $grand_payout = 0.0;

            $grand_collected = 0.0;

            $grand_balance = 0.0;

            foreach ($rows as $row) {
                $provider_id = (int) $row['provider_id'];

                if (!isset($by_provider[$provider_id])) {
                    $commission_type = $row['commission_type'] ?: 'percentage';

                    $by_provider[$provider_id] = [
                        'provider_id' => $provider_id,
                        'provider_name' => trim($row['provider_first_name'] . ' ' . $row['provider_last_name']),
                        'commission_type' => $commission_type,
                        'commission_value' => (float) $row['commission_value'],
                        'session_count' => 0,
                        'total' => 0.0,
                        'payout' => 0.0,
                        // Salon Flora customization - payment/collection + worked-time rollups for this provider.
                        'duration_minutes' => 0,
                        'collected_amount' => 0.0,
                        'balance_amount' => 0.0,
                        'invoiced_count' => 0,
                        'not_invoiced_count' => 0,
                        'not_collected_count' => 0,
                        'pending_payment_count' => 0,
                        'appointments' => [],
                    ];
                }

                // Faz 3.6 - Extracted revenue calculation to Reports_model for reuse
                $metrics = $this->reports_model->compute_row_metrics($row);
                $duration_minutes = $metrics['minutes'];
                $price = $metrics['price'];
                $payout = $metrics['payout'];

                $collected_amount = $row['payment_status'] === PAYMENT_STATUS_COLLECTED ? (float) ($row['payment_amount'] ?? 0) : 0.0;
                $balance_amount = $row['payment_status'] === PAYMENT_STATUS_NOT_COLLECTED ? (float) ($row['payment_balance_amount'] ?? 0) : 0.0;

                $by_provider[$provider_id]['session_count']++;
                $by_provider[$provider_id]['total'] += $price;
                $by_provider[$provider_id]['payout'] += $payout;
                $by_provider[$provider_id]['duration_minutes'] += $duration_minutes;
                $by_provider[$provider_id]['collected_amount'] += $collected_amount;
                $by_provider[$provider_id]['balance_amount'] += $balance_amount;
                $by_provider[$provider_id]['invoiced_count'] += (int) $row['is_invoiced'];
                $by_provider[$provider_id]['not_invoiced_count'] += $row['is_invoiced'] ? 0 : 1;

                if ($row['payment_status'] === PAYMENT_STATUS_NOT_COLLECTED) {
                    $by_provider[$provider_id]['not_collected_count']++;
                } elseif ($row['payment_status'] === PAYMENT_STATUS_PENDING) {
                    $by_provider[$provider_id]['pending_payment_count']++;
                }

                $by_provider[$provider_id]['appointments'][] = [
                    'id' => (int) $row['appointment_id'],
                    'service_name' => $row['service_name'],
                    'price' => $price,
                    'payout' => $payout,
                    'start_datetime' => $row['start_datetime'],
                    'duration_minutes' => $duration_minutes,
                    'payment_status' => $row['payment_status'],
                    'payment_method' => $row['payment_method'],
                    'payment_amount' => $row['payment_amount'] !== null ? (float) $row['payment_amount'] : null,
                    'payment_balance_amount' => $row['payment_balance_amount'] !== null ? (float) $row['payment_balance_amount'] : null,
                    'is_invoiced' => (bool) $row['is_invoiced'],
                ];

                $grand_total += $price;
                $grand_payout += $payout;
                $grand_collected += $collected_amount;
                $grand_balance += $balance_amount;
            }

            json_response([
                'date' => $date,
                'providers' => array_values($by_provider),
                'session_count' => count($rows),
                'grand_total' => $grand_total,
                'grand_payout' => $grand_payout,
                'grand_collected' => $grand_collected,
                'grand_balance' => $grand_balance,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Salon Flora customization (2026-08-25) - the full catalog of exportable per-appointment fields, one
     * key per column, shared by export_csv() (and, later, the Google Sheets field-mapping system so the
     * two never drift apart). 'pii' marks fields that are personal data (unchecked by default in the UI,
     * decrypted on demand only when actually selected).
     *
     * @return array<string, array{label: string, group: string, pii: bool}>
     */
    public static function field_catalog(): array
    {
        // Salon Flora customization - the actual catalog lives in Google_sheets_fields (a plain library
        // file, safely loadable from ANY controller) so the Sheets sync system can reuse the exact same
        // definitions - Reports.php itself is only autoloaded when it's the active controller, so a
        // cross-controller static call the other way around would fail.
        require_once APPPATH . 'libraries/Google_sheets_fields.php';

        return Google_sheets_fields::catalog('appointments');
    }

    /**
     * Salon Flora customization (2026-08-25) - flat, per-appointment CSV export over a date range (not
     * the per-provider daily summary above), with a user-selectable set of columns (see field_catalog()
     * above) - one row per completed session. GET (not POST/JSON) so the browser can download it directly
     * (<a href>/window.location), like any other file download.
     */
    public function export_csv(): void
    {
        try {
            method('get|post');

            if (cannot('view', PRIV_REPORTS)) {
                abort(403, 'Forbidden');
            }

            check('start_date', 'date');
            check('end_date', 'date');
            check('columns', 'string|null');

            $start_date = request('start_date');
            $end_date = request('end_date');

            if ($end_date < $start_date) {
                throw new InvalidArgumentException('Bitiş tarihi başlangıç tarihinden önce olamaz.');
            }

            $catalog = self::field_catalog();

            $requested_columns = array_filter(explode(',', (string) request('columns', '')));

            // No explicit selection = every column ("mümkün olan en kapsamlı hali").
            $columns = !empty($requested_columns) ? array_values(array_intersect($requested_columns, array_keys($catalog))) : array_keys($catalog);

            if (empty($columns)) {
                throw new InvalidArgumentException('En az bir sütun seçmelisiniz.');
            }

            $needs_customer_pii = !empty(array_intersect($columns, ['customer_phone', 'customer_email', 'customer_address']));

            $this->load->model('customers_model');
            $this->load->model('providers_model');
            $this->load->model('stations_model');

            $this->db
                ->select(
                    'appointments.*, providers.first_name AS provider_first_name, providers.last_name AS provider_last_name, providers.email AS provider_email, providers.commission_type AS provider_commission_type, providers.commission_value AS provider_commission_value, providers.commission_overtime_bonus, customers.first_name AS customer_first_name, customers.last_name AS customer_last_name, customers.id AS customer_id, services.name AS service_name, services.price AS service_price, services.duration AS service_duration, stations.name AS station_name, psc.commission_type AS override_commission_type, psc.commission_value AS override_commission_value',
                )
                ->from('appointments')
                ->join('users AS providers', 'providers.id = appointments.id_users_provider', 'inner')
                ->join('users AS customers', 'customers.id = appointments.id_users_customer', 'left')
                ->join('services', 'services.id = appointments.id_services', 'inner')
                ->join('stations', 'stations.id = appointments.id_stations', 'left')
                ->join(
                    'provider_service_commissions AS psc',
                    'psc.id_users = providers.id AND psc.id_services = services.id',
                    'left',
                )
                ->where('appointments.is_unavailability', false)
                ->where_not_in('appointments.status', ['Cancelled', 'Draft'])
                ->group_start()
                ->where('DATE(actual_end_datetime) >=', $start_date)
                ->where('DATE(actual_end_datetime) <=', $end_date)
                ->or_group_start()
                ->where('actual_end_datetime IS NULL', null, false)
                ->where('appointments.status', self::COMPLETED_STATUS)
                ->where('DATE(start_datetime) >=', $start_date)
                ->where('DATE(start_datetime) <=', $end_date)
                ->group_end()
                ->group_end()
                ->order_by('appointments.start_datetime', 'asc');

            $rows = $this->db->get()->result_array();

            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="rapor_' . $start_date . '_' . $end_date . '.csv"');
            header('Pragma: no-cache');
            header('Expires: 0');

            $output = fopen('php://output', 'w');

            // UTF-8 BOM + ';' delimiter so Excel (Turkish locale, where ',' is the decimal separator)
            // opens Turkish characters and columns correctly without a manual "import as UTF-8" step.
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, array_map(fn($key) => $catalog[$key]['label'], $columns), ';');

            $money = fn($value) => $value !== null ? number_format((float) $value, 2, ',', '') : '-';
            $dt = fn($value) => $value ? (new DateTime($value))->format('d.m.Y H:i') : '-';

            foreach ($rows as $row) {
                $billing = $this->appointments_model->compute_effective_billing(
                    ['price' => $row['service_price'], 'duration' => $row['service_duration']],
                    $row,
                );

                $has_override = $row['override_commission_type'] !== null;
                $commission_type = $has_override ? $row['override_commission_type'] : ($row['provider_commission_type'] ?: 'percentage');
                $commission_value = (float) ($has_override ? $row['override_commission_value'] : $row['provider_commission_value']);

                if ($commission_type === 'fixed') {
                    $payout = $commission_value;
                } elseif ($commission_type === 'hourly') {
                    $payout = $commission_value * ($billing['minutes'] / 60);
                    if ($billing['minutes'] > 60) {
                        $payout += (float) $row['commission_overtime_bonus'];
                    }
                } else {
                    $payout = $billing['price'] * ($commission_value / 100);
                }

                $customer_name = trim(($row['customer_first_name'] ?? '') . ' ' . ($row['customer_last_name'] ?? '')) ?: '-';

                // Salon Flora customization - customer phone/email/address are PII-encrypted at rest
                // (Customers_model::ENCRYPTED_AND_HASHED_FIELDS / ENCRYPTED_ONLY_FIELDS); decrypted here
                // ONLY when one of those columns was actually requested, one lookup per distinct
                // customer per export (not per row) would be an optimization, but exports are small
                // (a date range of daily reports, not the whole customer base) so a per-row find() is
                // simple and fine.
                $customer_pii = $needs_customer_pii && $row['customer_id']
                    ? $this->customers_model->find((int) $row['customer_id'])
                    : null;

                $start = new DateTime($row['start_datetime']);
                $end = new DateTime($row['end_datetime']);

                $values = [
                    'appointment_id' => $row['id'],
                    'created_at' => $dt($row['create_datetime']),
                    'date' => $start->format('d.m.Y'),
                    'time' => $start->format('H:i'),
                    'end_time' => $end->format('H:i'),
                    'actual_start' => $dt($row['actual_start_datetime']),
                    'actual_end' => $dt($row['actual_end_datetime']),
                    'status' => $row['status'] ?: '-',
                    'notes' => $row['notes'] ?: '-',
                    'location' => $row['location'] ?: '-',
                    'customer_name' => $customer_name,
                    'customer_phone' => $customer_pii['phone_number'] ?? '-',
                    'customer_email' => $customer_pii['email'] ?? '-',
                    'customer_address' => $customer_pii['address'] ?? '-',
                    'provider_name' => trim($row['provider_first_name'] . ' ' . $row['provider_last_name']),
                    'provider_email' => $row['provider_email'] ?: '-',
                    'service_name' => $row['service_name'],
                    'service_list_price' => $money($row['service_price']),
                    'service_planned_minutes' => $row['service_duration'],
                    'effective_minutes' => $billing['minutes'],
                    'effective_price' => $money($billing['price']),
                    'hourly_rate' => $money($billing['hourly_rate']),
                    'payout' => $money($payout),
                    'station_name' => $row['station_name'] ?: 'Atanmamış',
                    'payment_status' => $row['payment_status'] ?: '-',
                    'payment_method' => $row['payment_method'] ?: '-',
                    'payment_amount' => $money($row['payment_amount']),
                    'payment_balance' => $money($row['payment_balance_amount']),
                    'is_invoiced' => $row['is_invoiced'] ? 'Evet' : 'Hayır',
                    'deviation_type' => $row['session_deviation_type'] ?: '-',
                    'deviation_minutes' => $row['session_deviation_minutes'] ?? '-',
                    'early_exit_justification' => $row['early_exit_justification'] === 'justified' ? 'Haklı' : ($row['early_exit_justification'] === 'unjustified' ? 'Haksız' : '-'),
                    'early_exit_reason' => $row['early_exit_reason_code'] ? (EARLY_EXIT_REASON_CODES[$row['early_exit_reason_code']] ?? $row['early_exit_reason_code']) : '-',
                    'conflict_override' => $row['conflict_override'] ?: '-',
                ];

                fputcsv(
                    $output,
                    array_map(fn($key) => $values[$key] ?? '-', $columns),
                    ';',
                );
            }

            fclose($output);
        } catch (Throwable $e) {
            log_message('error', 'Reports::export_csv - ' . $e->getMessage());
            show_error($e->getMessage(), 400);
        }
    }

    /**
     * Pre-configured 1-click downloadable report templates.
     */
    public function download_template(): void
    {
        try {
            method('get|post');

            if (cannot('view', PRIV_REPORTS)) {
                abort(403, 'Forbidden');
            }

            $template = (string) request('template', 'gun_sonu');
            $today = date('Y-m-d');
            $first_of_month = date('Y-m-01');

            switch ($template) {
                case 'aylik_muhasebe':
                    $_GET['start_date'] = $_REQUEST['start_date'] = $first_of_month;
                    $_GET['end_date'] = $_REQUEST['end_date'] = date('Y-m-t');
                    $_GET['columns'] = $_REQUEST['columns'] = 'date,time,appointment_id,customer_name,service_name,effective_price,payment_status,payment_method,payment_amount,payment_balance,is_invoiced';
                    break;
                case 'personel_prim':
                    $_GET['start_date'] = $_REQUEST['start_date'] = $first_of_month;
                    $_GET['end_date'] = $_REQUEST['end_date'] = $today;
                    $_GET['columns'] = $_REQUEST['columns'] = 'date,provider_name,service_name,customer_name,effective_minutes,service_list_price,payout,early_exit_justification';
                    break;
                case 'hizmet_karlilik':
                    $_GET['start_date'] = $_REQUEST['start_date'] = date('Y-m-d', strtotime('-30 days'));
                    $_GET['end_date'] = $_REQUEST['end_date'] = $today;
                    $_GET['columns'] = $_REQUEST['columns'] = 'date,service_name,provider_name,service_list_price,effective_price,effective_minutes,payment_status';
                    break;
                case 'odeme_tahsilat':
                    $_GET['start_date'] = $_REQUEST['start_date'] = $first_of_month;
                    $_GET['end_date'] = $_REQUEST['end_date'] = $today;
                    $_GET['columns'] = $_REQUEST['columns'] = 'date,time,customer_name,payment_method,payment_status,payment_amount,payment_balance,is_invoiced';
                    break;
                case 'gun_sonu':
                default:
                    $_GET['start_date'] = $_REQUEST['start_date'] = $today;
                    $_GET['end_date'] = $_REQUEST['end_date'] = $today;
                    $_GET['columns'] = $_REQUEST['columns'] = 'appointment_id,time,customer_name,customer_phone,provider_name,service_name,service_list_price,payment_status,payment_method,payment_amount,is_invoiced';
                    break;
            }

            $this->export_csv();
        } catch (Throwable $e) {
            log_message('error', 'Reports::download_template - ' . $e->getMessage());
            show_error($e->getMessage(), 400);
        }
    }

    /**
     * Validate analytics request parameters and return parsed filters.
     *
     * Faz 3.6 - Helper for get_revenue_report(), get_utilization_report(), get_retention_report().
     * Validates permission, date range, group_by, and applies role-based filtering.
     *
     * @return array [$date_from, $date_to, $group_by, $filters]
     */
    private function analytics_request(): array
    {
        if (cannot('view', PRIV_REPORTS)) {
            abort(403, 'Forbidden');
        }

        check('date_from', 'date|null');
        check('date_to', 'date|null');
        check('group_by', 'string|null');

        $date_to = request('date_to') ?: date('Y-m-d');
        $date_from = request('date_from') ?: date('Y-m-d', strtotime('-29 days'));

        if ($date_to < $date_from) {
            throw new InvalidArgumentException('Bitiş tarihi başlangıç tarihinden önce olamaz.');
        }

        $group_by = in_array(request('group_by'), ['day', 'week', 'month'], true) ? request('group_by') : 'day';

        $filters = [];

        if (session('role_slug') === DB_SLUG_PROVIDER) {
            // Providers see only their own data
            $filters['provider_id'] = (int) session('user_id');
        } elseif (request('provider_id')) {
            $filters['provider_id'] = (int) request('provider_id');
        }

        if (request('service_id')) {
            $filters['service_id'] = (int) request('service_id');
        }

        return [$date_from, $date_to, $group_by, $filters];
    }

    /**
     * Get revenue report (aggregated by trend, provider, service).
     *
     * Faz 3.6 - Analytics endpoint for revenue trends, provider breakdown, service breakdown.
     */
    public function get_revenue_report(): void
    {
        try {
            method('post|get');

            [$date_from, $date_to, $group_by, $filters] = $this->analytics_request();
            $rows = $this->reports_model->get_revenue_rows($date_from, $date_to, $filters);

            $totals = ['gross' => 0.0, 'payout' => 0.0, 'net' => 0.0, 'session_count' => 0, 'avg_ticket' => 0.0];
            $trend = []; // key => ['key' => string, 'gross' => float, 'net' => float, 'session_count' => int]
            $by_provider = []; // provider_id => ['provider_name'=>, 'gross'=>, 'session_count'=>]
            $by_service = []; // service_id => ['service_name'=>, 'gross'=>, 'session_count'=>]

            foreach ($rows as $row) {
                $metrics = $this->reports_model->compute_row_metrics($row);
                $price = $metrics['price'];
                $payout = $metrics['payout'];

                // group_by key from appointment start_datetime
                $date = substr($row['start_datetime'], 0, 10); // 'YYYY-MM-DD'
                $key = match ($group_by) {
                    'week' => date('Y-\WW', strtotime($date)),
                    'month' => substr($date, 0, 7), // 'YYYY-MM'
                    default => $date,
                };

                if (!isset($trend[$key])) {
                    $trend[$key] = [
                        'key' => $key,
                        'period' => $key,
                        'gross' => 0.0,
                        'total_revenue' => 0.0,
                        'payout' => 0.0,
                        'total_payout' => 0.0,
                        'net' => 0.0,
                        'session_count' => 0,
                        'appointments_count' => 0,
                    ];
                }
                $trend[$key]['gross'] += $price;
                $trend[$key]['total_revenue'] += $price;
                $trend[$key]['payout'] += $payout;
                $trend[$key]['total_payout'] += $payout;
                $trend[$key]['net'] += ($price - $payout);
                $trend[$key]['session_count']++;
                $trend[$key]['appointments_count']++;

                $pid = (int) $row['provider_id'];
                if (!isset($by_provider[$pid])) {
                    $by_provider[$pid] = ['provider_id' => $pid, 'provider_name' => trim($row['provider_first_name'] . ' ' . $row['provider_last_name']), 'gross' => 0.0, 'session_count' => 0];
                }
                $by_provider[$pid]['gross'] += $price;
                $by_provider[$pid]['session_count']++;

                $sid = (int) $row['service_id'];
                if (!isset($by_service[$sid])) {
                    $by_service[$sid] = ['service_id' => $sid, 'service_name' => $row['service_name'], 'gross' => 0.0, 'session_count' => 0];
                }
                $by_service[$sid]['gross'] += $price;
                $by_service[$sid]['session_count']++;

                $totals['gross'] += $price;
                $totals['payout'] += $payout;
                $totals['session_count']++;
            }

            $totals['net'] = $totals['gross'] - $totals['payout'];
            $totals['avg_ticket'] = $totals['session_count'] > 0 ? $totals['gross'] / $totals['session_count'] : 0.0;

            ksort($trend);
            $series = array_values($trend);

            json_response([
                'date_from' => $date_from,
                'date_to' => $date_to,
                'group_by' => $group_by,
                'totals' => $totals,
                'trend' => $series,
                'series' => $series,
                'by_provider' => array_values($by_provider),
                'by_service' => array_values($by_service),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Get provider utilization report (booked vs. available minutes).
     *
     * Faz 3.6 - Analytics endpoint for provider capacity utilization.
     */
    public function get_utilization_report(): void
    {
        try {
            method('post|get');

            [$date_from, $date_to, , $filters] = $this->analytics_request();

            $provider_ids = !empty($filters['provider_id'])
                ? [(int) $filters['provider_id']]
                : array_column($this->providers_model->get(), 'id');

            $providers_out = [];

            foreach ($provider_ids as $pid) {
                $rows = $this->reports_model->get_revenue_rows($date_from, $date_to, ['provider_id' => $pid]);
                $booked_minutes = 0;
                foreach ($rows as $row) {
                    $metrics = $this->reports_model->compute_row_metrics($row);
                    $booked_minutes += $metrics['minutes'];
                }

                $available_minutes = $this->reports_model->calculate_available_minutes($pid, $date_from, $date_to);
                $provider = $this->providers_model->find($pid);

                $utilization_rate = $available_minutes > 0 ? round($booked_minutes / $available_minutes * 100, 1) : 0.0;

                $providers_out[] = [
                    'provider_id' => $pid,
                    'provider_name' => $provider ? trim($provider['first_name'] . ' ' . $provider['last_name']) : ('#' . $pid),
                    'booked_minutes' => $booked_minutes,
                    'available_minutes' => $available_minutes,
                    'utilization_pct' => $available_minutes > 0 ? $utilization_rate : null,
                    'utilization_rate' => $utilization_rate,
                ];
            }

            json_response([
                'date_from' => $date_from,
                'date_to' => $date_to,
                'providers' => $providers_out,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Get retention report (new vs. returning customers, churn).
     *
     * Faz 3.6 - Analytics endpoint for customer retention metrics.
     */
    public function get_retention_report(): void
    {
        try {
            method('post|get');

            if (session('role_slug') === DB_SLUG_PROVIDER) {
                abort(403, 'Forbidden');
            }

            [$date_from, $date_to, , ] = $this->analytics_request();

            check('churn_days', 'numeric|null');
            $churn_days = request('churn_days') ? (int) request('churn_days') : 90;

            // Each customer's first appointment date
            $first_seen_rows = $this->db
                ->select('id_users_customer, MIN(start_datetime) AS first_seen')
                ->from('appointments')
                ->where('is_unavailability', false)
                ->where_not_in('status', ['Cancelled', 'Draft'])
                ->group_by('id_users_customer')
                ->get()
                ->result_array();

            $first_seen_by_customer = [];
            foreach ($first_seen_rows as $r) {
                $first_seen_by_customer[$r['id_users_customer']] = substr($r['first_seen'], 0, 7); // 'YYYY-MM'
            }

            // Appointments in the date range: which customer, which month
            $range_rows = $this->db
                ->select("DATE_FORMAT(start_datetime, '%Y-%m') AS month, id_users_customer")
                ->from('appointments')
                ->where('is_unavailability', false)
                ->where_not_in('status', ['Cancelled', 'Draft'])
                ->where('start_datetime >=', $date_from)
                ->where('start_datetime <=', $date_to . ' 23:59:59')
                ->group_by('month, id_users_customer')
                ->get()
                ->result_array();

            $months = [];
            foreach ($range_rows as $r) {
                $month = $r['month'];
                if (!isset($months[$month])) {
                    $months[$month] = ['month' => $month, 'new' => 0, 'returning' => 0];
                }
                $is_new = ($first_seen_by_customer[$r['id_users_customer']] ?? null) === $month;
                if ($is_new) {
                    $months[$month]['new']++;
                } else {
                    $months[$month]['returning']++;
                }
            }
            foreach ($months as &$m) {
                $total = $m['new'] + $m['returning'];
                $m['repeat_rate'] = $total > 0 ? round($m['returning'] / $total * 100, 1) : 0.0;
            }
            unset($m);
            ksort($months);

            // Churn: customers whose last appointment is older than churn_days and have no new appointments
            $last_seen_rows = $this->db
                ->select('id_users_customer, MAX(start_datetime) AS last_seen')
                ->from('appointments')
                ->where('is_unavailability', false)
                ->where_not_in('status', ['Cancelled', 'Draft'])
                ->group_by('id_users_customer')
                ->get()
                ->result_array();

            $cutoff = date('Y-m-d H:i:s', strtotime('-' . $churn_days . ' days'));
            $churned = 0;
            foreach ($last_seen_rows as $r) {
                if ($r['last_seen'] < $cutoff) {
                    $churned++;
                }
            }

            json_response([
                'date_from' => $date_from,
                'date_to' => $date_to,
                'months' => array_values($months),
                'churn' => [
                    'churned' => $churned,
                    'total' => count($last_seen_rows),
                    'pct' => count($last_seen_rows) > 0 ? round($churned / count($last_seen_rows) * 100, 1) : 0.0,
                    'days' => $churn_days,
                ],
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Get consumables and session profit margin report.
     */
    public function get_consumables_report(): void
    {
        try {
            method('post|get');

            if (cannot('view', PRIV_REPORTS)) {
                abort(403, 'Forbidden');
            }

            $date_from = request('date_from') ?: date('Y-m-01');
            $date_to = request('date_to') ?: date('Y-m-d 23:59:59');
            $service_id = request('service_id') ? (int) request('service_id') : null;

            $this->load->model('inventory_consumables_model');
            $report = $this->inventory_consumables_model->get_consumables_report($date_from, $date_to, $service_id);

            json_response($report);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}

