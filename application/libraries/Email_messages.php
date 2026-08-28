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

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

/**
 * Email messages library.
 *
 * Handles the email messaging related functionality.
 *
 * Salon Flora customization: each of the templates below can be overridden from the backend
 * (Settings > Şablonlar) via a "{{placeholder}}" / "{{#if placeholder}}...{{/if}}" mini-templating
 * language - see render_email() and render_custom_template(). appointment_saved and
 * appointment_deleted additionally have a SEPARATE template per recipient role (customer / admin /
 * secretary / provider) - e.g. the business can word the provider's "new appointment" email
 * differently than the customer's confirmation, without touching the customer-facing one. The
 * stored value lives in the "settings" table under the keys in self::TEMPLATE_SETTING_KEYS. When
 * empty, behavior is 100% unchanged from stock: the original .php view is rendered exactly as
 * before, for every role. The custom engine intentionally never executes PHP or admin-supplied
 * <script> - it's plain string substitution - because the stored HTML is ultimately authored
 * through a settings page and must stay safe to both email and (for the live preview) render
 * inside the admin's own browser.
 *
 * @package Libraries
 */
class Email_messages
{
    /**
     * Salon Flora customization - settings-table keys for the customizable templates. Shared with
     * Email_template_settings so the settings page and the actual mailer agree on the same keys.
     * appointment_saved/appointment_deleted are nested by recipient role; account_recovery/
     * password_reset have no role-specific content (the recipient's own account, same text
     * regardless of who they are) so they stay a single flat key.
     */
    public const TEMPLATE_SETTING_KEYS = [
        'appointment_saved' => [
            'customer' => 'email_template_appointment_saved_customer',
            'admin' => 'email_template_appointment_saved_admin',
            'secretary' => 'email_template_appointment_saved_secretary',
            'provider' => 'email_template_appointment_saved_provider',
        ],
        'appointment_deleted' => [
            'customer' => 'email_template_appointment_deleted_customer',
            'admin' => 'email_template_appointment_deleted_admin',
            'secretary' => 'email_template_appointment_deleted_secretary',
            'provider' => 'email_template_appointment_deleted_provider',
        ],
        'account_recovery' => 'email_template_account_recovery',
        'password_reset' => 'email_template_password_reset',
    ];

    /**
     * @var EA_Controller|CI_Controller
     */
    protected EA_Controller|CI_Controller $CI;

    /**
     * Email_messages constructor.
     */
    public function __construct()
    {
        $this->CI = &get_instance();

        $this->CI->load->model('admins_model');
        $this->CI->load->model('appointments_model');
        $this->CI->load->model('providers_model');
        $this->CI->load->model('secretaries_model');
        $this->CI->load->model('secretaries_model');
        $this->CI->load->model('settings_model');

        $this->CI->load->library('email');
        $this->CI->load->library('ics_file');
        $this->CI->load->library('timezones');
    }

