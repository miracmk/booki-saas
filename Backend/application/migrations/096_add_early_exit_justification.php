<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Salon Flora customization - session duration/commission recalculation (2026-08-25).
 *
 * Replaces the old blind "round actual duration down to the nearest 30 minutes" billing rule (which
 * silently halved a therapist's commission on e.g. a 54-minute session against a 60-minute service - a
 * real, confirmed underpayment bug) with a tolerance window + explicit early-exit classification:
 *
 * - Within +/- session_deviation_tolerance_minutes of the planned duration: billed as the full planned
 *   duration ("Normal", no question asked).
 * - Ran over by more than the tolerance: billed at the real (longer) duration.
 * - Left more than the tolerance early: staff must classify it as "haklı" (justified - e.g. customer's
 *   own choice/circumstance) or "haksız" (unjustified). Justified bills the full planned duration
 *   (protects both the customer's charge and the therapist's commission); unjustified bills the real
 *   (shorter) duration.
 *
 * A "haklı" classification made by the therapist themselves (checking out their own session) is NOT
 * automatically trusted for billing purposes - it needs an admin/secretary's approval
 * (early_exit_approved_by) before compute_effective_billing() honors it. An admin/secretary marking it
 * themselves is self-approving (see Calendar.php::check_out()).
 * ---------------------------------------------------------------------------- */

class Migration_Add_early_exit_justification extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->field_exists('early_exit_justification', 'appointments')) {
            $this->dbforge->add_column('appointments', [
                'early_exit_justification' => [
                    'type' => 'ENUM',
                    'constraint' => ['justified', 'unjustified'],
                    'null' => true,
                    'after' => 'session_deviation_reason',
                ],
            ]);
        }

        if (!$this->db->field_exists('early_exit_reason_code', 'appointments')) {
            $this->dbforge->add_column('appointments', [
                'early_exit_reason_code' => [
                    'type' => 'VARCHAR',
                    'constraint' => 40,
                    'null' => true,
                    'after' => 'early_exit_justification',
                ],
            ]);
        }

        if (!$this->db->field_exists('early_exit_approved_by', 'appointments')) {
            $this->dbforge->add_column('appointments', [
                'early_exit_approved_by' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'after' => 'early_exit_reason_code',
                ],
            ]);
        }

        if (!$this->db->field_exists('early_exit_approved_at', 'appointments')) {
            $this->dbforge->add_column('appointments', [
                'early_exit_approved_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'early_exit_approved_by',
                ],
            ]);
        }

        // Seed the two new business_settings fields, only if not already present (idempotent, and
        // won't clobber a value an admin already changed via a re-run of this migration).
        foreach (['session_deviation_tolerance_minutes' => '8', 'session_duration_baseline' => 'check_in'] as $name => $value) {
            $exists = $this->db->get_where('settings', ['name' => $name])->row_array();

            if (!$exists) {
                $this->db->insert('settings', ['name' => $name, 'value' => $value]);
            }
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        foreach (
            ['early_exit_approved_at', 'early_exit_approved_by', 'early_exit_reason_code', 'early_exit_justification']
            as $column
        ) {
            if ($this->db->field_exists($column, 'appointments')) {
                $this->dbforge->drop_column('appointments', $column);
            }
        }

        $this->db->delete('settings', ['name' => 'session_deviation_tolerance_minutes']);
        $this->db->delete('settings', ['name' => 'session_duration_baseline']);
    }
}
