<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Recurring Appointments (Dalga 1, 2026-08-28).
 *
 * Creates a series of appointments (e.g. "every week for 8 weeks") by calling
 * Appointment_booking_service::create() once per occurrence - never a bulk
 * insert. Each occurrence goes through the exact same service/provider/
 * availability/station-lock validation as a single manual booking, so a
 * recurring series can never bypass the security-hardened booking path.
 *
 * A conflicting occurrence (slot no longer available on that future date) is
 * SKIPPED and reported back to the caller, not silently forced - these are
 * future dates the customer did not individually confirm one at a time, so
 * defaulting to a hard conflict-override would be surprising. Staff callers
 * may still resolve conflicts manually afterwards via the normal calendar
 * conflict-override flow (migration 095), appointment by appointment.
 * ---------------------------------------------------------------------------- */
class Recurrence_service
{
    /**
     * @var EA_Controller|CI_Controller
     */
    protected EA_Controller|CI_Controller $CI;

    private const VALID_FREQUENCIES = ['weekly', 'biweekly', 'monthly'];

    private const MAX_OCCURRENCES = 52;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->CI = &get_instance();

        $this->CI->load->model('recurrence_groups_model');
        $this->CI->load->model('customers_model');
        $this->CI->load->library('appointment_booking_service');
    }

    /**
     * Create a series of recurring appointments.
     *
     * @param array $appointment_template Base appointment data (same shape as
     *   Appointment_booking_service::create()'s $appointment argument):
     *   ['id_services' => int, 'id_users_provider' => int,
     *    'start_datetime' => 'Y-m-d H:i:s', ... other optional fields].
     *   The provided start_datetime is used for the first occurrence only;
     *   subsequent occurrences are computed from it.
     * @param array $customer Customer data (same shape as
     *   Appointment_booking_service::create()'s $customer argument).
     * @param string $frequency One of: weekly, biweekly, monthly.
     * @param int $occurrences_total Number of occurrences to attempt (1-52).
     * @param int $interval_count Repeat every N periods (e.g. 2 + weekly =
     *   every other week). Defaults to 1.
     * @param int|null $created_by User ID of the staff member creating the
     *   series (null for public/self-service bookings).
     *
     * @return array [
     *   'group_id' => int,
     *   'created_appointment_ids' => int[],
     *   'skipped' => [['occurrence' => int, 'date' => string, 'reason' => string], ...],
     * ]
     *
     * @throws InvalidArgumentException On invalid frequency/occurrence count/template.
     */
    public function create_series(
        array $appointment_template,
        array $customer,
        string $frequency,
        int $occurrences_total,
        int $interval_count = 1,
        ?int $created_by = null,
    ): array {
        if (!in_array($frequency, self::VALID_FREQUENCIES, true)) {
            throw new InvalidArgumentException('Invalid recurrence frequency: ' . $frequency);
        }

        if ($occurrences_total < 1 || $occurrences_total > self::MAX_OCCURRENCES) {
            throw new InvalidArgumentException(
                'Occurrences total must be between 1 and ' . self::MAX_OCCURRENCES . '.',
            );
        }

        if ($interval_count < 1) {
            throw new InvalidArgumentException('Interval count must be at least 1.');
        }

        if (empty($appointment_template['id_services']) || empty($appointment_template['id_users_provider'])) {
            throw new InvalidArgumentException('Service and provider are required to create a recurring series.');
        }

        if (empty($appointment_template['start_datetime'])) {
            throw new InvalidArgumentException('A start date/time is required to create a recurring series.');
        }

        try {
            $first_start = new DateTime($appointment_template['start_datetime']);
        } catch (Exception $e) {
            throw new InvalidArgumentException('Invalid start_datetime provided.');
        }

        // Resolve (or create) the customer once, up front, so every occurrence in the
        // series is attributed to the same customer record.
        $customer_id = $this->CI->customers_model->save($customer);
        $customer = $this->CI->customers_model->find($customer_id);

        $group_id = $this->CI->recurrence_groups_model->save([
            'id_created_by' => $created_by,
            'id_services' => (int) $appointment_template['id_services'],
            'id_users_provider' => (int) $appointment_template['id_users_provider'],
            'id_users_customer' => $customer_id,
            'frequency' => $frequency,
            'interval_count' => $interval_count,
            'occurrences_total' => $occurrences_total,
            'start_date' => $first_start->format('Y-m-d'),
        ]);

        $created_appointment_ids = [];
        $skipped = [];

        for ($sequence = 1; $sequence <= $occurrences_total; $sequence++) {
            $occurrence_start = $this->add_period(clone $first_start, $frequency, $interval_count, $sequence - 1);

            $occurrence_appointment = $appointment_template;
            $occurrence_appointment['start_datetime'] = $occurrence_start->format('Y-m-d H:i:s');
            $occurrence_appointment['id_recurrence_group'] = $group_id;
            $occurrence_appointment['recurrence_sequence'] = $sequence;

            $result = $this->CI->appointment_booking_service->create($occurrence_appointment, $customer);

            if ($result['success']) {
                $created_appointment_ids[] = $result['appointment_id'];
                $this->CI->recurrence_groups_model->increment_occurrences_created($group_id);
            } else {
                $skipped[] = [
                    'occurrence' => $sequence,
                    'date' => $occurrence_start->format('Y-m-d H:i:s'),
                    'reason' => $result['error'] ?? 'unknown_error',
                    'message' => $result['message'] ?? null,
                ];

                log_message(
                    'warning',
                    sprintf(
                        'Recurrence_service::create_series - occurrence %d of group %d skipped: %s',
                        $sequence,
                        $group_id,
                        $result['error'] ?? 'unknown_error',
                    ),
                );
            }
        }

        $this->CI->recurrence_groups_model->mark_status(
            $group_id,
            count($created_appointment_ids) > 0 ? 'active' : 'cancelled',
        );

        return [
            'group_id' => $group_id,
            'created_appointment_ids' => $created_appointment_ids,
            'skipped' => $skipped,
        ];
    }

    /**
     * Add N periods of the given frequency to a date.
     *
     * @param DateTime $date Base date (mutated and returned).
     * @param string $frequency weekly, biweekly, or monthly.
     * @param int $interval_count Repeat-every-N-periods multiplier.
     * @param int $periods_elapsed How many periods have already elapsed (0 = the base date itself).
     *
     * @return DateTime
     */
    private function add_period(DateTime $date, string $frequency, int $interval_count, int $periods_elapsed): DateTime
    {
        if ($periods_elapsed === 0) {
            return $date;
        }

        $multiplier = $periods_elapsed * $interval_count;

        switch ($frequency) {
            case 'weekly':
                $date->modify('+' . ($multiplier * 7) . ' days');
                break;

            case 'biweekly':
                $date->modify('+' . ($multiplier * 14) . ' days');
                break;

            case 'monthly':
                $date->modify('+' . $multiplier . ' months');
                break;
        }

        return $date;
    }
}
