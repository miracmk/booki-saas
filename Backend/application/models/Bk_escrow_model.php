<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Model: Bk_escrow_model
 * 
 * Manages Escrow & Settlement operations for RandevuBurada & BooKi:
 * Commission structure: %5 RandevuBurada + %5 Tosla POS + %1 EFT/FAST Transfer = %11 Total Fee.
 * %89 Net Payout to business after T+3 escrow holding period.
 */
class Bk_escrow_model extends CI_Model
{
    private string $table = 'bk_escrow_settlements';

    public function __construct()
    {
        $this->load->database();
    }

    /**
     * Create a new escrow record for an appointment with 7-day pre-auth hold.
     */
    public function create_settlement(array $data): int
    {
        $gross = (float) ($data['gross_amount'] ?? 0);
        $rbRate = (float) ($data['marketplace_rate'] ?? 5.00);
        $posRate = (float) ($data['pos_rate'] ?? 5.00);
        $transferRate = (float) ($data['transfer_rate'] ?? 1.00);

        $rbCommission = round($gross * ($rbRate / 100), 2);
        $posFee = round($gross * ($posRate / 100), 2);
        $transferFee = round($gross * ($transferRate / 100), 2);
        $netPayout = round($gross - ($rbCommission + $posFee + $transferFee), 2);

        $now = date('Y-m-d H:i:s');
        $deductionModel = in_array($data['deduction_model'] ?? '', ['model_a', 'model_b', 'model_c'], true) 
            ? $data['deduction_model'] 
            : 'model_a';

        $record = [
            'id_tenants'             => (int) $data['id_tenants'],
            'id_appointments'        => !empty($data['id_appointments']) ? (int) $data['id_appointments'] : null,
            'customer_id'            => !empty($data['customer_id']) ? (int) $data['customer_id'] : null,
            'customer_name'          => trim((string) ($data['customer_name'] ?? '')),
            'customer_phone'         => trim((string) ($data['customer_phone'] ?? '')),
            'service_name'           => trim((string) ($data['service_name'] ?? 'Hizmet')),
            'gross_amount'           => $gross,
            'marketplace_rate'       => $rbRate,
            'marketplace_commission' => $rbCommission,
            'pos_rate'               => $posRate,
            'pos_fee'                => $posFee,
            'transfer_rate'          => $transferRate,
            'transfer_fee'           => $transferFee,
            'net_payout_amount'      => $netPayout,
            'tosla_transaction_id'   => $data['tosla_transaction_id'] ?? null,
            'tosla_order_id'         => $data['tosla_order_id'] ?? ('ESC_' . time() . rand(10, 99)),
            'provision_status'       => $data['provision_status'] ?? 'authorized',
            'payout_status'          => $data['payout_status'] ?? 'pending_showup',
            'payout_due_date'        => null,
            'payout_iban'            => $data['payout_iban'] ?? null,
            'notes'                  => $data['notes'] ?? null,
            'created_at'             => $now,
            'updated_at'             => $now,
            'deduction_model'        => $deductionModel,
            'invoice_file_url'       => $data['invoice_file_url'] ?? null,
            'invoice_no'             => $data['invoice_no'] ?? null,
            'invoice_tax_id'         => $data['invoice_tax_id'] ?? null
        ];

        $this->db->query("
            INSERT INTO `{$this->table}` (
                `id_tenants`, `id_appointments`, `customer_id`, `customer_name`, `customer_phone`,
                `service_name`, `gross_amount`, `marketplace_rate`, `marketplace_commission`,
                `pos_rate`, `pos_fee`, `transfer_rate`, `transfer_fee`, `net_payout_amount`,
                `tosla_transaction_id`, `tosla_order_id`, `provision_status`, `payout_status`,
                `payout_due_date`, `payout_iban`, `notes`, `created_at`, `updated_at`,
                `deduction_model`, `invoice_file_url`, `invoice_no`, `invoice_tax_id`
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ", array_values($record));

        return (int) $this->db->insert_id();
    }

    public function get_by_id(int $id): ?array
    {
        $res = $this->db->query("SELECT * FROM `{$this->table}` WHERE `id` = ? LIMIT 1", [$id])->row_array();
        return $res ?: null;
    }

    public function get_by_order_id(string $orderId): ?array
    {
        $res = $this->db->query("SELECT * FROM `{$this->table}` WHERE `tosla_order_id` = ? LIMIT 1", [$orderId])->row_array();
        return $res ?: null;
    }

    public function get_by_appointment_id(int $appointmentId): ?array
    {
        $res = $this->db->query("SELECT * FROM `{$this->table}` WHERE `id_appointments` = ? LIMIT 1", [$appointmentId])->row_array();
        return $res ?: null;
    }

    /**
     * Mark appointment show-up: capture pre-auth and start T+3 escrow countdown.
     */
    public function mark_showup(int $id, string $capturedTransactionId = ''): bool
    {
        $settlement = $this->get_by_id($id);
        if (!$settlement) {
            return false;
        }

        // T+3 Calculation: +3 business days from now
        $dueDate = $this->calculate_t3_due_date();
        $now = date('Y-m-d H:i:s');

        $txId = $capturedTransactionId ?: $settlement['tosla_transaction_id'];

        return (bool) $this->db->query("
            UPDATE `{$this->table}`
            SET `provision_status` = 'captured',
                `payout_status` = 'in_escrow_t3',
                `payout_due_date` = ?,
                `tosla_transaction_id` = ?,
                `updated_at` = ?
            WHERE `id` = ?
        ", [$dueDate, $txId, $now, $id]);
    }

    /**
     * Calculate T+3 business days skipping weekend.
     */
    public function calculate_t3_due_date(): string
    {
        $daysAdded = 0;
        $current = time();

        while ($daysAdded < 3) {
            $current += 86400;
            $dayOfWeek = (int) date('N', $current);
            // 1 (Mon) - 5 (Fri) are business days
            if ($dayOfWeek <= 5) {
                $daysAdded++;
            }
        }

        return date('Y-m-d 17:00:00', $current);
    }

    /**
     * Mark cancellation or no-show.
     */
    public function mark_cancelled(int $id, string $reason = 'cancelled'): bool
    {
        $now = date('Y-m-d H:i:s');
        return (bool) $this->db->query("
            UPDATE `{$this->table}`
            SET `provision_status` = 'voided',
                `payout_status` = 'cancelled',
                `notes` = CONCAT(COALESCE(`notes`, ''), '\n[İptal] ', ?),
                `updated_at` = ?
            WHERE `id` = ?
        ", [$reason, $now, $id]);
    }

    /**
     * Upload official e-Fatura / e-SMM document for a matured settlement.
     */
    public function upload_merchant_invoice(int $settlementId, array $invoiceData): bool
    {
        $now = date('Y-m-d H:i:s');
        return (bool) $this->db->query("
            UPDATE `{$this->table}`
            SET `invoice_file_url` = ?,
                `invoice_no` = ?,
                `invoice_tax_id` = ?,
                `invoice_uploaded_at` = ?,
                `payout_status` = 'pending_invoice',
                `updated_at` = ?
            WHERE `id` = ?
        ", [
            $invoiceData['invoice_file_url'] ?? '',
            $invoiceData['invoice_no'] ?? '',
            $invoiceData['invoice_tax_id'] ?? '',
            $now,
            $now,
            $settlementId
        ]);
    }

    /**
     * Verify merchant e-Fatura / e-SMM and credit tenant unified current account.
     */
    public function verify_merchant_invoice(int $settlementId): array
    {
        $settlement = $this->get_by_id($settlementId);
        if (!$settlement) {
            throw new InvalidArgumentException("Hakediş bulunamadı: ID {$settlementId}");
        }

        $now = date('Y-m-d H:i:s');

        // Update settlement status to invoice_verified / ready_for_payout
        $this->db->query("
            UPDATE `{$this->table}`
            SET `payout_status` = 'invoice_verified',
                `invoice_verified_at` = ?,
                `updated_at` = ?
            WHERE `id` = ?
        ", [$now, $now, $settlementId]);

        // Credit to tenant bk_current_accounts
        $tenantId = (int) $settlement['id_tenants'];
        $amount = (float) $settlement['net_payout_amount'];
        $entryId = $this->record_current_account_entry([
            'id_tenants'       => $tenantId,
            'account_type'     => 'escrow_payout',
            'reference_id'     => 'SETTLE_' . $settlementId,
            'direction'        => 'credit',
            'amount'           => $amount,
            'status'           => 'ready_for_transfer',
            'description'      => "Escrow Hakediş (#{$settlementId} - {$settlement['service_name']})",
            'invoice_file_url' => $settlement['invoice_file_url'],
            'invoice_no'       => $settlement['invoice_no']
        ]);

        return [
            'settlement_id'    => $settlementId,
            'status'           => 'invoice_verified',
            'current_entry_id' => $entryId,
            'net_credited'     => $amount
        ];
    }

    /**
     * Record a transaction ledger entry in bk_current_accounts.
     */
    public function record_current_account_entry(array $data): int
    {
        $tenantId = (int) $data['id_tenants'];
        $direction = $data['direction'] ?? 'credit';
        $amount = round((float) ($data['amount'] ?? 0), 2);

        $currentBalance = $this->get_tenant_current_account_balance($tenantId);
        $newBalance = $direction === 'credit' ? round($currentBalance + $amount, 2) : round($currentBalance - $amount, 2);

        $now = date('Y-m-d H:i:s');
        $this->db->query("
            INSERT INTO `bk_current_accounts` (
                `id_tenants`, `account_type`, `reference_id`, `direction`, `amount`,
                `balance_after`, `status`, `description`, `invoice_file_url`, `invoice_no`,
                `created_at`, `updated_at`
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ", [
            $tenantId,
            $data['account_type'] ?? 'escrow_payout',
            $data['reference_id'] ?? null,
            $direction,
            $amount,
            $newBalance,
            $data['status'] ?? 'ready_for_transfer',
            $data['description'] ?? null,
            $data['invoice_file_url'] ?? null,
            $data['invoice_no'] ?? null,
            $now,
            $now
        ]);

        return (int) $this->db->insert_id();
    }

    /**
     * Calculate running available balance for a tenant from bk_current_accounts.
     */
    public function get_tenant_current_account_balance(int $tenantId): float
    {
        $row = $this->db->query("
            SELECT 
                COALESCE(SUM(CASE WHEN `direction` = 'credit' AND `status` IN ('invoice_verified', 'ready_for_transfer') THEN `amount` ELSE 0 END), 0) -
                COALESCE(SUM(CASE WHEN `direction` = 'debit' THEN `amount` ELSE 0 END), 0) as balance
            FROM `bk_current_accounts`
            WHERE `id_tenants` = ?
        ", [$tenantId])->row_array();

        return round((float) ($row['balance'] ?? 0), 2);
    }

    /**
     * List transaction ledger from bk_current_accounts.
     */
    public function get_tenant_ledger(int $tenantId, int $limit = 50): array
    {
        return $this->db->query("
            SELECT * FROM `bk_current_accounts`
            WHERE `id_tenants` = ?
            ORDER BY `id` DESC
            LIMIT ?
        ", [$tenantId, $limit])->result_array();
    }

    /**
     * Get settlements awaiting merchant invoice upload.
     */
    public function get_pending_invoices(?int $tenantId = null): array
    {
        if ($tenantId !== null && $tenantId > 0) {
            return $this->db->query("
                SELECT * FROM `{$this->table}`
                WHERE `payout_status` = 'pending_invoice' AND `id_tenants` = ?
                ORDER BY `id` DESC
            ", [$tenantId])->result_array();
        }

        return $this->db->query("
            SELECT * FROM `{$this->table}`
            WHERE `payout_status` = 'pending_invoice'
            ORDER BY `id` DESC
        ")->result_array();
    }

    /**
     * Get tenant escrow and payout balance summary.
     */
    public function get_tenant_financial_summary(int $tenantId): array
    {
        $row = $this->db->query("
            SELECT 
                COUNT(*) as total_bookings,
                COALESCE(SUM(gross_amount), 0) as total_gross_sales,
                COALESCE(SUM(marketplace_commission), 0) as total_marketplace_commission,
                COALESCE(SUM(pos_fee), 0) as total_pos_fees,
                COALESCE(SUM(transfer_fee), 0) as total_transfer_fees,
                COALESCE(SUM(CASE WHEN payout_status = 'pending_showup' THEN net_payout_amount ELSE 0 END), 0) as pending_showup_amount,
                COALESCE(SUM(CASE WHEN payout_status = 'in_escrow_t3' THEN net_payout_amount ELSE 0 END), 0) as in_escrow_t3_amount,
                COALESCE(SUM(CASE WHEN payout_status = 'pending_invoice' THEN net_payout_amount ELSE 0 END), 0) as pending_invoice_amount,
                COALESCE(SUM(CASE WHEN payout_status IN ('invoice_verified', 'ready_for_payout') THEN net_payout_amount ELSE 0 END), 0) as ready_for_payout_amount,
                COALESCE(SUM(CASE WHEN payout_status = 'transferred' THEN net_payout_amount ELSE 0 END), 0) as transferred_amount
            FROM `{$this->table}`
            WHERE `id_tenants` = ? AND `payout_status` != 'cancelled'
        ", [$tenantId])->row_array();

        $currentBalance = $this->get_tenant_current_account_balance($tenantId);

        return [
            'total_bookings'                 => (int) ($row['total_bookings'] ?? 0),
            'total_gross_sales'              => (float) ($row['total_gross_sales'] ?? 0),
            'total_commission_and_fees'      => round((float) ($row['total_marketplace_commission'] ?? 0) + (float) ($row['total_pos_fees'] ?? 0) + (float) ($row['total_transfer_fees'] ?? 0), 2),
            'marketplace_commission_cut'     => (float) ($row['total_marketplace_commission'] ?? 0),
            'pos_fee_cut'                    => (float) ($row['total_pos_fees'] ?? 0),
            'transfer_fee_cut'               => (float) ($row['total_transfer_fees'] ?? 0),
            'pending_showup_amount'          => (float) ($row['pending_showup_amount'] ?? 0),
            'in_escrow_t3_amount'            => (float) ($row['in_escrow_t3_amount'] ?? 0),
            'pending_invoice_amount'         => (float) ($row['pending_invoice_amount'] ?? 0),
            'ready_for_payout_amount'        => (float) ($row['ready_for_payout_amount'] ?? 0),
            'transferred_amount'             => (float) ($row['transferred_amount'] ?? 0),
            'available_to_payout'            => $currentBalance,
            'current_account_balance'        => $currentBalance
        ];
    }

    /**
     * Process due payouts where T+3 has matured:
     * Shift matured settlements from 'in_escrow_t3' to 'pending_invoice' (Gatekeeper).
     */
    public function process_matured_escrow_payouts(): int
    {
        $now = date('Y-m-d H:i:s');
        $this->db->query("
            UPDATE `{$this->table}`
            SET `payout_status` = 'pending_invoice',
                `updated_at` = ?
            WHERE `payout_status` = 'in_escrow_t3'
              AND `payout_due_date` <= ?
        ", [$now, $now]);

        return (int) $this->db->affected_rows();
    }
}
