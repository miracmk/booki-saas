<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Salon Flora customization - payment/collection tracking for a completed session:
 * how it was paid (or that it wasn't, in which case a balance may remain owed by
 * the customer), whether it was invoiced, and who recorded that information
 * (only admins/secretaries are allowed to - providers can check a session out
 * but never touch payment data).
 * ---------------------------------------------------------------------------- */

class Migration_Add_payment_fields_to_appointments_table extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->field_exists('payment_status', 'appointments')) {
            $this->dbforge->add_column('appointments', [
                'payment_status' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => false,
                    'default' => 'pending',
                    'after' => 'session_deviation_reason',
                ],
            ]);
        }

        if (!$this->db->field_exists('payment_method', 'appointments')) {
            $this->dbforge->add_column('appointments', [
                'payment_method' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true,
                    'after' => 'payment_status',
                ],
            ]);
        }

        if (!$this->db->field_exists('payment_amount', 'appointments')) {
            $this->dbforge->add_column('appointments', [
                'payment_amount' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'null' => true,
                    'after' => 'payment_method',
                ],
            ]);
        }

        if (!$this->db->field_exists('payment_balance_amount', 'appointments')) {
            $this->dbforge->add_column('appointments', [
                'payment_balance_amount' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'null' => true,
                    'after' => 'payment_amount',
                ],
            ]);
        }

        if (!$this->db->field_exists('is_invoiced', 'appointments')) {
            $this->dbforge->add_column('appointments', [
                'is_invoiced' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => false,
                    'default' => 0,
                    'after' => 'payment_balance_amount',
                ],
            ]);
        }

        if (!$this->db->field_exists('payment_recorded_by', 'appointments')) {
            $this->dbforge->add_column('appointments', [
                'payment_recorded_by' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'after' => 'is_invoiced',
                ],
            ]);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        foreach (
            [
                'payment_recorded_by',
                'is_invoiced',
                'payment_balance_amount',
                'payment_amount',
                'payment_method',
                'payment_status',
            ]
            as $column
        ) {
            if ($this->db->field_exists($column, 'appointments')) {
                $this->dbforge->drop_column('appointments', $column);
            }
        }
    }
}
