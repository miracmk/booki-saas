<?php declare(strict_types=1);

namespace Tests\Integration;

use Tests\TenantTestCase;

/**
 * Integration Test for Sector 2 (beauty_wellness) Backend Fixes.
 */
class BeautyWellnessFixesIntegrationTest extends TenantTestCase
{
    private static int $providerId;
    private static int $customerId;

    protected function setUp(): void
    {
        parent::setUp();
        self::ci()->load->model('adisyons_model');
        self::ci()->load->model('gift_cards_model');
        self::ci()->load->model('customer_memberships_model');
        self::ci()->load->model('packages_model');

        $db = self::db();
        // Ensure provider and customer exist for foreign keys
        $provider = $db->get_where('users', ['email' => 'test_provider@booki.local'])->row_array();
        if (!$provider) {
            $db->insert('users', [
                'first_name' => 'Test',
                'last_name' => 'Provider',
                'email' => 'test_provider@booki.local',
                'phone_number' => '05550000001',
                'id_roles' => 2,
            ]);
            self::$providerId = (int) $db->insert_id();
        } else {
            self::$providerId = (int) $provider['id'];
        }

        $customer = $db->get_where('users', ['email' => 'test_customer@booki.local'])->row_array();
        if (!$customer) {
            $db->insert('users', [
                'first_name' => 'Test',
                'last_name' => 'Customer',
                'email' => 'test_customer@booki.local',
                'phone_number' => '05550000002',
                'id_roles' => 3,
            ]);
            self::$customerId = (int) $db->insert_id();
        } else {
            self::$customerId = (int) $customer['id'];
        }
    }

    /**
     * Test 1: Verify get_or_create_for_appointment automatically creates deposit payment in adisyon_payments
     * when appointment has deposit_status = 'paid' and deposit_amount > 0.
     */
    public function testDepositAutomaticallyRecordedInAdisyon(): void
    {
        $db = self::db();

        // 1. Create a dummy appointment with deposit_status = 'paid'
        $db->insert('appointments', [
            'start_datetime' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'end_datetime' => date('Y-m-d H:i:s', strtotime('+1 day +1 hour')),
            'is_unavailability' => 0,
            'deposit_amount' => 75.00,
            'deposit_status' => 'paid',
            'deposit_paid_at' => date('Y-m-d H:i:s'),
            'id_users_customer' => self::$customerId,
            'id_users_provider' => self::$providerId,
            'id_services' => 1,
        ]);
        $appointmentId = (int) $db->insert_id();

        // 2. Call get_or_create_for_appointment
        $adisyon = self::ci()->adisyons_model->get_or_create_for_appointment($appointmentId);

        $this->assertNotEmpty($adisyon);
        $this->assertGreaterThanOrEqual(75.00, (float) $adisyon['paid_amount']);

        // Check adisyon_payments table for the deposit record
        $depositPayment = $db->get_where('adisyon_payments', [
            'id_adisyons' => $adisyon['id'],
            'notes' => 'Online Randevu Kaporası',
        ])->row_array();

        $this->assertNotNull($depositPayment, 'Deposit payment record must exist in adisyon_payments');
        $this->assertEquals(75.00, (float) $depositPayment['amount']);
        $this->assertEquals('card', $depositPayment['payment_method']);

        // Check idempotency: calling get_or_create_for_appointment again should not duplicate deposit
        $adisyonAgain = self::ci()->adisyons_model->get_or_create_for_appointment($appointmentId);
        $count = $db->where('id_adisyons', $adisyon['id'])
            ->like('notes', 'Online Randevu Kaporası')
            ->count_all_results('adisyon_payments');
        $this->assertEquals(1, $count, 'Deposit should not be recorded multiple times');

        // Cleanup
        $db->where('id_adisyons', $adisyon['id'])->delete('adisyon_payments');
        $db->where('id_adisyons', $adisyon['id'])->delete('adisyon_items');
        $db->where('id', $adisyon['id'])->delete('adisyons');
        $db->where('id', $appointmentId)->delete('appointments');
    }

