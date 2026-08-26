<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Open Source Web Scheduler
 *
 * @package     KiReservation
 * @author      Ki Software
 * @copyright   Copyright (c) 2013 - 2020, Ki Software
 * @license     http://opensource.org/licenses/Proprietary - Ki Software License
 * @link        http://kisoftware.com
 * @since       v1.4.0
 * ---------------------------------------------------------------------------- */

class Migration_Drop_delete_datetime_column_from_all_tables extends EA_Migration
{
    /**
     * @var string[]
     */
    protected $tables = [
        'appointments',
        'categories',
        'consents',
        'roles',
        'services',
        'settings',
        'users',
        'webhooks',
    ];

    /**
     * Upgrade method.
     */
    public function up(): void
    {
        foreach ($this->tables as $table) {
            if ($this->db->field_exists('delete_datetime', $table)) {
                $this->dbforge->drop_column($table, 'delete_datetime');
            }
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (!$this->db->field_exists('delete_datetime', $table)) {
                $fields = [
                    'delete_datetime' => [
                        'type' => 'DATETIME',
                        'null' => true,
                        'after' => 'update_datetime',
                    ],
                ];

                $this->dbforge->add_column($table, $fields);
            }
        }
    }
}
