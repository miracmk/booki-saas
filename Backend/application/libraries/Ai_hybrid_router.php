<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Dynamic Hybrid Model Router for Multi-Provider AI (Google, OpenAI, Anthropic, Groq, OpenRouter).
 *
 * Core Tenet:
 * - THERE IS NO PRIMARY PROVIDER.
 * - OpenAI, Anthropic, and Google are EQUAL, first-class AI providers.
 * - No static fallback chain (no OpenAI -> Anthropic -> Google, no OpenAI primary, etc.).
 * - Dynamically evaluates and ranks eligible models per request using 12 holistic criteria:
 *   1. Task type (appointment_booking, tool_execution, chat, fast_response, complex_reasoning, summary, structured_output)
 *   2. Model capability benchmark
 *   3. Tool / function calling support
 *   4. Structured output support
 *   5. Context requirements (token limits vs prompt size)
 *   6. Current provider health & circuit breaker
 *   7. Current latency (EMA)
 *   8. Error rate
 *   9. Remaining free quota (preserves existing free tier setup)
 *   10. Current paid API cost
 *   11. Tenant AI policy & preferences
 *   12. Model-specific historical success rate
 *
 * Dynamic Failover:
 * - If top model fails with a retryable error, mark failure, recalculate eligible models,
 *   exclude temporarily unhealthy models/providers, and dynamically select the next best model.
 * - Preserve conversation context and prevent duplicate tool executions.
 *
 * @package Libraries
 */
class Ai_hybrid_router
{
    private const CIRCUIT_BREAKER_THRESHOLD = 3; // consecutive failures before cooldown
    private const CIRCUIT_BREAKER_COOLDOWN = 60; // seconds

    protected ?CI_Controller $CI = null;
    protected array $metrics = [];
    protected array $executed_tool_keys = [];

    public function __construct()
    {
        if (function_exists('get_instance')) {
            $this->CI = &get_instance();
        }
        $this->load_metrics();
    }

    protected function get_metrics_cache_path(): string
    {
        if (defined('APPPATH')) {
            return APPPATH . '../storage/cache/ai_router_metrics.json';
        }
        return dirname(__DIR__, 2) . '/storage/cache/ai_router_metrics.json';
    }

