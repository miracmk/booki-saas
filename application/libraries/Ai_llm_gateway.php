<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Universal Multi-Provider AI / LLM Gateway.
 *
 * Supports:
 * 1. Google AI Studio / Gemini (Free & Pay-as-you-go)
 * 2. Groq (Free Tier & Fast Inference)
 * 3. OpenRouter (Multi-model & Free Tier routing)
 * 4. OpenAI (GPT-4o, GPT-4o-mini)
 * 5. Anthropic (Claude 3.5 Sonnet, Claude 3.5 Haiku)
 *
 * Provides hierarchical configuration resolution:
 * Tenant Settings -> Master/Superadmin Settings -> Environment Variables (getenv)
 * With automatic failover/fallback across configured providers.
 */
class Ai_llm_gateway
{
    /**
     * @var CI_Controller
     */
    protected CI_Controller $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->helper('setting');
        $this->CI->load->helper('tenant');
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
     * Get configured API key for a provider with hierarchical fallback:
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
     * Get active provider preference.
     *
     * @return string 'auto' | 'google' | 'groq' | 'openrouter' | 'openai' | 'anthropic'
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
     * Send chat completion request across providers with automatic fallback.
     *
     * @param array $messages Standard format: [['role' => 'system'|'user'|'assistant'|'tool', 'content' => ...], ...]
     * @param array $options Configuration options:
     *   - 'tools': Optional function definitions
     *   - 'temperature': float (default 0.3)
     *   - 'max_tokens': int (default 1024)
     *   - 'provider': specific provider override
     *   - 'model': specific model override
     *
     * @return array|null Result array or null on total failure:
     *   [
     *       'success' => bool,
     *       'reply' => string,
     *       'tool_calls' => array,
     *       'provider' => string,
     *       'model' => string,
     *   ]
     */
    public function chat(array $messages, array $options = []): ?array
    {
        $requested_provider = $options['provider'] ?? $this->get_active_provider();
        $tools = $options['tools'] ?? null;
        $temperature = $options['temperature'] ?? 0.3;
        $max_tokens = $options['max_tokens'] ?? 1024;

        // Provider fallback order
        $providers_to_try = [];
        if ($requested_provider !== 'auto' && $requested_provider !== '') {
            $providers_to_try[] = $requested_provider;
        }

        // Fill remaining candidates with available keys
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

            // The Anthropic branch of this gateway does not implement tool
            // calling: it returns tool_calls: [] and the model would then
            // answer WITHOUT any real data (hallucinating "rezervasyon yok").
            // Never use it for a tool request - fall through to the next
            // provider instead.
            if (!empty($tools) && ($prov === 'anthropic' || $prov === 'claude')) {
                log_message('debug', 'Ai_llm_gateway: skipping anthropic for a tool request.');
                continue;
            }

            try {
                $result = match ($prov) {
                    'google', 'gemini' => $this->call_google_gemini($messages, $model, $key, $tools, $temperature, $max_tokens),
                    'groq' => $this->call_groq($messages, $model, $key, $tools, $temperature, $max_tokens),
                    'openrouter' => $this->call_openrouter($messages, $model, $key, $tools, $temperature, $max_tokens),
                    'openai' => $this->call_openai($messages, $model, $key, $tools, $temperature, $max_tokens),
                    'anthropic', 'claude' => $this->call_anthropic($messages, $model, $key, $tools, $temperature, $max_tokens),
                    default => null,
                };

                if ($result !== null) {
                    $result['provider'] = $prov;
                    $result['model'] = $model;
                    return $result;
                }
            } catch (Throwable $e) {
                log_message('error', "Ai_llm_gateway: Provider {$prov} call failed: " . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Get default model name for a provider.
     */
    /**
     * Get default model name for a provider.
     */
    public function get_default_model(string $provider): string
    {
        $setting_key = "ai_model_{$provider}";
        $custom_model = $this->get_setting_safely($setting_key);
        if (!empty($custom_model)) {
            // Automatically upgrade deprecated/sunset/limited Google models to gemini-3.8-flash
            if (($provider === 'google' || $provider === 'gemini') && ($custom_model === 'gemini-2.5-flash' || str_starts_with($custom_model, 'gemini-1.') || str_starts_with($custom_model, 'gemini-2.0'))) {
                return 'gemini-3.8-flash';
            }
            return $custom_model;
        }

        return match ($provider) {
            'google', 'gemini' => getenv('GEMINI_MODEL') ?: 'gemini-3.8-flash',
            'groq' => getenv('GROQ_MODEL') ?: 'qwen/qwen3.8-27b',
            'openrouter' => (!empty(getenv('OPENROUTER_MODEL')) && getenv('OPENROUTER_MODEL') !== 'nvidia/nemotron-3.5-lightning:free')
                ? getenv('OPENROUTER_MODEL')
                : 'qwen/qwen-2.5-72b-instruct',
            'openai' => getenv('OPENAI_MODEL') ?: 'gpt-4o-mini',
            'anthropic', 'claude' => getenv('ANTHROPIC_MODEL') ?: 'claude-3-5-haiku-20241022',
            default => 'gemini-3.8-flash',
        };
    }

    /**
     * Google Gemini API (native generateContent endpoint with function calling)
     */
    protected function call_google_gemini(array $messages, string $model, string $api_key, ?array $tools, float $temperature, int $max_tokens): ?array
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($api_key);

        $payload = $this->format_messages_for_gemini($messages, $tools, $temperature, $max_tokens);
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
    protected function call_groq(array $messages, string $model, string $api_key, ?array $tools, float $temperature, int $max_tokens): ?array
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
    protected function call_openrouter(array $messages, string $model, string $api_key, ?array $tools, float $temperature, int $max_tokens): ?array
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
     * OpenAI API
     */
    protected function call_openai(array $messages, string $model, string $api_key, ?array $tools, float $temperature, int $max_tokens): ?array
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

        $headers = [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json',
        ];

        $res = $this->http_post($url, $payload, $headers);
        return $res ? $this->parse_openai_response($res) : null;
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
                if (is_array($params)) {
                    if (empty($params['properties'])) {
                        $params['properties'] = (object)[];
                    }
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
     * Anthropic Claude Messages API
     */
    protected function call_anthropic(array $messages, string $model, string $api_key, ?array $tools, float $temperature, int $max_tokens): ?array
    {
        $url = 'https://api.anthropic.com/v1/messages';

        $system_prompt = '';
        $claude_messages = [];

        foreach ($messages as $m) {
            $role = $m['role'] ?? 'user';
            $content = $m['content'] ?? '';

            if ($role === 'system') {
                $system_prompt .= $content . "\n";
            } elseif ($role === 'assistant') {
                $claude_messages[] = ['role' => 'assistant', 'content' => (string) $content];
            } else {
                $claude_messages[] = ['role' => 'user', 'content' => (string) $content];
            }
        }

        $payload = [
            'model' => $model,
            'messages' => $claude_messages,
            'max_tokens' => $max_tokens,
            'temperature' => $temperature,
        ];

        if ($system_prompt !== '') {
            $payload['system'] = trim($system_prompt);
        }

        $headers = [
            'x-api-key: ' . $api_key,
            'anthropic-version: 2023-06-01',
            'Content-Type: application/json',
        ];

        $res = $this->http_post($url, $payload, $headers);
        if ($res === null) {
            return null;
        }

        $reply = '';
        if (isset($res['content']) && is_array($res['content'])) {
            foreach ($res['content'] as $block) {
                if (($block['type'] ?? '') === 'text') {
                    $reply .= $block['text'] ?? '';
                }
            }
        }

        return [
            'success' => true,
            'reply' => trim($reply),
            'tool_calls' => [],
        ];
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
     * (e.g. Qwen, DeepSeek, Nemotron) so internal prompts or draft steps never leak to users.
     */
    public static function clean_thinking_traces(string $text): string
    {
        // 1. Strip XML-style thought tags
        $text = preg_replace('/<think(?:ing)?>.*?<\/think(?:ing)?>/is', '', $text);
        $text = preg_replace('/<thought>.*?<\/thought>/is', '', $text);

        // 2. Strip plaintext thinking blocks such as "Here's a thinking process: ... Draft: ..."
        if (preg_match('/^(?:Here\'?s a thinking process|Thinking Process|Thought Process|Düşünce Süreci):.*?(?=(?:\n\nDraft:|\nDraft:|\n\n[A-ZÇĞİÖŞÜ]|\n\n\*\*|\n\nMerhaba|\n\nSayın|\n\n[a-zçğıöşü]+ Bey|\n\n[a-zçğıöşü]+ Hanım|\Z))/is', $text, $m)) {
            $text = substr($text, strlen($m[0]));
        }

        // 3. If there is a "Draft:" label before the final message, strip it
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
     * Execute cURL HTTP POST.
     */
    protected function http_post(string $url, array $payload, array $headers): ?array
    {
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
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            curl_close($curl);

            if ($error) {
                log_message('error', "Ai_llm_gateway cURL error on {$url}: " . $error);
                return null;
            }

            if ($http_code < 200 || $http_code >= 300) {
                log_message('error', "Ai_llm_gateway HTTP error {$http_code} on {$url}: " . $response);
                return null;
            }

            $decoded = json_decode($response, true);
            return is_array($decoded) ? $decoded : null;
        } catch (Throwable $e) {
            log_message('error', "Ai_llm_gateway exception on {$url}: " . $e->getMessage());
            return null;
        }
    }
}

