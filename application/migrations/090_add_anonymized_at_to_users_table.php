<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Salon Flora customization - KVKK data-retention support (2026-08-24). `anonymized_at` marks a
 * `users` row (customer or provider) whose PII has been scrubbed in place - by the nightly automated
 * retention job (Console::anonymize_stale_customers()) or a manual "KVKK - Unutulma Hakkı" action from
 * the admin panel. The row itself is kept (never deleted) specifically so that
 * `ea_appointments`/reports/revenue history stay intact - `appointments.id_users_customer` is
 * ON DELETE CASCADE, so actually deleting a customer row would destroy their appointment/payment
 * history too, which conflicts with VUK (Tax Procedure Law) record-keeping requirements. Anonymizing
 * in place satisfies KVKK's "don't keep identifiable personal data longer than necessary" without
 * touching financial record retention.
 * ---------------------------------------------------------------------------- */

class Migration_Add_anonymized_at_to_users_table extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->field_exists('anonymized_at', 'users')) {
            $this->dbforge->add_column('users', [
                'anonymized_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'notes',
                ],
            ]);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->field_exists('anonymized_at', 'users')) {
            $this->dbforge->drop_column('users', 'anonymized_at');
        }
    }
}
