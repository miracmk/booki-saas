<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Online Appointment Scheduler
 *
 * @package     KiReservation
 * @author      Ki Software
 * @copyright   Copyright (c) Ki Software
 * @license     Proprietary - see LICENSE file
 * @link        https://kisoftware.com
 * ---------------------------------------------------------------------------- */

/**
 * Notifications library.
 *
 * Handles the notifications related functionality.
 *
 * @package Libraries
 */
class Notifications
{
    /**
     * @var EA_Controller|CI_Controller
     */
    protected EA_Controller|CI_Controller $CI;

    /**
     * Notifications constructor.
     */
    public function __construct()
    {
        $this->CI = &get_instance();

        $this->CI->load->model('admins_model');
        $this->CI->load->model('appointments_model');
        $this->CI->load->model('providers_model');
        $this->CI->load->model('secretaries_model');
        $this->CI->load->model('settings_model');
        // Ki Reservation (Dalga 1) - SMS/WhatsApp channel settings, see send_sms()/send_whatsapp().
        $this->CI->load->model('messaging_settings_model');

        $this->CI->load->library('email_messages');
        $this->CI->load->library('ics_file');
        $this->CI->load->library('timezones');
        // Salon Flora customization - native Telegram channel, alongside email (see send_telegram()).
        $this->CI->load->library('telegram_client');
    }

