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

class Reports extends EA_Controller
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
            method('post');

            if (cannot('view', PRIV_REPORTS)) {
                abort(403, 'Forbidden');
            }

            check('date', 'date');

            $date = request('date');

            $this->db
                ->select(
                    'appointments.id AS appointment_id, appointments.start_datetime, appointments.actual_start_datetime, appointments.actual_end_datetime, appointments.custom_duration_minutes, appointments.price_override, appointments.status, appointments.payment_status, appointments.payment_method, appointments.payment_amount, appointments.payment_balance_amount, appointments.is_invoiced, appointments.early_exit_justification, appointments.early_exit_approved_by, users.id AS provider_id, users.first_name AS provider_first_name, users.last_name AS provider_last_name, users.commission_type, users.commission_value, users.commission_overtime_bonus, services.id AS service_id, services.name AS service_name, services.price AS service_price, services.duration AS service_duration, psc.commission_type AS override_commission_type, psc.commission_value AS override_commission_value',
                )
                ->from('appointments')
                ->join('users', 'users.id = appointments.id_users_provider', 'inner')
                ->join('services', 'services.id = appointments.id_services', 'inner')
                // Salon Flora customization: per-provider, per-service commission override (falls back to the
                // provider's default commission_type/commission_value when no override row exists).
                ->join(
                    'provider_service_commissions AS psc',
                    'psc.id_users = users.id AND psc.id_services = services.id',
                    'left',
                )
                ->where('appointments.is_unavailability', false)
                ->where_not_in('appointments.status', ['Cancelled', 'Draft'])
                ->group_start()
                ->where('DATE(actual_end_datetime) =', $date)
                ->or_group_start()
                ->where('actual_end_datetime IS NULL', null, false)
                ->where('appointments.status', self::COMPLETED_STATUS)
                ->where('DATE(start_datetime) =', $date)
                ->group_end()
                ->group_end();

            $rows = $this->db->get()->result_array();

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

                // Salon Flora customization - the EFFECTIVE billed duration/price for this session (real
                // check-in/check-out duration, rounded down to the nearest half hour, times the service's hourly
                // rate - or a price_override/custom_duration_minutes override) - see compute_effective_billing()
                // for the full rule, shared with the frontend price preview.
                $billing = $this->appointments_model->compute_effective_billing(
                    ['price' => $row['service_price'], 'duration' => $row['service_duration']],
                    [
                        'start_datetime' => $row['start_datetime'],
                        'actual_start_datetime' => $row['actual_start_datetime'],
                        'actual_end_datetime' => $row['actual_end_datetime'],
                        'custom_duration_minutes' => $row['custom_duration_minutes'],
                        'price_override' => $row['price_override'],
                        'early_exit_justification' => $row['early_exit_justification'],
                        'early_exit_approved_by' => $row['early_exit_approved_by'],
                    ],
                );

                $duration_minutes = $billing['minutes'];
                $price = $billing['price'];

                // Salon Flora customization - the payout owed to the therapist for this session: a fixed amount
                // per session, a percentage of the (effective) service price, or an hourly rate times the
                // effective duration above. Resolved PER SESSION (provider + service), not once per provider - a
                // therapist may earn a different commission for different services (or duration variants).
                $has_override = $row['override_commission_type'] !== null;
                $commission_type = $has_override ? $row['override_commission_type'] : $by_provider[$provider_id]['commission_type'];
                $commission_value = $has_override ? (float) $row['override_commission_value'] : $by_provider[$provider_id]['commission_value'];

                if ($commission_type === 'fixed') {
                    $payout = $commission_value;
                } elseif ($commission_type === 'hourly') {
                    $payout = $commission_value * ($duration_minutes / 60);

                    // Salon Flora customization - a one-time fixed bonus (provider-level, not overridable per
                    // service) added whenever the EFFECTIVE session duration exceeds 60 minutes - once per
                    // session, regardless of how far past the hour it runs.
                    if ($duration_minutes > 60) {
                        $payout += (float) $row['commission_overtime_bonus'];
                    }
                } else {
                    $payout = $price * ($commission_value / 100);
                }

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
            method('get');

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
}
