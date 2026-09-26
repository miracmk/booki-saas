<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Salon Flora customization - secretary role should only VIEW stations
 * (value 1), not add/edit/delete them (was mistakenly set to 15 in
 * migration 073).
 * ---------------------------------------------------------------------------- */

class Migration_Restrict_secretary_stations_permission extends App_Migration
{
    public function up(): void
    {
        $this->db->update('roles', ['stations' => '1'], ['slug' => 'secretary']);
    }

    public function down(): void
    {
        $this->db->update('roles', ['stations' => '15'], ['slug' => 'secretary']);
    }
}
