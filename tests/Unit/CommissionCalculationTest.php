<?php declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Unit tests for commission calculation math.
 */
class CommissionCalculationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test percentage based commission calculation.
     */
    public function testPercentageCommission(): void
    {
        $service_price = 1000.0;
        $commission_rate = 30.0; // 30%

        $payout = $service_price * ($commission_rate / 100);
        $this->assertEquals(300.0, $payout);
    }

    /**
     * Test fixed amount commission calculation.
     */
    public function testFixedCommission(): void
    {
        $service_price = 1000.0;
        $fixed_commission = 200.0;

        $payout = $fixed_commission;
        $this->assertEquals(200.0, $payout);
    }

    /**
     * Test hourly commission calculation.
     */
    public function testHourlyCommission(): void
    {
        $duration_minutes = 90; // 1.5 hours
        $hourly_rate = 100.0;

        $payout = ($duration_minutes / 60) * $hourly_rate;
        $this->assertEquals(150.0, $payout);
    }

    /**
     * Test net calculations for the provider.
     */
    public function testNetPayoutMath(): void
    {
        $service_price = 1500.0;
        $commission_rate = 40.0; 
        $deductions = 100.0; // e.g. materials

        $payout = ($service_price * ($commission_rate / 100)) - $deductions;
        $this->assertEquals(500.0, $payout);
    }
}