    /**
     * Test 2: Verify Gift Card redeem deducts balance, updates status to depleted when 0, and logs.
     */
    public function testGiftCardRedemption(): void
    {
        $db = self::db();

        // Issue a card
        $card = self::ci()->gift_cards_model->issue_card([
            'initial_amount' => 200.00,
            'notes' => 'Test Card',
        ]);
        $cardId = (int) $card['id'];
        $code = $card['code'];

        // Partial redeem
        $redeem1 = self::ci()->gift_cards_model->redeem($code, 50.00);
        $this->assertTrue($redeem1['success']);
        $this->assertEquals(150.00, (float) $redeem1['remaining_balance']);
        $this->assertEquals('active', $redeem1['card_status']);

        // Full redeem remaining
        $redeem2 = self::ci()->gift_cards_model->redeem($code, 150.00);
        $this->assertTrue($redeem2['success']);
        $this->assertEquals(0.00, (float) $redeem2['remaining_balance']);

        $updatedCard = $db->get_where('gift_cards', ['id' => $cardId])->row_array();
        $this->assertEquals(0.00, (float) $updatedCard['current_balance']);

        // Check redemption log
        $redemptions = $db->get_where('gift_card_redemptions', ['id_gift_cards' => $cardId])->result_array();
        $this->assertCount(2, $redemptions);

        // Cleanup
        $db->where('id_gift_cards', $cardId)->delete('gift_card_redemptions');
        $db->where('id', $cardId)->delete('gift_cards');
    }

    /**
     * Test 3: Verify record_payment against adisyon with gift card.
     */
    public function testGiftCardPaymentAgainstAdisyon(): void
    {
        $db = self::db();

        // Create an appointment and adisyon
        $db->insert('appointments', [
            'start_datetime' => date('Y-m-d H:i:s', strtotime('+2 days')),
            'end_datetime' => date('Y-m-d H:i:s', strtotime('+2 days +1 hour')),
            'is_unavailability' => 0,
            'deposit_amount' => 0.00,
            'deposit_status' => 'none',
            'id_users_customer' => self::$customerId,
            'id_users_provider' => self::$providerId,
            'id_services' => 1,
        ]);
        $appointmentId = (int) $db->insert_id();

        $adisyon = self::ci()->adisyons_model->get_or_create_for_appointment($appointmentId);
        $adisyonId = (int) $adisyon['id'];

        // Pay with Gift Card via model
        $card = self::ci()->gift_cards_model->issue_card([
            'initial_amount' => 100.00,
            'notes' => 'Test Card 2',
        ]);

        $paymentId = self::ci()->adisyons_model->record_payment($adisyonId, [
            'amount' => 30.00,
            'payment_method' => 'gift_card',
            'gift_card_code' => $card['code'],
            'notes' => 'Gift card checkout',
        ]);

        $this->assertGreaterThan(0, $paymentId);

        // Verify gift card balance deducted
        $cardAfter = $db->get_where('gift_cards', ['id' => $card['id']])->row_array();
        $this->assertEquals(70.00, (float) $cardAfter['current_balance']);

        // Verify adisyon updated
        $updatedAdisyon = self::ci()->adisyons_model->find($adisyonId);
        $this->assertEquals(30.00, (float) $updatedAdisyon['paid_amount']);

        // Cleanup
        $db->where('id_adisyons', $adisyonId)->delete('adisyon_payments');
        $db->where('id_adisyons', $adisyonId)->delete('adisyon_items');
        $db->where('id', $adisyonId)->delete('adisyons');
        $db->where('id', $appointmentId)->delete('appointments');
        $db->where('id_gift_cards', $card['id'])->delete('gift_card_redemptions');
        $db->where('id', $card['id'])->delete('gift_cards');
    }

