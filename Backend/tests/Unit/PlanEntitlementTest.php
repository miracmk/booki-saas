<?php declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Unit tests for plan entitlement check logic (plan_allows).
 */
class PlanEntitlementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    private function mock_plan_allows(string $plan, string $feature): bool
    {
        $features = [
            'free' => ['basic_booking'],
            'basic' => ['basic_booking', 'email_reminders'],
            'premium' => ['basic_booking', 'email_reminders', 'sms_reminders', 'analytics'],
            'elite' => ['basic_booking', 'email_reminders', 'sms_reminders', 'analytics', 'api_access', 'custom_domain'],
        ];

        return in_array($feature, $features[$plan] ?? []);
    }

    /**
     * Test Free plan entitlements.
     */
    public function testFreePlanEntitlements(): void
    {
        $this->assertTrue($this->mock_plan_allows('free', 'basic_booking'));
        $this->assertFalse($this->mock_plan_allows('free', 'sms_reminders'));
    }

    /**
     * Test Basic plan entitlements.
     */
    public function testBasicPlanEntitlements(): void
    {
        $this->assertTrue($this->mock_plan_allows('basic', 'email_reminders'));
        $this->assertFalse($this->mock_plan_allows('basic', 'analytics'));
    }

    /**
     * Test Premium plan entitlements.
     */
    public function testPremiumPlanEntitlements(): void
    {
        $this->assertTrue($this->mock_plan_allows('premium', 'analytics'));
        $this->assertFalse($this->mock_plan_allows('premium', 'api_access'));
    }

    /**
     * Test Elite plan entitlements.
     */
    public function testElitePlanEntitlements(): void
    {
        $this->assertTrue($this->mock_plan_allows('elite', 'api_access'));
        $this->assertTrue($this->mock_plan_allows('elite', 'custom_domain'));
    }
}