    /**
     * Complete Model Catalog across all equal providers.
     */
    public function get_model_catalog(): array
    {
        return [
            // --- GOOGLE (First-Class Provider) ---
            'gemini-3.8-flash' => [
                'provider' => 'google',
                'model' => 'gemini-3.8-flash',
                'display_name' => 'Google Gemini 3.8 Flash',
                'capability' => 92,
                'reasoning' => 90,
                'tool_support' => true,
                'structured_output' => true,
                'context_window' => 1048576,
                'default_latency_ms' => 380,
                'cost_input_1k' => 0.00010,
                'cost_output_1k' => 0.00040,
                'has_free_tier' => true,
                'free_rpd_limit' => 1500,
                'task_fit' => [
                    'appointment_booking' => 94,
                    'tool_execution' => 93,
                    'chat' => 95,
                    'fast_response' => 96,
                    'complex_reasoning' => 90,
                    'summary' => 95,
                    'structured_output' => 93,
                ],
            ],
            'gemini-2.5-flash' => [
                'provider' => 'google',
                'model' => 'gemini-2.5-flash',
                'display_name' => 'Google Gemini 2.5 Flash',
                'capability' => 88,
                'reasoning' => 86,
                'tool_support' => true,
                'structured_output' => true,
                'context_window' => 1048576,
                'default_latency_ms' => 420,
                'cost_input_1k' => 0.000075,
                'cost_output_1k' => 0.00030,
                'has_free_tier' => true,
                'free_rpd_limit' => 1500,
                'task_fit' => [
                    'appointment_booking' => 90,
                    'tool_execution' => 89,
                    'chat' => 92,
                    'fast_response' => 93,
                    'complex_reasoning' => 85,
                    'summary' => 92,
                    'structured_output' => 89,
                ],
            ],
            'gemini-1.5-pro' => [
                'provider' => 'google',
                'model' => 'gemini-1.5-pro',
                'display_name' => 'Google Gemini 1.5 Pro',
                'capability' => 95,
                'reasoning' => 96,
                'tool_support' => true,
                'structured_output' => true,
                'context_window' => 2097152,
                'default_latency_ms' => 850,
                'cost_input_1k' => 0.00125,
                'cost_output_1k' => 0.0050,
                'has_free_tier' => true,
                'free_rpd_limit' => 50,
                'task_fit' => [
                    'appointment_booking' => 95,
                    'tool_execution' => 95,
                    'chat' => 90,
                    'fast_response' => 75,
                    'complex_reasoning' => 97,
                    'summary' => 98,
                    'structured_output' => 96,
                ],
            ],

            // --- OPENAI (First-Class Provider) ---
            'gpt-4o-mini' => [
                'provider' => 'openai',
                'model' => 'gpt-4o-mini',
                'display_name' => 'OpenAI GPT-4o Mini',
                'capability' => 90,
                'reasoning' => 89,
                'tool_support' => true,
                'structured_output' => true,
                'context_window' => 128000,
                'default_latency_ms' => 450,
                'cost_input_1k' => 0.00015,
                'cost_output_1k' => 0.00060,
                'has_free_tier' => false,
                'free_rpd_limit' => 0,
                'task_fit' => [
                    'appointment_booking' => 93,
                    'tool_execution' => 94,
                    'chat' => 94,
                    'fast_response' => 92,
                    'complex_reasoning' => 89,
                    'summary' => 90,
                    'structured_output' => 95,
                ],
            ],
            'gpt-4o' => [
                'provider' => 'openai',
                'model' => 'gpt-4o',
                'display_name' => 'OpenAI GPT-4o',
                'capability' => 96,
                'reasoning' => 96,
                'tool_support' => true,
                'structured_output' => true,
                'context_window' => 128000,
                'default_latency_ms' => 700,
                'cost_input_1k' => 0.0025,
                'cost_output_1k' => 0.0100,
                'has_free_tier' => false,
                'free_rpd_limit' => 0,
                'task_fit' => [
                    'appointment_booking' => 96,
                    'tool_execution' => 97,
                    'chat' => 92,
                    'fast_response' => 80,
                    'complex_reasoning' => 97,
                    'summary' => 94,
                    'structured_output' => 98,
                ],
            ],

            // --- ANTHROPIC (First-Class Provider) ---
            'claude-3-5-haiku-20241022' => [
                'provider' => 'anthropic',
                'model' => 'claude-3-5-haiku-20241022',
                'display_name' => 'Anthropic Claude 3.5 Haiku',
                'capability' => 91,
                'reasoning' => 90,
                'tool_support' => true,
                'structured_output' => true,
                'context_window' => 200000,
                'default_latency_ms' => 390,
                'cost_input_1k' => 0.0008,
                'cost_output_1k' => 0.0040,
                'has_free_tier' => false,
                'free_rpd_limit' => 0,
                'task_fit' => [
                    'appointment_booking' => 94,
                    'tool_execution' => 94,
                    'chat' => 95,
                    'fast_response' => 95,
                    'complex_reasoning' => 90,
                    'summary' => 93,
                    'structured_output' => 94,
                ],
            ],
            'claude-3-5-sonnet-20241022' => [
                'provider' => 'anthropic',
                'model' => 'claude-3-5-sonnet-20241022',
                'display_name' => 'Anthropic Claude 3.5 Sonnet',
                'capability' => 98,
                'reasoning' => 98,
                'tool_support' => true,
                'structured_output' => true,
                'context_window' => 200000,
                'default_latency_ms' => 780,
                'cost_input_1k' => 0.0030,
                'cost_output_1k' => 0.0150,
                'has_free_tier' => false,
                'free_rpd_limit' => 0,
                'task_fit' => [
                    'appointment_booking' => 98,
                    'tool_execution' => 98,
                    'chat' => 93,
                    'fast_response' => 82,
                    'complex_reasoning' => 99,
                    'summary' => 96,
                    'structured_output' => 98,
                ],
            ],

            // --- GROQ (Free Tier & Speed Inference) ---
            'llama-3.3-70b-versatile' => [
                'provider' => 'groq',
                'model' => 'llama-3.3-70b-versatile',
                'display_name' => 'Groq Llama 3.3 70B',
                'capability' => 86,
                'reasoning' => 85,
                'tool_support' => true,
                'structured_output' => true,
                'context_window' => 128000,
                'default_latency_ms' => 220,
                'cost_input_1k' => 0.00059,
                'cost_output_1k' => 0.00079,
                'has_free_tier' => true,
                'free_rpd_limit' => 1000,
                'task_fit' => [
                    'appointment_booking' => 88,
                    'tool_execution' => 88,
                    'chat' => 94,
                    'fast_response' => 98,
                    'complex_reasoning' => 84,
                    'summary' => 88,
                    'structured_output' => 88,
                ],
            ],

            // --- OPENROUTER (Multi-Model & Free Routing) ---
            'qwen/qwen-2.5-72b-instruct' => [
                'provider' => 'openrouter',
                'model' => 'qwen/qwen-2.5-72b-instruct',
                'display_name' => 'OpenRouter Qwen 2.5 72B',
                'capability' => 85,
                'reasoning' => 85,
                'tool_support' => true,
                'structured_output' => true,
                'context_window' => 65536,
                'default_latency_ms' => 550,
                'cost_input_1k' => 0.0004,
                'cost_output_1k' => 0.0004,
                'has_free_tier' => true,
                'free_rpd_limit' => 500,
                'task_fit' => [
                    'appointment_booking' => 86,
                    'tool_execution' => 86,
                    'chat' => 90,
                    'fast_response' => 88,
                    'complex_reasoning' => 85,
                    'summary' => 87,
                    'structured_output' => 86,
                ],
            ],
        ];
    }

