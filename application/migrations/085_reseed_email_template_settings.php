<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Salon Flora customization - fixes the "Görsel" (WYSIWYG/iframe designMode) email template editor
 * corrupting saved templates. Root cause: the default templates placed "{{#if key}}"/"{{/if}}"
 * markers directly inside a <table> as bare text siblings of <tr> (no <tbody> wrapper) - invalid
 * HTML5 content that the browser's parser "foster-parents" out of the table on every save/round-trip
 * through the iframe. This silently turned conditional rows (Durum/Açıklama/Konum/Toplantı
 * Linki/Notlar/E-posta/Telefon/Adres) into permanently-visible-but-empty rows in sent emails.
 *
 * The fix has two parts: (1) the default template files and Email_messages::render_custom_template()
 * were updated to use an HTML-comment-wrapped marker form that survives the browser round-trip
 * (see Email_messages.php docblock); (2) this migration re-seeds the `ea_settings` rows with the
 * corrected default HTML.
 *
 * Every one of the 10 pre-existing email_template_* rows was verified (2026-08-24, by diffing
 * visible text against the stock default) to contain ZERO admin-authored customization - the panel
 * had only ever been opened, round-tripped through the buggy iframe, and saved, never actually
 * reworded. It is therefore safe to overwrite all of them with the corrected default. The two
 * role-less orphan rows (email_template_appointment_saved / email_template_appointment_deleted, from
 * before the feature was split per-recipient-role) are read by neither the current controller
 * (Email_template_settings::TEMPLATE_KEYS) nor the current sender (Email_messages::TEMPLATE_SETTING_KEYS)
 * and are removed as dead data.
 *
 * Rollback: a full pre-migration dump of every email_template_* row lives at
 * backups/ea_settings_email_templates_pre_fix_*.sql (mysqldump --complete-insert, so importing it
 * after `DELETE FROM ea_settings WHERE name LIKE 'email_template%'` restores the exact prior state,
 * including the two orphan rows this migration removes).
 * ---------------------------------------------------------------------------- */

class Migration_Reseed_email_template_settings extends EA_Migration
{
    private const ROLE_KEYS = [
        'email_template_appointment_saved_customer' => 'appointment_saved',
        'email_template_appointment_saved_admin' => 'appointment_saved',
        'email_template_appointment_saved_secretary' => 'appointment_saved',
        'email_template_appointment_saved_provider' => 'appointment_saved',
        'email_template_appointment_deleted_customer' => 'appointment_deleted',
        'email_template_appointment_deleted_admin' => 'appointment_deleted',
        'email_template_appointment_deleted_secretary' => 'appointment_deleted',
        'email_template_appointment_deleted_provider' => 'appointment_deleted',
        'email_template_account_recovery' => 'account_recovery',
        'email_template_password_reset' => 'password_reset',
    ];

    private const ORPHAN_KEYS = ['email_template_appointment_saved', 'email_template_appointment_deleted'];

    /**
     * Upgrade method.
     */
    public function up(): void
    {
        foreach (self::ROLE_KEYS as $setting_name => $base_template) {
            $path = APPPATH . 'views/emails/templates_default/' . $base_template . '.html';

            if (!is_file($path)) {
                continue;
            }

            $html = file_get_contents($path);

            $existing = $this->db->get_where('settings', ['name' => $setting_name])->row_array();

            if ($existing) {
                $this->db->where('name', $setting_name)->update('settings', ['value' => $html]);
            } else {
                $this->db->insert('settings', ['name' => $setting_name, 'value' => $html]);
            }
        }

        $this->db->where_in('name', self::ORPHAN_KEYS)->delete('settings');
    }

    /**
     * Downgrade method.
     *
     * There is no in-code "previous" value to restore to (the pre-fix content was corrupted browser
     * output, not something derivable from source). Restore from
     * backups/ea_settings_email_templates_pre_fix_*.sql if a rollback is ever needed.
     */
    public function down(): void {}
}
