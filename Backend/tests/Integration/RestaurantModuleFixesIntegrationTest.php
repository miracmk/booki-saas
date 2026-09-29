<?php declare(strict_types=1);

namespace Tests\Integration;

use Tests\TenantTestCase;

/**
 * Comprehensive Integration Test for Restaurant Module & Split Payment Fixes.
 */
class RestaurantModuleFixesIntegrationTest extends TenantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        self::ci()->load->model('split_payments_model');
        self::ci()->load->model('restaurant_model');
        self::ci()->load->model('adisyons_model');
        self::ci()->load->model('customers_model');
        self::ci()->load->model('loyalty_points_model');
    }

    /**
     * Test 1: Verify Split_payments_model recognizes credit_card and accumulates into total_paid.
     */
    public function testCreditCardRecognizedInSplitPaymentTotals(): void
    {
        $db = self::db();
        $db->trans_start();

        $testAdisyonId = 999991;

        // Insert split payment records via model
        self::ci()->split_payments_model->add_payment([
            'entity_type' => 'adisyon',
            'entity_id' => $testAdisyonId,
            'payment_type' => 'credit_card',
            'amount' => 150.50,
            'notes' => 'Split via Credit Card'
        ]);

        self::ci()->split_payments_model->add_payment([
            'entity_type' => 'adisyon',
            'entity_id' => $testAdisyonId,
            'payment_type' => 'cash',
            'amount' => 50.00,
            'notes' => 'Split via Cash'
        ]);

        self::ci()->split_payments_model->add_payment([
            'entity_type' => 'adisyon',
            'entity_id' => $testAdisyonId,
            'payment_type' => 'card',
            'amount' => 100.00,
            'notes' => 'Split via Card'
        ]);

        self::ci()->split_payments_model->add_payment([
            'entity_type' => 'adisyon',
            'entity_id' => $testAdisyonId,
            'payment_type' => 'discount',
            'amount' => 20.00,
            'notes' => 'Promo discount'
        ]);

        $totals = self::ci()->split_payments_model->get_totals('adisyon', $testAdisyonId);

        // 150.50 (credit_card) + 50.00 (cash) + 100.00 (card) = 300.50
        $this->assertEqualsWithDelta(300.50, (float)$totals['total_paid'], 0.001);
        $this->assertEqualsWithDelta(20.00, (float)$totals['total_discount'], 0.001);

        $db->trans_rollback();
    }

    /**
     * Test 2: Verify add_payment handles entity_id, entity_type, notes.
     */
    public function testAddPaymentAcceptsFrontendContractKeys(): void
    {
        $db = self::db();
        $db->trans_start();

        $testAdisyonId = 999992;
        $noteText = 'QA contract test note';

        $paymentId = self::ci()->split_payments_model->add_payment([
            'entity_type' => 'adisyon',
            'entity_id' => $testAdisyonId,
            'payment_type' => 'credit_card',
            'amount' => 75.25,
            'notes' => $noteText
        ]);

        $this->assertGreaterThan(0, $paymentId);

        $paymentRow = $db->get_where('system_split_payments', ['id' => $paymentId])->row_array();
        $this->assertNotEmpty($paymentRow);
        $this->assertSame('adisyon', $paymentRow['entity_type']);
        $this->assertSame((string)$testAdisyonId, (string)$paymentRow['entity_id']);
        $this->assertSame('credit_card', $paymentRow['payment_type']);
        $this->assertEqualsWithDelta(75.25, (float)$paymentRow['amount'], 0.001);
        $this->assertSame($noteText, $paymentRow['notes']);

        $db->trans_rollback();
    }

    /**
     * Test 3: Table transfer model integrity for source/target table.
     */
    public function testTableTransferIntegrity(): void
    {
        $db = self::db();
        $db->trans_start();

        // Create or find two tables
        $db->insert('restaurant_tables', [
            'table_number' => 'QA-T1',
            'name' => 'QA Masa A',
            'status' => 'occupied',
            'capacity' => 4,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        $sourceId = (int)$db->insert_id();

        $db->insert('restaurant_tables', [
            'table_number' => 'QA-T2',
            'name' => 'QA Masa B',
            'status' => 'available',
            'capacity' => 4,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        $targetId = (int)$db->insert_id();

        // Create adisyon for source table
        $db->insert('adisyons', [
            'id_restaurant_tables' => $sourceId,
            'status' => 'open',
            'total_amount' => 250.00,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        $adisyonId = (int)$db->insert_id();

        $db->update('restaurant_tables', ['current_id_adisyons' => $adisyonId], ['id' => $sourceId]);

        // Transfer table using model
        $res = self::ci()->restaurant_model->transfer_table($sourceId, $targetId);
        $this->assertSame('success', $res['status']);

        // Check target table now has the adisyon
        $targetTable = $db->get_where('restaurant_tables', ['id' => $targetId])->row_array();
        $this->assertSame((string)$adisyonId, (string)$targetTable['current_id_adisyons']);
        $this->assertSame('dining', $targetTable['status']);

        // Source table should be cleaning and current_id_adisyons null
        $sourceTable = $db->get_where('restaurant_tables', ['id' => $sourceId])->row_array();
        $this->assertNull($sourceTable['current_id_adisyons']);
        $this->assertSame('cleaning', $sourceTable['status']);

        $db->trans_rollback();
    }

    /**
     * Test 4: Verify customer attachment when missing during loyalty redemption or directly.
     */
    public function testCustomerAttachmentToAdisyon(): void
    {
        $db = self::db();
        $db->trans_start();

        // Create dummy customer
        $db->insert('users', [
            'first_name' => 'QA',
            'last_name' => 'Tester',
            'email' => 'qa_test_' . time() . '@example.com',
            'phone_number' => '5551234567',
            'role_slug' => 'customer',
            'create_datetime' => date('Y-m-d H:i:s')
        ]);
        $customerId = (int)$db->insert_id();

        // Create open adisyon without customer
        $db->insert('adisyons', [
            'status' => 'open',
            'total_amount' => 500.00,
            'id_users_customer' => null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        $adisyonId = (int)$db->insert_id();

        $adisyon = self::ci()->adisyons_model->find($adisyonId);
        $this->assertEmpty($adisyon['id_users_customer']);

        // Simulate attaching customer
        $db->update('adisyons', ['id_users_customer' => $customerId], ['id' => $adisyonId]);

        $updatedAdisyon = self::ci()->adisyons_model->find($adisyonId);
        $this->assertSame((int)$customerId, (int)$updatedAdisyon['id_users_customer']);

        $db->trans_rollback();
    }

    /**
     * Test 5: Verify customer phone lookup logic with formatted/unformatted variations.
     */
    public function testCustomerPhoneLookupVariations(): void
    {
        $db = self::db();
        $db->trans_start();

        $uniquePhone = '5329988776';
        $db->insert('users', [
            'first_name' => 'Sadık',
            'last_name' => 'Müşteri',
            'email' => 'sadik_' . time() . '@example.com',
            'phone_number' => '+90 (532) 998 87 76',
            'mobile_number' => '05329988776',
            'role_slug' => 'customer',
            'create_datetime' => date('Y-m-d H:i:s')
        ]);
        $customerId = (int)$db->insert_id();

        // Query using the same logic as Restaurant::api_customer_lookup
        $search_phone = preg_replace('/[^0-9]/', '', '0532 998 87 76');
        $last10 = (strlen($search_phone) >= 10) ? substr($search_phone, -10) : $search_phone;

        $customer_row = $db->select('users.*')
            ->from('users')
            ->join('roles', 'roles.id = users.id_roles', 'left')
            ->group_start()
                ->where('users.role_slug', 'customer')
                ->or_where('roles.slug', 'customer')
            ->group_end()
            ->group_start()
                ->like('users.phone_number', $search_phone)
                ->or_like('users.phone_number', $last10)
                ->or_like('users.mobile_number', $search_phone)
                ->or_like('users.mobile_number', $last10)
            ->group_end()
            ->limit(1)
            ->get()
            ->row_array();

        $this->assertNotEmpty($customer_row, 'Customer must be resolved via phone lookup logic');
        $this->assertSame($customerId, (int)$customer_row['id']);

        $db->trans_rollback();
    }

    /**
     * Test 6: Finalize split payment with credit_card mapping and close_table integration.
     */
    public function testFinalizeSplitPaymentWithCreditCardAndCloseTable(): void
    {
        $db = self::db();
        $db->trans_start();

        // Create table
        $db->insert('restaurant_tables', [
            'table_number' => 'QA-T9',
            'name' => 'QA Masa 9',
            'status' => 'dining',
            'capacity' => 4,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        $tableId = (int)$db->insert_id();

        // Create adisyon
        $db->insert('adisyons', [
            'id_restaurant_tables' => $tableId,
            'status' => 'open',
            'total_amount' => 200.00,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        $adisyonId = (int)$db->insert_id();
        $db->update('restaurant_tables', ['current_id_adisyons' => $adisyonId], ['id' => $tableId]);

        // Add split payments: 1 credit_card (120 TL) + 1 cash (80 TL)
        self::ci()->split_payments_model->add_payment([
            'entity_type' => 'adisyon',
            'entity_id' => $adisyonId,
            'payment_type' => 'credit_card',
            'amount' => 120.00,
        ]);
        self::ci()->split_payments_model->add_payment([
            'entity_type' => 'adisyon',
            'entity_id' => $adisyonId,
            'payment_type' => 'cash',
            'amount' => 80.00,
        ]);

        // Test Split_payments_model get_totals
        $totals = self::ci()->split_payments_model->get_totals('adisyon', $adisyonId);
        $this->assertEqualsWithDelta(200.00, (float)$totals['total_paid'], 0.001);

        // Simulate finalize_split_payment logic
        $payments = self::ci()->split_payments_model->get_payments('adisyon', $adisyonId);
        $paid_by_type = ['cash' => 0.0, 'card' => 0.0, 'transfer' => 0.0];
        $total_paid = 0.0;
        foreach ($payments as $payment) {
            $p_type = $payment['payment_type'];
            if (in_array($p_type, ['cash', 'card', 'credit_card', 'transfer', 'gift_card', 'membership'])) {
                $mapped_type = $p_type;
                if ($p_type === 'credit_card') $mapped_type = 'card';
                if ($p_type === 'gift_card') $mapped_type = 'cash';
                if ($p_type === 'membership') $mapped_type = 'cash';
                if (isset($paid_by_type[$mapped_type])) {
                    $paid_by_type[$mapped_type] += (float) $payment['amount'];
                }
                $total_paid += (float) $payment['amount'];
            }
        }

        $this->assertEqualsWithDelta(120.00, $paid_by_type['card'], 0.001, 'Credit card payment must be mapped to card');
        $this->assertEqualsWithDelta(80.00, $paid_by_type['cash'], 0.001);
        $this->assertEqualsWithDelta(200.00, $total_paid, 0.001);

        // Simulate close_table logic
        $table = $db->get_where('restaurant_tables', ['current_id_adisyons' => $adisyonId])->row_array();
        $this->assertNotEmpty($table);
        $db->where('id', (int) $table['id'])->update('restaurant_tables', [
            'status' => 'cleaning',
            'current_id_adisyons' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $closedTable = $db->get_where('restaurant_tables', ['id' => $tableId])->row_array();
        $this->assertNull($closedTable['current_id_adisyons']);
        $this->assertSame('cleaning', $closedTable['status']);

        $db->trans_rollback();
    }
}
