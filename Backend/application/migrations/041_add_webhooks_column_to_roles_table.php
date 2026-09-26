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

class Migration_Add_webhooks_column_to_roles_table extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->field_exists('webhooks', 'roles')) {
            $fields = [
                'webhooks' => [
                    'type' => 'INT',
                    'constraint' => '11',
                    'null' => true,
                ],
            ];

            $this->dbforge->add_column('roles', $fields);

            $this->db->update('roles', ['webhooks' => '15'], ['slug' => 'admin']);

            $this->db->update('roles', ['webhooks' => '0'], ['slug !=' => 'admin']);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->field_exists('webhooks', 'roles')) {
            $this->dbforge->drop_column('roles', 'webhooks');
        }
    }
}