    /**
     * Send an email with the appointment details.
     *
     * @param array $appointment Appointment data.
     * @param array $provider Provider data.
     * @param array $service Service data.
     * @param array $customer Customer data.
     * @param array $settings App settings.
     * @param string $subject Email subject.
     * @param string $message Email message.
     * @param string $appointment_link Appointment unique URL.
     * @param string $recipient_email Recipient email address.
     * @param string $ics_stream ICS file contents.
     * @param string|null $timezone Custom timezone.
     * @param string $recipient_role Salon Flora customization - one of 'customer', 'admin',
     *   'secretary', 'provider'. Selects which of the four saved templates to use.
     *
     * @throws DateInvalidTimeZoneException
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function send_appointment_saved(
        array $appointment,
        array $provider,
        array $service,
        array $customer,
        array $settings,
        string $subject,
        string $message,
        string $appointment_link,
        string $recipient_email,
        string $ics_stream,
        ?string $timezone = null,
        string $recipient_role = 'customer',
    ): void {
        $appointment_timezone = new DateTimeZone($provider['timezone']);

        $appointment_start = new DateTime($appointment['start_datetime'], $appointment_timezone);

        $appointment_end = new DateTime($appointment['end_datetime'], $appointment_timezone);

        if ($timezone && $timezone !== $provider['timezone']) {
            $custom_timezone = new DateTimeZone($timezone);

            $appointment_start->setTimezone($custom_timezone);
            $appointment['start_datetime'] = $appointment_start->format('Y-m-d H:i:s');

            $appointment_end->setTimezone($custom_timezone);
            $appointment['end_datetime'] = $appointment_end->format('Y-m-d H:i:s');
        }

        $html = $this->render_email(
            'appointment_saved',
            $recipient_role,
            'emails/appointment_saved_email',
            [
                'subject' => $subject,
                'message' => $message,
                'appointment' => $appointment,
                'service' => $service,
                'provider' => $provider,
                'customer' => $customer,
                'settings' => $settings,
                'timezone' => $timezone,
                'appointment_link' => $appointment_link,
            ],
            $this->build_appointment_saved_placeholders(
                $subject,
                $message,
                $appointment,
                $service,
                $provider,
                $customer,
                $settings,
                $timezone,
                $appointment_link,
            ),
        );

        $php_mailer = $this->get_php_mailer($recipient_email, $subject, $html);

        $php_mailer->addStringAttachment($ics_stream, 'invitation.ics', PHPMailer::ENCODING_BASE64, 'text/calendar');

        $php_mailer->send();
    }

    /**
     * Send an email with the appointment removal details.
     *
     * @param array $appointment Appointment data.
     * @param array $provider Provider data.
     * @param array $service Service data.
     * @param array $customer Customer data.
     * @param array $settings App settings.
     * @param string $recipient_email Recipient email address.
     * @param string|null $reason Removal reason.
     * @param string|null $timezone Custom timezone.
     * @param string $recipient_role Salon Flora customization - one of 'customer', 'admin',
     *   'secretary', 'provider'. Selects which of the four saved templates to use.
     *
     * @throws DateInvalidTimeZoneException
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function send_appointment_deleted(
        array $appointment,
        array $provider,
        array $service,
        array $customer,
        array $settings,
        string $recipient_email,
        ?string $reason = null,
        ?string $timezone = null,
        string $recipient_role = 'customer',
    ): void {
        $appointment_timezone = new DateTimeZone($provider['timezone']);

        $appointment_start = new DateTime($appointment['start_datetime'], $appointment_timezone);

        $appointment_end = new DateTime($appointment['end_datetime'], $appointment_timezone);

        if ($timezone && $timezone !== $provider['timezone']) {
            $custom_timezone = new DateTimeZone($timezone);

            $appointment_start->setTimezone($custom_timezone);
            $appointment['start_datetime'] = $appointment_start->format('Y-m-d H:i:s');

            $appointment_end->setTimezone($custom_timezone);
            $appointment['end_datetime'] = $appointment_end->format('Y-m-d H:i:s');
        }

        $html = $this->render_email(
            'appointment_deleted',
            $recipient_role,
            'emails/appointment_deleted_email',
            [
                'appointment' => $appointment,
                'service' => $service,
                'provider' => $provider,
                'customer' => $customer,
                'settings' => $settings,
                'timezone' => $timezone,
                'reason' => $reason,
            ],
            $this->build_appointment_deleted_placeholders(
                $appointment,
                $service,
                $provider,
                $customer,
                $settings,
                $timezone,
                $reason,
            ),
        );

        $subject = lang('appointment_cancelled_title');

        $php_mailer = $this->get_php_mailer($recipient_email, $subject, $html);

        $php_mailer->send();
    }

    /**
     * Send the account recovery details.
     *
     * @param string $password New password.
     * @param string $recipient_email Recipient email address.
     * @param array $settings App settings.
     *
     * @throws Exception
     */
    public function send_password(string $password, string $recipient_email, array $settings): void
    {
        $subject = lang('new_account_password');

        $message = str_replace('$password', '<strong>' . $password . '</strong>', lang('new_password_is'));

        $html = $this->render_email(
            'account_recovery',
            null,
            'emails/account_recovery_email',
            [
                'subject' => $subject,
                'message' => $message,
                'settings' => $settings,
            ],
            [
                'subject' => $subject,
                'message' => $message,
                'company_name' => e($settings['company_name'] ?? ''),
                'company_link' => e($settings['company_link'] ?? ''),
            ],
        );

        $php_mailer = $this->get_php_mailer($recipient_email, $subject, $html);

        $php_mailer->send();
    }

