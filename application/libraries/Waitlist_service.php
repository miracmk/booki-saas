<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Waitlist (Dalga 1, 2026-08-28).
 *
 * Orchestrates joining the waitlist and notifying matching entries when a
 * slot opens up (an appointment is cancelled/deleted). Called from the two
 * appointment-deletion entry points (Calendar.php::delete_appointment() and
 * Appointments.php::destroy()), always wrapped in a non-blocking try/catch by
 * the caller - a notification failure must never prevent the underlying
 * cancellation from succeeding.
 * ---------------------------------------------------------------------------- */
class Waitlist_service
{
    /**
     * @var EA_Controller|CI_Controller
     */
    protected EA_Controller|CI_Controller $CI;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->CI = &get_instance();

        $this->CI->load->model('waitlist_model');
        $this->CI->load->model('customers_model');
        $this->CI->load->model('services_model');
        $this->CI->load->library('notifications');
    }

    /**
     * Join the waitlist for a service (optionally with a specific provider and/or date/time
     * window). Expires stale 'notified' entries first, so a customer who is about to be
     * re-notified for a different slot doesn't get skipped over by their own stale claim window.
     *
     * @param array $entry [
     *   'id_users_customer' => int,
     *   'id_services' => int,
     *   'id_users_provider' => ?int,
     *   'requested_date' => ?string ('Y-m-d'),
     *   'requested_time_window_start' => ?string ('H:i'),
     *   'requested_time_window_end' => ?string ('H:i'),
     *   'notify_channel' => 'sms'|'whatsapp'|'both',
     * ]
     *
     * @return int The new waitlist entry ID.
     */
    public function join(array $entry): int
    {
        $this->CI->waitlist_model->expire_stale();

        return $this->CI->waitlist_model->save($entry);
    }

    /**
     * Check whether any waitlist entries match a slot that just opened up (an appointment was
     * cancelled/deleted), and notify them. Notifies the single oldest matching entry (first-come-
     * first-served) rather than every match at once, to avoid multiple customers racing for the
     * same now-open slot; if that entry's claim window lapses without a booking,
     * Waitlist_model::expire_stale() reverts it and the next matching entry becomes eligible on
     * the next check.
     *
     * @param array $freed_appointment The appointment row that was just cancelled/deleted (must
     *   have 'id_services', 'id_users_provider', 'start_datetime').
     */
    public function check_and_notify_on_opening(array $freed_appointment): void
    {
        if (empty($freed_appointment['id_services']) || empty($freed_appointment['id_users_provider'])) {
            return;
        }

        if (empty($freed_appointment['start_datetime'])) {
            return;
        }

        $this->CI->waitlist_model->expire_stale();

        $matches = $this->CI->waitlist_model->get_matching_entries(
            (int) $freed_appointment['id_services'],
            (int) $freed_appointment['id_users_provider'],
            $freed_appointment['start_datetime'],
        );

        if (empty($matches)) {
            return;
        }

        $entry = $matches[0];

        $customer = $this->CI->customers_model->find((int) $entry['id_users_customer']);
        $service = $this->CI->services_model->find((int) $entry['id_services']);

        $this->CI->waitlist_model->mark_notified((int) $entry['id'], $entry['notify_channel'] ?? 'both');

        $this->CI->notifications->notify_waitlist_slot_available(
            $customer,
            $service,
            $freed_appointment['start_datetime'],
            $entry['notify_channel'] ?? 'both',
        );
    }

    /**
     * Proactively notify a waiting customer about an upcoming or open slot.
     *
     * @param int $entry_id Waitlist entry ID.
     * @param string|null $slot_datetime Datetime of the available slot.
     * @param string $channel sms, whatsapp, or both.
     * @return array Result information.
     */
    public function proactively_notify_customer(int $entry_id, ?string $slot_datetime = null, string $channel = 'both'): array
    {
        $entry = $this->CI->waitlist_model->find($entry_id);

        if (!$entry) {
            throw new InvalidArgumentException('Bekleme listesi kaydı bulunamadı: ' . $entry_id);
        }

        $customer = $this->CI->customers_model->find((int) $entry['id_users_customer']);
        $service = $this->CI->services_model->find((int) $entry['id_services']);

        if (!$customer || !$service) {
            throw new RuntimeException('Müşteri veya hizmet bilgisi bulunamadı.');
        }

        if (empty($slot_datetime)) {
            $slot_datetime = !empty($entry['requested_date'])
                ? $entry['requested_date'] . ' ' . ($entry['requested_time_window_start'] ?? '10:00:00')
                : date('Y-m-d H:i:s', strtotime('+1 day 10:00'));
        }

        $this->CI->waitlist_model->mark_notified($entry_id, $channel, 30);

        $this->CI->notifications->notify_waitlist_slot_available(
            $customer,
            $service,
            $slot_datetime,
            $channel
        );

        return [
            'entry_id' => $entry_id,
            'customer_name' => trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')),
            'service_name' => $service['name'] ?? '',
            'slot_datetime' => $slot_datetime,
            'channel' => $channel,
            'status' => 'notified',
        ];
    }
}
