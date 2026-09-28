<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Ai_hybrid_router and Ai_llm_gateway integration.
 */
class AiHybridModelRouterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../application/libraries/Ai_hybrid_router.php';
    }

    public function testModelCatalogContainsEqualFirstClassProviders(): void
    {
        $router = new \Ai_hybrid_router();
        $catalog = $router->get_model_catalog();

        $this->assertNotEmpty($catalog);

        // Verify Google, OpenAI, Anthropic are all first-class models
        $this->assertArrayHasKey('gemini-3.8-flash', $catalog);
        $this->assertArrayHasKey('gpt-4o-mini', $catalog);
        $this->assertArrayHasKey('claude-3-5-haiku-20241022', $catalog);

        // Verify all 3 top providers support tools and structured output
        $google = $catalog['gemini-3.8-flash'];
        $openai = $catalog['gpt-4o-mini'];
        $anthropic = $catalog['claude-3-5-haiku-20241022'];

        $this->assertEquals('google', $google['provider']);
        $this->assertTrue($google['tool_support']);
        $this->assertTrue($google['structured_output']);

        $this->assertEquals('openai', $openai['provider']);
        $this->assertTrue($openai['tool_support']);
        $this->assertTrue($openai['structured_output']);

        $this->assertEquals('anthropic', $anthropic['provider']);
        $this->assertTrue($anthropic['tool_support']);
        $this->assertTrue($anthropic['structured_output']);

        // Verify capability benchmarks are balanced and high
        $this->assertGreaterThanOrEqual(90, $google['capability']);
        $this->assertGreaterThanOrEqual(90, $openai['capability']);
        $this->assertGreaterThanOrEqual(90, $anthropic['capability']);
    }

    public function testDynamicModelScoringAndRankingAcrossProviders(): void
    {
        $router = new \Ai_hybrid_router();

        $context = [
            'task_type' => 'appointment_booking',
            'tools' => [
                [
                    'type' => 'function',
                    'function' => [
                        'name' => 'list_appointments',
                        'parameters' => ['type' => 'object', 'properties' => []],
                    ],
                ],
            ],
            'structured_output' => false,
            'estimated_tokens' => 400,
            'available_providers' => ['google', 'openai', 'anthropic'],
        ];

        $ranked = $router->rank_models($context);

        $this->assertNotEmpty($ranked);
        $top = $ranked[0];

        $this->assertArrayHasKey('provider', $top);
        $this->assertArrayHasKey('model', $top);
        $this->assertArrayHasKey('total_score', $top);
        $this->assertArrayHasKey('breakdown', $top);

        // Verify all 12 criteria are in breakdown
        $breakdown = $top['breakdown'];
        $expectedCriteria = [
            'task_type_fit',
            'model_capability',
            'tool_support',
            'structured_output',
            'context_fit',
            'provider_health',
            'current_latency',
            'error_rate',
            'remaining_free_quota',
            'paid_api_cost',
            'tenant_ai_policy',
            'historical_success_rate',
        ];

        foreach ($expectedCriteria as $crit) {
            $this->assertArrayHasKey($crit, $breakdown, "Missing criterion: {$crit}");
        }
    }

    public function testDynamicFailoverSelectsNextBestModelWithoutHardcodedChain(): void
    {
        $router = new \Ai_hybrid_router();

        $context = [
            'task_type' => 'appointment_booking',
            'tools' => [['type' => 'function', 'function' => ['name' => 'book']]],
            'available_providers' => ['google', 'openai', 'anthropic'],
        ];

        // 1. Initial selection
        $firstChoice = $router->select_best_model($context);
        $this->assertNotNull($firstChoice);

        $firstModel = $firstChoice['model'];
        $firstProvider = $firstChoice['provider'];

        // 2. Simulate failure of first choice
        $router->record_failure($firstProvider, $firstModel, '503 Service Unavailable', 503);

        // 3. Recalculate eligible models excluding the failed model
        $secondChoice = $router->select_best_model($context, [$firstModel]);
        $this->assertNotNull($secondChoice);

        // Verify next best model is different from the failed one
        $this->assertNotEquals($firstModel, $secondChoice['model']);
        $this->assertGreaterThan(0, $secondChoice['total_score']);
    }

    public function testCircuitBreakerTripsAfterThresholdFailures(): void
    {
        $router = new \Ai_hybrid_router();
        $provider = 'openai';
        $model = 'gpt-4o';

        // Record 3 failures
        $router->record_failure($provider, $model, 'Timeout', 504);
        $router->record_failure($provider, $model, 'Timeout', 504);
        $router->record_failure($provider, $model, 'Timeout', 504);

        $health = $router->get_model_health($provider, $model);

        $this->assertEquals('cooling_down', $health['state']);
        $this->assertGreaterThan(time(), $health['cooldown_until']);

        // Cooling down model must not be selected
        $context = [
            'task_type' => 'complex_reasoning',
            'available_providers' => ['openai', 'anthropic'],
        ];
        $ranked = $router->rank_models($context);
        $modelsRanked = array_column($ranked, 'model');

        $this->assertNotContains($model, $modelsRanked, 'Cooling down model must be excluded from eligible ranking');

        // Record success resets health
        $router->record_success($provider, $model, 320.0);
        $healthAfter = $router->get_model_health($provider, $model);
        $this->assertEquals('healthy', $healthAfter['state']);
        $this->assertEquals(0, $healthAfter['consecutive_failures']);
    }

    public function testRetryabilityClassification(): void
    {
        $router = new \Ai_hybrid_router();

        // Retryable
        $this->assertTrue($router->is_retryable_error(429, 'Rate limit exceeded'));
        $this->assertTrue($router->is_retryable_error(500, 'Internal Server Error'));
        $this->assertTrue($router->is_retryable_error(502, 'Bad Gateway'));
        $this->assertTrue($router->is_retryable_error(503, 'Service Unavailable'));
        $this->assertTrue($router->is_retryable_error(504, 'Gateway Timeout'));
        $this->assertTrue($router->is_retryable_error(0, 'cURL error 28: Operation timed out'));

        // Non-retryable
        $this->assertFalse($router->is_retryable_error(400, 'Bad Request: invalid prompt schema'));
    }

    public function testToolIdempotencyPreventsDuplicateExecution(): void
    {
        $router = new \Ai_hybrid_router();
        $tool = 'create_appointment';
        $args = ['service_id' => 5, 'date' => '2026-09-29'];

        $this->assertFalse($router->is_tool_executed($tool, $args));

        $router->mark_tool_executed($tool, $args);

        $this->assertTrue($router->is_tool_executed($tool, $args));

        // Different args are not marked
        $differentArgs = ['service_id' => 5, 'date' => '2026-09-30'];
        $this->assertFalse($router->is_tool_executed($tool, $differentArgs));
    }

    public function testFreeQuotaPreservationForExistingFreeSetup(): void
    {
        $router = new \Ai_hybrid_router();
        $catalog = $router->get_model_catalog();

        $gemini = $catalog['gemini-3.8-flash'];
        $this->assertTrue($gemini['has_free_tier']);

        $remaining = $router->get_remaining_free_quota('google', 'gemini-3.8-flash', $gemini);
        $this->assertGreaterThan(0, $remaining);

        // Context with free quota enabled should score high on criterion 9
        $context = [
            'task_type' => 'chat',
            'available_providers' => ['google', 'openai', 'anthropic'],
        ];

        $ranked = $router->rank_models($context);
        $googleRanked = null;
        foreach ($ranked as $r) {
            if ($r['model'] === 'gemini-3.8-flash') {
                $googleRanked = $r;
                break;
            }
        }

        $this->assertNotNull($googleRanked);
        $this->assertEquals(25, $googleRanked['breakdown']['remaining_free_quota']);
    }
}