    /**
     * Test 4: Verify membership payment deducts session and records in customer_membership_sessions.
     */
    public function testMembershipPaymentDeductsSession(): void
    {
        $db = self::db();

        // 1. Create a membership plan
        $db->insert('membership_plans', [
            'name' => 'Gold VIP Beauty Pass',
            'id_services' => 1,
            'billing_period' => 'monthly',
            'price' => 500.00,
            'sessions_per_period' => 5,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $planId = (int) $db->insert_id();

        // 2. Create customer membership
        $db->insert('customer_memberships', [
            'id_users_customer' => self::$customerId,
            'id_membership_plans' => $planId,
            'status' => 'active',
            'current_period_start' => date('Y-m-d H:i:s'),
            'current_period_end' => date('Y-m-d H:i:s', strtotime('+30 days')),
            'sessions_used_this_period' => 1,
            'auto_renew' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $cmId = (int) $db->insert_id();

        // 3. Create appointment and adisyon
        $db->insert('appointments', [
            'start_datetime' => date('Y-m-d H:i:s', strtotime('+3 days')),
            'end_datetime' => date('Y-m-d H:i:s', strtotime('+3 days +1 hour')),
            'is_unavailability' => 0,
            'deposit_amount' => 0.00,
            'deposit_status' => 'none',
            'id_users_customer' => self::$customerId,
            'id_users_provider' => self::$providerId,
            'id_services' => 1,
        ]);
        $appointmentId = (int) $db->insert_id();

        $adisyon = self::ci()->adisyons_model->get_or_create_for_appointment($appointmentId);
        $adisyonId = (int) $adisyon['id'];

        // 4. Record membership payment
        $paymentId = self::ci()->adisyons_model->record_payment($adisyonId, [
            'amount' => 100.00,
            'payment_method' => 'membership',
            'id_customer_memberships' => $cmId,
            'notes' => 'Membership session payment',
        ]);

        $this->assertGreaterThan(0, $paymentId);

        // 5. Verify sessions_used_this_period incremented from 1 to 2
        $cmAfter = $db->get_where('customer_memberships', ['id' => $cmId])->row_array();
        $this->assertEquals(2, (int) $cmAfter['sessions_used_this_period']);

        // 6. Verify customer_membership_sessions row exists
        $sessionLog = $db->get_where('customer_membership_sessions', [
            'id_customer_memberships' => $cmId,
            'id_appointments' => $appointmentId,
        ])->row_array();
        $this->assertNotNull($sessionLog);

        // Cleanup
        $db->where('id_customer_memberships', $cmId)->delete('customer_membership_sessions');
        $db->where('id_adisyons', $adisyonId)->delete('adisyon_payments');
        $db->where('id_adisyons', $adisyonId)->delete('adisyon_items');
        $db->where('id', $adisyonId)->delete('adisyons');
        $db->where('id', $appointmentId)->delete('appointments');
        $db->where('id', $cmId)->delete('customer_memberships');
        $db->where('id', $planId)->delete('membership_plans');
    }

    /**
     * Test 5: Verify package payment deducts session and records in customer_package_sessions.
     */
    public function testPackagePaymentDeductsSession(): void
    {
        $db = self::db();

        // 1. Create a customer package
        $db->insert('customer_packages', [
            'id_users_customer' => self::$customerId,
            'id_services' => 1,
            'total_sessions' => 10,
            'used_sessions' => 2,
            'unit_price' => 80.00,
            'purchased_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+60 days')),
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $pkgId = (int) $db->insert_id();

        // 2. Create appointment and adisyon
        $db->insert('appointments', [
            'start_datetime' => date('Y-m-d H:i:s', strtotime('+4 days')),
            'end_datetime' => date('Y-m-d H:i:s', strtotime('+4 days +1 hour')),
            'is_unavailability' => 0,
            'deposit_amount' => 0.00,
            'deposit_status' => 'none',
            'id_users_customer' => self::$customerId,
            'id_users_provider' => self::$providerId,
            'id_services' => 1,
        ]);
        $appointmentId = (int) $db->insert_id();

        $adisyon = self::ci()->adisyons_model->get_or_create_for_appointment($appointmentId);
        $adisyonId = (int) $adisyon['id'];

        // 3. Record package payment
        $paymentId = self::ci()->adisyons_model->record_payment($adisyonId, [
            'amount' => 80.00,
            'payment_method' => 'package',
            'id_customer_packages' => $pkgId,
            'notes' => 'Package session payment',
        ]);

        $this->assertGreaterThan(0, $paymentId);

        // 4. Verify used_sessions incremented from 2 to 3
        $pkgAfter = $db->get_where('customer_packages', ['id' => $pkgId])->row_array();
        $this->assertEquals(3, (int) $pkgAfter['used_sessions']);

        // 5. Verify customer_package_sessions row exists
        $pkgSessionLog = $db->get_where('customer_package_sessions', [
            'id_customer_packages' => $pkgId,
            'id_appointments' => $appointmentId,
        ])->row_array();
        $this->assertNotNull($pkgSessionLog);

        // Cleanup
        $db->where('id_customer_packages', $pkgId)->delete('customer_package_sessions');
        $db->where('id_adisyons', $adisyonId)->delete('adisyon_payments');
        $db->where('id_adisyons', $adisyonId)->delete('adisyon_items');
        $db->where('id', $adisyonId)->delete('adisyons');
        $db->where('id', $appointmentId)->delete('appointments');
        $db->where('id', $pkgId)->delete('customer_packages');
    }

    /**
     * Test 6: Verify Gift Card edge cases (0 balance and expired date).
     */
    public function testGiftCardEdgeCasesZeroBalanceAndExpired(): void
    {
        $db = self::db();

        // 1. Zero balance / overdraft test
        $card = self::ci()->gift_cards_model->issue_card([
            'initial_amount' => 50.00,
            'notes' => 'Zero Balance Edge Case Card',
        ]);
        $cardId = (int) $card['id'];
        $code = $card['code'];

        // Redeem full balance to reach 0
        $redeemFull = self::ci()->gift_cards_model->redeem($code, 50.00);
        $this->assertTrue($redeemFull['success']);
        $this->assertEquals(0.00, (float) $redeemFull['remaining_balance']);
        $this->assertEquals('redeemed', $redeemFull['card_status']);

        // Attempting to redeem again with 0 balance must fail
        $redeemAgain = self::ci()->gift_cards_model->redeem($code, 10.00);
        $this->assertFalse($redeemAgain['success']);
        $this->assertStringContainsString('aktif değil', $redeemAgain['message']);

        // 2. Expired card test
        $db->insert('gift_cards', [
            'code' => 'EXPIRED-CARD-999',
            'initial_amount' => 100.00,
            'current_balance' => 100.00,
            'status' => 'active',
            'expires_at' => date('Y-m-d', strtotime('-1 day')),
            'created_at' => date('Y-m-d H:i:s', strtotime('-30 days')),
        ]);
        $expiredCardId = (int) $db->insert_id();

        // Attempt to redeem expired card
        $redeemExpired = self::ci()->gift_cards_model->redeem('EXPIRED-CARD-999', 20.00);
        $this->assertFalse($redeemExpired['success']);
        $this->assertStringContainsString('aktif değil', $redeemExpired['message']);

        // Check that card status transitioned to 'expired'
        $expiredRow = $db->get_where('gift_cards', ['id' => $expiredCardId])->row_array();
        $this->assertEquals('expired', $expiredRow['status']);

        // Cleanup
        $db->where('id_gift_cards', $cardId)->delete('gift_card_redemptions');
        $db->where('id', $cardId)->delete('gift_cards');
        $db->where('id', $expiredCardId)->delete('gift_cards');
    }

    /**
     * Test 7: Verify Membership session decrement overdraft prevention when sessions_used >= sessions_per_period.
     */
    public function testMembershipOverdraftPrevention(): void
    {
        $db = self::db();

        // 1. Create a membership plan with 2 sessions per period
        $db->insert('membership_plans', [
            'name' => 'Limited Session Pass',
            'id_services' => 1,
            'billing_period' => 'monthly',
            'price' => 300.00,
            'sessions_per_period' => 2,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $planId = (int) $db->insert_id();

        // 2. Create customer membership with 2 sessions already used (exhausted)
        $db->insert('customer_memberships', [
            'id_users_customer' => self::$customerId,
            'id_membership_plans' => $planId,
            'status' => 'active',
            'current_period_start' => date('Y-m-d H:i:s'),
            'current_period_end' => date('Y-m-d H:i:s', strtotime('+30 days')),
            'sessions_used_this_period' => 2,
            'auto_renew' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $cmId = (int) $db->insert_id();

        // Check get_for_customer remaining credits
        $customerMemberships = self::ci()->customer_memberships_model->get_for_customer(self::$customerId);
        $foundCm = null;
        foreach ($customerMemberships as $cm) {
            if ((int) $cm['id'] === $cmId) {
                $foundCm = $cm;
                break;
            }
        }
        $this->assertNotNull($foundCm);
        $this->assertEquals(0, $foundCm['remaining_credits']);

        // 3. Create appointment & adisyon
        $db->insert('appointments', [
            'start_datetime' => date('Y-m-d H:i:s', strtotime('+5 days')),
            'end_datetime' => date('Y-m-d H:i:s', strtotime('+5 days +1 hour')),
            'is_unavailability' => 0,
            'deposit_amount' => 0.00,
            'deposit_status' => 'none',
            'id_users_customer' => self::$customerId,
            'id_users_provider' => self::$providerId,
            'id_services' => 1,
        ]);
        $appointmentId = (int) $db->insert_id();

        $adisyon = self::ci()->adisyons_model->get_or_create_for_appointment($appointmentId);
        $adisyonId = (int) $adisyon['id'];

        // 4. Attempt to consume session directly -> must throw RuntimeException
        $exceptionThrown = false;
        try {
            self::ci()->customer_memberships_model->consume_session($cmId, $appointmentId);
        } catch (\Throwable $e) {
            $exceptionThrown = true;
            $this->assertStringContainsString('tükenmiştir', $e->getMessage());
        }
        $this->assertTrue($exceptionThrown, 'consume_session must prevent overdraft when sessions_used >= sessions_per_period');

        // Verify sessions_used_this_period was NOT incremented beyond 2
        $cmAfter = $db->get_where('customer_memberships', ['id' => $cmId])->row_array();
        $this->assertEquals(2, (int) $cmAfter['sessions_used_this_period']);

        // 5. Attempt payment via record_payment with membership -> must fail and rollback
        $paymentFailed = false;
        try {
            self::ci()->adisyons_model->record_payment($adisyonId, [
                'amount' => 150.00,
                'payment_method' => 'membership',
                'id_customer_memberships' => $cmId,
                'notes' => 'Overdraft attempt payment',
            ]);
        } catch (\Throwable $e) {
            $paymentFailed = true;
        }
        $this->assertTrue($paymentFailed, 'record_payment must fail when membership is exhausted');

        // Adisyon must remain unpaid
        $updatedAdisyon = self::ci()->adisyons_model->find($adisyonId);
        $this->assertEquals(0.00, (float) $updatedAdisyon['paid_amount']);

        // Cleanup
        $db->where('id_customer_memberships', $cmId)->delete('customer_membership_sessions');
        $db->where('id_adisyons', $adisyonId)->delete('adisyon_payments');
        $db->where('id_adisyons', $adisyonId)->delete('adisyon_items');
        $db->where('id', $adisyonId)->delete('adisyons');
        $db->where('id', $appointmentId)->delete('appointments');
        $db->where('id', $cmId)->delete('customer_memberships');
        $db->where('id', $planId)->delete('membership_plans');
    }
}
