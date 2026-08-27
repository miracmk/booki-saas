<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Online Appointment Scheduler
 *
 * @package     KiReservation
 * @author      Ki Software
 * @copyright   Copyright (c) Ki Software
 * @license     Proprietary - see LICENSE file
 * @link        https://kisoftware.com
 * ---------------------------------------------------------------------------- */

/**
 * AI LLM Client library.
 *
 * Handles communication with LLM providers (Gemini and Groq) for conversational
 * appointment booking assistance. Provides a unified interface with automatic
 * fallback between providers.
 *
 * @package Libraries
 */
class Ai_llm_client
{
    /**
     * Chat with LLM to process appointment requests.
     *
     * Accepts a conversation history and system context (available services, providers, etc.),
     * then communicates with an LLM (Gemini preferred, Groq fallback) to generate either a
     * conversational reply or structured appointment creation data.
     *
     * Security: This method does NOT validate the returned appointment data. The caller must
     * use Appointment_booking_service::create() which re-validates everything against the database.
     *
     * @param array $messages Conversation history: [['role' => 'user'|'assistant', 'content' => string], ...]
     * @param array $context Context for the LLM: ['services' => [...], 'providers' => [...], 'today' => 'Y-m-d']
     *
     * @return array One of:
     *   - ['type' => 'reply', 'text' => string] for conversational responses
     *   - ['type' => 'create_appointment', 'service_id' => int, 'provider_id' => int, 'date' => string, 'time' => string, 'customer' => [...]]
     *   - ['type' => 'error', 'text' => string] if both providers fail
     */
    public function chat(array $messages, array $context): array
    {
        try {
            // Try Gemini first
            $gemini_api_key = getenv('GEMINI_API_KEY');

            if (!empty($gemini_api_key)) {
                $result = $this->call_gemini($messages, $context, $gemini_api_key);

                if ($result !== null) {
                    return $result;
                }
            }

            // Fallback to Groq
            $groq_api_key = getenv('GROQ_API_KEY');

            if (!empty($groq_api_key)) {
                $result = $this->call_groq($messages, $context, $groq_api_key);

                if ($result !== null) {
                    return $result;
                }
            }

            // Neither provider available
            return [
                'type' => 'reply',
                'text' => 'Üzgünüm, AI Asistan şu anda kullanılamıyor. Lütfen daha sonra tekrar deneyin.',
            ];
        } catch (Throwable $e) {
            log_message('error', 'Ai_llm_client::chat - ' . $e->getMessage());

            return [
                'type' => 'reply',
                'text' => 'Üzgünüm, isteğinizi işlerken bir hata oluştu. Lütfen tekrar deneyin.',
            ];
        }
    }

    /**
     * Call Google Gemini API for chat completion.
     *
     * @param array $messages Conversation history
     * @param array $context Context for the LLM
     * @param string $api_key Gemini API key
     *
     * @return array|null Response if successful, null if failed (for fallback)
     */
    private function call_gemini(array $messages, array $context, string $api_key): ?array
    {
        try {
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=' . urlencode($api_key);

            $system_prompt = $this->build_system_prompt($context);

            // Build Gemini request
            $request_body = [
                'system' => [
                    [
                        'text' => $system_prompt,
                    ],
                ],
                'contents' => $this->convert_messages_to_gemini_format($messages),
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'responseSchema' => [
                        'type' => 'object',
                        'properties' => [
                            'type' => [
                                'type' => 'string',
                                'enum' => ['reply', 'create_appointment'],
                            ],
                            'text' => [
                                'type' => 'string',
                            ],
                            'service_id' => [
                                'type' => 'integer',
                            ],
                            'provider_id' => [
                                'type' => 'integer',
                            ],
                            'date' => [
                                'type' => 'string',
                            ],
                            'time' => [
                                'type' => 'string',
                            ],
                            'customer' => [
                                'type' => 'object',
                                'properties' => [
                                    'first_name' => ['type' => 'string'],
                                    'last_name' => ['type' => 'string'],
                                    'email' => ['type' => 'string'],
                                    'phone' => ['type' => 'string'],
                                ],
                            ],
                        ],
                        'required' => ['type'],
                    ],
                ],
            ];

            $response = $this->make_curl_request($url, $request_body);

            if ($response === null) {
                return null;
            }

            // Parse Gemini response
            $data = json_decode($response, true);

            if (empty($data['candidates'][0]['content']['parts'][0]['text'])) {
                log_message('error', 'Gemini API returned empty response');

                return null;
            }

            $text = $data['candidates'][0]['content']['parts'][0]['text'];
            $parsed = json_decode($text, true);

            if (empty($parsed) || empty($parsed['type'])) {
                log_message('error', 'Gemini API returned invalid JSON: ' . $text);

                return null;
            }

            return $parsed;
        } catch (Throwable $e) {
            log_message('error', 'Gemini API call failed: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Call Groq API for chat completion (Llama model).
     *
     * @param array $messages Conversation history
     * @param array $context Context for the LLM
     * @param string $api_key Groq API key
     *
     * @return array|null Response if successful, null if failed (for fallback)
     */
    private function call_groq(array $messages, array $context, string $api_key): ?array
    {
        try {
            $url = 'https://api.groq.com/openai/v1/chat/completions';

            $system_prompt = $this->build_system_prompt($context);

            // Append JSON schema instructions to system prompt
            $system_prompt .= "\n\nYou MUST respond with a JSON object. Do NOT include any text before or after the JSON.\n"
                . "Valid response formats:\n"
                . "1. {\"type\": \"reply\", \"text\": \"Your message in Turkish\"}\n"
                . "2. {\"type\": \"create_appointment\", \"service_id\": 123, \"provider_id\": 456, \"date\": \"Y-m-d\", "
                . "\"time\": \"H:i\", \"customer\": {\"first_name\": \"...\", \"last_name\": \"...\", \"email\": \"...\", \"phone\": \"...\"}}\n"
                . "Always use the first format for general replies. Only use the second format when the user clearly wants to book an appointment "
                . "and you have extracted all required information.";

            // Convert messages to OpenAI format
            $groq_messages = [];

            foreach ($messages as $msg) {
                $groq_messages[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content'],
                ];
            }

            // Build Groq request
            $request_body = [
                'model' => 'openai/gpt-oss-120b',
                'messages' => array_merge(
                    [
                        [
                            'role' => 'system',
                            'content' => $system_prompt,
                        ],
                    ],
                    $groq_messages,
                ),
                'response_format' => [
                    'type' => 'json_object',
                ],
                'temperature' => 0.7,
                'max_tokens' => 1024,
            ];

            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($request_body),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $api_key,
                    'Content-Type: application/json',
                ],
            ]);

            $response = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);