    /**
     * Evaluate and rank all eligible models using the 12 holistic criteria.
     *
     * @param array $context Request context:
     *   - 'task_type': string
     *   - 'tools': array|null
     *   - 'structured_output': bool
     *   - 'estimated_tokens': int
     *   - 'available_providers': array (providers with valid keys)
     *   - 'tenant_policy': array|null
     *   - 'preferred_provider': string|null
     * @param array $exclude_models Models to exclude (e.g. failed in current turn)
     * @param array $exclude_providers Providers to exclude (e.g. auth failed)
     *
     * @return array Ranked list of models with detailed score breakdown.
     */
    public function rank_models(array $context, array $exclude_models = [], array $exclude_providers = []): array
    {
        $exclude_models = !empty($exclude_models) ? $exclude_models : ($context['exclude_models'] ?? []);
        $exclude_providers = !empty($exclude_providers) ? $exclude_providers : ($context['exclude_providers'] ?? []);

        $catalog = $this->get_model_catalog();
        $available_providers = $context['available_providers'] ?? [];
        $task_type = $context['task_type'] ?? 'chat';
        $requires_tools = !empty($context['tools']);
        $requires_structured = !empty($context['structured_output']);
        $estimated_tokens = (int) ($context['estimated_tokens'] ?? 500);
        $tenant_policy = $context['tenant_policy'] ?? [];
        $preferred_provider = $context['preferred_provider'] ?? null;

        $ranked = [];

        foreach ($catalog as $key => $model) {
            $provider = $model['provider'];
            $model_name = $model['model'];

            // 1. Exclude if provider has no key or is excluded
            if (!in_array($provider, $available_providers, true) || in_array($provider, $exclude_providers, true)) {
                continue;
            }

            // 2. Exclude if specific model was excluded for this request
            if (in_array($model_name, $exclude_models, true) || in_array($key, $exclude_models, true) || in_array("{$provider}/{$model_name}", $exclude_models, true)) {
                continue;
            }

            // 3. Exclude if circuit breaker tripped
            $health = $this->get_model_health($provider, $model_name);
            if ($health['state'] === 'cooling_down' && time() < ($health['cooldown_until'] ?? 0)) {
                continue;
            }

            // 4. Criterion 3: Tool Support filter
            if ($requires_tools && empty($model['tool_support'])) {
                continue;
            }

            // 5. Criterion 5: Context window requirement
            if ($estimated_tokens > $model['context_window']) {
                continue;
            }

            // Calculate the 12 criteria scores
            $scores = $this->score_model($model, $context, $health);
            $total_score = array_sum($scores);

            $ranked[] = [
                'key' => $key,
                'provider' => $provider,
                'model' => $model_name,
                'display_name' => $model['display_name'],
                'total_score' => round($total_score, 2),
                'breakdown' => $scores,
                'health' => $health,
                'has_free_tier' => $model['has_free_tier'],
            ];
        }

        // Sort descending by total score
        usort($ranked, static fn ($a, $b) => $b['total_score'] <=> $a['total_score']);

        return $ranked;
    }

