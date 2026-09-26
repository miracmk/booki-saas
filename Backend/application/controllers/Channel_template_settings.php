<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * BooKi - Channel message template settings controller.
 *
 * Lets the business admin edit the tenant-level channel message templates (Telegram / WhatsApp /
 * Instagram) from a "Kanal Şablonları" settings tab, with a live preview. The six template keys
 * mirror Channel_templates::TEMPLATES - appointment_pending / appointment_approved /
 * appointment_rescheduled / appointment_cancelled / appointment_reminder / customer_channel_linked.
 * Saved content is persisted via ea_settings as "channel_template_<key>" and consumed by
 * Channel_templates::render() (which falls back to the built-in presets when unset/empty), so this
 * is per-tenant and DB-managed, with no schema change required.
 *
 * Access is hard-restricted to the DB_SLUG_ADMIN role regardless of PRIV_SYSTEM_SETTINGS grants,
 * keeping this an owner-only tool just like Email_template_settings.
 *
 * @package Controllers
 */
class Channel_template_settings extends App_Controller
{
    /**
     * Flat "template key" -> settings-table name whitelist. Keeps the save/preview endpoints from
     * being used to touch arbitrary "settings" rows. The "channel_template_" + key naming is also
     * relied on by the frontend JS (channel_template_settings.js) to derive setting names.
     *
     * Duplicated as literals (not a reference to Channel_templates::keys()) because the library is
     * only loaded on demand via $this->load->library() - a class-constant expression referencing it
     * here would be evaluated before that load happens.
     */
    private const TEMPLATE_KEYS = [
        'appointment_pending' => 'channel_template_appointment_pending',
        'appointment_approved' => 'channel_template_appointment_approved',
        'appointment_rescheduled' => 'channel_template_appointment_rescheduled',
        'appointment_cancelled' => 'channel_template_appointment_cancelled',
        'appointment_reminder' => 'channel_template_appointment_reminder',
        'customer_channel_linked' => 'channel_template_customer_channel_linked',
    ];

    /**
     * Sample data rendered against a template for the live preview.
     */
    private const SAMPLE_DATA = [
        'company_name' => 'Güzellik S Demo',
        'customer_name' => 'Miraç Kılınç',
        'service_name' => 'Klasik Masaj - 60 Dakika',
        'start_datetime' => 'now',
        'end_datetime' => 'now',
        'provider_name' => 'Nur Ş.',
        'company_address' => 'Atatürk Cad. No:12, Kadıköy',
        'company_phone' => '+90 555 123 45 67',
        'notes' => 'Rahatlama ve klasik masaj',
    ];

    public function __construct()
    {
        parent::__construct();

        $this->load->model('settings_model');

        $this->load->library('accounts');
        $this->load->library('channel_templates');
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
     * Render the backend "Kanal Şablonları" settings page.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('channel_template_settings')]);

        $this->require_admin();

        $user_id = session('user_id');

        $templates = [];

        foreach (self::TEMPLATE_KEYS as $template_key => $setting_key) {
            $templates[$template_key] = [
                'value' => (string) setting($setting_key, ''),
                'default' => $this->channel_templates->default_template($template_key),
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

        $this->load->view('pages/channel_template_settings');
    }

    /**
     * Save one or more channel templates.
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

                $value = $this->sanitize_template_text((string) ($template['value'] ?? ''));

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
     * Render a live preview of a (possibly unsaved) template against sample channel data.
     */
    public function preview(): void
    {
        try {
            method('post');

            $this->require_admin();

            check('template_key', 'string');
            check('value', 'string');

            $template_key = request('template_key');

            if (!array_key_exists($template_key, self::TEMPLATE_KEYS)) {
                throw new InvalidArgumentException('Unknown template key: ' . $template_key);
            }

            $value = $this->sanitize_template_text((string) request('value', ''));

            $sample = self::SAMPLE_DATA;

            $sample['start_datetime'] = $this->sample_datetime(2);
            $sample['end_datetime'] = $this->sample_datetime(3);

            $sample['booking_url'] = site_url('booking');

            $rendered = $this->channel_templates->render($template_key, $sample, $value);

            json_response(['text' => $rendered]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * A datetime a few days from now, used as the sample booking time in previews.
     */
    private function sample_datetime(int $days_ahead): string
    {
        return date('Y-m-d H:i:s', strtotime("+{$days_ahead} days 14:00"));
    }

    /**
     * Hygiene pass on admin-authored plain-text channel templates: drop null bytes and trim. These
     * bodies ship as plain chat text (never parsed as HTML), so no tag/attribute stripping is
     * needed - the real boundary is admin-only access to this controller.
     */
    private function sanitize_template_text(string $value): string
    {
        return trim(preg_replace('/\x00/', '', $value) ?? '');
    }
}