    /**
     * Send the password reset link.
     *
     * @param string $reset_link The password reset URL.
     * @param string $recipient_email Recipient email address.
     * @param array $settings App settings.
     *
     * @throws Exception
     */
    public function send_password_reset_link(string $reset_link, string $recipient_email, array $settings): void
    {
        $subject = lang('password_reset_request');

        $message = lang('password_reset_email_message');

        $html = $this->render_email(
            'password_reset',
            null,
            'emails/password_reset_email',
            [
                'subject' => $subject,
                'message' => $message,
                'reset_link' => $reset_link,
                'settings' => $settings,
            ],
            [
                'subject' => $subject,
                'message' => $message,
                'reset_link' => e($reset_link),
                'company_name' => e($settings['company_name'] ?? ''),
                'company_link' => e($settings['company_link'] ?? ''),
            ],
        );

        $php_mailer = $this->get_php_mailer($recipient_email, $subject, $html);

        $php_mailer->send();
    }

    /**
     * Faz 30 (KVKK) - notify a customer that their requested data export is ready to download.
     * Deliberately not in TEMPLATE_SETTING_KEYS/render_email() - this is a one-off compliance
     * notice, not a business-branded customer touchpoint like appointment emails.
     *
     * @param string $download_link The one-time download URL (contains the raw token - never store this).
     * @param string $expires_human Human-readable expiry (e.g. "3 gün").
     * @param string $recipient_email Recipient email address.
     * @param array $settings App settings.
     *
     * @throws Exception
     */
    public function send_data_export_ready(string $download_link, string $expires_human, string $recipient_email, array $settings): void
    {
        $subject = lang('data_export_ready_subject');

        $message = lang('data_export_ready_message');

        $html = $this->CI->load->view(
            'emails/data_export_ready_email',
            [
                'subject' => $subject,
                'message' => $message,
                'download_link' => $download_link,
                'expires_human' => $expires_human,
                'settings' => $settings,
            ],
            true,
        );

        $php_mailer = $this->get_php_mailer($recipient_email, $subject, $html);

        $php_mailer->send();
    }

    /**
     * Salon Flora customization - render one of the customizable email templates: the business's
     * custom HTML (from Settings > Şablonlar) if one has been saved, otherwise the stock .php view,
     * unchanged.
     *
     * @param string $template_key One of self::TEMPLATE_SETTING_KEYS top-level keys.
     * @param string|null $role_slug Recipient role ('customer'/'admin'/'secretary'/'provider') for
     *   the two role-aware templates (appointment_saved/appointment_deleted); null for the two flat
     *   ones (account_recovery/password_reset).
     * @param string $default_view The stock CodeIgniter view name (unchanged rendering path).
     * @param array $view_data Data for the stock view.
     * @param array $placeholders Flat placeholder map for the custom template engine.
     */
    private function render_email(
        string $template_key,
        ?string $role_slug,
        string $default_view,
        array $view_data,
        array $placeholders,
    ): string {
        $keys = self::TEMPLATE_SETTING_KEYS[$template_key] ?? null;

        $setting_key = is_array($keys) ? ($keys[$role_slug] ?? null) : $keys;

        $custom_html = $setting_key ? trim((string) setting($setting_key, '')) : '';

        if ($custom_html !== '') {
            return $this->render_custom_template($custom_html, $placeholders);
        }

        return $this->CI->load->view($default_view, $view_data, true);
    }