    /**
     * Dynamically select the single best available model.
     *
     * @param array $context
     * @param array $exclude_models
     * @param array $exclude_providers
     * @return array|null The selected model metadata or null if none available.
     */
    public function select_best_model(array $context, array $exclude_models = [], array $exclude_providers = []): ?array
    {
        $ranked = $this->rank_models($context, $exclude_models, $exclude_providers);
        return !empty($ranked) ? $ranked[0] : null;
    }

    /**
     * Compute the 12 Criteria score breakdown for a model.
     */
    protected function score_model(array $model, array $context, array $health): array
    {
        $task_type = $context['task_type'] ?? 'chat';
        $requires_tools = !empty($context['tools']);
        $requires_structured = !empty($context['structured_output']);
        $estimated_tokens = (int) ($context['estimated_tokens'] ?? 500);
        $tenant_policy = $context['tenant_policy'] ?? [];
        $preferred_provider = $context['preferred_provider'] ?? null;

        $scores = [];

        // 1. Task Type Fit (0 - 25 points)
        $fit = $model['task_fit'][$task_type] ?? $model['capability'];
        $scores['task_type_fit'] = ($fit / 100) * 25;

        // 2. Model Capability Benchmark (0 - 20 points)
        $scores['model_capability'] = ($model['capability'] / 100) * 20;

        // 3. Tool / Function Calling Support (0 - 15 points)
        if ($requires_tools) {
            $scores['tool_support'] = $model['tool_support'] ? 15 : 0;
        } else {
            $scores['tool_support'] = 10; // neutral bonus for versatility
        }

        // 4. Structured Output Support (0 - 10 points)
        if ($requires_structured) {
            $scores['structured_output'] = $model['structured_output'] ? 10 : 0;
        } else {
            $scores['structured_output'] = 5;
        }

        // 5. Context Requirements (0 - 10 points)
        $context_ratio = $estimated_tokens / max(1, $model['context_window']);
        if ($context_ratio < 0.1) {
            $scores['context_fit'] = 10; // ample headroom
        } elseif ($context_ratio < 0.5) {
            $scores['context_fit'] = 8;
        } else {
            $scores['context_fit'] = 4;
        }

        // 6. Current Provider Health (0 - 15 points)
        $scores['provider_health'] = match ($health['state']) {
            'healthy' => 15,
            'degraded' => 5,
            'cooling_down' => -20,
            default => 0,
        };

        // 7. Current Latency EMA (0 - 15 points)
        $latency = $health['latency_ema_ms'] ?? $model['default_latency_ms'];
        if ($latency < 350) {
            $scores['current_latency'] = 15;
        } elseif ($latency < 600) {
            $scores['current_latency'] = 12;
        } elseif ($latency < 1000) {
            $scores['current_latency'] = 8;
        } else {
            $scores['current_latency'] = 3;
        }

        // 8. Error Rate (0 - 15 points)
        $error_rate = $health['error_rate'] ?? 0.0;
        if ($error_rate <= 0.01) {
            $scores['error_rate'] = 15;
        } elseif ($error_rate <= 0.08) {
            $scores['error_rate'] = 10;
        } elseif ($error_rate <= 0.20) {
            $scores['error_rate'] = 4;
        } else {
            $scores['error_rate'] = -15;
        }

        // 9. Remaining Free Quota (0 - 25 points)
        // Crucial: Preserves existing free tier setup ("Mevcut ücretsiz yapı kurgusu devam edecek")
        $free_quota_remaining = $this->get_remaining_free_quota($model['provider'], $model['model'], $model);
        if ($model['has_free_tier'] && $free_quota_remaining > 0) {
            $scores['remaining_free_quota'] = 25;
        } else {
            $scores['remaining_free_quota'] = 0;
        }

        // 10. Current Paid API Cost (0 - 15 points)
        // For equal capability, cost efficiency provides a bonus
        $cost_total = ($model['cost_input_1k'] * 1.5) + ($model['cost_output_1k'] * 0.5);
        if ($cost_total <= 0.0003) {
            $scores['paid_api_cost'] = 15; // ultra-low or free
        } elseif ($cost_total <= 0.0015) {
            $scores['paid_api_cost'] = 11;
        } elseif ($cost_total <= 0.005) {
            $scores['paid_api_cost'] = 6;
        } else {
            $scores['paid_api_cost'] = 2; // high-tier cost (GPT-4o, Claude Sonnet)
        }

        // 11. Tenant AI Policy & Preference (0 - 20 points)
        $scores['tenant_ai_policy'] = 10; // neutral base
        if ($preferred_provider !== null && $preferred_provider !== 'auto') {
            if ($model['provider'] === $preferred_provider) {
                $scores['tenant_ai_policy'] += 20;
            } else {
                $scores['tenant_ai_policy'] -= 5;
            }
        }
        // Check allowed / disallowed in tenant policy
        if (!empty($tenant_policy['allowed_providers']) && is_array($tenant_policy['allowed_providers'])) {
            if (!in_array($model['provider'], $tenant_policy['allowed_providers'], true)) {
                $scores['tenant_ai_policy'] -= 50;
            }
        }

        // 12. Model-Specific Historical Success Rate (0 - 15 points)
        $success_rate = $health['success_rate'] ?? 1.0;
        if ($success_rate >= 0.98) {
            $scores['historical_success_rate'] = 15;
        } elseif ($success_rate >= 0.90) {
            $scores['historical_success_rate'] = 11;
        } elseif ($success_rate >= 0.80) {
            $scores['historical_success_rate'] = 5;
        } else {
            $scores['historical_success_rate'] = -10;
        }

        return $scores;
    }

