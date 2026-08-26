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
 * Email template settings controller.
 *
 * Salon Flora customization: brand-new controller (no stock equivalent). Lets the business admin
 * edit the customer/provider-facing email templates from a "Şablonlar" settings tab, with a live
 * preview. appointment_saved and appointment_deleted each have FOUR separate templates - one per
 * recipient role (customer/admin/secretary/provider) - so e.g. the provider's "new appointment"
 * email can be worded differently than the customer's confirmation. account_recovery/password_reset
 * are role-agnostic (same content regardless of who's resetting their password). Access is
 * hard-restricted to the DB_SLUG_ADMIN role regardless of PRIV_SYSTEM_SETTINGS grants, because this
 * is meant to stay an owner-only tool even if a future role edit grants secretaries system settings
 * access. See Email_messages::render_email() / render_custom_template() for how the saved HTML is
 * actually used when sending mail - it never executes PHP or admin-authored <script>.
 *
 * @package Controllers
 */
class Email_template_settings extends EA_Controller
{
    /**
     * Salon Flora customization - flat "template key" -> settings-table name whitelist. Keeps the
     * save/preview endpoints from being used to touch arbitrary "settings" table rows. Every key
     * here must exist, flattened, in Email_messages::TEMPLATE_SETTING_KEYS (duplicated as a literal,
     * not referenced directly, because Email_messages is only loaded on demand via
     * $this->load->library() - a class-constant expression referencing it here would be evaluated
     * before that load happens). The "email_template_" + key naming is also relied on by the
     * frontend JS (email_template_settings.js) to derive setting names without a manual map.
     */
    private const TEMPLATE_KEYS = [
        'appointment_saved_customer' => 'email_template_appointment_saved_customer',
        'appointment_saved_admin' => 'email_template_appointment_saved_admin',
        'appointment_saved_secretary' => 'email_template_appointment_saved_secretary',
        'appointment_saved_provider' => 'email_template_appointment_saved_provider',
        'appointment_deleted_customer' => 'email_template_appointment_deleted_customer',
        'appointment_deleted_admin' => 'email_template_appointment_deleted_admin',
        'appointment_deleted_secretary' => 'email_template_appointment_deleted_secretary',
        'appointment_deleted_provider' => 'email_template_appointment_deleted_provider',
        'account_recovery' => 'email_template_account_recovery',
        'password_reset' => 'email_template_password_reset',
    ];

    /**
     * Recipient roles that appointment_saved/appointment_deleted are split by.
     */
    private const RECIPIENT_ROLES = ['customer', 'admin', 'secretary', 'provider'];

    public function __construct()
    {
        parent::__construct();

        $this->load->model('settings_model');

        $this->load->library('accounts');
        $this->load->library('email_messages');
    }

    /**
     * Only the business admin may view or edit these templates.
     */
    private function require_admin(): void
    {
        $user_id = session('user_id');

        if (session('role_slug') !== DB_SLUG_ADMIN) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');
        }
    }

    /**
     * Render the backend "Şablonlar" settings page.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('email_template_settings')]);

        $this->require_admin();

        $user_id = session('user_id');

        $templates = [];

        foreach (self::TEMPLATE_KEYS as $template_key => $setting_key) {
            [$base_template, ] = $this->parse_template_key($template_key);

            $templates[$template_key] = [
                'value' => (string) setting($setting_key, ''),
                'default' => $this->read_default_template($base_template),
            ];
        }

        script_vars([
            'user_id' => $user_id,
            'role_slug' => session('role_slug'),
            'templates' => $templates,
        ]);

        html_vars([
            'page_title' => lang('settings'),
            'active_menu' => PRIV_SYSTEM_SETTINGS,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
        ]);

        $this->load->view('pages/email_template_settings');
    }

    /**
     * Save one or more templates.
     */
    public function save(): void
    {
        try {
            method('post');

            $this->require_admin();

            check('templates', 'array');

            $templates = request('templates', []);

            foreach ($templates as $template) {
                $name = $template['name'] ?? null;

                if (!in_array($name, self::TEMPLATE_KEYS, true)) {
                    throw new InvalidArgumentException('Unknown template setting name: ' . $name);
                }

                $value = $this->sanitize_template_html((string) ($template['value'] ?? ''));

                $existing_setting = $this->settings_model->query()->where('name', $name)->get()->row_array();

                $setting = ['name' => $name, 'value' => $value];

                if (!empty($existing_setting)) {
                    $setting['id'] = $existing_setting['id'];
                }

                $this->settings_model->save($setting);
            }

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Render a live preview of a (possibly unsaved) template against sample data.
     */
    public function preview(): void
    {
        try {
            method('post');

            $this->require_admin();

            check('template_key', 'string');
            check('html', 'string');

            $template_key = request('template_key');

            if (!array_key_exists($template_key, self::TEMPLATE_KEYS)) {
                throw new InvalidArgumentException('Unknown template key: ' . $template_key);
            }

            $html = $this->sanitize_template_html((string) request('html', ''));

            [$base_template, $role_slug] = $this->parse_template_key($template_key);

            $placeholders = $this->email_messages->get_sample_placeholders($base_template, $role_slug);

            $rendered = $this->email_messages->render_custom_template($html, $placeholders);

            json_response(['html' => $rendered]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Salon Flora customization - split a flat template key (e.g. "appointment_saved_provider")
     * into its base template ("appointment_saved") and recipient role ("provider"). Role-agnostic
     * keys (account_recovery/password_reset) come back with a null role.
     *
     * @return array{0: string, 1: ?string}
     */
    private function parse_template_key(string $template_key): array
    {
        foreach (['appointment_saved_', 'appointment_deleted_'] as $prefix) {
            if (str_starts_with($template_key, $prefix)) {
                $role_slug = substr($template_key, strlen($prefix));

                if (in_array($role_slug, self::RECIPIENT_ROLES, true)) {
                    return [rtrim($prefix, '_'), $role_slug];
                }
            }
        }

        return [$template_key, null];
    }

    /**
     * Read the shipped default (placeholder-based) seed HTML for a base template ("appointment_saved",
     * "appointment_deleted", "account_recovery" or "password_reset"), used both to seed the editor
     * when nothing has been customized yet and for the "reset to default" button. The same seed is
     * shared by all four recipient roles of a given base template - the default DESIGN is identical
     * across roles, only the placeholder VALUES differ at send time (e.g. a restricted role simply
     * never receives a customer_email/customer_phone value to fill in).
     */
    private function read_default_template(string $base_template): string
    {
        $path = APPPATH . 'views/emails/templates_default/' . $base_template . '.html';

        return is_file($path) ? (string) file_get_contents($path) : '';
    }

    /**
     * Salon Flora customization - defense-in-depth stripping of <script>/event-handler/javascript:
     * content from admin-authored template HTML. Deliberately NOT HTMLPurifier::purify() (pure_html()
     * helper), because its default config strips <head>/<style>, which these responsive email
     * templates depend on. The actual security boundary is (a) admin-only access to this controller
     * and (b) the sandboxed, script-disabled iframe used for the live preview - this is a hygiene
     * pass on top of that, not the only defense.
     */
    private function sanitize_template_html(string $html): string
    {
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? $html;
        $html = preg_replace('/<iframe\b[^>]*>.*?<\/iframe>/is', '', $html) ?? $html;
        $html = preg_replace('/\son\w+\s*=\s*"[^"]*"/i', '', $html) ?? $html;
        $html = preg_replace("/\son\w+\s*=\s*'[^']*'/i", '', $html) ?? $html;
        $html = preg_replace('/(href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\2/i', '$1=$2#$2', $html) ?? $html;

        return $html;
    }
}
