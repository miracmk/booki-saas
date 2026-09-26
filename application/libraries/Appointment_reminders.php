<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Appointment reminders library.
 *
 * Sends multi-offset appointment reminders for the current tenant. Shared by the
 * CLI cron (Console::send_reminders -> run(false)) and the manual "Run reminders"
 * button (Messaging_settings::run_reminders -> run(true)).
 *
 * Timing is driven by messaging_settings.reminder_offsets: up to 4 integer
 * offsets (hours before the appointment start). A per-appointment JSON set
 * (appointments.reminders_notified) prevents re-sending an offset that has
 * already been dispatched.
 *
 * @package Libraries
 * ---------------------------------------------------------------------------- */

class Appointment_reminders
{
    /**
     * @var App_Controller|CI_Controller
     */
    protected App_Controller|CI_Controller $CI;

    /**
     * Appointment_reminders constructor.
     */
    public function __construct()
    {
        $this->CI = &get_instance();

        $this->CI->load->library('notifications');
        $this->CI->load->library('channel_templates');
        $this->CI->load->model('appointments_model');
        $this->CI->load->model('customers_model');
        $this->CI->load->model('services_model');
        $this->CI->load->model('providers_model');
        $this->CI->load->model('messaging_settings_model');
    }

    /**
     * Run the reminder sweep for the current tenant.
     *
     * @param bool $force When true, send regardless of reminder_notifications_enabled
     *                    (legacy manual-trigger behaviour). When false, tenants that
     *                    disabled appointment reminders are skipped.
     * @return int Number of reminder notifications dispatched.
     */
    public function run(bool $force = false): int
    {
        $msg_settings = $this->CI->messaging_settings_model->get_settings();

        if (!$force) {
            // Tenant has disabled appointment reminders.
            if (isset($msg_settings['reminder_notifications_enabled']) && !(bool) $msg_settings['reminder_notifications_enabled']) {
                return 0;
            }
        }

        $offsets = $this->CI->messaging_settings_model->normalize_reminder_offsets(
            $msg_settings['reminder_offsets'] ?? null
        );

        // No offsets configured -> nothing to send.
        if (empty($offsets)) {
            return 0;
        }

        $max_offset = max($offsets);

        $now = date('Y-m-d H:i:s');
        $target_time = date('Y-m-d H:i:s', strtotime("+{$max_offset} hours"));

        // Upcoming confirmed/reserved appointments within the widest reminder window.
        $this->CI->db
            ->from('appointments')
            ->where('is_unavailability', false)
            ->where('start_datetime >=', $now)
            ->where('start_datetime <=', $target_time)
            ->where_not_in('status', ['Cancelled', 'Draft']);

        $appointments = $this->CI->db->get()->result_array();

        $company_settings = [
            'company_name' => setting('company_name'),
            'company_link' => setting('company_link'),
            'company_email' => setting('company_email'),
            'company_color' => setting('company_color'),
            'company_address' => setting('company_address'),
            'company_phone' => setting('company_phone'),
            'date_format' => setting('date_format'),
            'time_format' => setting('time_format'),
        ];

        $now_ts = time();
        $has_reminders_notified = $this->CI->db->field_exists('reminders_notified', 'appointments');
        $has_is_reminder_sent = $this->CI->db->field_exists('is_reminder_sent', 'appointments');
        $has_reminder_sent_at = $this->CI->db->field_exists('reminder_sent_at', 'appointments');

        $sent_count = 0;
        foreach ($appointments as $appointment) {
            try {
                $service = $this->CI->services_model->find((int) $appointment['id_services']) ?: [];
                $provider = $this->CI->providers_model->find((int) $appointment['id_users_provider']) ?: [];
                $customer = $this->CI->customers_model->find((int) $appointment['id_users_customer']) ?: [];

                if (empty($customer)) {
                    continue;
                }

                $start_ts = strtotime((string) ($appointment['start_datetime'] ?? ''));
                if (!$start_ts) {
                    continue;
                }

                $notified = $has_reminders_notified
                    ? $this->decode_notified($appointment['reminders_notified'] ?? null)
                    : [];

                // Determine which configured offsets are due (appointment start is at
                // most `offset` hours away) and have not been dispatched yet.
                $due = [];
                foreach ($offsets as $offset) {
                    if ($start_ts <= $now_ts + ($offset * 3600) && !in_array($offset, $notified, true)) {
                        $due[] = $offset;
                    }
                }

                if (empty($due)) {
                    continue;
                }

                // Send only the largest due offset per run so multi-offset reminders
                // are naturally spaced across consecutive cron invocations.
                $send_offset = max($due);

                $this->CI->notifications->notify_appointment_reminder(
                    $appointment,
                    $provider,
                    $service,
                    $customer,
                    $company_settings
                );
                $sent_count++;

                // Record the dispatched offset so it is not re-sent later.
                $notified[] = $send_offset;
                sort($notified);

                $update = [];
                if ($has_reminders_notified) {
                    $update['reminders_notified'] = json_encode(array_values($notified));
                }
                if ($has_reminder_sent_at) {
                    $update['reminder_sent_at'] = date('Y-m-d H:i:s');
                }

                // is_reminder_sent becomes 1 only once every configured offset has been notified.
                if ($has_is_reminder_sent) {
                    $update['is_reminder_sent'] = count(array_diff($offsets, $notified)) === 0 ? 1 : 0;
                }

                if (!empty($update)) {
                    $this->CI->db->where('id', (int) $appointment['id'])->update('appointments', $update);
                }
            } catch (Throwable $e) {
                log_message('error', 'Appointment_reminders failed for apt ' . $appointment['id'] . ': ' . $e->getMessage());
            }
        }

        return $sent_count;
    }

    /**
     * Decode the stored "offsets already notified" JSON into an int array.
     */
    private function decode_notified(?string $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? array_map('intval', $decoded) : [];
    }
}