    /**
     * Record model success and update metrics (EMA latency, count, quota).
     */
    public function record_success(string $provider, string $model, float $latency_ms, int $tokens_used = 0): void
    {
        $key = "{$provider}/{$model}";
        $today = date('Y-m-d');

        if (!isset($this->metrics['models'][$key])) {
            $this->metrics['models'][$key] = $this->create_default_model_metrics();
        }

        $m = &$this->metrics['models'][$key];
        $m['state'] = 'healthy';
        $m['consecutive_failures'] = 0;
        $m['successful_requests'] = ($m['successful_requests'] ?? 0) + 1;
        $m['total_requests'] = ($m['total_requests'] ?? 0) + 1;

        // Exponential Moving Average for latency: 70% previous, 30% new
        $prev_ema = (float) ($m['latency_ema_ms'] ?? $latency_ms);
        $m['latency_ema_ms'] = round(($prev_ema * 0.7) + ($latency_ms * 0.3), 1);

        // Daily quota tracking
        if (($m['last_used_date'] ?? '') !== $today) {
            $m['free_quota_used_today'] = 0;
            $m['last_used_date'] = $today;
        }
        $m['free_quota_used_today'] = ($m['free_quota_used_today'] ?? 0) + 1;

        // Provider health reset
        $this->metrics['providers'][$provider]['state'] = 'healthy';
        $this->metrics['providers'][$provider]['consecutive_failures'] = 0;

        $this->save_metrics();
    }

