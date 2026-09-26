<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Salon Flora customization (2026-08-25) - "where" a company Google connection's Drive/Sheets access
 * actually points: a specific folder (Drive) and/or a specific spreadsheet (Sheets), chosen once after
 * connecting - either an existing one (pasted URL/ID) or a brand new one created on the spot. See
 * Google_integrations_client::create_drive_folder()/create_spreadsheet() and
 * Google_integrations.php::set_drive_target()/set_sheets_target().
 * ---------------------------------------------------------------------------- */

class Migration_Add_drive_sheets_targets extends App_Migration
{
    private const COLUMNS = [
        'drive_folder_id' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        'drive_folder_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        'sheets_spreadsheet_id' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        'sheets_spreadsheet_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
    ];

    /**
     * Upgrade method.
     */
    public function up(): void
    {
        $after = 'enabled_services';

        foreach (self::COLUMNS as $name => $definition) {
            if (!$this->db->field_exists($name, 'google_connections')) {
                $this->dbforge->add_column('google_connections', [$name => $definition + ['after' => $after]]);
            }

            $after = $name;
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        foreach (array_reverse(array_keys(self::COLUMNS)) as $name) {
            if ($this->db->field_exists($name, 'google_connections')) {
                $this->dbforge->drop_column('google_connections', $name);
            }
        }
    }
}
