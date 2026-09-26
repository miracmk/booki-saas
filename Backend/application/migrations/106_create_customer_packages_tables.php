<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Multi-session packages (customer packages / package sessions).
 *
 * Supports sale and tracking of packages (e.g. "5-session massage packages").
 * customer_packages: one row per package purchase, with total_sessions, used_sessions,
 * pricing, expiry, status.
 * customer_package_sessions: one row per appointment that consumed a session from a
 * package (appointment -> package link, with UNIQUE constraint to prevent double-usage).
 * ---------------------------------------------------------------------------- */

class Migration_Create_customer_packages_tables extends App_Migration
{
    public function up()
    {
        // customer_packages table
        $this->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => true,
            ],
            'id_users_customer' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'comment' => 'FK to users.id',
            ],
            'id_services' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'comment' => 'FK to services.id',
            ],
            'total_sessions' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'comment' => 'Total sessions in this package',
            ],
            'used_sessions' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
                'comment' => 'Sessions already consumed',
            ],
            'unit_price' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'comment' => 'Price per session, for reference',
            ],
            'purchased_at' => [
                'type' => 'DATETIME',
                'comment' => 'When the package was purchased',
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Package expiry date, NULL = no expiry',
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 16,
                'default' => 'active',
                'comment' => 'active, exhausted, cancelled',
            ],
            'sold_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'FK to users.id (admin/secretary who created it)',
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('id_users_customer');
        $this->dbforge->add_key('id_services');
        $this->dbforge->add_key('status');
        $this->dbforge->add_key('expires_at');

        $this->dbforge->create_table('customer_packages');

        // customer_package_sessions table
        $this->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => true,
            ],
            'id_customer_packages' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'comment' => 'FK to customer_packages.id',
            ],
            'id_appointments' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'comment' => 'FK to appointments.id, UNIQUE to prevent double-usage',
            ],
            'consumed_at' => [
                'type' => 'DATETIME',
                'comment' => 'When the session was consumed',
            ],
        ]);

        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('id_customer_packages');
        $this->dbforge->add_key(['id_appointments'], false, true); // UNIQUE index (also serves as the lookup index)

        $this->dbforge->create_table('customer_package_sessions');
    }

    public function down()
    {
        $this->dbforge->drop_table('customer_package_sessions');
        $this->dbforge->drop_table('customer_packages');
    }
}