    /**
     * Salon Flora customization - the custom template mini-language: "{{#if key}}...{{/if}}" blocks
     * (kept only when the placeholder is non-empty, non-nested) followed by plain "{{key}}"
     * substitution. Deliberately NOT a PHP template - admin-authored HTML must never be eval'd.
     *
     * The markers accept two forms: the bare "{{#if key}}"/"{{/if}}" form, and an HTML-comment-wrapped
     * "<!--{{#if key}}-->"/"<!--{{/if}}-->" form. The comment form exists because the admin panel's
     * "Görsel" (WYSIWYG/iframe designMode) editor round-trips the HTML through the browser's own
     * parser on every save - and per the HTML5 spec, a bare text node placed directly inside a
     * <table>/<tbody> (as a sibling of <tr>, with no wrapping <td>) is invalid content and gets
     * "foster-parented" out of the table by the browser. An HTML comment node, by contrast, is left
     * exactly where it was written even inside a table - so it survives that round-trip unchanged.
     * Both forms are supported so already-saved bare-marker templates keep rendering correctly.
     *
     * @param string $html Raw template HTML, as saved by the admin.
     * @param array $placeholders Flat map of placeholder name => already-safe HTML/text value.
     */
    public function render_custom_template(string $html, array $placeholders): string
    {
        $html = preg_replace_callback(
            '/(?:<!--\s*)?\{\{#if\s+([a-zA-Z0-9_]+)\}\}(?:\s*-->)?(.*?)(?:<!--\s*)?\{\{\/if\}\}(?:\s*-->)?/s',
            static function (array $matches) use ($placeholders): string {
                $value = $placeholders[$matches[1]] ?? '';

                return $value !== '' && $value !== null ? $matches[2] : '';
            },
            $html,
        );

        // Defensive cleanup: strip any leftover/unbalanced {{#if}}/{{/if}} marker (bare or
        // comment-wrapped) so a malformed template never leaks raw marker text into a sent email.
        $html = preg_replace('/(?:<!--\s*)?\{\{\s*[#\/]if(?:\s+[a-zA-Z0-9_]+)?\s*\}\}(?:\s*-->)?/', '', $html) ?? $html;

        return strtr($html, array_map(static fn($value) => (string) ($value ?? ''), $this->prefix_placeholder_keys($placeholders)));
    }

    /**
     * Wrap every placeholder key with "{{" / "}}" so strtr() can do a single-pass replacement.
     */
    private function prefix_placeholder_keys(array $placeholders): array
    {
        $wrapped = [];

        foreach ($placeholders as $key => $value) {
            $wrapped['{{' . $key . '}}'] = $value;
        }

        return $wrapped;
    }

    /**
     * Salon Flora customization - build the placeholder map for the appointment_saved template.
     * Every value is pre-escaped (or already-safe pre-built HTML) since it goes straight into the
     * admin-authored template with no further processing.
     */
    private function build_appointment_saved_placeholders(
        string $subject,
        string $message,
        array $appointment,
        array $service,
        array $provider,
        array $customer,
        array $settings,
        ?string $timezone,
        string $appointment_link,
    ): array {
        $common = $this->build_appointment_common_placeholders($appointment, $service, $provider, $customer, $settings, $timezone);

        return array_merge($common, [
            'subject' => $subject,
            'message' => $message,
            'appointment_link' => e($appointment_link),
        ]);
    }

    /**
     * Salon Flora customization - build the placeholder map for the appointment_deleted template.
     */
    private function build_appointment_deleted_placeholders(
        array $appointment,
        array $service,
        array $provider,
        array $customer,
        array $settings,
        ?string $timezone,
        ?string $reason,
    ): array {
        $common = $this->build_appointment_common_placeholders($appointment, $service, $provider, $customer, $settings, $timezone);

        return array_merge($common, [
            'cancellation_reason' => e($reason ?? ''),
        ]);
    }