    /**
     * Salon Flora customization - best-effort native Telegram notification for a recipient who has
     * linked their Telegram account (see Telegram.php). No-ops silently if Telegram notifications are
     * disabled, the recipient never linked a chat, or the bot isn't configured - Telegram is always an
     * addition to email, never a replacement staff can get locked out by.
     *
     * @param array $user Recipient row (must have 'telegram_chat_id').
     * @param string $text
     */
    private function send_telegram(array $user, string $text): void
    {
        if (empty($user['telegram_chat_id'])) {
            return;
        }

        if (!filter_var(setting('telegram_notifications_enabled'), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        try {
            $this->CI->telegram_client->send_message($user['telegram_chat_id'], $text);
        } catch (Throwable $e) {
            $this->log_exception($e, 'telegram notification', $user['id'] ?? null);
        }
    }

    /**
     * Ki Reservation (Dalga 1) - best-effort SMS notification via whichever gateway is configured
     * in messaging_settings (currently: Netgsm). No-ops silently if SMS notifications are disabled,
     * no gateway is configured, or the recipient has no phone number - mirrors send_telegram()'s
     * degrade-gracefully contract. This is the first real appointment-notification call site for
     * Sms_gateway_factory, which previously existed unused.
     *
     * @param array $user Recipient row (must have 'phone_number').
     * @param string $text
     */
    private function send_sms(array $user, string $text): void
    {
        if (empty($user['phone_number'])) {
            return;
        }

        $settings = $this->CI->messaging_settings_model->get_settings();

        if (!filter_var($settings['sms_notifications_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        try {
            $gateway = Sms_gateway_factory::make($settings);

            if ($gateway === null) {
                return;
            }

            $gateway->send($user['phone_number'], $text);
        } catch (Throwable $e) {
            $this->log_exception($e, 'sms notification', $user['id'] ?? null);
        }
    }

    /**
     * Ki Reservation (Dalga 1) - best-effort WhatsApp notification via the WhatsApp Business API.
     * No-ops silently if WhatsApp notifications are disabled, the gateway isn't configured, or the
     * recipient has no phone number - mirrors send_telegram()'s degrade-gracefully contract. This is
     * the first appointment-notification call site for Whatsapp_client; previously it was only used
     * for manual staff replies and the inbound webhook (see Whatsapp.php).
     *
     * @param array $user Recipient row (must have 'phone_number').
     * @param string $text
     */
    private function send_whatsapp(array $user, string $text): void
    {
        if (empty($user['phone_number'])) {
            return;
        }

        $settings = $this->CI->messaging_settings_model->get_settings();

        if (!filter_var($settings['whatsapp_notifications_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        try {
            $whatsapp_client = new Whatsapp_client(
                $settings['whatsapp_phone_number_id'],
                $settings['whatsapp_access_token'],
            );

            if (!$whatsapp_client->is_configured()) {
                return;
            }

            $whatsapp_client->send_text($user['phone_number'], $text);
        } catch (Throwable $e) {
            $this->log_exception($e, 'whatsapp notification', $user['id'] ?? null);
        }
    }

    /**
     * Ki Reservation (Dalga 1) - notify a waitlist entry's customer that a matching slot has opened
     * up. Sends via whichever channel(s) the entry requested (see waitlist_entries.notify_channel),
     * each independently best-effort - a failure on one channel never blocks the other or the caller
     * (Waitlist_service::check_and_notify_on_opening(), itself called from the non-blocking
     * appointment-cancellation hooks in Calendar.php/Appointments.php).
     *
     * @param array $customer Customer row (must have 'id', 'phone_number').
     * @param array $service Service data.
     * @param string $slot_datetime The freed slot's start datetime ('Y-m-d H:i:s').
     * @param string $channel One of: sms, whatsapp, both.
     */
    public function notify_waitlist_slot_available(
        array $customer,
        array $service,
        string $slot_datetime,
        string $channel,
    ): void {
        try {
            $formatted_datetime = date('d.m.Y H:i', strtotime($slot_datetime));

            $text = sprintf(
                '%s için %s tarihinde bir randevu yeri açıldı. Randevunuzu almak için lütfen bizi arayın.',
                $service['name'] ?? 'Hizmet',
                $formatted_datetime,
            );

            if ($channel === 'sms' || $channel === 'both') {
                $this->send_sms($customer, $text);
            }

            if ($channel === 'whatsapp' || $channel === 'both') {
                $this->send_whatsapp($customer, $text);
            }
        } catch (Throwable $e) {
            $this->log_exception($e, 'waitlist slot available notification', $customer['id'] ?? null);
        }
    }

    /**
     * Send the required notifications, related to an appointment creation/modification.
     *
     * @param array $appointment Appointment data.
     * @param array $service Service data.
     * @param array $provider Provider data.
     * @param array $customer Customer data.
     * @param array $settings Required settings.
     * @param bool|false $manage_mode Manage mode.
     * @param bool $notify_customer Salon Flora customization - whether to email the customer.
     * @param bool $notify_provider Salon Flora customization - whether to email the provider.
     * @param bool $notify_admin Salon Flora customization - whether to email admins/secretaries.
     */
    public function notify_appointment_saved(
        array $appointment,
        array $service,
        array $provider,
        array $customer,
        array $settings,
        bool $manage_mode = false,
        bool $notify_customer = true,
        bool $notify_provider = true,
        bool $notify_admin = true,
    ): void {
        try {
            $current_language = config('language');

            $customer_link = site_url('booking/reschedule/' . $appointment['hash']);

            $provider_link = site_url('calendar/reschedule/' . $appointment['hash']);

            $ics_stream = $this->CI->ics_file->get_stream($appointment, $service, $provider, $customer);

            // Salon Flora customization - providers only get the customer's name in their
            // notification emails; contact details (email, phone, address, custom fields)
            // stay business-exclusive (customer's own copy and admin copies keep full data).
            $provider_customer = $this->restrict_customer_details($customer);

            // Notify customer.
            $send_customer =
                $notify_customer &&
                !empty($customer['email']) &&
                filter_var(setting('customer_notifications'), FILTER_VALIDATE_BOOLEAN);

            if ($send_customer === true) {
                config(['language' => $customer['language']]);
                $this->CI->lang->load('translations');
                $subject = $manage_mode ? lang('appointment_details_changed') : lang('appointment_booked');
                $message = $manage_mode ? '' : lang('thank_you_for_appointment');

                try {
                    $this->CI->email_messages->send_appointment_saved(
                        $appointment,
                        $provider,
                        $service,
                        $customer,
                        $settings,
                        $subject,
                        $message,
                        $customer_link,
                        $customer['email'],
                        $ics_stream,
                        $customer['timezone'],
                        'customer',
                    );
                } catch (Throwable $e) {
                    $this->log_exception($e, 'appointment-saved to customer', $appointment['id'] ?? null);
                }

                $this->send_telegram($customer, $subject . "\n" . $service['name'] . ' - ' . $provider['first_name'] . ' ' . $provider['last_name'] . "\n" . $appointment['start_datetime']);
            }

            // Notify provider.
            $send_provider =
                $notify_provider &&
                filter_var(
                    $this->CI->providers_model->get_setting($provider['id'], 'notifications'),
                    FILTER_VALIDATE_BOOLEAN,
                );

            if ($send_provider === true) {
                config(['language' => $provider['language']]);
                $this->CI->lang->load('translations');
                $subject = $manage_mode ? lang('appointment_details_changed') : lang('appointment_added_to_your_plan');
                $message = $manage_mode ? '' : lang('appointment_link_description');

                try {
                    $this->CI->email_messages->send_appointment_saved(
                        $appointment,
                        $provider,
                        $service,
                        $provider_customer,
                        $settings,
                        $subject,
                        $message,
                        $provider_link,
                        $provider['email'],
                        $ics_stream,
                        $provider['timezone'],
                        'provider',
                    );
                } catch (Throwable $e) {
                    $this->log_exception($e, 'appointment-saved to provider', $appointment['id'] ?? null);
                }

                $this->send_telegram($provider, $subject . "\n" . $service['name'] . ' - ' . $provider_customer['first_name'] . "\n" . $appointment['start_datetime']);
            }

            // Notify admins. Salon Flora customization - gated behind the "yönetici" tick; secretaries
            // below share this same tick since they're staff/management too and the notify dialog only
            // exposes three ticks (customer / provider / admin).
            $admins = $notify_admin ? $this->CI->admins_model->get() : [];

            foreach ($admins as $admin) {
                if ($admin['settings']['notifications'] === '0') {
                    continue;
                }

                config(['language' => $admin['language']]);
                $this->CI->lang->load('translations');
                $subject = $manage_mode ? lang('appointment_details_changed') : lang('appointment_added_to_your_plan');
                $message = $manage_mode ? '' : lang('appointment_link_description');

                try {
                    $this->CI->email_messages->send_appointment_saved(
                        $appointment,
                        $provider,
                        $service,
                        $customer,
                        $settings,
                        $subject,
                        $message,
                        $provider_link,
                        $admin['email'],
                        $ics_stream,
                        $admin['timezone'],
                        'admin',
                    );
                } catch (Throwable $e) {
                    $this->log_exception($e, 'appointment-saved to admin', $appointment['id'] ?? null);
                }

                $this->send_telegram($admin, $subject . "\n" . $customer['first_name'] . ' ' . $customer['last_name'] . ' - ' . $service['name'] . "\n" . $appointment['start_datetime']);
            }

            // Notify secretaries (shares the "yönetici" tick with admins - see above).
            $secretaries = $notify_admin ? $this->CI->secretaries_model->get() : [];

            foreach ($secretaries as $secretary) {
                if ($secretary['settings']['notifications'] === '0') {
                    continue;
                }

                if (!in_array($provider['id'], $secretary['providers'])) {
                    continue;
                }

                config(['language' => $secretary['language']]);
                $this->CI->lang->load('translations');
                $subject = $manage_mode ? lang('appointment_details_changed') : lang('appointment_added_to_your_plan');
                $message = $manage_mode ? '' : lang('appointment_link_description');

                try {
                    $this->CI->email_messages->send_appointment_saved(
                        $appointment,
                        $provider,
                        $service,
                        $provider_customer,
                        $settings,
                        $subject,
                        $message,
                        $provider_link,
                        $secretary['email'],
                        $ics_stream,
                        $secretary['timezone'],
                        'secretary',
                    );
                } catch (Throwable $e) {
                    $this->log_exception($e, 'appointment-saved to secretary', $appointment['id'] ?? null);
                }

                $this->send_telegram($secretary, $subject . "\n" . $provider_customer['first_name'] . ' - ' . $service['name'] . "\n" . $appointment['start_datetime']);
            }
        } catch (Throwable $e) {
            $this->log_exception($e, 'appointment-saved (general exception)', $appointment['id'] ?? null);
        } finally {
            config(['language' => $current_language ?? 'english']);
            $this->CI->lang->load('translations');
        }
    }

    /**
     * Send the required notifications, related to an appointment removal.
     *
     * @param array $appointment Appointment data.
     * @param array $service Service data.
     * @param array $provider Provider data.
     * @param array $customer Customer data.
     * @param array $settings Required settings.
     */
    public function notify_appointment_deleted(
        array $appointment,
        array $service,
        array $provider,
        array $customer,
        array $settings,
        string $cancellation_reason = '',
    ): void {
        try {
            $current_language = config('language');

            // Salon Flora customization - see notify_appointment_saved() for rationale.
            $provider_customer = $this->restrict_customer_details($customer);

            // Notify provider.
            $send_provider = filter_var(
                $this->CI->providers_model->get_setting($provider['id'], 'notifications'),
                FILTER_VALIDATE_BOOLEAN,
            );

            if ($send_provider === true) {
                config(['language' => $provider['language']]);
                $this->CI->lang->load('translations');

                try {
                    $this->CI->email_messages->send_appointment_deleted(
                        $appointment,
                        $provider,
                        $service,
                        $provider_customer,
                        $settings,
                        $provider['email'],
                        $cancellation_reason,
                        $provider['timezone'],
                        'provider',
                    );
                } catch (Throwable $e) {
                    $this->log_exception($e, 'appointment-deleted to provider', $appointment['id'] ?? null);
                }
            }

            // Notify customer.
            $send_customer =
                !empty($customer['email']) && filter_var(setting('customer_notifications'), FILTER_VALIDATE_BOOLEAN);

            if ($send_customer === true) {
                config(['language' => $customer['language']]);
                $this->CI->lang->load('translations');

                try {
                    $this->CI->email_messages->send_appointment_deleted(
                        $appointment,
                        $provider,
                        $service,
                        $customer,
                        $settings,
                        $customer['email'],
                        $cancellation_reason,
                        $customer['timezone'],
                        'customer',
                    );
                } catch (Throwable $e) {
                    $this->log_exception($e, 'appointment-deleted to customer', $appointment['id'] ?? null);
                }
            }

            // Notify admins.
            $admins = $this->CI->admins_model->get();

            foreach ($admins as $admin) {
                if ($admin['settings']['notifications'] === '0') {
                    continue;
                }

                config(['language' => $admin['language']]);
                $this->CI->lang->load('translations');

                try {
                    $this->CI->email_messages->send_appointment_deleted(
                        $appointment,
                        $provider,
                        $service,
                        $customer,
                        $settings,
                        $admin['email'],
                        $cancellation_reason,
                        $admin['timezone'],
                        'admin',
                    );
                } catch (Throwable $e) {
                    $this->log_exception($e, 'appointment-deleted to admin', $appointment['id'] ?? null);
                }
            }

            // Notify secretaries.
            $secretaries = $this->CI->secretaries_model->get();

            foreach ($secretaries as $secretary) {
                if ($secretary['settings']['notifications'] === '0') {
                    continue;
                }

                if (!in_array($provider['id'], $secretary['providers'])) {
                    continue;
                }

                config(['language' => $secretary['language']]);
                $this->CI->lang->load('translations');

                try {
                    $this->CI->email_messages->send_appointment_deleted(
                        $appointment,
                        $provider,
                        $service,
                        $provider_customer,
                        $settings,
                        $secretary['email'],
                        $cancellation_reason,
                        $secretary['timezone'],
                        'secretary',
                    );
                } catch (Throwable $e) {
                    $this->log_exception($e, 'appointment-deleted to secretary', $appointment['id'] ?? null);
                }
            }
        } catch (Throwable $e) {
            log_message(
                'error',
                'Notifications - Could not email cancellation details of appointment (' .
                    ($appointment['id'] ?? '-') .
                    ') : ' .
                    $e->getMessage(),
            );
            log_message('error', $e->getTraceAsString());
        } finally {
            config(['language' => $current_language ?? 'english']);
            $this->CI->lang->load('translations');
        }
    }

    /**
     * Salon Flora customization - strip a customer array down to just the name (plus the
     * fields email templates need for delivery, e.g. language) so that provider/secretary
     * notification emails never leak contact details. Full details stay reserved for the
     * customer's own copy and the business (admin) copies.
     *
     * @param array $customer Full customer data.
     *
     * @return array Restricted customer data.
     */
    private function restrict_customer_details(array $customer): array
    {
        return [
            'first_name' => $customer['first_name'] ?? '',
            'last_name' => $customer['last_name'] ?? '',
            'language' => $customer['language'] ?? 'english',
            'timezone' => $customer['timezone'] ?? null,
        ];
    }

    private function log_exception(Throwable $e, string $message, ?int $appointment_id): void
    {
        log_message(
            'error',
            'Notifications - Could not email ' . $message . ' (' . ($appointment_id ?? '-') . ') : ' . $e->getMessage(),
        );
        log_message('error', $e->getTraceAsString());
    }
}
