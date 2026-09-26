<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Salon Flora customization - a one-time fixed bonus added to an HOURLY-commission provider's payout whenever a
 * session's effective billed duration exceeds 60 minutes (once per session, regardless of how much over an hour
 * it runs - not a per-additional-hour multiplier).
 * ---------------------------------------------------------------------------- */

class Migration_Add_overtime_bonus_to_users_table extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->field_exists('commission_overtime_bonus', 'users')) {
            $this->dbforge->add_column('users', [
                'commission_overtime_bonus' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'null' => false,
                    'default' => 0,
                    'after' => 'commission_value',
                ],
            ]);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->field_exists('commission_overtime_bonus', 'users')) {
            $this->dbforge->drop_column('users', 'commission_overtime_bonus');
        }
    }
}
