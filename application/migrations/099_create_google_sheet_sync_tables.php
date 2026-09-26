<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - real-time, multi-module Google Sheets sync (2026-08-25).
 *
 * A "sync" is one configured pipe: one module (appointments/customers/providers) -> one sheet tab in a
 * connected spreadsheet, with a field->column mapping the user chose (see
 * Google_sheets_fields::catalog()). Every create/update/delete of a record in that module writes to the
 * sheet immediately (see Google_sheets_writer::sync_record()).
 * ---------------------------------------------------------------------------- */

class Migration_Create_google_sheet_sync_tables extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->table_exists('google_sheet_syncs')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'id_google_connections' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
                'module' => [
                    'type' => 'ENUM',
                    'constraint' => ['appointments', 'customers', 'providers'],
                    'null' => false,
                ],
                'name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                'spreadsheet_id' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                'sheet_title' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                'sheet_gid' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'header_row' => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 1],
                // Salon Flora customization - KVKK/HIPAA posture for this pipe. 'exclude' (default) never
                // writes fields flagged pii=>true in the catalog; 'encrypted' writes sf_pii_encrypt()
                // ciphertext for them (present for backup/disaster-recovery purposes, not human-readable
                // in the sheet without the app's keys); 'plaintext' writes them decrypted - the sheet
                // then genuinely leaves KVKK/HIPAA protection, which is exactly what the compliance badge
                // (see Google_integrations.php) warns about.
                'pii_mode' => [
                    'type' => 'ENUM',
                    'constraint' => ['exclude', 'encrypted', 'plaintext'],
                    'null' => false,
                    'default' => 'exclude',
                ],
                'dedup_column' => ['type' => 'VARCHAR', 'constraint' => 8, 'null' => true],
                'write_mode' => ['type' => 'ENUM', 'constraint' => ['append', 'upsert'], 'null' => false, 'default' => 'upsert'],
                'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 1],
                'last_synced_at' => ['type' => 'DATETIME', 'null' => true],
                'last_error' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
                'updated_at' => ['type' => 'DATETIME', 'null' => false],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('google_sheet_syncs', true, ['engine' => 'InnoDB']);
        }

        if (!$this->db->table_exists('google_sheet_field_mappings')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'id_google_sheet_syncs' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
                'field_key' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
                'column_letter' => ['type' => 'VARCHAR', 'constraint' => 8, 'null' => false],
                'column_header' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'sort_order' => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 0],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('google_sheet_field_mappings', true, ['engine' => 'InnoDB']);

            $this->db->query(
                'ALTER TABLE ' .
                    $this->db->dbprefix('google_sheet_field_mappings') .
                    ' ADD INDEX idx_gsfm_sync (id_google_sheet_syncs)',
            );
        }

        if (!$this->db->table_exists('google_sheet_sync_log')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'id_google_sheet_syncs' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
                'record_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
                'row_number' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'row_hash' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'status' => ['type' => 'ENUM', 'constraint' => ['synced', 'failed', 'deleted'], 'null' => false, 'default' => 'synced'],
                'error_message' => ['type' => 'TEXT', 'null' => true],
                'synced_at' => ['type' => 'DATETIME', 'null' => false],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('google_sheet_sync_log', true, ['engine' => 'InnoDB']);

            $this->db->query(
                'ALTER TABLE ' .
                    $this->db->dbprefix('google_sheet_sync_log') .
                    ' ADD UNIQUE INDEX idx_gssl_sync_record (id_google_sheet_syncs, record_id)',
            );
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        foreach (['google_sheet_sync_log', 'google_sheet_field_mappings', 'google_sheet_syncs'] as $table) {
            if ($this->db->table_exists($table)) {
                $this->dbforge->drop_table($table);
            }
        }
    }
}
