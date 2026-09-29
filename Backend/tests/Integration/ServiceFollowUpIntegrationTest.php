<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * BooKi Integration Test - Service-Level Follow-Up Engine.
 *
 * Validates:
 * 1. Service-level follow-up field defaults per sector
 * 2. Follow-up rule filtering by service category
 * 3. Priority-based opt-out bypass (critical vs optional)
 * 4. Service-level delay and message overrides
 * 5. Backward compatibility for legacy services without follow-up fields
 *
 * @package Tests\Integration
 */
class ServiceFollowUpIntegrationTest extends App_TestCase
{
    /**
     * @test
     * Verify that the Follow_up_engine correctly filters rules
     * based on service-level follow_up_category configuration.
     */
    public function testServiceLevelRuleFiltering(): void
    {
        $this->load->library('follow_up_engine');

        // Use reflection to access private filter method
        $engine = $this->follow_up_engine;
        $method = new ReflectionMethod(Follow_up_engine::class, 'filter_rules_by_service_config');
        $method->setAccessible(true);

        // Mock rules
        $rules = [
            ['rule_type' => 'reaction_check', 'id' => 'r1'],
            ['rule_type' => 'aftercare', 'id' => 'r2'],
            ['rule_type' => 'retention_rebook', 'id' => 'r3'],
            ['rule_type' => 'review_request', 'id' => 'r4'],
            ['rule_type' => 'asset_delivery', 'id' => 'r5'],
            ['rule_type' => 'diet_form', 'id' => 'r6'],
        ];

        // Case 1: Medical reaction category should match reaction_check + bonus marketing
        $filtered = $method->invoke($engine, $rules, true, 'medical_reaction', 'critical');
        $types = array_column($filtered, 'rule_type');
        $this->assertContains('reaction_check', $types, 'Medical reaction should include reaction_check');
        $this->assertContains('retention_rebook', $types, 'Should include retention_rebook as bonus');
        $this->assertContains('review_request', $types, 'Should include review_request as bonus');
        $this->assertNotContains('asset_delivery', $types, 'Should NOT include asset_delivery for medical_reaction');

        // Case 2: Aftercare safety should match aftercare + reaction_check
        $filtered = $method->invoke($engine, $rules, true, 'aftercare_safety', 'standard');
        $types = array_column($filtered, 'rule_type');
        $this->assertContains('aftercare', $types, 'Aftercare safety should include aftercare');
        $this->assertContains('reaction_check', $types, 'Aftercare safety should include reaction_check');

        // Case 3: Optional marketing service should only get marketing/NPS rules
        $filtered = $method->invoke($engine, $rules, false, 'retention_marketing', 'optional');
        $types = array_column($filtered, 'rule_type');
        $this->assertContains('retention_rebook', $types, 'Optional should include retention_rebook');
        $this->assertNotContains('reaction_check', $types, 'Optional should NOT include reaction_check');
        $this->assertNotContains('aftercare', $types, 'Optional should NOT include aftercare');

        // Case 4: Asset delivery category
        $filtered = $method->invoke($engine, $rules, true, 'asset_delivery', 'standard');
        $types = array_column($filtered, 'rule_type');
        $this->assertContains('asset_delivery', $types, 'Asset delivery should match');
        $this->assertContains('retention_rebook', $types, 'Should include retention_rebook as bonus');

        // Case 5: Medical protocol (psychology/dietitian)
        $filtered = $method->invoke($engine, $rules, true, 'medical_protocol', 'standard');
        $types = array_column($filtered, 'rule_type');
        $this->assertContains('diet_form', $types, 'Medical protocol should include diet_form');
        $this->assertContains('reaction_check', $types, 'Medical protocol should include reaction_check');
    }

    /**
     * @test
     * Verify backward compatibility: services without follow-up fields
     * should use all sector rules (legacy behavior).
     */
    public function testLegacyServiceBackwardCompatibility(): void
    {
        $this->load->library('follow_up_engine');

        $method = new ReflectionMethod(Follow_up_engine::class, 'filter_rules_by_service_config');
        $method->setAccessible(true);

        $rules = [
            ['rule_type' => 'reaction_check', 'id' => 'r1'],
            ['rule_type' => 'aftercare', 'id' => 'r2'],
            ['rule_type' => 'retention_rebook', 'id' => 'r3'],
        ];

        // Legacy service: follow_up_required=false, category=null
        $filtered = $method->invoke($this->follow_up_engine, $rules, false, null, 'optional');

        $this->assertCount(3, $filtered, 'Legacy services should receive ALL sector rules for backward compatibility');
    }

    /**
     * @test
     * Verify that critical priority rules bypass customer opt-out.
     */
    public function testCriticalPriorityBypassesOptOut(): void
    {
        $this->load->library('follow_up_engine');

        $method = new ReflectionMethod(Follow_up_engine::class, 'resolve_effective_priority');
        $method->setAccessible(true);

        // reaction_check is always critical
        $result = $method->invoke($this->follow_up_engine, ['rule_type' => 'reaction_check'], 'optional');
        $this->assertEquals('critical', $result, 'Reaction check rules must always be critical');

        // Service-level critical priority elevates other rules
        $result = $method->invoke($this->follow_up_engine, ['rule_type' => 'aftercare'], 'critical');
        $this->assertEquals('critical', $result, 'Critical service priority should elevate aftercare');

        // Standard priority for non-critical rules
        $result = $method->invoke($this->follow_up_engine, ['rule_type' => 'retention_rebook'], 'standard');
        $this->assertEquals('standard', $result, 'Standard priority for retention_rebook');

        // Optional priority
        $result = $method->invoke($this->follow_up_engine, ['rule_type' => 'review_request'], 'optional');
        $this->assertEquals('optional', $result, 'Optional priority for review_request');
    }

