<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Online Appointment Scheduler
 *
 * @package     KiReservation
 * @author      Ki Software
 * @copyright   Copyright (c) Ki Software
 * @license     Proprietary - see LICENSE file
 * @link        https://kisoftware.com
 * ---------------------------------------------------------------------------- */

/**
 * AI Agent client library (Dalga 4, 2026-09-12).
 *
 * Admin-panel-internal assistant, separate from Ai_llm_client.php (which powers
 * the PUBLIC booking-widget assistant with single-shot structured output).
 * This one runs a real tool-calling loop against an OpenAI-compatible chat
 * completions endpoint (OpenRouter by default - one API surface that fronts
 * many providers/models, matching the user's "3-4 providers talking to each
 * other" long-term direction; swapping providers later is a config change,
 * not a rewrite of this class).
 *
 * Trust boundary: this class NEVER writes to `users`/`customers` or any other
 * business table directly. The one write-shaped tool it exposes
 * (`propose_customer_update`) only inserts an `ai_agent_pending_changes` row;
 * an admin must explicitly approve it (Ai_agent::approve()) before
 * Customers_model::save() is ever called. Read tools are free to call.
 *
 * @package Libraries
 */
class Ai_agent_client
{
    /** Hard cap on tool-call round trips per turn - stops a runaway loop from
     * burning API budget if a model keeps calling tools without ever answering. */
    private const MAX_TOOL_ITERATIONS = 6;

