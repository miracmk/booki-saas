<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Migration 158: Add geocoordinates to leads table for map view.
 * -------------------------------------------------------------------------- */

class Migration_Add_leads_geocoordinates extends EA_Migration
{
    public function up(): void
    {
        if ($this->db->table_exists('leads')) {
            if (!$this->db->field_exists('latitude', 'leads')) {
                $this->dbforge->add_column('leads', [
                    'latitude' => ['type' => 'DECIMAL', 'constraint' => '10,8', 'null' => true, 'after' => 'tags'],
                ]);
            }
            if (!$this->db->field_exists('longitude', 'leads')) {
                $this->dbforge->add_column('leads', [
                    'longitude' => ['type' => 'DECIMAL', 'constraint' => '11,8', 'null' => true, 'after' => 'latitude'],
                ]);
            }
        }
    }

    public function down(): void
    {
        if ($this->db->table_exists('leads')) {
            if ($this->db->field_exists('latitude', 'leads')) {
                $this->dbforge->drop_column('leads', 'latitude');
            }
            if ($this->db->field_exists('longitude', 'leads')) {
                $this->dbforge->drop_column('leads', 'longitude');
            }
        }
    }
}
