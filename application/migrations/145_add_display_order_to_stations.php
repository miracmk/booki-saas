<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * BooKi (2026-09-17) - "İlk Müsaitlik" room/station ranking. Stations have no organic satisfaction
 * signal (reviews are tied to appointments/providers, not rooms), so ranking is an explicit,
 * admin-editable priority instead: lower display_order sorts first. Existing stations default to 0
 * (all equal - ties break on name, unchanged behavior until an admin sets a real order).
 */
class Migration_Add_display_order_to_stations extends EA_Migration
{
    public function up(): void
    {
        if ($this->db->table_exists('stations') && !$this->db->field_exists('display_order', 'stations')) {
            $this->dbforge->add_column('stations', [
                'display_order' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                    'null' => false,
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->table_exists('stations') && $this->db->field_exists('display_order', 'stations')) {
            $this->dbforge->drop_column('stations', 'display_order');
        }
    }
}