    private const TOOLS = [
        [
            'type' => 'function',
            'function' => [
                'name' => 'search_customers',
                'description' => 'Search customers by name, phone number, or email keyword. Returns up to 10 matches.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'keyword' => ['type' => 'string', 'description' => 'Search keyword (name, phone, or email fragment).'],
                    ],
                    'required' => ['keyword'],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_customer',
                'description' => 'Get full profile details for one customer by ID.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'customer_id' => ['type' => 'integer'],
                    ],
                    'required' => ['customer_id'],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_customer_appointments',
                'description' => 'Get the appointment history (past and upcoming) for one customer by ID.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'customer_id' => ['type' => 'integer'],
                    ],
                    'required' => ['customer_id'],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'propose_customer_update',
                'description' => 'Propose a change to a customer record (e.g. corrected phone number, email, or name). '
                    . 'This does NOT apply the change - it queues it for a human admin to approve or reject. '
                    . 'Always explain the change to the user as "queued for approval", never as "done".',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'customer_id' => ['type' => 'integer'],
                        'changes' => [
                            'type' => 'object',
                            'description' => 'Map of field name to new value, e.g. {"phone_number": "5551234567"}. '
                                . 'Only first_name, last_name, email, phone_number, address, notes are allowed.',
                        ],
                        'reason' => ['type' => 'string', 'description' => 'Why this change is being proposed, shown to the approving admin.'],
                    ],
                    'required' => ['customer_id', 'changes', 'reason'],
                ],
            ],
        ],
    ];

    private const ALLOWED_UPDATE_FIELDS = ['first_name', 'last_name', 'email', 'phone_number', 'address', 'notes'];

    /**
     * Run one chat turn: send the conversation to the model, execute any tool
     * calls it makes, feed the results back, and repeat until it produces a
     * final plain-text answer (or the iteration cap is hit).
     *
     * @param array $messages Conversation history: [['role' => 'user'|'assistant'|'tool', 'content' => string, ...], ...]
     *
     * @return array ['reply' => string, 'tool_calls' => array] - tool_calls is a transparency log of what the
     *   agent looked up/proposed this turn (for display in the UI), never used for control flow by the caller.
     */
    public function chat(array $messages): array
    {
        $api_key = getenv('OPENROUTER_API_KEY');

        if (empty($api_key)) {
            return [
                'reply' => 'AI Asistan henüz yapılandırılmadı: OPENROUTER_API_KEY tanımlı değil.',
                'tool_calls' => [],
            ];
        }

        $model = getenv('AI_AGENT_MODEL') ?: 'openrouter/free';

        $conversation = array_merge([
            ['role' => 'system', 'content' => $this->build_system_prompt()],
        ], $messages);

        $tool_call_log = [];

        for ($i = 0; $i < self::MAX_TOOL_ITERATIONS; $i++) {
            $response = $this->call_openrouter($conversation, $model, $api_key);

            if ($response === null) {
                return [
                    'reply' => 'Üzgünüm, AI Asistan şu anda yanıt veremiyor. Lütfen daha sonra tekrar deneyin.',
                    'tool_calls' => $tool_call_log,
                ];
            }

            $message = $response['choices'][0]['message'] ?? null;

            if ($message === null) {
                return ['reply' => 'Üzgünüm, beklenmeyen bir yanıt alındı.', 'tool_calls' => $tool_call_log];
            }

            $tool_calls = $message['tool_calls'] ?? [];

            if (empty($tool_calls)) {
                return [
                    'reply' => (string) ($message['content'] ?? ''),
                    'tool_calls' => $tool_call_log,
                ];
            }

            // Model wants to call tools - append its own tool-call message, then one
            // 'tool' role message per call with the result, and loop back.
            $conversation[] = $message;

            foreach ($tool_calls as $call) {
                $name = $call['function']['name'] ?? '';
                $args = json_decode($call['function']['arguments'] ?? '{}', true) ?: [];

                $result = $this->execute_tool($name, $args);

                $tool_call_log[] = ['name' => $name, 'args' => $args, 'result' => $result];

                $conversation[] = [
                    'role' => 'tool',
                    'tool_call_id' => $call['id'] ?? '',
                    'content' => json_encode($result, JSON_UNESCAPED_UNICODE),
                ];
            }
        }

        return [
            'reply' => 'Bu istek çok fazla adım gerektirdi, lütfen daha basit bir soru olarak tekrar deneyin.',
            'tool_calls' => $tool_call_log,
        ];
    }

    /**
     * Dispatch one tool call by name. Unknown tools/invalid args return a
     * structured error the model can react to, rather than throwing.
     */
    private function execute_tool(string $name, array $args): array
    {
        /** @var EA_Controller $CI */
        $CI = &get_instance();
        $CI->load->model('customers_model');
        $CI->load->model('appointments_model');

        try {
            switch ($name) {
                case 'search_customers':
                    $keyword = (string) ($args['keyword'] ?? '');

                    if ($keyword === '') {
                        return ['error' => 'keyword is required'];
                    }

                    $results = $CI->customers_model->search($keyword, 10);

                    return array_map(static fn (array $c) => [
                        'id' => $c['id'],
                        'first_name' => $c['first_name'] ?? null,
                        'last_name' => $c['last_name'] ?? null,
                        'phone_number' => $c['phone_number'] ?? null,
                        'email' => $c['email'] ?? null,
                    ], $results);

                case 'get_customer':
                    $customer_id = (int) ($args['customer_id'] ?? 0);
                    $customer = $CI->customers_model->find($customer_id);

                    return [
                        'id' => $customer['id'],
                        'first_name' => $customer['first_name'] ?? null,
                        'last_name' => $customer['last_name'] ?? null,
                        'phone_number' => $customer['phone_number'] ?? null,
                        'email' => $customer['email'] ?? null,
                        'address' => $customer['address'] ?? null,
                        'notes' => $customer['notes'] ?? null,
                    ];

                case 'get_customer_appointments':
                    $customer_id = (int) ($args['customer_id'] ?? 0);
                    $appointments = $CI->appointments_model->get_for_customer($customer_id);

                    return array_map(static fn (array $a) => [
                        'id' => $a['id'],
                        'start_datetime' => $a['start_datetime'] ?? null,
                        'end_datetime' => $a['end_datetime'] ?? null,
                        'status' => $a['status'] ?? null,
                    ], $appointments);

                case 'propose_customer_update':
                    return $this->propose_customer_update($CI, $args);

                default:
                    return ['error' => 'unknown tool: ' . $name];
            }
        } catch (Throwable $e) {
            log_message('error', 'Ai_agent_client tool ' . $name . ' failed: ' . $e->getMessage());

            return ['error' => $e->getMessage()];
        }
    }

    /**
     * @param EA_Controller $CI
     */
    private function propose_customer_update($CI, array $args): array
    {
        $customer_id = (int) ($args['customer_id'] ?? 0);
        $changes = (array) ($args['changes'] ?? []);
        $reason = (string) ($args['reason'] ?? '');

        if (empty($customer_id) || empty($changes)) {
            return ['error' => 'customer_id and changes are required'];
        }

        // Confirm the customer exists (throws if not) and drop any field the model
        // tried to sneak in that isn't on the allowlist.
        $CI->customers_model->find($customer_id);

        $filtered = array_intersect_key($changes, array_flip(self::ALLOWED_UPDATE_FIELDS));

        if (empty($filtered)) {
            return ['error' => 'no allowed fields in changes (allowed: ' . implode(', ', self::ALLOWED_UPDATE_FIELDS) . ')'];
        }

        $model = getenv('AI_AGENT_MODEL') ?: 'openrouter/free';

        $CI->db->insert('ai_agent_pending_changes', [
            'target_table' => 'users',
            'target_id' => $customer_id,
            'changes' => json_encode($filtered, JSON_UNESCAPED_UNICODE),
            'reason' => $reason,
            'model_name' => $model,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return [
            'queued' => true,
            'pending_id' => $CI->db->insert_id(),
            'note' => 'Change queued for admin approval - not yet applied.',
        ];
    }

    private function build_system_prompt(): string
    {
        return <<<PROMPT
Sen BooKi admin panelinde çalışan bir iç asistansın. Personel (admin/sekreter) sana müşteri
kayıtları ve randevu geçmişi hakkında küçük sorular sorar.

KURALLAR:
1. Her zaman TÜRKÇE yanıt ver, kısa ve net ol.
2. Müşteri bulmak/bilgi okumak için search_customers, get_customer, get_customer_appointments araçlarını kullan.
3. Bir müşteri kaydını DEĞİŞTİRMEN istenirse asla doğrudan "değiştirdim" deme - sadece propose_customer_update
   aracını çağır ve kullanıcıya bunun bir admin onayı beklediğini söyle. Sen hiçbir zaman veriyi kalıcı olarak
   değiştiremezsin, sadece öneri kuyruğuna eklersin.
4. Elinde olmayan bilgiyi UYDURMA - bilmiyorsan söyle veya ilgili aracı çağır.
5. Kişisel/hassas veriyi (telefon, email, adres) gereksiz yere tekrar etme; sadece soruyla doğrudan ilgiliyse paylaş.
PROMPT;
    }

    /**
     * OpenAI-compatible chat completions call (works against OpenRouter's API,
     * which itself proxies many providers/models under this one schema).
     */
    private function call_openrouter(array $messages, string $model, string $api_key): ?array
    {
        try {
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://openrouter.ai/api/v1/chat/completions',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 45,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'model' => $model,
                    'messages' => $messages,
                    'tools' => self::TOOLS,
                    'temperature' => 0.3,
                    'max_tokens' => 1024,
                ], JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $api_key,
                    'Content-Type: application/json',
                    // OpenRouter uses these for its (optional) app leaderboard - harmless to include.
                    'HTTP-Referer: https://' . (getenv('TENANT_APP_DOMAIN') ?: 'reservationapp.kibusiness.co'),
                    'X-Title: BooKi AI Asistan',
                ],
            ]);

            $response = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);

            curl_close($curl);

            if ($error) {
                log_message('error', 'Ai_agent_client cURL error: ' . $error);

                return null;
            }

            if ($http_code !== 200) {
                log_message('error', 'Ai_agent_client OpenRouter error (HTTP ' . $http_code . '): ' . $response);

                return null;
            }

            $data = json_decode($response, true);

            return is_array($data) ? $data : null;
        } catch (Throwable $e) {
            log_message('error', 'Ai_agent_client OpenRouter call failed: ' . $e->getMessage());

            return null;
        }
    }
}