    /**
     * Salon Flora customization - placeholders shared between appointment_saved and
     * appointment_deleted (service/provider/appointment/customer/company details).
     */
    private function build_appointment_common_placeholders(
        array $appointment,
        array $service,
        array $provider,
        array $customer,
        array $settings,
        ?string $timezone,
    ): array {
        $customer_first_name = trim((string) ($customer['first_name'] ?? ''));
        $customer_last_name = trim((string) ($customer['last_name'] ?? ''));
        $customer_full_name = trim($customer_first_name . ' ' . $customer_last_name);
        $customer_email = trim((string) ($customer['email'] ?? ''));
        $customer_phone_number = trim((string) ($customer['phone_number'] ?? ''));
        $customer_address = trim((string) ($customer['address'] ?? ''));

        $location = (string) ($appointment['location'] ?? '');
        $location_html = '';

        if ($location !== '') {
            $location_html = str_starts_with($location, 'http')
                ? '<a href="' . e($location) . '" target="_blank">' . e($location) . '</a>'
                : e($location);
        }

        $meeting_link = (string) ($appointment['meeting_link'] ?? '');
        $meeting_link_html = $meeting_link !== ''
            ? '<a href="' . e($meeting_link) . '" target="_blank">' . e($meeting_link) . '</a>'
            : '';

        return [
            'service_name' => e($service['name'] ?? ''),
            'service_description' => !empty($service['description']) ? nl2br(e($service['description'])) : '',
            'provider_name' => e(trim(($provider['first_name'] ?? '') . ' ' . ($provider['last_name'] ?? ''))),
            'appointment_start' => format_date_time($appointment['start_datetime']),
            'appointment_end' => format_date_time($appointment['end_datetime']),
            'appointment_timezone' => (string) format_timezone($timezone),
            'appointment_status' => !empty($appointment['status']) ? e($appointment['status']) : '',
            'appointment_location' => $location_html,
            'appointment_meeting_link' => $meeting_link_html,
            'appointment_notes' => !empty($appointment['notes']) ? e($appointment['notes']) : '',
            'customer_name' => $customer_full_name !== '' ? e($customer_full_name) : '',
            'customer_email' => $customer_email !== '' ? e($customer_email) : '',
            'customer_phone' => $customer_phone_number !== '' ? e($customer_phone_number) : '',
            'customer_address' => $customer_address !== '' ? e($customer_address) : '',
            'company_name' => e($settings['company_name'] ?? ''),
            'company_link' => e($settings['company_link'] ?? ''),
        ];
    }

