<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Reports model (Faz 3.6, 2026-09-10).
 *
 * Extracted revenue calculation logic from Reports controller for reuse
 * across analytics, exports, and BI systems.
 *
 * @package Models
 */

class Reports_model extends EA_Model
{
    private const COMPLETED_STATUS = 'Tamamlandı';

    /**
     * Reports model constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('appointments_model');
    }

    /**
     * Get revenue rows for a date range, joined with provider/service/commission data.
     *
     * Retrieves all completed appointments in the date range, joined against users (providers),
     * services, and provider_service_commissions. The WHERE logic matches the original
     * Reports.get_daily_revenue() behavior: appointments with actual_end_datetime set, or
     * status='Tamamlandı' (Completed) as fallback.
     *
     * @param string $date_from Date in YYYY-MM-DD format (inclusive)
     * @param string $date_to Date in YYYY-MM-DD format (inclusive)
     * @param array $filters Optional filters: 'provider_id' => int, 'service_id' => int
     *
     * @return array Array of appointment rows with all joined fields
     */
    public function get_revenue_rows(string $date_from, string $date_to, array $filters = []): array
    {
        $this->db
            ->select(
                'appointments.id AS appointment_id, appointments.start_datetime, appointments.actual_start_datetime, appointments.actual_end_datetime, appointments.custom_duration_minutes, appointments.price_override, appointments.status, appointments.payment_status, appointments.payment_method, appointments.payment_amount, appointments.payment_balance_amount, appointments.is_invoiced, appointments.early_exit_justification, appointments.early_exit_approved_by, users.id AS provider_id, users.first_name AS provider_first_name, users.last_name AS provider_last_name, users.commission_type, users.commission_value, users.commission_overtime_bonus, services.id AS service_id, services.name AS service_name, services.price AS service_price, services.duration AS service_duration, psc.commission_type AS override_commission_type, psc.commission_value AS override_commission_value',
            )
            ->from('appointments')
            ->join('users', 'users.id = appointments.id_users_provider', 'inner')
            ->join('services', 'services.id = appointments.id_services', 'inner')
            ->join(
                'provider_service_commissions AS psc',
                'psc.id_users = users.id AND psc.id_services = services.id',
                'left',
            )
            ->where('appointments.is_unavailability', false)
            ->where_not_in('appointments.status', ['Cancelled', 'Draft'])
            ->group_start()
            ->where('DATE(actual_end_datetime) >=', $date_from)
            ->where('DATE(actual_end_datetime) <=', $date_to)
            ->or_group_start()
            ->where('actual_end_datetime IS NULL', null, false)
            ->where('appointments.status', self::COMPLETED_STATUS)
            ->where('DATE(start_datetime) >=', $date_from)
            ->where('DATE(start_datetime) <=', $date_to)
            ->group_end()
            ->group_end();

        // Apply optional filters
        if (!empty($filters['provider_id'])) {
            $this->db->where('appointments.id_users_provider', (int) $filters['provider_id']);
        }
        if (!empty($filters['service_id'])) {
            $this->db->where('appointments.id_services', (int) $filters['service_id']);
        }

        return $this->db->get()->result_array();
    }

