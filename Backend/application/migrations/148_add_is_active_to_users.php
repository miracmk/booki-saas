<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * BooKi (2026-09-17) - provider active/inactive toggle (user request: deactivate a provider
 * instead of deleting them, so past appointments/data/commissions stay intact, while new
 * bookings stop being routed to them). `is_active` lives on `users` (same table as the sibling
 * `station_restriction_enabled` flag, migration 082) rather than a provider-only table, since
 * this is a per-user row property. Default 1 (active) - every existing provider keeps working
 * exactly as before until an admin deliberately deactivates one.
 *
 * Scope: this column is generic on `users`, but only Providers_model/the Providers settings
 * page read or expose it in this tour - customers/admins/secretaries are unaffected.
 */
class Migration_Add_is_active_to_users extends App_Migration
{
    public function up(): void
    {
        if (!$this->db->field_exists('is_active', 'users')) {
            $this->dbforge->add_column('users', [
                'is_active' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => false,
                    'default' => 1,
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->field_exists('is_active', 'users')) {
            $this->dbforge->drop_column('users', 'is_active');
        }
    }
}
