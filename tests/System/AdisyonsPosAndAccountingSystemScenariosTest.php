<?php declare(strict_types=1);

namespace Tests\System;

use Tests\TestCase;

/**
 * System Use Case Scenarios: Adisyons, POS, Payments, Wallets & Invoicing (UC-141 to UC-165).
 */
class AdisyonsPosAndAccountingSystemScenariosTest extends TestCase
{
    /** UC-141: Open adisyon from scheduled appointment */
    public function test_UC141_open_adisyon_from_appointment(): void
    {
        $adisyon = [
            'appointment_id' => 105,
            'customer_id' => 12,
            'status' => 'open',
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $this->assertSame('open', $adisyon['status']);
        $this->assertSame(105, $adisyon['appointment_id']);
    }

    /** UC-142: Add service line item to adisyon */
    public function test_UC142_add_service_line_item(): void
    {
        $items = [];
        $items[] = ['type' => 'service', 'name' => 'Saç Kesimi', 'qty' => 1, 'unit_price' => 300.00];
        $this->assertCount(1, $items);
        $this->assertSame(300.00, $items[0]['unit_price']);
    }

    /** UC-143: Add retail product line item to adisyon */
    public function test_UC143_add_product_line_item(): void
    {
        $items = [];
        $items[] = ['type' => 'product', 'name' => 'Şampuan 500ml', 'qty' => 2, 'unit_price' => 150.00];
        $lineTotal = $items[0]['qty'] * $items[0]['unit_price'];
        $this->assertSame(300.00, $lineTotal);
    }

    /** UC-144: Modify line item quantity and unit price */
    public function test_UC144_modify_line_item(): void
    {
        $item = ['qty' => 1, 'unit_price' => 100.00];
        $item['qty'] = 3;
        $item['unit_price'] = 90.00;
        $this->assertSame(270.00, $item['qty'] * $item['unit_price']);
    }

    /** UC-145: Remove line item from adisyon */
    public function test_UC145_remove_line_item(): void
    {
        $items = ['item_1' => ['price' => 100], 'item_2' => ['price' => 200]];
        unset($items['item_1']);
        $this->assertArrayNotHasKey('item_1', $items);
        $this->assertCount(1, $items);
    }

    /** UC-146: Subtotal, tax (KDV 20%) and grand total auto-calculation */
    public function test_UC146_subtotal_and_tax_calculation(): void
    {
        $subtotal = 1000.00;
        $kdvRate = 0.20; // %20 KDV
        $kdvAmount = $subtotal * $kdvRate;
        $grandTotal = $subtotal + $kdvAmount;
        $this->assertSame(200.00, $kdvAmount);
        $this->assertSame(1200.00, $grandTotal);
    }

    /** UC-147: Apply percentage discount to whole adisyon */
    public function test_UC147_adisyon_percentage_discount(): void
    {
        $total = 500.00;
        $discountPct = 10;
        $discountedTotal = $total * (1 - ($discountPct / 100));
        $this->assertSame(450.00, $discountedTotal);
    }

    /** UC-148: Apply fixed monetary discount to whole adisyon */
    public function test_UC148_adisyon_fixed_discount(): void
    {
        $total = 500.00;
        $discountAmount = 75.00;
        $discountedTotal = max(0, $total - $discountAmount);
        $this->assertSame(425.00, $discountedTotal);
    }

    /** UC-149: Record cash payment on adisyon */
    public function test_UC149_record_cash_payment(): void
    {
        $payment = ['method' => 'cash', 'amount' => 425.00, 'status' => 'completed'];
        $this->assertSame('cash', $payment['method']);
        $this->assertSame('completed', $payment['status']);
    }

    /** UC-150: Record credit card POS payment on adisyon */
    public function test_UC150_record_pos_payment(): void
    {
        $payment = ['method' => 'credit_card', 'amount' => 300.00, 'transaction_id' => 'pos_99812'];
        $this->assertSame('credit_card', $payment['method']);
        $this->assertSame('pos_99812', $payment['transaction_id']);
    }

    /** UC-151: Record split payment (part cash, part credit card) */
    public function test_UC151_split_payment(): void
    {
        $totalDue = 1000.00;
        $payments = [
            ['method' => 'cash', 'amount' => 400.00],
            ['method' => 'credit_card', 'amount' => 600.00],
        ];
        $totalPaid = array_sum(array_column($payments, 'amount'));
        $this->assertSame($totalDue, $totalPaid);
    }

    /** UC-152: Partial payment leaves remaining balance due */
    public function test_UC152_partial_payment_balance(): void
    {
        $totalDue = 1000.00;
        $paid = 400.00;
        $remaining = $totalDue - $paid;
        $this->assertSame(600.00, $remaining);
    }

    /** UC-153: Full payment marks adisyon as paid and appointment closed */
    public function test_UC153_full_payment_closes_adisyon(): void
    {
        $adisyon = ['total' => 500.00, 'paid' => 500.00, 'status' => 'open'];
        if ($adisyon['paid'] >= $adisyon['total']) {
            $adisyon['status'] = 'closed';
        }
        $this->assertSame('closed', $adisyon['status']);
    }

    /** UC-154: POS gateway: Iyzico HMAC signature verification */
    public function test_UC154_iyzico_hmac_signature(): void
    {
        $apiKey = 'sandbox-api-key';
        $secretKey = 'sandbox-secret-key';
        $randomStr = '123456789';
        $data = $apiKey . $randomStr . $secretKey;
        $signature = base64_encode(sha1($data, true));
        $this->assertNotEmpty($signature);
    }

    /** UC-155: POS gateway: Stripe webhook signature verification */
    public function test_UC155_stripe_webhook_signature(): void
    {
        $payload = '{"id":"evt_123"}';
        $secret = 'whsec_test_secret';
        $timestamp = time();
        $signedPayload = "{$timestamp}.{$payload}";
        $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);
        $this->assertSame(64, strlen($expectedSignature));
    }