    /**
     * Record model failure and update circuit breaker state.
     */
    public function record_failure(string $provider, string $model, string $error_message, int $http_code = 0): void
    {
        $key = "{$provider}/{$model}";

        if (!isset($this->metrics['models'][$key])) {
            $this->metrics['models'][$key] = $this->create_default_model_metrics();
        }

        $m = &$this->metrics['models'][$key];
        $m['consecutive_failures'] = ($m['consecutive_failures'] ?? 0) + 1;
        $m['failed_requests'] = ($m['failed_requests'] ?? 0) + 1;
        $m['total_requests'] = ($m['total_requests'] ?? 0) + 1;
        $m['last_failure_time'] = time();
        $m['last_error_message'] = $error_message;
        $m['last_http_code'] = $http_code;

        // Trip circuit breaker if failure threshold reached
        if ($m['consecutive_failures'] >= self::CIRCUIT_BREAKER_THRESHOLD || $http_code === 429) {
            $m['state'] = 'cooling_down';
            $m['cooldown_until'] = time() + self::CIRCUIT_BREAKER_COOLDOWN;
            if (function_exists('log_message')) {
                log_message('error', "Ai_hybrid_router: Circuit breaker tripped for {$key}. Cooldown for " . self::CIRCUIT_BREAKER_COOLDOWN . 's');
            }
        } else {
            $m['state'] = 'degraded';
        }

        // Provider-level failure tracking
        if (!isset($this->metrics['providers'][$provider])) {
            $this->metrics['providers'][$provider] = ['state' => 'healthy', 'consecutive_failures' => 0];
        }
        $p = &$this->metrics['providers'][$provider];
        $p['consecutive_failures'] = ($p['consecutive_failures'] ?? 0) + 1;

        if ($http_code === 401 || $http_code === 403) {
            $p['state'] = 'auth_failed';
            $p['last_error'] = 'Invalid API key or authentication failed';
        } elseif ($p['consecutive_failures'] >= self::CIRCUIT_BREAKER_THRESHOLD) {
            $p['state'] = 'cooling_down';
            $p['cooldown_until'] = time() + self::CIRCUIT_BREAKER_COOLDOWN;
        }

        $this->save_metrics();
    }

    /**
     * Determine if an error is retryable (rate limits, timeouts, temporary 5xx).
     */
    public function is_retryable_error(int $http_code, string $error_message): bool
    {
        // 400 Bad Request is client schema syntax error -> not retryable on another provider
        if ($http_code === 400) {
            return false;
        }

        // 401/403 Invalid API key -> fail this provider permanently, but can failover to other providers
        if ($http_code === 401 || $http_code === 403) {
            return true;
        }

        // Rate limits, server overloads, network issues are fully retryable
        if (in_array($http_code, [408, 429, 500, 502, 503, 504], true)) {
            return true;
        }

        // cURL timeout or connection reset
        if (str_contains(strtolower($error_message), 'timeout')
            || str_contains(strtolower($error_message), 'timed out')
            || str_contains(strtolower($error_message), 'connection refused')
            || str_contains(strtolower($error_message), 'empty response')
            || str_contains(strtolower($error_message), 'rate limit')) {
            return true;
        }

        return $http_code >= 500 || $http_code === 0;
    }

    /**
     * Retrieve health stats for a model.
     */
    public function get_model_health(string $provider, string $model): array
    {
        $key = "{$provider}/{$model}";
        $data = $this->metrics['models'][$key] ?? [];

        $total = (int) ($data['total_requests'] ?? 0);
        $failed = (int) ($data['failed_requests'] ?? 0);
        $successful = (int) ($data['successful_requests'] ?? 0);

        $error_rate = $total > 0 ? round($failed / $total, 3) : 0.0;
        $success_rate = $total > 0 ? round($successful / $total, 3) : 1.0;

        $state = $data['state'] ?? 'healthy';
        $cooldown_until = (int) ($data['cooldown_until'] ?? 0);

        if ($state === 'cooling_down' && time() >= $cooldown_until) {
            $state = 'healthy';
        }

        return [
            'state' => $state,
            'latency_ema_ms' => (float) ($data['latency_ema_ms'] ?? 400.0),
            'total_requests' => $total,
            'successful_requests' => $successful,
            'failed_requests' => $failed,
            'error_rate' => $error_rate,
            'success_rate' => $success_rate,
            'consecutive_failures' => (int) ($data['consecutive_failures'] ?? 0),
            'cooldown_until' => $cooldown_until,
            'last_error' => $data['last_error_message'] ?? null,
            'last_http_code' => $data['last_http_code'] ?? null,
        ];
    }

