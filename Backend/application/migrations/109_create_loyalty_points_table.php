<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi customization - "Loyalty Points Program" (2026-08-27):
 * Stores customer loyalty points earnings and redemptions.
 *
 * Points can be earned per appointment (configurable via Business_settings),
 * redeemed by customers (future UI), or adjusted manually. A balance is maintained
 * on the users table for quick lookups.
 * ---------------------------------------------------------------------------- */

class Migration_Create_loyalty_points_table extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        // Create loyalty_points table
        if (!$this->db->table_exists('loyalty_points')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'id_users_customer' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => false,
                ],
                'points' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => false,
                    'comment' => 'Positive for earnings, negative for redemptions',
                ],
                'reason' => [
                    'type' => 'ENUM',
                    'constraint' => ['earned_appointment', 'redeemed', 'manual_adjustment', 'expired'],
                    'null' => false,
                ],
                'id_appointments' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => false,
                ],
                'created_by' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'comment' => 'Staff ID who created the adjustment (if applicable)',
                ],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('loyalty_points', true, ['engine' => 'InnoDB']);

            // Add indexes for quick filtering
            $this->db->query(
                'ALTER TABLE ' .
                    $this->db->dbprefix('loyalty_points') .
                    ' ADD INDEX idx_loyalty_points_customer (id_users_customer)',
            );

            $this->db->query(
                'ALTER TABLE ' .
                    $this->db->dbprefix('loyalty_points') .
                    ' ADD INDEX idx_loyalty_points_appointment (id_appointments)',
            );
        }

        // Add loyalty_points_balance column to users table
        if (!$this->db->field_exists('loyalty_points_balance', 'users')) {
            $this->dbforge->add_column('users', [
                'loyalty_points_balance' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                    'null' => false,
                    'after' => 'anonymized_at',
                ],
            ]);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->table_exists('loyalty_points')) {
            $this->dbforge->drop_table('loyalty_points');
        }

        if ($this->db->field_exists('loyalty_points_balance', 'users')) {
            $this->dbforge->drop_column('users', 'loyalty_points_balance');
        }
    }
}