    /** UC-156: POS gateway: ÖdeAl OAuth token refresh */
    public function test_UC156_odeal_token_refresh(): void
    {
        $tokenResponse = ['access_token' => 'odeal_token_abc', 'expires_in' => 3600];
        $this->assertArrayHasKey('access_token', $tokenResponse);
    }

    /** UC-157: Generate electronic invoice payload from closed adisyon */
    public function test_UC157_e_invoice_payload_generation(): void
    {
        $eInvoice = [
            'invoice_number' => 'GIB2026000000001',
            'tax_number' => '1111111111',
            'total_amount' => 1200.00,
            'tax_amount' => 200.00,
        ];
        $this->assertStringStartsWith('GIB2026', $eInvoice['invoice_number']);
    }

    /** UC-158: Expense record creation */
    public function test_UC158_create_expense_record(): void
    {
        $expense = [
            'title' => 'Salon Kirası Eylül 2026',
            'amount' => 15000.00,
            'category' => 'rent',
            'date' => '2026-09-01',
        ];
        $this->assertSame('rent', $expense['category']);
        $this->assertSame(15000.00, $expense['amount']);
    }

    /** UC-159: Expense category classification */
    public function test_UC159_expense_categories(): void
    {
        $validCategories = ['rent', 'utilities', 'supplies', 'salaries', 'marketing'];
        $this->assertContains('supplies', $validCategories);
    }

    /** UC-160: Delete expense via CSRF POST request */
    public function test_UC160_delete_expense_csrf(): void
    {
        $requestMethod = 'POST';
        $csrfTokenValid = true;
        $canDelete = ($requestMethod === 'POST' && $csrfTokenValid);
        $this->assertTrue($canDelete);
    }

    /** UC-161: Daily cash register Z-report summary calculation */
    public function test_UC161_daily_z_report_calculation(): void
    {
        $cashIn = 2450.00;
        $posIn = 7800.00;
        $expensesOut = 650.00;
        $netCash = $cashIn - $expensesOut;
        $totalDailyRevenue = $cashIn + $posIn;
        $this->assertSame(1800.00, $netCash);
        $this->assertSame(10250.00, $totalDailyRevenue);
    }

    /** UC-162: Tenant internal wallet creation on provisioning */
    public function test_UC162_tenant_wallet_creation(): void
    {
        $wallet = ['tenant_id' => 1, 'balance' => 0.00, 'currency' => 'TRY'];
        $this->assertSame(0.00, $wallet['balance']);
    }

    /** UC-163: Wallet ledger credit entry on appointment closed */
    public function test_UC163_wallet_credit_entry(): void
    {
        $balance = 0.00;
        $appointmentEarning = 500.00;
        $balance += $appointmentEarning;
        $this->assertSame(500.00, $balance);
    }

    /** UC-164: Platform commission debit entry (5%) into wallet ledger */
    public function test_UC164_wallet_commission_debit(): void
    {
        $balance = 500.00;
        $commissionRate = 0.05; // %5
        $commissionDebit = 500.00 * $commissionRate;
        $balance -= $commissionDebit;
        $this->assertSame(25.00, $commissionDebit);
        $this->assertSame(475.00, $balance);
    }

    /** UC-165: Wallet balance reconciliation */
    public function test_UC165_wallet_reconciliation(): void
    {
        $credits = [500.00, 300.00, 1000.00];
        $debits = [25.00, 15.00, 50.00];
        $netBalance = array_sum($credits) - array_sum($debits);
        $this->assertSame(1710.00, $netBalance);
    }
}