    /**
     * @test
     * Verify interval parsing for common delay formats.
     */
    public function testIntervalParsing(): void
    {
        $this->load->library('follow_up_engine');

        $this->assertEquals(0, $this->follow_up_engine->parse_interval_seconds('0 minutes'));
        $this->assertEquals(900, $this->follow_up_engine->parse_interval_seconds('15 minutes'));
        $this->assertEquals(86400, $this->follow_up_engine->parse_interval_seconds('24 hours'));
        $this->assertEquals(172800, $this->follow_up_engine->parse_interval_seconds('48 hours'));
        $this->assertEquals(604800, $this->follow_up_engine->parse_interval_seconds('7 days'));
        $this->assertEquals(15552000, $this->follow_up_engine->parse_interval_seconds('180 days'));
    }

    /**
     * @test
     * Verify quiet hours enforcement.
     */
    public function testQuietHoursEnforcement(): void
    {
        $this->load->library('follow_up_engine');
        $tz = 'Europe/Istanbul';

        // 22:30 should be postponed to next day 09:30
        $late_night = new DateTime('2026-10-01 22:30:00', new DateTimeZone($tz));
        $result = $this->follow_up_engine->apply_quiet_hours($late_night, $tz);
        $this->assertEquals('09', $result->format('H'), 'Late night should be postponed to 09:xx');
        $this->assertEquals('30', $result->format('i'), 'Should be postponed to xx:30');
        $this->assertEquals('02', $result->format('d'), 'Should be next day');

        // 03:00 should be postponed to same day 09:30
        $early_morning = new DateTime('2026-10-01 03:00:00', new DateTimeZone($tz));
        $result = $this->follow_up_engine->apply_quiet_hours($early_morning, $tz);
        $this->assertEquals('09', $result->format('H'));
        $this->assertEquals('30', $result->format('i'));
        $this->assertEquals('01', $result->format('d'), 'Should be same day');

        // 14:00 should NOT be modified
        $afternoon = new DateTime('2026-10-01 14:00:00', new DateTimeZone($tz));
        $result = $this->follow_up_engine->apply_quiet_hours($afternoon, $tz);
        $this->assertEquals('14', $result->format('H'), 'Afternoon should not be modified');
    }

    /**
     * @test
     * Verify the industry family resolution map covers all blueprints.
     */
    public function testIndustryFamilyResolutionCoverage(): void
    {
        $this->load->library('follow_up_engine');

        $expected = [
            'dentist' => 'health_clinical',
            'doctor_clinic' => 'health_clinical',
            'psychology_dietitian_clinic' => 'health_clinical',
            'beauty_salon' => 'beauty_wellness',
            'nail_studio' => 'beauty_wellness',
            'massage_spa' => 'beauty_wellness',
            'barber' => 'beauty_wellness',
            'auto_service_detailing' => 'automotive',
            'car_wash' => 'automotive',
            'restaurant' => 'hospitality_food',
            'hotel' => 'hospitality_food',
            'pt_training' => 'sports_fitness',
            'pilates_studio' => 'sports_fitness',
            'sports_court' => 'sports_fitness',
            'gym' => 'sports_fitness',
            'law_firm' => 'professional',
            'consulting_agency' => 'professional',
        ];

        foreach ($expected as $blueprint => $family) {
            $result = $this->follow_up_engine->resolve_industry_family($blueprint);
            $this->assertEquals($family, $result, "Blueprint '{$blueprint}' should map to family '{$family}'");
        }
    }

    /**
     * @test
     * Verify category-to-rule-type mapping completeness.
     */
    public function testFollowUpCategoryCoverage(): void
    {
        $expected_categories = [
            'medical_reaction',
            'medical_protocol',
            'aftercare_safety',
            'asset_delivery',
            'compliance_check',
            'veterinary_postop',
            'retention_marketing',
            'review_nps',
        ];

        $this->load->library('follow_up_engine');

        $method = new ReflectionMethod(Follow_up_engine::class, 'filter_rules_by_service_config');
        $method->setAccessible(true);

        $rules = [
            ['rule_type' => 'reaction_check', 'id' => 'r1'],
            ['rule_type' => 'aftercare', 'id' => 'r2'],
            ['rule_type' => 'retention_rebook', 'id' => 'r3'],
            ['rule_type' => 'review_request', 'id' => 'r4'],
            ['rule_type' => 'asset_delivery', 'id' => 'r5'],
            ['rule_type' => 'diet_form', 'id' => 'r6'],
            ['rule_type' => 'routine_check', 'id' => 'r7'],
        ];

        foreach ($expected_categories as $category) {
            $filtered = $method->invoke($this->follow_up_engine, $rules, true, $category, 'standard');
            $this->assertNotEmpty($filtered, "Category '{$category}' should match at least one rule type");
        }
    }
}
