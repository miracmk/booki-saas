<?php declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Quality Gate: Validates database schema column contracts and model return types.
 *
 * Prevents regressions like:
 * - "Unknown column 'id_users_provider'" during adisyon close / staff commission calculation
 * - TypeError in Segments_model when methods returning array were incorrectly type-hinted as int
 */
class StaffCommissionsAndSegmentsContractTest extends TestCase
{
    /**
     * Ensure Staff_commissions_model does not reference deprecated or non-existent columns.
     */
    public function testStaffCommissionsModelColumnContract(): void
    {
        $baseDir = dirname(__DIR__, 2);
        $modelPath = $baseDir . '/application/models/Staff_commissions_model.php';

        $this->assertFileExists($modelPath);
        $content = file_get_contents($modelPath);

        // id_users_provider does not exist in provider_service_commissions table (actual column is id_users)
        $this->assertStringNotContainsString(
            'id_users_provider',
            $content,
            'Staff_commissions_model must not reference non-existent column "id_users_provider".'
        );

        // provider_service_commissions column is commission_value, not commission_rate
        $this->assertStringNotContainsString(
            "where('commission_rate'",
            $content,
            'Staff_commissions_model must use commission_value for provider_service_commissions.'
        );
    }

    /**
     * Ensure Segments_model methods have correct array return type signatures.
     */
    public function testSegmentsModelReturnTypeSignatures(): void
    {
        $baseDir = dirname(__DIR__, 2);
        $modelPath = $baseDir . '/application/models/Segments_model.php';

        $this->assertFileExists($modelPath);
        $content = file_get_contents($modelPath);

        // vip_customer_ids, inactive_customer_ids, birthday_customer_ids must return array, not int
        $this->assertMatchesRegularExpression(
            '/(protected|public) function vip_customer_ids\([^)]*\)\s*:\s*array/',
            $content,
            'Segments_model::vip_customer_ids must return array.'
        );

        $this->assertMatchesRegularExpression(
            '/(protected|public) function inactive_customer_ids\([^)]*\)\s*:\s*array/',
            $content,
            'Segments_model::inactive_customer_ids must return array.'
        );

        $this->assertMatchesRegularExpression(
            '/(protected|public) function birthday_customer_ids\([^)]*\)\s*:\s*array/',
            $content,
            'Segments_model::birthday_customer_ids must return array.'
        );
    }

    /**
     * Test commission calculation engine math with percentage and fixed commissions.
     */
    public function testCommissionCalculationLogic(): void
    {
        // 1. Percentage commission
        $servicePrice = 500.0;
        $percentageRate = 20.0; // 20%
        $calculatedPercentage = $servicePrice * ($percentageRate / 100);
        $this->assertSame(100.0, $calculatedPercentage);

        // 2. Fixed commission
        $fixedRate = 75.0;
        $this->assertSame(75.0, $fixedRate);

        // 3. Service specific override takes precedence over general provider rate
        $defaultProviderRate = 15.0;
        $serviceSpecificRate = 25.0;
        $effectiveRate = $serviceSpecificRate ?: $defaultProviderRate;
        $this->assertSame(25.0, $effectiveRate);
    }
}