    /**
     * Salon Flora customization - sample data for the Settings > Şablonlar live preview, where no
     * real appointment/customer exists yet. Used only by Email_template_settings::preview(). For
     * appointment_saved/appointment_deleted, $role_slug matters: secretary/provider previews blank
     * out the customer's email/phone/address, mirroring what Notifications::restrict_customer_details()
     * actually sends them - so the preview never overpromises what that role will really see.
     */
    public function get_sample_placeholders(string $template_key, ?string $role_slug = null): array
    {
        $settings = [
            'company_name' => setting('company_name', 'Salon Flora'),
            'company_link' => setting('company_link', 'https://salonflora.tr'),
        ];

        $restricted = in_array($role_slug, ['secretary', 'provider'], true);

        return match ($template_key) {
            'appointment_saved' => [
                'subject' => 'Randevunuz Onaylandı',
                'message' => 'Randevunuz için teşekkür ederiz.',
                'service_name' => 'Klasik Masaj',
                'service_description' => '60 dakikalık rahatlatıcı masaj seansı.',
                'provider_name' => 'Ayşe Yılmaz',
                'appointment_start' => format_date_time(date('Y-m-d H:i:s', strtotime('+1 day 10:00'))),
                'appointment_end' => format_date_time(date('Y-m-d H:i:s', strtotime('+1 day 11:00'))),
                'appointment_timezone' => 'Europe/Istanbul',
                'appointment_status' => 'Onaylandı',
                'appointment_location' => 'Salon Flora, İstanbul',
                'appointment_meeting_link' => '',
                'appointment_notes' => '',
                'customer_name' => 'Elif Demir',
                'customer_email' => $restricted ? '' : 'elif.demir@example.com',
                'customer_phone' => $restricted ? '' : '+90 555 000 00 00',
                'customer_address' => '',
                'appointment_link' => '#',
                'company_name' => e($settings['company_name']),
                'company_link' => e($settings['company_link']),
            ],
            'appointment_deleted' => [
                'service_name' => 'Klasik Masaj',
                'service_description' => '60 dakikalık rahatlatıcı masaj seansı.',
                'provider_name' => 'Ayşe Yılmaz',
                'appointment_start' => format_date_time(date('Y-m-d H:i:s', strtotime('+1 day 10:00'))),
                'appointment_end' => format_date_time(date('Y-m-d H:i:s', strtotime('+1 day 11:00'))),
                'appointment_timezone' => 'Europe/Istanbul',
                'appointment_status' => '',
                'appointment_location' => '',
                'appointment_meeting_link' => '',
                'appointment_notes' => '',
                'customer_name' => 'Elif Demir',
                'customer_email' => $restricted ? '' : 'elif.demir@example.com',
                'customer_phone' => $restricted ? '' : '+90 555 000 00 00',
                'customer_address' => '',
                'cancellation_reason' => 'Müşteri isteğiyle iptal edildi.',
                'company_name' => e($settings['company_name']),
                'company_link' => e($settings['company_link']),
            ],
            'account_recovery' => [
                'subject' => 'Yeni Hesap Şifresi',
                'message' => 'Yeni şifreniz: <strong>Or5k2LmZ</strong>',
                'company_name' => e($settings['company_name']),
                'company_link' => e($settings['company_link']),
            ],
            'password_reset' => [
                'subject' => 'Şifre Sıfırlama İsteği',
                'message' => 'Şifrenizi sıfırlamak için aşağıdaki bağlantıya tıklayın.',
                'reset_link' => '#',
                'company_name' => e($settings['company_name']),
                'company_link' => e($settings['company_link']),
            ],
            default => [],
        };
    }

    /**
     * Create PHP Mailer instance based on the email configuration.
     *
     * @param string|null $recipient_email
     * @param string|null $subject
     * @param string|null $html
     *
     * @return PHPMailer
     *
     * @throws Exception
     */
    private function get_php_mailer(
        ?string $recipient_email = null,
        ?string $subject = null,
        ?string $html = null,
    ): PHPMailer {
        $php_mailer = new PHPMailer(true);

        $php_mailer->CharSet = 'UTF-8';
        $php_mailer->SMTPDebug = config('smtp_debug') ? SMTP::DEBUG_SERVER : null;

        if (config('protocol') === 'smtp') {
            $php_mailer->isSMTP();
            $php_mailer->Host = config('smtp_host');
            $php_mailer->SMTPAuth = config('smtp_auth');
            $php_mailer->Username = config('smtp_user');
            $php_mailer->Password = config('smtp_pass');
            $php_mailer->SMTPSecure = config('smtp_crypto');
            $php_mailer->Port = config('smtp_port');
        }

        $from_name = config('from_name') ?: setting('company_name');
        $from_address = config('from_address') ?: setting('company_email');
        $reply_to_address = config('reply_to') ?: setting('company_email');

        $php_mailer->setFrom($from_address, $from_name);
        $php_mailer->addReplyTo($reply_to_address);

        if ($recipient_email) {
            $php_mailer->addAddress($recipient_email);
        }

        if ($subject) {
            $php_mailer->Subject = $subject;
        }

        if ($html) {
            $plain_text = str_replace(["\n\n", "\n\n\n"], '', strip_tags($html));

            if (config('mailtype') === 'html') {
                $php_mailer->isHTML();
            } else {
                $html = $plain_text;
            }

            $php_mailer->Body = $html;
            $php_mailer->AltBody = $plain_text;
        }

        $php_mailer->addEmbeddedImage(FCPATH . 'assets/img/logo.png', 'logo.png', 'logo.png', 'base64', 'image/png');

        return $php_mailer;
    }
}
