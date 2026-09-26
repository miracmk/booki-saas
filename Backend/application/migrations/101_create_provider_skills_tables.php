<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - provider skills/specialties (2026-08-26).
 *
 * A lightweight catalog (`provider_skills`) + a many-to-many assignment table
 * (`provider_skill_assignments`), mirroring the existing stations_providers pattern
 * (see Stations_model/Providers_model::set_station_ids()). Unlike stations, skills
 * carry no availability/booking logic - they are a tagging vocabulary used to
 * surface a matching provider in the customer CRM "favorite provider" insight
 * (see Customers.php/customers.js).
 * ---------------------------------------------------------------------------- */

class Migration_Create_provider_skills_tables extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->table_exists('provider_skills')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'name' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => false],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('provider_skills', true, ['engine' => 'InnoDB']);

            $this->db->query(
                'ALTER TABLE ' . $this->db->dbprefix('provider_skills') . ' ADD UNIQUE INDEX idx_ps_name (name)',
            );
        }

        if (!$this->db->table_exists('provider_skill_assignments')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'id_users' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
                'id_provider_skills' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('provider_skill_assignments', true, ['engine' => 'InnoDB']);

            $this->db->query(
                'ALTER TABLE ' .
                    $this->db->dbprefix('provider_skill_assignments') .
                    ' ADD UNIQUE INDEX idx_psa_user_skill (id_users, id_provider_skills),' .
                    ' ADD INDEX idx_psa_skill (id_provider_skills)',
            );
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        foreach (['provider_skill_assignments', 'provider_skills'] as $table) {
            if ($this->db->table_exists($table)) {
                $this->dbforge->drop_table($table);
            }
        }
    }
}
