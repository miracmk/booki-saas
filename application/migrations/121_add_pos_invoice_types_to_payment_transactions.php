<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - POS, wave 1 (2026-08-28).
 *
 * The ONE migration in this wave that touches the pre-existing
 * payment_transactions table (migration 103) - deliberately the LAST schema
 * change of the wave, applied only after Invoicing has spent a full phase
 * reading (never writing) payment-adjacent data with no incidents.
 *
 * Purely additive:
 *  - widens the `type` ENUM to add 'pos_sale' and 'invoice_payment' (the
 *    existing 'deposit'/'full_payment'/'refund' values are untouched, so
 *    every row written by the existing deposit flow - Booking.php,
 *    Appointment_booking_service.php - keeps working byte-for-byte the same)
 *  - adds two new nullable FK columns, id_orders and id_invoices, so a
 *    transaction can optionally be linked to the POS/Invoicing record that
 *    caused it
 *
 * Payment_transactions_model's method bodies are NOT touched by this
 * migration - save()/update_status()/find*() already accept/return whatever
 * columns exist on the table, so POS (Orders_model::checkout(), see
 * migration 120) can start writing type='pos_sale' rows with zero code
 * changes to the model itself.
 * ---------------------------------------------------------------------------- */

class Migration_Add_pos_invoice_types_to_payment_transactions extends EA_Migration
{
    private const WIDENED_TYPES = ['deposit', 'full_payment', 'refund', 'pos_sale', 'invoice_payment'];

    private const ORIGINAL_TYPES = ['deposit', 'full_payment', 'refund'];

    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->is_type_already_widened()) {
            $this->dbforge->modify_column('payment_transactions', [
                'type' => [
                    'type' => 'ENUM',
                    'constraint' => self::WIDENED_TYPES,
                    'default' => 'deposit',
                    'null' => false,
                ],
            ]);
        }

        if (!$this->db->field_exists('id_orders', 'payment_transactions')) {
            $this->dbforge->add_column('payment_transactions', [
                'id_orders' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'comment' => 'FK to orders.id, NULL unless type=pos_sale',
                    'after' => 'id_appointments',
                ],
            ]);
        }

        if (!$this->db->field_exists('id_invoices', 'payment_transactions')) {
            $this->dbforge->add_column('payment_transactions', [
                'id_invoices' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'comment' => 'FK to invoices.id, NULL unless type=invoice_payment',
                    'after' => 'id_orders',
                ],
            ]);
        }
    }

    /**
     * Downgrade method. Only shrinks the enum back if no row actually uses one of the new
     * values - this codebase's migrations never silently destroy data (see project convention),
     * so a wave-6-in-production rollback attempt is logged and left as-is rather than corrupting
     * rows.
     */
    public function down(): void
    {
        if ($this->db->field_exists('id_invoices', 'payment_transactions')) {
            $this->dbforge->drop_column('payment_transactions', 'id_invoices');
        }

        if ($this->db->field_exists('id_orders', 'payment_transactions')) {
            $this->dbforge->drop_column('payment_transactions', 'id_orders');
        }

        $rows_using_new_types = (int) $this->db
            ->where_in('type', ['pos_sale', 'invoice_payment'])
            ->count_all_results('payment_transactions');

        if ($rows_using_new_types === 0 && $this->is_type_already_widened()) {
            $this->dbforge->modify_column('payment_transactions', [
                'type' => [
                    'type' => 'ENUM',
                    'constraint' => self::ORIGINAL_TYPES,
                    'default' => 'deposit',
                    'null' => false,
                ],
            ]);
        } else {
            log_message(
                'warning',
                'Migration_Add_pos_invoice_types_to_payment_transactions::down() - skipped enum shrink, ' .
                    $rows_using_new_types . ' row(s) still use a pos_sale/invoice_payment type.',
            );
        }
    }

    /**
     * Check whether the `type` column's ENUM already includes the new values, so up() is
     * idempotent (matches the field_exists()/table_exists() idiom used everywhere else in this
     * codebase's migrations, applied here to a column MODIFICATION rather than a column
     * addition).
     */
    private function is_type_already_widened(): bool
    {
        $column = $this->db
            ->query("SHOW COLUMNS FROM " . $this->db->dbprefix('payment_transactions') . " LIKE 'type'")
            ->row_array();

        if (!$column) {
            return false;
        }

        return str_contains((string) $column['Type'], 'pos_sale');
    }
}
