<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * BooKi (2026-09-17) - `appointment_updated` communication_rules rows.
 *
 * Edits to an existing appointment (reschedule/service/provider change) never went through
 * the Communication Hub / Automation Engine at all - see application/controllers/Calendar.php
 * (the `!$manage_mode` guard around the appointment_created publish/evaluate calls). Provider
 * and admin recipients in particular got no WhatsApp/SMS option on an edit at all (the legacy
 * Notifications::notify_appointment_saved() path only ever sends them email + Telegram, never
 * WhatsApp/SMS, regardless of manage_mode). Seeded DISABLED, same rationale as the
 * appointment_created/appointment_cancelled defaults in migration 128: the legacy path already
 * covers the customer with *some* notification (email, and WhatsApp/SMS once
 * Providers_model::get_setting()'s null-return crash - see 2026-09-17 fix - stops silently
 * aborting it), so this is an opt-in complementary channel + per-tenant template, not a
 * duplicate-by-default send.
 */
class Migration_Seed_appointment_updated_communication_rules extends EA_Migration
{
    public function up(): void
    {
        if (!$this->db->table_exists('communication_rules')) {
            return;
        }

        if ($this->db->get_where('communication_rules', ['event' => 'appointment_updated'])->num_rows() > 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $defaults = [
            ['recipient' => 'customer', 'subject' => 'Randevunuz Güncellendi', 'message' => 'Merhaba {customer_name}, {service_name} için randevunuz {start_datetime} tarihine güncellendi. {company_name}'],
            ['recipient' => 'provider', 'subject' => 'Randevu Güncellendi', 'message' => 'Randevu güncellendi: {customer_name} - {service_name}, {start_datetime}. {company_name}'],
            ['recipient' => 'admin', 'subject' => 'Randevu Güncellendi', 'message' => 'Randevu güncellendi: {customer_name} - {service_name} ({provider_name}), {start_datetime}. {company_name}'],
        ];

        foreach ($defaults as $rule) {
            $this->db->insert('communication_rules', [
                'event' => 'appointment_updated',
                'recipient' => $rule['recipient'],
                'channel' => 'sms,whatsapp',
                'subject' => $rule['subject'],
                'message' => $rule['message'],
                'enabled' => 0,
                'created_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->table_exists('communication_rules')) {
            $this->db->where('event', 'appointment_updated')->delete('communication_rules');
        }
    }
}