    /**
     * Get remaining daily free quota for a model.
     */
    public function get_remaining_free_quota(string $provider, string $model, ?array $model_meta = null): int
    {
        if ($model_meta === null) {
            $catalog = $this->get_model_catalog();
            $model_meta = $catalog[$model] ?? null;
        }

        if (!$model_meta || empty($model_meta['has_free_tier'])) {
            return 0;
        }

        $key = "{$provider}/{$model}";
        $data = $this->metrics['models'][$key] ?? [];

        $today = date('Y-m-d');
        if (($data['last_used_date'] ?? '') !== $today) {
            return (int) ($model_meta['free_rpd_limit'] ?? 1500);
        }

        $used = (int) ($data['free_quota_used_today'] ?? 0);
        $limit = (int) ($model_meta['free_rpd_limit'] ?? 1500);

        return max(0, $limit - $used);
    }

    /**
     * Get all live metrics for the admin dashboard.
     */
    public function get_all_metrics(): array
    {
        $catalog = $this->get_model_catalog();
        $overview = [];

        foreach ($catalog as $key => $meta) {
            $health = $this->get_model_health($meta['provider'], $meta['model']);
            $remaining_quota = $this->get_remaining_free_quota($meta['provider'], $meta['model'], $meta);

            $overview[$key] = [
                'display_name' => $meta['display_name'],
                'provider' => $meta['provider'],
                'model' => $meta['model'],
                'capability' => $meta['capability'],
                'tool_support' => $meta['tool_support'],
                'has_free_tier' => $meta['has_free_tier'],
                'remaining_free_quota' => $remaining_quota,
                'latency_ms' => $health['latency_ema_ms'],
                'state' => $health['state'],
                'success_rate' => round($health['success_rate'] * 100, 1),
                'total_requests' => $health['total_requests'],
                'consecutive_failures' => $health['consecutive_failures'],
                'last_error' => $health['last_error'],
            ];
        }

        return [
            'models' => $overview,
            'summary' => [
                'total_requests' => array_sum(array_column($overview, 'total_requests')),
                'healthy_models' => count(array_filter($overview, static fn ($m) => $m['state'] === 'healthy')),
                'total_models' => count($overview),
            ],
        ];
    }

    /**
     * Clear all recorded health & latency metrics (Admin Reset).
     */
    public function reset_metrics(): void
    {
        $this->metrics = ['models' => [], 'providers' => []];
        $this->save_metrics();
    }

    /**
     * Check if a tool execution with specific arguments has already completed.
     * Prevents duplicate execution during retry turns.
     */
    public function is_tool_executed(string $tool_name, array $args): bool
    {
        $signature = md5($tool_name . ':' . json_encode($args));
        return in_array($signature, $this->executed_tool_keys, true);
    }

    /**
     * Mark a tool execution as completed.
     */
    public function mark_tool_executed(string $tool_name, array $args): void
    {
        $signature = md5($tool_name . ':' . json_encode($args));
        $this->executed_tool_keys[] = $signature;
    }

    /**
     * Load metrics from cache file.
     */
    protected function load_metrics(): void
    {
        $path = $this->get_metrics_cache_path();
        if (file_exists($path)) {
            $raw = @file_get_contents($path);
            if ($raw) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $this->metrics = $decoded;
                    return;
                }
            }
        }

        $this->metrics = [
            'models' => [],
            'providers' => [],
        ];
    }

    /**
     * Save metrics to cache file.
     */
    protected function save_metrics(): void
    {
        $path = $this->get_metrics_cache_path();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        @file_put_contents($path, json_encode($this->metrics, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    protected function create_default_model_metrics(): array
    {
        return [
            'state' => 'healthy',
            'consecutive_failures' => 0,
            'successful_requests' => 0,
            'failed_requests' => 0,
            'total_requests' => 0,
            'latency_ema_ms' => 400.0,
            'free_quota_used_today' => 0,
            'last_used_date' => date('Y-m-d'),
            'last_failure_time' => null,
            'last_error_message' => null,
            'last_http_code' => null,
        ];
    }
}
