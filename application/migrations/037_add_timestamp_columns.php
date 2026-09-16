<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Open Source Web Scheduler
 *
 * @package     KiReservation
 * @author      Ki Software
 * @copyright   Copyright (c) 2013 - 2020, Ki Software
 * @license     http://opensource.org/licenses/Proprietary - Ki Software License
 * @link        http://kisoftware.com
 * @since       v1.4.0
 * ---------------------------------------------------------------------------- */

class Migration_Add_timestamp_columns extends EA_Migration
{
    /**
     * @var string[]
     */
    protected $tables = ['appointments', 'categories', 'consents', 'roles', 'services', 'settings', 'users'];

    /**
     * @var string[]
     */
    protected $columns = ['delete_datetime', 'update_datetime', 'create_datetime'];

    /**
     * Upgrade method.
     */
    public function up(): void
    {
        foreach ($this->tables as $table) {
            foreach ($this->columns as $column) {
                if (!$this->db->field_exists($column, $table)) {
                    $fields = [
                        $column => [
                            'type' => 'DATETIME',
                            'null' => true,
                            'after' => 'id',
                        ],
                    ];

                    $this->dbforge->add_column($table, $fields);
                }
            }
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        foreach ($this->tables as $table) {
            foreach ($this->columns as $column) {
                if ($this->db->field_exists($column, $table)) {
                    $this->dbforge->drop_column($table, $column);
                }
            }
        }
    }
}
