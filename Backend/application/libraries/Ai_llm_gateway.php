<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Universal Multi-Provider AI / LLM Gateway.
 *
 * Core Tenet:
 * - THERE IS NO PRIMARY PROVIDER.
 * - OpenAI, Anthropic, and Google are EQUAL, first-class AI providers.
 * - Groq and OpenRouter provide additional ultra-fast and free open routing.
 * - Dynamic selection is managed via Ai_hybrid_router evaluating 12 holistic criteria per request.
 * - Dynamic failover recalculates eligible models and selects the next best model dynamically.
 * - An admin toggle allows temporary switching between Legacy and New Hybrid Router engines.
 *
 * @package Libraries
 */
class Ai_llm_gateway
{
    /**
     * @var CI_Controller
     */
    protected CI_Controller $CI;

    /**
     * Last HTTP response status and error message for dynamic retry evaluation.
     */
    protected int $last_http_code = 0;
    protected string $last_error_message = '';

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->helper('setting');
        $this->CI->load->helper('tenant');
        $this->CI->load->library('ai_hybrid_router');
    }

    /**
     * Get active engine mode: 'hybrid' (default / dynamic 12-criteria) or 'legacy'.
     */
    public function get_engine_version(): string
    {
        $version = $this->get_setting_safely('ai_engine_version');
        if (empty($version)) {
            $enabled = $this->get_setting_safely('ai_hybrid_router_enabled');
            $version = ($enabled === '0' || $enabled === 'legacy') ? 'legacy' : 'hybrid';
        }

        return strtolower(trim($version)) === 'legacy' ? 'legacy' : 'hybrid';
    }

    /**
     * Safely resolve a setting value checking tenant setting, master setting and environment.
     */
    public function get_setting_safely(string $key): ?string
    {
        // 1. Tenant setting
        if (function_exists('setting')) {
            try {
                $val = setting($key);
                if (!empty($val)) {
                    return trim((string) $val);
                }
            } catch (\Throwable $e) {
                // Ignore if not in tenant DB context
            }
        }

        // 2. Master setting
        if (function_exists('master_setting')) {
            try {
                $val = master_setting($key);
                if (!empty($val)) {
                    return trim((string) $val);
                }
            } catch (\Throwable $e) {
                // Ignore if master DB query fails
            }
        }

        // 3. Environment variable
        $env = getenv($key);
        if ($env !== false && $env !== '') {
            return trim((string) $env);
        }

        return null;
    }

    /**
     * Get configured API key for a provider with hierarchical resolution:
     * Tenant setting -> Master setting -> getenv
     *
     * @param string $provider 'google' | 'groq' | 'openrouter' | 'openai' | 'anthropic'
     * @return string|null
     */
    public function get_api_key(string $provider): ?string
    {
        $keys = [
            'google' => ['google_ai_key', 'gemini_api_key', 'GEMINI_API_KEY', 'GOOGLE_AI_KEY'],
            'groq' => ['groq_api_key', 'GROQ_API_KEY'],
            'openrouter' => ['openrouter_api_key', 'OPENROUTER_API_KEY'],
            'openai' => ['openai_api_key', 'OPENAI_API_KEY'],
            'anthropic' => ['anthropic_api_key', 'ANTHROPIC_API_KEY'],
        ];

        $target_keys = $keys[$provider] ?? [$provider . '_api_key'];

        foreach ($target_keys as $k) {
            $val = $this->get_setting_safely($k);
            if (!empty($val)) {
                return $val;
            }
        }

        return null;
    }

    /**
     * Get active provider preference ('auto' or specific tenant override).
     */
    public function get_active_provider(): string
    {
        $provider = $this->get_setting_safely('ai_provider');
        if (empty($provider)) {
            $provider = 'auto';
        }

        return strtolower(trim($provider));
    }

    /**
     * Send chat completion request.
     * Uses dynamic Hybrid Model Router by default, with dynamic failover across equal providers.
     *
     * @param array $messages Standard format: [['role' => 'system'|'user'|'assistant'|'tool', 'content' => ...], ...]
     * @param array $options Configuration options:
     *   - 'tools': Optional function definitions
     *   - 'temperature': float (default 0.3)
     *   - 'max_tokens': int (default 1024)
     *   - 'task_type': optional task type override
     *   - 'response_format': optional structured output format (e.g. 'json_object')
     *   - 'provider': specific provider override
     *   - 'model': specific model override
     *   - 'force_legacy': bool
     *
     * @return array|null Result array or null on total failure
     */
    public function chat(array $messages, array $options = []): ?array
    {
        $engine = !empty($options['force_legacy']) ? 'legacy' : $this->get_engine_version();

        if ($engine === 'legacy') {
            return $this->chat_legacy($messages, $options);
        }

        return $this->chat_hybrid($messages, $options);
    }

    /**
     * Dynamic Hybrid Model Router Chat Implementation.
     * OpenAI, Anthropic, and Google are equal first-class citizens.
     */
    protected function chat_hybrid(array $messages, array $options = []): ?array
    {
        $tools = $options['tools'] ?? null;
        $temperature = $options['temperature'] ?? 0.3;
        $max_tokens = $options['max_tokens'] ?? 1024;
        $task_type = $options['task_type'] ?? $this->infer_task_type($messages, $options);

        // Collect available providers that have API keys configured
        $all_providers = ['google', 'openai', 'anthropic', 'groq', 'openrouter'];
        $available_providers = [];
        foreach ($all_providers as $p) {
            if ($this->get_api_key($p) !== null) {
                $available_providers[] = $p;
            }
        }

        if (empty($available_providers)) {
            log_message('error', 'Ai_llm_gateway: No AI API keys configured on platform, tenant, or environment.');
            return null;
        }

        // Build request context for the 12 criteria scoring
        $request_context = [
            'task_type' => $task_type,
            'tools' => $tools,
            'structured_output' => !empty($options['response_format']) || !empty($options['json_schema']),
            'estimated_tokens' => $this->estimate_tokens($messages),
            'available_providers' => $available_providers,
            'preferred_provider' => $options['provider'] ?? $this->get_active_provider(),
            'tenant_policy' => $this->get_tenant_ai_policy(),
        ];

        // Specific model override requested by caller?
        if (!empty($options['model']) && !empty($options['provider'])) {
            $override_prov = $options['provider'];
            $override_model = $options['model'];
            $key = $this->get_api_key($override_prov);
            if (!empty($key)) {
                $start = microtime(true);
                $res = $this->execute_provider_call($override_prov, $override_model, $key, $messages, $tools, $temperature, $max_tokens, $options);
                $latency_ms = round((microtime(true) - $start) * 1000, 1);
                if ($res !== null) {
                    $this->CI->ai_hybrid_router->record_success($override_prov, $override_model, $latency_ms);
                    $res['provider'] = $override_prov;
                    $res['model'] = $override_model;
                    $res['engine'] = 'hybrid';
                    $res['latency_ms'] = $latency_ms;
                    return $res;
                }
            }
        }

        $excluded_models = [];
        $excluded_providers = [];
        $max_attempts = 4;

        for ($attempt = 1; $attempt <= $max_attempts; $attempt++) {
            // Select next best model dynamically based on the 12 criteria
            $selected = $this->CI->ai_hybrid_router->select_best_model($request_context, $excluded_models, $excluded_providers);

            if ($selected === null) {
                log_message('error', "Ai_llm_gateway: No more eligible healthy models available at attempt {$attempt}.");
                break;
            }

            $prov = $selected['provider'];
            $model = $selected['model'];
            $key = $this->get_api_key($prov);

            if (empty($key)) {
                $excluded_providers[] = $prov;
                continue;
            }

            $start = microtime(true);
            try {
                $result = $this->execute_provider_call($prov, $model, $key, $messages, $tools, $temperature, $max_tokens, $options);
                $latency_ms = round((microtime(true) - $start) * 1000, 1);

                if ($result !== null && !empty($result['success'])) {
                    // Success! Record metrics & return
                    $this->CI->ai_hybrid_router->record_success($prov, $model, $latency_ms);
                    $result['provider'] = $prov;
                    $result['model'] = $model;
                    $result['engine'] = 'hybrid';
                    $result['latency_ms'] = $latency_ms;
                    $result['router_score'] = $selected['total_score'];
                    return $result;
                }
            } catch (\Throwable $e) {
                $this->last_error_message = $e->getMessage();
                $this->last_http_code = 0;
            }

            // Failure handling & dynamic failover
            $http_code = $this->last_http_code;
            $error_message = $this->last_error_message;

            $this->CI->ai_hybrid_router->record_failure($prov, $model, $error_message, $http_code);
            log_message('warn', "Ai_llm_gateway: Model {$model} ({$prov}) failed with HTTP {$http_code}: {$error_message}. Dynamically recalculating next best model...");

            // If account auth error (401/403), exclude entire provider
            if ($http_code === 401 || $http_code === 403) {
                $excluded_providers[] = $prov;
            }

            // Check retryability
            if (!$this->CI->ai_hybrid_router->is_retryable_error($http_code, $error_message)) {
                log_message('error', "Ai_llm_gateway: Non-retryable error {$http_code} from {$prov}/{$model}: {$error_message}");
                break;
            }

            $excluded_models[] = $model;
        }

        return null;
    }

    /**
     * Dispatch call to specific provider implementation.
     */
    protected function execute_provider_call(string $provider, string $model, string $key, array $messages, ?array $tools, float $temperature, int $max_tokens, array $options): ?array
    {
        return match ($provider) {
            'google', 'gemini' => $this->call_google_gemini($messages, $model, $key, $tools, $temperature, $max_tokens, $options),
            'groq' => $this->call_groq($messages, $model, $key, $tools, $temperature, $max_tokens, $options),
            'openrouter' => $this->call_openrouter($messages, $model, $key, $tools, $temperature, $max_tokens, $options),
            'openai' => $this->call_openai($messages, $model, $key, $tools, $temperature, $max_tokens, $options),
            'anthropic', 'claude' => $this->call_anthropic($messages, $model, $key, $tools, $temperature, $max_tokens, $options),
            default => null,
        };
    }

    /**
     * Legacy sequential provider fallback loop (for admin toggle backward compatibility).
     */
    protected function chat_legacy(array $messages, array $options = []): ?array
    {
        $requested_provider = $options['provider'] ?? $this->get_active_provider();
        $tools = $options['tools'] ?? null;
        $temperature = $options['temperature'] ?? 0.3;
        $max_tokens = $options['max_tokens'] ?? 1024;

        $providers_to_try = [];
        if ($requested_provider !== 'auto' && $requested_provider !== '') {
            $providers_to_try[] = $requested_provider;
        }

        $all_candidates = ['google', 'groq', 'openrouter', 'openai', 'anthropic'];
        foreach ($all_candidates as $candidate) {
            if (!in_array($candidate, $providers_to_try, true) && $this->get_api_key($candidate) !== null) {
                $providers_to_try[] = $candidate;
            }
        }

        if (empty($providers_to_try)) {
            log_message('error', 'Ai_llm_gateway: No AI API keys configured on platform, tenant or environment.');
            return null;
        }

        foreach ($providers_to_try as $prov) {
            $key = $this->get_api_key($prov);
            if (empty($key)) {
                continue;
            }

            $model = $options['model'] ?? $this->get_default_model($prov);

            try {
                $result = $this->execute_provider_call($prov, $model, $key, $messages, $tools, $temperature, $max_tokens, $options);
                if ($result !== null) {
                    $result['provider'] = $prov;
                    $result['model'] = $model;
                    $result['engine'] = 'legacy';
                    return $result;
                }
            } catch (\Throwable $e) {
                log_message('error', "Ai_llm_gateway legacy: Provider {$prov} call failed: " . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Infer task type from message content and tools.
     */
    protected function infer_task_type(array $messages, array $options): string
    {
        if (!empty($options['tools'])) {
            foreach ($options['tools'] as $tool) {
                $name = $tool['function']['name'] ?? '';
                if (str_contains($name, 'appointment') || str_contains($name, 'slot') || str_contains($name, 'service')) {
                    return 'appointment_booking';
                }
            }
            return 'tool_execution';
        }

        if (!empty($options['response_format']) || !empty($options['json_schema'])) {
            return 'structured_output';
        }

        $all_text = '';
        foreach ($messages as $m) {
            $all_text .= ' ' . ($m['content'] ?? '');
        }

        if (str_contains(mb_strtolower($all_text), 'özet') || str_contains(mb_strtolower($all_text), 'summary') || count($messages) > 15) {
            return 'summary';
        }

        if (str_contains(mb_strtolower($all_text), 'şikayet') || str_contains(mb_strtolower($all_text), 'iade') || str_contains(mb_strtolower($all_text), 'anlaşmazlık')) {
            return 'complex_reasoning';
        }

        if (mb_strlen($all_text) < 120 && count($messages) <= 2) {
            return 'fast_response';
        }

        return 'chat';
    }

    /**
     * Estimate token length of messages.
     */
    protected function estimate_tokens(array $messages): int
    {
        $len = 0;
        foreach ($messages as $m) {
            $len += mb_strlen((string) ($m['content'] ?? ''));
        }
        return (int) ceil($len / 3.5);
    }

    /**
     * Retrieve tenant AI policy if available.
     */
    protected function get_tenant_ai_policy(): array
    {
        try {
            if ($this->CI->db && $this->CI->db->table_exists('tenant_ai_policies')) {
                $row = $this->CI->db->get('tenant_ai_policies')->row_array();
                return is_array($row) ? $row : [];
            }
        } catch (\Throwable $e) {
            // Ignore DB errors in CLI / master context
        }
        return [];
    }

    /**
     * Get default model name for a provider.
     */
    public function get_default_model(string $provider): string
    {
        $setting_key = "ai_model_{$provider}";
        $custom_model = $this->get_setting_safely($setting_key);
        if (!empty($custom_model)) {
            if (($provider === 'google' || $provider === 'gemini') && ($custom_model === 'gemini-2.5-flash' || str_starts_with($custom_model, 'gemini-1.') || str_starts_with($custom_model, 'gemini-2.0'))) {
                return 'gemini-3.8-flash';
            }
            return $custom_model;
        }

        return match ($provider) {
            'google', 'gemini' => getenv('GEMINI_MODEL') ?: 'gemini-3.8-flash',
            'groq' => getenv('GROQ_MODEL') ?: 'llama-3.3-70b-versatile',
            'openrouter' => (!empty(getenv('OPENROUTER_MODEL')) && getenv('OPENROUTER_MODEL') !== 'nvidia/nemotron-3.5-lightning:free')
                ? getenv('OPENROUTER_MODEL')
                : 'qwen/qwen-2.5-72b-instruct',
            'openai' => getenv('OPENAI_MODEL') ?: 'gpt-4o-mini',
            'anthropic', 'claude' => getenv('ANTHROPIC_MODEL') ?: 'claude-3-5-haiku-20241022',
            default => 'gemini-3.8-flash',
        };
    }

    /**
     * Google Gemini API (native generateContent endpoint with function calling & structured output)
     */
    protected function call_google_gemini(array $messages, string $model, string $api_key, ?array $tools, float $temperature, int $max_tokens, array $options = []): ?array
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($api_key);

        $payload = $this->format_messages_for_gemini($messages, $tools, $temperature, $max_tokens);

        // Structured output support
        if (!empty($options['response_format']) && $options['response_format'] === 'json_object') {
            $payload['generationConfig']['responseMimeType'] = 'application/json';
        }

        $headers = [
            'Content-Type: application/json',
        ];

        $res = $this->http_post($url, $payload, $headers);
        if (!$res && $model === 'gemini-3.8-flash') {
            $fallback_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . urlencode($api_key);
            $res = $this->http_post($fallback_url, $payload, $headers);
        }
        if (!$res) {
            return null;
        }

        return $this->parse_gemini_response($res);
    }

    protected function format_messages_for_gemini(array $messages, ?array $tools, float $temperature, int $max_tokens): array
    {
        $system_text = '';
        $contents = [];

        foreach ($messages as $m) {
            $role = $m['role'] ?? 'user';
            $content = $m['content'] ?? '';

            if ($role === 'system') {
                $system_text .= $content . "\n";
            } elseif ($role === 'assistant') {
                if (!empty($m['model_parts'])) {
                    $contents[] = [
                        'role' => 'model',
                        'parts' => $m['model_parts'],
                    ];
                } else {
                    $parts = [];
                    if (!empty($content)) {
                        $parts[] = ['text' => (string) $content];
                    }
                    if (!empty($m['tool_calls'])) {
                        foreach ($m['tool_calls'] as $tc) {
                            $fn = $tc['function'] ?? [];
                            $args = is_string($fn['arguments'] ?? null) ? json_decode($fn['arguments'], true) : ($fn['arguments'] ?? []);
                            $f_part = [
                                'functionCall' => [
                                    'name' => $fn['name'] ?? '',
                                    'args' => $args ?: (object)[],
                                ],
                            ];
                            if (!empty($tc['thought_signature'])) {
                                $f_part['thoughtSignature'] = $tc['thought_signature'];
                            }
                            $parts[] = $f_part;
                        }
                    }
                    if (!empty($parts)) {
                        $contents[] = [
                            'role' => 'model',
                            'parts' => $parts,
                        ];
                    }
                }
            } elseif ($role === 'tool') {
                $response_data = is_array($content) ? $content : (json_decode((string) $content, true) ?: ['result' => $content]);
                if (!is_array($response_data) || array_is_list($response_data)) {
                    $response_data = ['response' => $response_data];
                }
                $tool_part = [
                    'functionResponse' => [
                        'name' => $m['name'] ?? 'tool_result',
                        'response' => $response_data,
                    ],
                ];

                $last_idx = count($contents) - 1;
                if ($last_idx >= 0 && $contents[$last_idx]['role'] === 'user' && !empty($contents[$last_idx]['parts'][0]['functionResponse'])) {
                    $contents[$last_idx]['parts'][] = $tool_part;
                } else {
                    $contents[] = [
                        'role' => 'user',
                        'parts' => [$tool_part],
                    ];
                }
            } else {
                $last_idx = count($contents) - 1;
                if ($last_idx >= 0 && $contents[$last_idx]['role'] === 'user' && empty($contents[$last_idx]['parts'][0]['functionResponse'])) {
                    $contents[$last_idx]['parts'][] = ['text' => (string) $content];
                } else {
                    $contents[] = [
                        'role' => 'user',
                        'parts' => [['text' => (string) $content]],
                    ];
                }
            }
        }

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $temperature,
                'maxOutputTokens' => $max_tokens,
                'thinkingConfig' => [
                    'thinkingBudget' => 0,
                ],
            ],
        ];

        if ($system_text !== '') {
            $payload['systemInstruction'] = [
                'parts' => [['text' => trim($system_text)]],
            ];
        }

        if (!empty($tools)) {
            $function_declarations = [];
            foreach ($tools as $t) {
                if (($t['type'] ?? '') === 'function' && !empty($t['function'])) {
                    $fn = $t['function'];
                    $params = $fn['parameters'] ?? (object)[];
                    if (is_array($params) && empty($params['properties'])) {
                        $params['properties'] = (object)[];
                    }
                    $function_declarations[] = [
                        'name' => $fn['name'],
                        'description' => $fn['description'] ?? '',
                        'parameters' => $params,
                    ];
                }
            }
            if (!empty($function_declarations)) {
                $payload['tools'] = [
                    ['functionDeclarations' => $function_declarations],
                ];
            }
        }

        return $payload;
    }

    protected function parse_gemini_response(array $res): ?array
    {
        $candidate = $res['candidates'][0] ?? null;
        if (!$candidate) {
            return null;
        }

        $reply_text = '';
        $tool_calls = [];

        foreach ($candidate['content']['parts'] ?? [] as $part) {
            if (!empty($part['thought'])) {
                continue;
            }
            if (!empty($part['text'])) {
                $reply_text .= $part['text'];
            }
            if (!empty($part['functionCall'])) {
                $tc_entry = [
                    'id' => 'call_' . uniqid(),
                    'type' => 'function',
                    'function' => [
                        'name' => $part['functionCall']['name'] ?? '',
                        'arguments' => json_encode($part['functionCall']['args'] ?? [], JSON_UNESCAPED_UNICODE),
                    ],
                ];
                if (!empty($part['thoughtSignature'])) {
                    $tc_entry['thought_signature'] = $part['thoughtSignature'];
                }
                $tool_calls[] = $tc_entry;
            }
        }

        return [
            'success' => true,
            'reply' => self::clean_thinking_traces($reply_text),
            'tool_calls' => $tool_calls,
            'raw_message' => $candidate,
            'model_parts' => $candidate['content']['parts'] ?? [],
        ];
    }

    /**
     * Groq API
     */
    protected function call_groq(array $messages, string $model, string $api_key, ?array $tools, float $temperature, int $max_tokens, array $options = []): ?array
    {
        $url = 'https://api.groq.com/openai/v1/chat/completions';
        $payload = [
            'model' => $model,
            'messages' => $this->normalize_messages_for_openai($messages),
            'temperature' => $temperature,
            'max_tokens' => $max_tokens,
        ];
        if (!empty($tools)) {
            $payload['tools'] = $this->normalize_tools_for_openai($tools);
        }
        if (!empty($options['response_format']) && $options['response_format'] === 'json_object') {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $headers = [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json',
        ];

        $res = $this->http_post($url, $payload, $headers);
        if (!$res && $model !== 'openai/gpt-oss-20b') {
            $payload['model'] = 'openai/gpt-oss-20b';
            $res = $this->http_post($url, $payload, $headers);
        }
        return $res ? $this->parse_openai_response($res) : null;
    }

    /**
     * OpenRouter API
     */
    protected function call_openrouter(array $messages, string $model, string $api_key, ?array $tools, float $temperature, int $max_tokens, array $options = []): ?array
    {
        $url = 'https://openrouter.ai/api/v1/chat/completions';
        $payload = [
            'model' => $model,
            'messages' => $this->normalize_messages_for_openai($messages),
            'temperature' => $temperature,
            'max_tokens' => $max_tokens,
        ];
        if (!empty($tools)) {
            $payload['tools'] = $this->normalize_tools_for_openai($tools);
        }
        if (!empty($options['response_format']) && $options['response_format'] === 'json_object') {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $headers = [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json',
            'HTTP-Referer: https://' . (getenv('TENANT_APP_DOMAIN') ?: 'bookiapp.kibusiness.co'),
            'X-Title: BooKi AI Asistan',
        ];

        $res = $this->http_post($url, $payload, $headers);
        return $res ? $this->parse_openai_response($res) : null;
    }

    /**
     * OpenAI API (First-Class Provider with full function calling & structured outputs)
     */
    protected function call_openai(array $messages, string $model, string $api_key, ?array $tools, float $temperature, int $max_tokens, array $options = []): ?array
    {
        $url = 'https://api.openai.com/v1/chat/completions';
        $payload = [
            'model' => $model,
            'messages' => $this->normalize_messages_for_openai($messages),
            'temperature' => $temperature,
            'max_tokens' => $max_tokens,
        ];
        if (!empty($tools)) {
            $payload['tools'] = $this->normalize_tools_for_openai($tools);
        }
        if (!empty($options['response_format']) && $options['response_format'] === 'json_object') {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $headers = [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json',
        ];

        $res = $this->http_post($url, $payload, $headers);
        return $res ? $this->parse_openai_response($res) : null;
    }

    /**
     * Anthropic Claude API (First-Class Provider with full function calling & tool use)
     */
    protected function call_anthropic(array $messages, string $model, string $api_key, ?array $tools, float $temperature, int $max_tokens, array $options = []): ?array
    {
        $url = 'https://api.anthropic.com/v1/messages';

        $anthropic_data = $this->normalize_messages_for_anthropic($messages);

        $payload = [
            'model' => $model,
            'messages' => $anthropic_data['messages'],
            'max_tokens' => $max_tokens,
            'temperature' => $temperature,
        ];

        if (!empty($anthropic_data['system'])) {
            $payload['system'] = $anthropic_data['system'];
        }

        if (!empty($tools)) {
            $payload['tools'] = $this->normalize_tools_for_anthropic($tools);
        }

        $headers = [
            'x-api-key: ' . $api_key,
            'anthropic-version: 2023-06-01',
            'Content-Type: application/json',
        ];

        $res = $this->http_post($url, $payload, $headers);
        return $res ? $this->parse_anthropic_response($res) : null;
    }

    /**
     * Normalize tools array for Anthropic Claude input_schema format.
     */
    protected function normalize_tools_for_anthropic(?array $tools): ?array
    {
        if (empty($tools)) {
            return null;
        }

        $anthropic_tools = [];
        foreach ($tools as $t) {
            if (($t['type'] ?? '') === 'function' && !empty($t['function'])) {
                $fn = $t['function'];
                $params = $fn['parameters'] ?? ['type' => 'object', 'properties' => (object)[]];
                if (is_array($params) && empty($params['properties'])) {
                    $params['properties'] = (object)[];
                }
                $anthropic_tools[] = [
                    'name' => $fn['name'],
                    'description' => $fn['description'] ?? '',
                    'input_schema' => $params,
                ];
            }
        }

        return !empty($anthropic_tools) ? $anthropic_tools : null;
    }

    /**
     * Normalize conversation history for Anthropic Claude format:
     * - Top-level system prompt
     * - Strictly alternating user and assistant messages
     * - tool_use blocks for assistant tool calls
     * - tool_result blocks in user messages for tool responses
     */
    protected function normalize_messages_for_anthropic(array $messages): array
    {
        $system_prompt = '';
        $claude_messages = [];

        foreach ($messages as $m) {
            $role = $m['role'] ?? 'user';
            $content = $m['content'] ?? '';

            if ($role === 'system') {
                $system_prompt .= ($system_prompt !== '' ? "\n" : '') . trim((string) $content);
                continue;
            }

            if ($role === 'assistant') {
                $content_blocks = [];
                if (!empty($content)) {
                    $content_blocks[] = ['type' => 'text', 'text' => (string) $content];
                }
                if (!empty($m['tool_calls'])) {
                    foreach ($m['tool_calls'] as $tc) {
                        $fn = $tc['function'] ?? [];
                        $args = is_string($fn['arguments'] ?? null)
                            ? (json_decode($fn['arguments'], true) ?: (object)[])
                            : ($fn['arguments'] ?? (object)[]);
                        $content_blocks[] = [
                            'type' => 'tool_use',
                            'id' => $tc['id'] ?? ('toolu_' . uniqid()),
                            'name' => $fn['name'] ?? '',
                            'input' => $args ?: (object)[],
                        ];
                    }
                }

                $claude_messages[] = [
                    'role' => 'assistant',
                    'content' => !empty($content_blocks) ? $content_blocks : (string) $content,
                ];
            } elseif ($role === 'tool') {
                // In Anthropic Messages API, tool results are returned as user content blocks
                $tool_res_block = [
                    'type' => 'tool_result',
                    'tool_use_id' => $m['tool_call_id'] ?? '',
                    'content' => is_string($content) ? $content : json_encode($content, JSON_UNESCAPED_UNICODE),
                ];

                $last_idx = count($claude_messages) - 1;
                if ($last_idx >= 0 && $claude_messages[$last_idx]['role'] === 'user' && is_array($claude_messages[$last_idx]['content'])) {
                    $claude_messages[$last_idx]['content'][] = $tool_res_block;
                } else {
                    $claude_messages[] = [
                        'role' => 'user',
                        'content' => [$tool_res_block],
                    ];
                }
            } else {
                // Regular user message
                $claude_messages[] = [
                    'role' => 'user',
                    'content' => (string) $content,
                ];
            }
        }

        return [
            'system' => $system_prompt,
            'messages' => $claude_messages,
        ];
    }

    /**
     * Parse Anthropic Messages API response with tool_use extraction.
     */
    protected function parse_anthropic_response(array $res): ?array
    {
        $reply_text = '';
        $tool_calls = [];

        if (isset($res['content']) && is_array($res['content'])) {
            foreach ($res['content'] as $block) {
                if (($block['type'] ?? '') === 'text') {
                    $reply_text .= $block['text'] ?? '';
                } elseif (($block['type'] ?? '') === 'tool_use') {
                    $tool_calls[] = [
                        'id' => $block['id'] ?? ('toolu_' . uniqid()),
                        'type' => 'function',
                        'function' => [
                            'name' => $block['name'] ?? '',
                            'arguments' => json_encode($block['input'] ?? (object)[], JSON_UNESCAPED_UNICODE),
                        ],
                    ];
                }
            }
        }

        return [
            'success' => true,
            'reply' => self::clean_thinking_traces(trim($reply_text)),
            'tool_calls' => $tool_calls,
            'raw_message' => $res,
        ];
    }

    /**
     * Ensure tools schema is 100% compliant with JSON schema spec for OpenAI/Groq/OpenRouter.
     * Prevents "got array, want object" errors for empty properties.
     */
    protected function normalize_tools_for_openai(?array $tools): ?array
    {
        if (empty($tools)) {
            return null;
        }

        $normalized = [];
        foreach ($tools as $t) {
            if (($t['type'] ?? '') === 'function' && !empty($t['function'])) {
                $fn = $t['function'];
                $params = $fn['parameters'] ?? (object)[];
                if (is_array($params) && empty($params['properties'])) {
                    $params['properties'] = (object)[];
                }
                $normalized[] = [
                    'type' => 'function',
                    'function' => [
                        'name' => $fn['name'],
                        'description' => $fn['description'] ?? '',
                        'parameters' => $params,
                    ],
                ];
            } else {
                $normalized[] = $t;
            }
        }

        return $normalized;
    }

    /**
     * Normalize OpenAI response structure.
     */
    protected function parse_openai_response(array $data): array
    {
        $choice = $data['choices'][0]['message'] ?? [];
        $content = trim((string) ($choice['content'] ?? ''));
        $tool_calls = $choice['tool_calls'] ?? [];

        return [
            'success' => true,
            'reply' => self::clean_thinking_traces($content),
            'tool_calls' => $tool_calls,
            'raw_message' => $choice,
        ];
    }

    /**
     * Remove reasoning / chain-of-thought traces generated by thinking models
     */
    public static function clean_thinking_traces(string $text): string
    {
        // 1. Strip XML-style thought tags
        $text = preg_replace('/<think(?:ing)?>.*?<\/think(?:ing)?>/is', '', $text);
        $text = preg_replace('/<thought>.*?<\/thought>/is', '', $text);

        // 2. Strip plaintext thinking blocks
        if (preg_match('/^(?:Here\'?s a thinking process|Thinking Process|Thought Process|Düşünce Süreci):.*?(?=(?:\n\nDraft:|\nDraft:|\n\n[A-ZÇĞİÖŞÜ]|\n\n\*\*|\n\nMerhaba|\n\nSayın|\n\n[a-zçğıöşü]+ Bey|\n\n[a-zçğıöşü]+ Hanım|\Z))/is', $text, $m)) {
            $text = substr($text, strlen($m[0]));
        }

        // 3. Strip "Draft:" label
        $text = preg_replace('/^Draft:\s*/i', '', trim($text));

        return trim($text);
    }

    /**
     * Clean messages array for OpenAI endpoint specs.
     */
    protected function normalize_messages_for_openai(array $messages): array
    {
        $normalized = [];
        foreach ($messages as $m) {
            $item = [
                'role' => $m['role'] ?? 'user',
                'content' => (string) ($m['content'] ?? ''),
            ];
            if (!empty($m['tool_calls'])) {
                $item['tool_calls'] = $m['tool_calls'];
            }
            if (!empty($m['tool_call_id'])) {
                $item['tool_call_id'] = $m['tool_call_id'];
            }
            $normalized[] = $item;
        }
        return $normalized;
    }

    /**
     * Execute cURL HTTP POST with status tracking.
     */
    protected function http_post(string $url, array $payload, array $headers): ?array
    {
        $this->last_http_code = 0;
        $this->last_error_message = '';

        try {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 45,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER => $headers,
            ]);

            $response = curl_exec($curl);
            $this->last_http_code = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            curl_close($curl);

            if ($error) {
                $this->last_error_message = $error;
                log_message('error', "Ai_llm_gateway cURL error on {$url}: " . $error);
                return null;
            }

            if ($this->last_http_code < 200 || $this->last_http_code >= 300) {
                $this->last_error_message = (string) $response;
                log_message('error', "Ai_llm_gateway HTTP error {$this->last_http_code} on {$url}: " . $response);
                return null;
            }

            $decoded = json_decode($response, true);
            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable $e) {
            $this->last_error_message = $e->getMessage();
            log_message('error', "Ai_llm_gateway exception on {$url}: " . $e->getMessage());
            return null;
        }
    }

    public function get_last_http_code(): int
    {
        return $this->last_http_code;
    }

    public function get_last_error_message(): string
    {
        return $this->last_error_message;
    }
}
