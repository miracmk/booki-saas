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

class Migration_Add_secret_header_column_to_webhooks_table extends App_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->field_exists('secret_header', 'webhooks')) {
            $fields = [
                'secret_header' => [
                    'type' => 'VARCHAR',
                    'constraint' => '256',
                    'default' => 'X-Ea-Token',
                    'after' => 'actions',
                ],
            ];

            $this->dbforge->add_column('webhooks', $fields);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->field_exists('secret_header', 'webhooks')) {
            $this->dbforge->drop_column('webhooks', 'secret_header');
        }
    }
}
