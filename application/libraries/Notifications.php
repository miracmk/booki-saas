<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Online Appointment Scheduler
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
        // BooKi (Dalga 1) - SMS/WhatsApp channel settings, see send_sms()/send_whatsapp().
        $this->CI->load->model('messaging_settings_model');

        $this->CI->load->library('email_messages');
        $this->CI->load->library('ics_file');
        $this->CI->load->library('timezones');
        // Salon Flora customization - native Telegram channel, alongside email (see send_telegram()).
        $this->CI->load->library('telegram_client');
        // BooKi (Dalga 2) - queue gate for send_sms()/send_whatsapp()/send_telegram() and
        // the appointment-saved/deleted email paths. MUST be loaded here - every one of those methods
        // calls $this->CI->queue->enabled(), which would fatal ("call to a member function on null")
        // on every single notification send if this library were never loaded, regardless of whether
        // the queue is actually enabled. Confirmed missing via code review (php -l cannot catch a
        // missing library load - that only shows up at runtime) and verified fixed by an actual
        // Docker run (see project notes) before this code shipped.
        $this->CI->load->library('queue');
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
    public function send_telegram(array $user, string $text): void
    {
        // Queue if enabled; fall through to synchronous send if queue is disabled or push fails.
        if ($this->CI->queue->enabled()) {
            if ($this->CI->queue->push(
                'telegram',
                'notifications.send_telegram',
                [
                    'user_id' => $user['id'] ?? null,
                    'text' => $text,
                ],
            ) !== null) {
                return;
            }
        }

        $this->do_send_telegram($user, $text);
    }

    /**
     * Internal helper: perform the actual Telegram send (no queue check).
     *
     * @param array $user Recipient row.
     * @param string $text
     */
    private function do_send_telegram(array $user, string $text): void
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
     * BooKi (Dalga 1) - best-effort SMS notification via whichever gateway is configured
     * in messaging_settings (currently: Netgsm). No-ops silently if SMS notifications are disabled,
     * no gateway is configured, or the recipient has no phone number - mirrors send_telegram()'s
     * degrade-gracefully contract. This is the first real appointment-notification call site for
     * Sms_gateway_factory, which previously existed unused.
     *
     * @param array $user Recipient row (must have 'phone_number').
     * @param string $text
     */
    public function send_sms(array $user, string $text): void
    {
        // Queue if enabled; fall through to synchronous send if queue is disabled or push fails.
        if ($this->CI->queue->enabled()) {
            if ($this->CI->queue->push(
                'sms',
                'notifications.send_sms',
                [
                    'user_id' => $user['id'] ?? null,
                    'text' => $text,
                ],
            ) !== null) {
                return;
            }
        }

        $this->do_send_sms($user, $text);
    }

    /**
     * Internal helper: perform the actual SMS send (no queue check).
     *
     * @param array $user Recipient row.
     * @param string $text
     */
    private function do_send_sms(array $user, string $text): void
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
     * BooKi (Dalga 1) - best-effort WhatsApp notification via the WhatsApp Business API.
     * No-ops silently if WhatsApp notifications are disabled, the gateway isn't configured, or the
     * recipient has no phone number - mirrors send_telegram()'s degrade-gracefully contract. This is
     * the first appointment-notification call site for Whatsapp_client; previously it was only used
     * for manual staff replies and the inbound webhook (see Whatsapp.php).
     *
     * @param array $user Recipient row (must have 'phone_number').
     * @param string $text
     */
    public function send_whatsapp(array $user, string $text): void
    {
        // Queue if enabled; fall through to synchronous send if queue is disabled or push fails.
        if ($this->CI->queue->enabled()) {
            if ($this->CI->queue->push(
                'whatsapp',
                'notifications.send_whatsapp',
                [
                    'user_id' => $user['id'] ?? null,
                    'text' => $text,
                ],
            ) !== null) {
                return;
            }
        }

        $this->do_send_whatsapp($user, $text);
    }

    /**
     * Internal helper: perform the actual WhatsApp send (no queue check).
     *
     * @param array $user Recipient row.
     * @param string $text
     */
    private function do_send_whatsapp(array $user, string $text): void
    {
        if (empty($user['phone_number'])) {
            return;
        }

        $settings = $this->CI->messaging_settings_model->get_settings();

        if (!filter_var($settings['whatsapp_notifications_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        try {
            // BooKi (Dalga 3 / Faz 3.5) - dual-mode sender routing. The
            // whatsapp_mode setting decides which transport carries notifications:
            //   official   -> Meta WhatsApp Business Cloud API (Whatsapp_client)
            //   unofficial -> the ki-wa-bridge Node sidecar (Whatsapp_bridge).
            // Both are best-effort: a failure logs and is silently dropped, never
            // re-thrown into the appointment flow.
            if (($settings['whatsapp_mode'] ?? 'official') === 'unofficial') {
                if (!class_exists('Whatsapp_bridge', false)) {
                    $this->CI->load->library('whatsapp_bridge');
                }

                $bridge = new Whatsapp_bridge($settings['whatsapp_bridge_url'], $settings['whatsapp_bridge_secret']);
                if (!$bridge->is_configured()) {
                    return;
                }

                $bridge->send($this->tenant_identifier(), $user['phone_number'], $text);

                return;
            }

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
     * BooKi (2026-09-11) - send the customer's extra notification channel (on top of the
     * email that's already sent unconditionally elsewhere) through whichever channel the tenant
     * picked in Ayarlar > SMS ve WhatsApp Ayarları > Bildirim Motoru. Defaults to 'telegram' (see
     * migration 136) so existing tenants keep the exact behavior notify_appointment_saved() always
     * had before this setting existed. 'email' means "no extra channel" - email already covers it.
     * Each underlying send_*() call is already self-gating (its own enabled flag + configured-ness
     * check) and best-effort, so this never needs its own try/catch.
     */
    private function dispatch_default_channel(array $user, string $text): void
    {
        $settings = $this->CI->messaging_settings_model->get_settings();
        $channel = $settings['default_notification_channel'] ?? 'telegram';

        match ($channel) {
            'sms' => $this->send_sms($user, $text),
            'whatsapp' => $this->send_whatsapp($user, $text),
            'telegram' => $this->send_telegram($user, $text),
            default => null,
        };
    }

    /**
     * BooKi (Dalga 3 / Faz 3.5) - stable tenant identifier for the
     * WhatsApp bridge session keys. Multi-tenant mode uses the tenant's subdomain;
     * standalone deployments fall back to a fixed 'default' key so the sidecar's
     * per-tenant session storage stays uniform.
     */
    private function tenant_identifier(): string
    {
        $context = tenant_context();

        return $context['subdomain'] ?? 'default';
    }

    /**
     * BooKi (Dalga 3 / Faz 3.1) - generic best-effort email for the Communication Hub's
     * email channel. Unlike the specialized appointment emails (which carry full appointment data
     * and an ICS attachment), this is a short, plain notice built by the hub from its rule template.
     * No-ops silently if the recipient has no email address - mirrors the other channels' degrade-
     * gracefully contract. The optional '_recipient_type' key on $user (set by Communication_hub)
     * lets the queued handler re-fetch the correct role table even when user IDs collide across
     * role tables.
     *
     * @param array $user Recipient row (must have 'email').
     * @param string $subject
     * @param string $text
     */
    public function send_generic_email(array $user, string $subject, string $text): void
    {
        if (empty($user['email'])) {
            return;
        }

        // Queue if enabled; fall through to synchronous send if queue is disabled or push fails.
        if ($this->CI->queue->enabled()) {
            if ($this->CI->queue->push(
                'email',
                'notifications.generic_email',
                [
                    'user_id' => $user['id'] ?? null,
                    'recipient_type' => $user['_recipient_type'] ?? null,
                    'subject' => $subject,
                    'text' => $text,
                ],
            ) !== null) {
                return;
            }
        }

        $this->do_send_generic_email($user, $subject, $text);
    }

    /**
     * Internal helper: perform the actual generic email send (no queue check).
     *
     * @param array $user Recipient row.
     * @param string $subject
     * @param string $text
     */
    private function do_send_generic_email(array $user, string $subject, string $text): void
    {
        if (empty($user['email'])) {
            return;
        }

        try {
            $this->CI->email_messages->send_simple_html($user['email'], $subject, $text);
        } catch (Throwable $e) {
            $this->log_exception($e, 'hub generic email', $user['id'] ?? null);
        }
    }

    /**
     * BooKi (Dalga 1) - notify a waitlist entry's customer that a matching slot has opened
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
                $email_queued = false;

                // Attempt to queue email; if successful, skip synchronous send.
                if ($this->CI->queue->enabled()) {
                    if ($this->CI->queue->push(
                        'email',
                        'notifications.appointment_saved_email',
                        [
                            'appointment_id' => $appointment['id'] ?? null,
                            'recipient_type' => 'customer',
                            'recipient_id' => null,
                            'manage_mode' => $manage_mode,
                        ],
                    ) !== null) {
                        $email_queued = true;
                    }
                }

                if (!$email_queued) {
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
                } else {
                    // Email was queued, but we still need to define subject for telegram
                    config(['language' => $customer['language']]);
                    $this->CI->lang->load('translations');
                    $subject = $manage_mode ? lang('appointment_details_changed') : lang('appointment_booked');
                }

                $this->dispatch_default_channel(
                    $customer,
                    $subject . "\n" . $service['name'] . ' - ' . $provider['first_name'] . ' ' . $provider['last_name'] . "\n" . $appointment['start_datetime'],
                );
            }

            // Notify provider.
            $send_provider =
                $notify_provider &&
                filter_var(
                    $this->CI->providers_model->get_setting($provider['id'], 'notifications'),
                    FILTER_VALIDATE_BOOLEAN,
                );

            if ($send_provider === true) {
                $email_queued = false;

                // Attempt to queue email; if successful, skip synchronous send.
                if ($this->CI->queue->enabled()) {
                    if ($this->CI->queue->push(
                        'email',
                        'notifications.appointment_saved_email',
                        [
                            'appointment_id' => $appointment['id'] ?? null,
                            'recipient_type' => 'provider',
                            'recipient_id' => null,
                            'manage_mode' => $manage_mode,
                        ],
                    ) !== null) {
                        $email_queued = true;
                    }
                }

                if (!$email_queued) {
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
                } else {
                    // Email was queued, but we still need to define subject for telegram
                    config(['language' => $provider['language']]);
                    $this->CI->lang->load('translations');
                    $subject = $manage_mode ? lang('appointment_details_changed') : lang('appointment_added_to_your_plan');
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

                $email_queued = false;

                // Attempt to queue email; if successful, skip synchronous send.
                if ($this->CI->queue->enabled()) {
                    if ($this->CI->queue->push(
                        'email',
                        'notifications.appointment_saved_email',
                        [
                            'appointment_id' => $appointment['id'] ?? null,
                            'recipient_type' => 'admin',
                            'recipient_id' => $admin['id'] ?? null,
                            'manage_mode' => $manage_mode,
                        ],
                    ) !== null) {
                        $email_queued = true;
                    }
                }

                if (!$email_queued) {
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
                } else {
                    // Email was queued, but we still need to define subject for telegram
                    config(['language' => $admin['language']]);
                    $this->CI->lang->load('translations');
                    $subject = $manage_mode ? lang('appointment_details_changed') : lang('appointment_added_to_your_plan');
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

                $email_queued = false;

                // Attempt to queue email; if successful, skip synchronous send.
                if ($this->CI->queue->enabled()) {
                    if ($this->CI->queue->push(
                        'email',
                        'notifications.appointment_saved_email',
                        [
                            'appointment_id' => $appointment['id'] ?? null,
                            'recipient_type' => 'secretary',
                            'recipient_id' => $secretary['id'] ?? null,
                            'manage_mode' => $manage_mode,
                        ],
                    ) !== null) {
                        $email_queued = true;
                    }
                }

                if (!$email_queued) {
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
                } else {
                    // Email was queued, but we still need to define subject for telegram
                    config(['language' => $secretary['language']]);
                    $this->CI->lang->load('translations');
                    $subject = $manage_mode ? lang('appointment_details_changed') : lang('appointment_added_to_your_plan');
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
        // BooKi (Dalga 2) - deliberately NOT queued, unlike notify_appointment_saved().
        // By the time this method is called, the appointment row is already DELETED from the DB
        // (Calendar.php/Appointments.php call appointments_model->delete() BEFORE calling this) -
        // a queued job storing only appointment_id could never re-fetch it later, since there is
        // nothing left to fetch. Queueing this would require putting full appointment/customer PII
        // into the job payload, which violates this queue's "IDs only" contract. Same category of
        // decision as Recovery.php's password-reset email staying synchronous - not every send site
        // is safe to queue, and this one genuinely isn't without a larger redesign.
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

                $this->dispatch_default_channel(
                    $customer,
                    lang('appointment_cancelled_title') . "\n" . $service['name'] . ' - ' . $provider['first_name'] . ' ' . $provider['last_name'] . "\n" . $appointment['start_datetime'],
                );
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

    /**
     * Re-fetch a queued recipient across the role tables.
     *
     * When a known 'recipient_type' is supplied (customer/provider/admin/secretary - the hub
     * always sets one), look up ONLY that table. This avoids the legacy cross-table scan's
     * collision hazard: user IDs are not guaranteed unique across role tables, so a bare
     * id-first-scan can resolve to the wrong person. When no type is given (legacy callers),
     * fall back to the original admins -> providers -> secretaries -> customers scan.
     */
    private function find_queued_recipient($CI, int $user_id, ?string $recipient_type): ?array
    {
        if ($recipient_type === 'customer') {
            $CI->load->model('customers_model');

            return $CI->customers_model->find($user_id) ?: null;
        }

        if ($recipient_type === 'provider') {
            return $CI->providers_model->find($user_id) ?: null;
        }

        if ($recipient_type === 'secretary') {
            return $CI->secretaries_model->find($user_id) ?: null;
        }

        if ($recipient_type === 'admin') {
            return $CI->admins_model->find($user_id) ?: null;
        }

        // Legacy fallback: cross-table scan, first match wins.
        $user = $CI->admins_model->find($user_id);

        if (!$user) {
            $user = $CI->providers_model->find($user_id);
        }

        if (!$user) {
            $user = $CI->secretaries_model->find($user_id);
        }

        if (!$user) {
            $CI->load->model('customers_model');
            $user = $CI->customers_model->find($user_id);
        }

        return $user ?: null;
    }

    /**
     * Queued handler for SMS notifications (called by Job_dispatcher).
     * Re-fetches the user and sends the SMS via the configured gateway.
     *
     * @param EA_Controller|CI_Controller $CI
     * @param array $payload Must contain 'user_id' and 'text'; 'recipient_type' optional.
     */
    public function handle_queued_sms($CI, array $payload): void
    {
        try {
            $user_id = $payload['user_id'] ?? null;
            $text = $payload['text'] ?? '';

            if (!$user_id || !$text) {
                return;
            }

            $user = $this->find_queued_recipient($CI, (int) $user_id, $payload['recipient_type'] ?? null);

            if (!$user) {
                log_message('warning', 'Notifications::handle_queued_sms() - User not found: ' . $user_id);
                return;
            }

            $this->do_send_sms($user, $text);
        } catch (Throwable $e) {
            log_message('error', 'Notifications::handle_queued_sms() failed: ' . $e->getMessage());
            log_message('error', $e->getTraceAsString());
        }
    }

    /**
     * Queued handler for WhatsApp notifications (called by Job_dispatcher).
     * Re-fetches the user and sends the WhatsApp message via the configured gateway.
     *
     * @param EA_Controller|CI_Controller $CI
     * @param array $payload Must contain 'user_id' and 'text'; 'recipient_type' optional.
     */
    public function handle_queued_whatsapp($CI, array $payload): void
    {
        try {
            $user_id = $payload['user_id'] ?? null;
            $text = $payload['text'] ?? '';

            if (!$user_id || !$text) {
                return;
            }

            $user = $this->find_queued_recipient($CI, (int) $user_id, $payload['recipient_type'] ?? null);

            if (!$user) {
                log_message('warning', 'Notifications::handle_queued_whatsapp() - User not found: ' . $user_id);
                return;
            }

            $this->do_send_whatsapp($user, $text);
        } catch (Throwable $e) {
            log_message('error', 'Notifications::handle_queued_whatsapp() failed: ' . $e->getMessage());
            log_message('error', $e->getTraceAsString());
        }
    }

    /**
     * Queued handler for Telegram notifications (called by Job_dispatcher).
     * Re-fetches the user and sends the Telegram message via the configured bot.
     *
     * @param EA_Controller|CI_Controller $CI
     * @param array $payload Must contain 'user_id' and 'text'; 'recipient_type' optional.
     */
    public function handle_queued_telegram($CI, array $payload): void
    {
        try {
            $user_id = $payload['user_id'] ?? null;
            $text = $payload['text'] ?? '';

            if (!$user_id || !$text) {
                return;
            }

            $user = $this->find_queued_recipient($CI, (int) $user_id, $payload['recipient_type'] ?? null);

            if (!$user) {
                log_message('warning', 'Notifications::handle_queued_telegram() - User not found: ' . $user_id);
                return;
            }

            $this->do_send_telegram($user, $text);
        } catch (Throwable $e) {
            log_message('error', 'Notifications::handle_queued_telegram() failed: ' . $e->getMessage());
            log_message('error', $e->getTraceAsString());
        }
    }

    /**
     * BooKi (Dalga 3 / Faz 3.1) - queued handler for the Communication Hub's generic
     * email channel (called by Job_dispatcher). Re-fetches the user and sends a simple HTML mail.
     *
     * @param EA_Controller|CI_Controller $CI
     * @param array $payload Must contain 'user_id', 'subject', 'text'; 'recipient_type' optional.
     */
    public function handle_queued_generic_email($CI, array $payload): void
    {
        try {
            $user_id = $payload['user_id'] ?? null;
            $subject = (string) ($payload['subject'] ?? '');
            $text = (string) ($payload['text'] ?? '');

            if (!$user_id) {
                return;
            }

            $user = $this->find_queued_recipient($CI, (int) $user_id, $payload['recipient_type'] ?? null);

            if (!$user) {
                log_message('warning', 'Notifications::handle_queued_generic_email() - User not found: ' . $user_id);
                return;
            }

            $this->do_send_generic_email($user, $subject, $text);
        } catch (Throwable $e) {
            log_message('error', 'Notifications::handle_queued_generic_email() failed: ' . $e->getMessage());
            log_message('error', $e->getTraceAsString());
        }
    }

    /**
     * Queued handler for appointment saved email notifications (called by Job_dispatcher).
     * Re-fetches the appointment and related data, then sends the email to the specified recipient.
     *
     * @param EA_Controller|CI_Controller $CI
     * @param array $payload Must contain 'appointment_id', 'recipient_type', 'recipient_id', 'manage_mode'
     */
    public function handle_queued_appointment_saved_email($CI, array $payload): void
    {
        try {
            $appointment_id = $payload['appointment_id'] ?? null;
            $recipient_type = $payload['recipient_type'] ?? '';
            $recipient_id = $payload['recipient_id'] ?? null;
            $manage_mode = (bool) ($payload['manage_mode'] ?? false);

            if (!$appointment_id) {
                return;
            }

            // Re-fetch appointment
            $appointment = $CI->appointments_model->find($appointment_id);
            if (!$appointment) {
                log_message('warning', 'Notifications::handle_queued_appointment_saved_email() - Appointment not found: ' . $appointment_id);
                return;
            }

            // Re-fetch service
            $CI->load->model('services_model');
            $service = $CI->services_model->find($appointment['id_services']);
            if (!$service) {
                log_message('warning', 'Notifications::handle_queued_appointment_saved_email() - Service not found for appointment: ' . $appointment_id);
                return;
            }

            // Re-fetch provider
            $provider = $CI->providers_model->find($appointment['id_users_provider']);
            if (!$provider) {
                log_message('warning', 'Notifications::handle_queued_appointment_saved_email() - Provider not found for appointment: ' . $appointment_id);
                return;
            }

            // Re-fetch customer
            $CI->load->model('customers_model');
            $customer = $CI->customers_model->find($appointment['id_users_customer']);
            if (!$customer) {
                log_message('warning', 'Notifications::handle_queued_appointment_saved_email() - Customer not found for appointment: ' . $appointment_id);
                return;
            }

            // Re-fetch settings
            $settings = $CI->settings_model->get();

            // Generate links
            $customer_link = site_url('booking/reschedule/' . $appointment['hash']);
            $provider_link = site_url('calendar/reschedule/' . $appointment['hash']);

            // Generate ICS stream
            $CI->load->library('ics_file');
            $ics_stream = $CI->ics_file->get_stream($appointment, $service, $provider, $customer);

            // Restrict customer details for provider/secretary
            $provider_customer = $this->restrict_customer_details($customer);

            // Determine recipient email and data based on type
            $recipient_email = '';
            $recipient_data = null;
            $recipient_language = 'english';
            $recipient_timezone = null;

            if ($recipient_type === 'customer') {
                $recipient_email = $customer['email'] ?? '';
                $recipient_data = $customer;
                $recipient_language = $customer['language'] ?? 'english';
                $recipient_timezone = $customer['timezone'] ?? null;
            } elseif ($recipient_type === 'provider') {
                $recipient_email = $provider['email'] ?? '';
                $recipient_data = $provider_customer;
                $recipient_language = $provider['language'] ?? 'english';
                $recipient_timezone = $provider['timezone'] ?? null;
            } elseif ($recipient_type === 'admin') {
                $admin = $CI->admins_model->find($recipient_id);
                if (!$admin) {
                    log_message('warning', 'Notifications::handle_queued_appointment_saved_email() - Admin not found: ' . $recipient_id);
                    return;
                }
                $recipient_email = $admin['email'] ?? '';
                $recipient_data = $customer;
                $recipient_language = $admin['language'] ?? 'english';
                $recipient_timezone = $admin['timezone'] ?? null;
            } elseif ($recipient_type === 'secretary') {
                $secretary = $CI->secretaries_model->find($recipient_id);
                if (!$secretary) {
                    log_message('warning', 'Notifications::handle_queued_appointment_saved_email() - Secretary not found: ' . $recipient_id);
                    return;
                }
                $recipient_email = $secretary['email'] ?? '';
                $recipient_data = $provider_customer;
                $recipient_language = $secretary['language'] ?? 'english';
                $recipient_timezone = $secretary['timezone'] ?? null;
            } else {
                log_message('warning', 'Notifications::handle_queued_appointment_saved_email() - Unknown recipient type: ' . $recipient_type);
                return;
            }

            if (!$recipient_email) {
                return; // Silently skip if no email available
            }

            // Set language for subject/message generation
            config(['language' => $recipient_language]);
            $CI->lang->load('translations');

            $subject = $manage_mode ? lang('appointment_details_changed') : lang('appointment_booked');
            $message = $manage_mode ? '' : lang('thank_you_for_appointment');

            try {
                $CI->email_messages->send_appointment_saved(
                    $appointment,
                    $provider,
                    $service,
                    $recipient_data,
                    $settings,
                    $subject,
                    $message,
                    ($recipient_type === 'customer') ? $customer_link : $provider_link,
                    $recipient_email,
                    $ics_stream,
                    $recipient_timezone,
                    $recipient_type,
                );
            } catch (Throwable $e) {
                log_message('error', 'Notifications::handle_queued_appointment_saved_email() failed for ' . $recipient_type . ': ' . $e->getMessage());
                log_message('error', $e->getTraceAsString());
            }
        } catch (Throwable $e) {
            log_message('error', 'Notifications::handle_queued_appointment_saved_email() exception: ' . $e->getMessage());
            log_message('error', $e->getTraceAsString());
        }
    }
}
