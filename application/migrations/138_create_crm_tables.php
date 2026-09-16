<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Online Appointment Scheduler
 *
 * @package     KiReservation
 * @author      Ki Software
 * @copyright   Copyright (c) Ki Software
 * @license     Proprietary - see LICENSE file
 * @link        https://kisoftware.com
 * ---------------------------------------------------------------------------- */

/**
 * BooKi (2026-09-16) - Zoho CRM integration, part 1/3: the per-tenant outbox.
 *
 * `crm_outbox` - queue of customer/appointment lifecycle events still waiting to be pushed to the
 * platform CRM (Zoho). Written by Crm_sync::enqueue() from the booking models; drained by the
 * `console crm_sync` worker. Only LOCAL ids are stored (never PII) - the worker re-joins the actual
 * customer/appointment rows when building the CRM payload.
 *
 * `crm_id_map` - maps a local record (customer/appointment) to the external CRM record id it was
 * pushed to, so later events (appointment.updated / appointment.cancelled) can update the SAME deal
 * instead of duplicating it.
 */
class Migration_Create_crm_tables extends EA_Migration
{
    public function up(): void
    {
        if (!$this->db->table_exists('crm_outbox')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'auto_increment' => true,
                ],
                'action' => [
                    'type' => 'VARCHAR',
                    'constraint' => '40',
                    'null' => false,
                ],
                'appointment_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
                'customer_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
                'status' => [
                    'type' => 'VARCHAR',
                    'constraint' => '16',
                    'default' => 'pending',
                ],
                'attempts' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                ],
                'error' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'synced_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->dbforge->add_key('id', true);

            $this->dbforge->create_table('crm_outbox', true, ['engine' => 'InnoDB']);

            $this->db->query(
                'ALTER TABLE ' . $this->db->dbprefix('crm_outbox')
                    . ' ADD INDEX idx_crm_outbox_status (status)',
            );

            echo 'Created "crm_outbox" table.' . PHP_EOL;
        }

        if (!$this->db->table_exists('crm_id_map')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'auto_increment' => true,
                ],
                'local_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => '20',
                    'null' => false,
                ],
                'local_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => false,
                ],
                'zoho_module' => [
                    'type' => 'VARCHAR',
                    'constraint' => '64',
                    'null' => false,
                ],
                'zoho_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => '64',
                    'null' => false,
                ],
                'synced_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->dbforge->add_key('id', true);

            $this->dbforge->create_table('crm_id_map', true, ['engine' => 'InnoDB']);

            $this->db->query(
                'ALTER TABLE ' . $this->db->dbprefix('crm_id_map')
                    . ' ADD UNIQUE INDEX idx_crm_id_map_unique (local_type, local_id, zoho_module),'
                    . ' ADD INDEX idx_crm_id_map_zoho (zoho_module, zoho_id)',
            );

            echo 'Created "crm_id_map" table.' . PHP_EOL;
        }
    }

    public function down(): void
    {
        if ($this->db->table_exists('crm_id_map')) {
            $this->dbforge->drop_table('crm_id_map');
        }

        if ($this->db->table_exists('crm_outbox')) {
            $this->dbforge->drop_table('crm_outbox');
        }
    }
}