            curl_close($curl);

            if ($error) {
                log_message('error', 'Groq cURL error: ' . $error);

                return null;
            }

            if ($http_code !== 200) {
                log_message('error', 'Groq API error (HTTP ' . $http_code . '): ' . $response);

                return null;
            }

            $data = json_decode($response, true);

            if (empty($data['choices'][0]['message']['content'])) {
                log_message('error', 'Groq API returned empty response');

                return null;
            }

            $text = $data['choices'][0]['message']['content'];
            $parsed = json_decode($text, true);

            if (empty($parsed) || empty($parsed['type'])) {
                log_message('error', 'Groq API returned invalid JSON: ' . $text);

                // Fallback: return safe error reply
                return [
                    'type' => 'reply',
                    'text' => 'Üzgünüm, isteğinizi anlayamadım. Lütfen tekrar eder misiniz?',
                ];
            }

            return $parsed;
        } catch (Throwable $e) {
            log_message('error', 'Groq API call failed: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Build system prompt for the LLM.
     *
     * @param array $context Context including services, providers, and current date
     *
     * @return string System prompt in Turkish
     */
    private function build_system_prompt(array $context): string
    {
        $today = $context['today'] ?? date('Y-m-d');
        $services = $context['services'] ?? [];
        $providers = $context['providers'] ?? [];

        $services_json = json_encode($services, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $providers_json = json_encode($providers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Sen bir randevu sistemi asistanısın. Kullanıcılara hizmetleri seçmelerine, sağlayıcı seçmelerine ve randevu saati ayarlamalarına yardımcı ol.

Bugünün tarihi: {$today}

Mevcut Hizmetler (JSON):
{$services_json}

Mevcut Sağlayıcılar (JSON):
{$providers_json}

KURALLAR:
1. Her zaman TÜRKÇE yanıt ver
2. Hizmet seçerken sadece yukarıdaki listede yer alan hizmetleri öner
3. Sağlayıcı seçerken sadece yukarıdaki listede yer alan sağlayıcıları öner
4. Tarih önerileri bugünden sonra olmalı
5. Randevu saati seçerken mantıklı iş saatleri öner (örn. 09:00 - 18:00)
6. Müşteri bilgilerini (ad, soyadı, email, telefon) toplama sürecinde dışarıda çıkma

Randevu oluşturma seçeneği için SADECE müşteri tüm gerekli bilgileri (ad, soyadı, email, telefon) verdiğinde ve hizmet + sağlayıcı + tarih + saat açıkça seçildiğinde "create_appointment" formatını kullan.

Bunun dışında her zaman "reply" formatını kullan ve konuşmaya devam et.
PROMPT;
    }

    /**
     * Convert CI message format to Gemini format.
     *
     * @param array $messages Messages in [['role' => 'user'|'assistant', 'content' => string], ...] format
     *
     * @return array Gemini format contents
     */
    private function convert_messages_to_gemini_format(array $messages): array
    {
        $contents = [];

        foreach ($messages as $msg) {
            $contents[] = [
                'role' => $msg['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [
                    [
                        'text' => $msg['content'],
                    ],
                ],
            ];
        }

        return $contents;
    }

    /**
     * Make a cURL request to an API endpoint.
     *
     * @param string $url API endpoint URL
     * @param array $body Request body to be JSON-encoded
     *
     * @return string|null Response body if successful, null if failed
     */
    private function make_curl_request(string $url, array $body): ?string
    {
        try {
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($body),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                ],
            ]);

            $response = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);

            curl_close($curl);

            if ($error) {
                log_message('error', 'cURL error: ' . $error);

                return null;
            }

            if ($http_code !== 200) {
                log_message('error', 'API error (HTTP ' . $http_code . ')');

                return null;
            }

            return $response;
        } catch (Throwable $e) {
            log_message('error', 'cURL request failed: ' . $e->getMessage());

            return null;
        }
    }
}