    /**
     * Compute billing metrics (duration, effective price, payout) for a single appointment row.
     *
     * Encapsulates the effective billing calculation (check-in/check-out duration, price overrides,
     * custom duration) and commission payout logic (fixed/hourly/percentage, overtime bonuses,
     * per-service overrides).
     *
     * @param array $row An appointment row from get_revenue_rows() (must include all service, provider, and commission fields)
     *
     * @return array{minutes: int, price: float, payout: float} Effective duration (minutes), billed price, and provider payout
     */
    public function compute_row_metrics(array $row): array
    {
        // Compute the EFFECTIVE billed duration/price for this session
        // (real check-in/check-out duration, rounded down to the nearest half hour,
        // times the service's hourly rate - or a price_override/custom_duration_minutes override)
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

        // Compute the payout owed to the therapist: a fixed amount per session, a percentage
        // of the (effective) service price, or an hourly rate times the effective duration.
        // Resolved PER SESSION (provider + service), not once per provider - a therapist may
        // earn a different commission for different services (or duration variants).
        $has_override = $row['override_commission_type'] !== null;
        $commission_type = $has_override ? $row['override_commission_type'] : ($row['commission_type'] ?: 'percentage');
        $commission_value = $has_override ? (float) $row['override_commission_value'] : (float) $row['commission_value'];

        if ($commission_type === 'fixed') {
            $payout = $commission_value;
        } elseif ($commission_type === 'hourly') {
            $payout = $commission_value * ($duration_minutes / 60);

            // A one-time fixed bonus (provider-level, not overridable per service) added
            // whenever the EFFECTIVE session duration exceeds 60 minutes - once per session,
            // regardless of how far past the hour it runs.
            if ($duration_minutes > 60) {
                $payout += (float) $row['commission_overtime_bonus'];
            }
        } else {
            // 'percentage'
            $payout = $price * ($commission_value / 100);
        }

        return [
            'minutes' => $duration_minutes,
            'price' => $price,
            'payout' => $payout,
        ];
    }

    /**
     * Calculate total available (working) minutes for a provider in a date range.
     *
     * Uses the provider's working_plan (stored in user_settings as JSON) and any
     * working_plan_exceptions that overlap the date range. Accounts for daily breaks.
     *
     * @param int $provider_id The provider's user ID
     * @param string $date_from Date in YYYY-MM-DD format (inclusive)
     * @param string $date_to Date in YYYY-MM-DD format (inclusive)
     *
     * @return int Total available minutes
     */
    public function calculate_available_minutes(int $provider_id, string $date_from, string $date_to): int
    {
        // Fetch the provider's working plan (JSON)
        $this->db->select('working_plan')->from('user_settings')->where('id_users', $provider_id);
        $row = $this->db->get()->row_array();

        if (!$row || empty($row['working_plan'])) {
            return 0;
        }

        $plan = json_decode($row['working_plan'], true) ?: [];

        // Fetch exceptions that overlap the date range
        $this->db
            ->select('start_date, end_date, start_time, end_time, breaks')
            ->from('working_plan_exceptions')
            ->where('id_users_provider', $provider_id)
            ->where('start_date <=', $date_to)
            ->where('end_date >=', $date_from);
        $exceptions_rows = $this->db->get()->result_array();

        // Index exceptions by date for fast lookup
        $exceptions_by_date = [];
        foreach ($exceptions_rows as $ex) {
            $cursor = strtotime($ex['start_date']);
            $end = strtotime($ex['end_date']);
            while ($cursor <= $end) {
                $exceptions_by_date[date('Y-m-d', $cursor)] = $ex;
                $cursor = strtotime('+1 day', $cursor);
            }
        }

        $total_minutes = 0;
        $cursor = strtotime($date_from);
        $end = strtotime($date_to);

        while ($cursor <= $end) {
            $date_str = date('Y-m-d', $cursor);
            $weekday = strtolower(date('l', $cursor));

            if (isset($exceptions_by_date[$date_str])) {
                $ex = $exceptions_by_date[$date_str];
                $start_time = $ex['start_time'];
                $end_time = $ex['end_time'];
                $breaks = $ex['breaks'] ? (json_decode($ex['breaks'], true) ?: []) : [];
            } else {
                $day_plan = $plan[$weekday] ?? null;
                $start_time = $day_plan['start'] ?? null;
                $end_time = $day_plan['end'] ?? null;
                $breaks = $day_plan['breaks'] ?? [];
            }

            if ($start_time && $end_time) {
                $day_minutes = (strtotime($end_time) - strtotime($start_time)) / 60;
                foreach ($breaks as $break) {
                    if (!empty($break['start']) && !empty($break['end'])) {
                        $day_minutes -= (strtotime($break['end']) - strtotime($break['start'])) / 60;
                    }
                }
                $total_minutes += max(0, $day_minutes);
            }

            $cursor = strtotime('+1 day', $cursor);
        }

        return (int) $total_minutes;
    }
}
