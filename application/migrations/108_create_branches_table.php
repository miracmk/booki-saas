<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Multi-branch support (single tenant, multiple physical locations).
 *
 * Adds branches table and optional id_branches foreign key columns to providers,
 * stations, and appointments to enable filtering by branch while maintaining
 * backward compatibility with single-branch deployments.
 *
 * Backfill: all existing records are assigned to the auto-created default branch,
 * but id_branches columns remain NULLABLE to preserve zero-impact behavior in
 * existing single-branch codepaths.
 *
 * Compatibility rule: when Branches_model::count_active() <= 1, the UI and queries
 * must NOT apply branch filtering (branch-awareness is disabled for single-branch
 * tenants, which comprise the vast majority of production deployments).
 * ---------------------------------------------------------------------------- */

class Migration_Create_branches_table extends App_Migration
{
    public function up()
    {
        // Create branches table
        $this->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 191,
                'comment' => 'Branch name (e.g. "Main Branch", "Downtown Office")',
            ],
            'address' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Branch address',
            ],
            'phone' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'null' => true,
                'comment' => 'Branch phone number',
            ],
            'is_default' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'comment' => 'Is this the default branch',
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
                'comment' => 'Is this branch active',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'comment' => 'Creation timestamp',
            ],
        ]);

        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('is_default');
        $this->dbforge->add_key('is_active');

        $this->dbforge->create_table('branches');

        // Insert default branch
        $this->db->insert('branches', [
            'name' => 'Default Branch',
            'is_default' => 1,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $default_branch_id = $this->db->insert_id();

        // Add id_branches column to the users table (providers are users with a provider role in this
        // codebase - there is no separate "providers" table). NULLABLE for backward compatibility.
        // Raw queries bypass query-builder auto-prefixing, so the table name must be resolved via
        // dbprefix() explicitly (see e.g. migration 001_specific_calendar_sync.php for the same pattern).
        if (!$this->db->field_exists('id_branches', 'users')) {
            $this->db->query(
                'ALTER TABLE ' . $this->db->dbprefix('users') . ' ADD COLUMN id_branches INT(11) UNSIGNED NULL AFTER id'
            );
        }

        // Add id_branches column to stations table (NULLABLE for backward compatibility)
        if (!$this->db->field_exists('id_branches', 'stations')) {
            $this->db->query(
                'ALTER TABLE ' . $this->db->dbprefix('stations') . ' ADD COLUMN id_branches INT(11) UNSIGNED NULL AFTER id'
            );
        }

        // Add id_branches column to appointments table (NULLABLE for backward compatibility)
        if (!$this->db->field_exists('id_branches', 'appointments')) {
            $this->db->query(
                'ALTER TABLE ' . $this->db->dbprefix('appointments') . ' ADD COLUMN id_branches INT(11) UNSIGNED NULL AFTER id'
            );
        }

        // Backfill: assign all existing records to the default branch
        $this->db->query(
            'UPDATE ' . $this->db->dbprefix('users') . ' SET id_branches = ? WHERE id_branches IS NULL',
            [$default_branch_id]
        );

        $this->db->query(
            'UPDATE ' . $this->db->dbprefix('stations') . ' SET id_branches = ? WHERE id_branches IS NULL',
            [$default_branch_id]
        );

        $this->db->query(
            'UPDATE ' . $this->db->dbprefix('appointments') . ' SET id_branches = ? WHERE id_branches IS NULL',
            [$default_branch_id]
        );
    }

    public function down()
    {
        // Drop id_branches columns from tables
        if ($this->db->field_exists('id_branches', 'users')) {
            $this->db->query('ALTER TABLE ' . $this->db->dbprefix('users') . ' DROP COLUMN id_branches');
        }

        if ($this->db->field_exists('id_branches', 'stations')) {
            $this->db->query('ALTER TABLE ' . $this->db->dbprefix('stations') . ' DROP COLUMN id_branches');
        }

        if ($this->db->field_exists('id_branches', 'appointments')) {
            $this->db->query('ALTER TABLE ' . $this->db->dbprefix('appointments') . ' DROP COLUMN id_branches');
        }

        // Drop branches table
        $this->dbforge->drop_table('branches');
    }
}
