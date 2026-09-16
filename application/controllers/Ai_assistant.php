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
 * AI Assistant controller.
 *
 * Handles AI-powered features like voice transcription and scheduling assistance.
 * This is a skeleton implementation — actual LLM integration and booking automation
 * are not yet implemented.
 *
 * @package Controllers
 */
class Ai_assistant extends EA_Controller
{
    /**
     * Ai_assistant constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('settings_model');
        $this->load->model('services_model');
        $this->load->model('providers_model');
        $this->load->library('ai_llm_client');
        $this->load->library('appointment_booking_service');
    }

    /**
     * Get AI Assistant status and configuration.
     *
     * Returns the current AI Assistant status. This endpoint can be used to check
     * if the feature is enabled and available.
     */
    public function index(): void
    {
        try {
            method('get');

            $ai_enabled = $this->get_ai_assistant_enabled();

            json_response([
                'status' => 'coming_soon',
                'message' => 'AI Assistant will soon be active for automated booking assistance',
                'enabled' => $ai_enabled,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Transcribe audio to text using OpenAI Whisper API.
     *
     * Accepts a multipart audio file upload and transcribes it to text.
     * If OPENAI_API_KEY is not configured, returns a graceful "not_configured" response
     * instead of raising an error.
     *
     * Supported formats: mp3, wav, m4a, ogg
     * Maximum file size: 10MB
     */
    public function transcribe(): void
    {
        try {
            method('post');

            // Check if audio file was uploaded
            if (empty($_FILES['audio'])) {
                throw new InvalidArgumentException('No audio file provided.');
            }

            $file = $_FILES['audio'];

            // Validate file size (max 10MB)
            $max_size = 10 * 1024 * 1024; // 10MB in bytes
            if ($file['size'] > $max_size) {
                throw new InvalidArgumentException('Audio file exceeds maximum size of 10MB.');
            }

            // Validate file format
            $allowed_formats = ['mp3', 'wav', 'm4a', 'ogg'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($file_ext, $allowed_formats)) {
                throw new InvalidArgumentException('Unsupported audio format. Allowed: ' . implode(', ', $allowed_formats));
            }

            // Try Groq Whisper first, then fall back to OpenAI
            $temp_file = $file['tmp_name'];
            $transcription = null;
            $used_provider = null;

            // Try Groq first (usually faster for Whisper)
            $groq_api_key = getenv('GROQ_API_KEY');
            if (!empty($groq_api_key)) {
                $transcription = $this->call_groq_whisper($temp_file, $groq_api_key);
                if ($transcription !== null) {
                    $used_provider = 'groq';
                }
            }

            // Fallback to OpenAI if Groq unavailable or failed
            if ($transcription === null) {
                $openai_api_key = getenv('OPENAI_API_KEY');
                if (!empty($openai_api_key)) {
                    $transcription = $this->call_openai_whisper($temp_file, $openai_api_key);
                    if ($transcription !== null) {
                        $used_provider = 'openai';
                    }
                }
            }

            // No transcription provider available
            if ($transcription === null) {
                json_response([
                    'success' => false,
                    'error' => 'not_configured',
                    'message' => 'Speech transcription is not configured. Please try again later.',
                ], 200);
                return;
            }

            json_response([
                'success' => true,
                'text' => $transcription,
                'message' => 'Audio transcribed successfully.',
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Chat with the AI Assistant.
     *
     * Accepts a user message and optional conversation history, then communicates
     * with an LLM to generate either a conversational reply or structured appointment
     * creation data. The session maintains the chat history.
     *
     * @return void Outputs JSON response
     */
    public function chat(): void
    {
        try {
            method('post');

            check('message', 'string');

            $message = request('message');

            if (empty($message)) {
                throw new InvalidArgumentException('Message cannot be empty.');
            }

            // Load existing chat history from session (client-supplied history is never trusted -
            // the session is the only source of truth so a caller can't inject fake prior turns).
            $chat_history = $this->session->userdata('ai_chat_history') ?? [];

            // Append new user message
            $chat_history[] = [
                'role' => 'user',
                'content' => $message,
            ];

            // Cap the history sent to the LLM so a long conversation doesn't grow the request
            // payload/cost/latency without bound or eventually exceed the model's context window.
            $max_history_messages = 20;
            if (count($chat_history) > $max_history_messages) {
                $chat_history = array_slice($chat_history, -$max_history_messages);
            }

            // Build context for LLM. Providers are reduced to id/name/services only - the LLM only
            // needs this to pick a provider, and get_available_providers() otherwise returns decrypted
            // staff PII (email, phone, address, notes, ...) that must never be sent to a third-party API.
            $providers_for_llm = array_map(
                static fn(array $provider): array => [
                    'id' => $provider['id'],
                    'first_name' => $provider['first_name'] ?? '',
                    'last_name' => $provider['last_name'] ?? '',
                    'services' => $provider['services'] ?? [],
                ],
                $this->providers_model->get_available_providers(true),
            );

            $context = [
                'services' => $this->services_model->get_available_services(true),
                'providers' => $providers_for_llm,
                'today' => date('Y-m-d'),
            ];

            // Get LLM response
            $llm_response = $this->ai_llm_client->chat($chat_history, $context);

            // Defensive validation: the LLM's declared schema only strictly requires "type" (see
            // Ai_llm_client::call_gemini()), so a minimal but schema-valid reply like
            // {"type":"create_appointment"} is possible. Treat an incomplete create_appointment
            // payload as an invalid response rather than passing null/missing fields down into
            // Appointment_booking_service::create(), which expects well-formed arrays.
            if ($llm_response['type'] === 'create_appointment' && !$this->is_valid_create_appointment_response($llm_response)) {
                log_message('error', 'Ai_assistant::chat - LLM returned incomplete create_appointment payload: ' . json_encode($llm_response));

                $llm_response = [
                    'type' => 'reply',
                    'text' => 'Randevunuzu oluşturabilmem için hizmet, sağlayıcı, tarih/saat ve iletişim bilgilerinizin hepsine ihtiyacım var. Bu bilgileri tekrar paylaşır mısınız?',
                ];
            }

            // Handle LLM response
            if ($llm_response['type'] === 'reply') {
                // Regular conversational reply
                $chat_history[] = [
                    'role' => 'assistant',
                    'content' => $llm_response['text'],
                ];

                $this->session->set_userdata('ai_chat_history', $chat_history);

                json_response([
                    'type' => 'reply',
                    'text' => $llm_response['text'],
                ]);
            } elseif ($llm_response['type'] === 'create_appointment') {
                // LLM wants to create an appointment
                $appointment_data = [
                    'id_services' => $llm_response['service_id'],
                    'id_users_provider' => $llm_response['provider_id'],
                    'start_datetime' => $llm_response['date'] . ' ' . $llm_response['time'] . ':00',
                ];

                // Map the LLM's "phone" field to the phone_number key Customers_model/
                // Appointment_booking_service actually expect.
                $customer_data = $llm_response['customer'];
                if (isset($customer_data['phone']) && !isset($customer_data['phone_number'])) {
                    $customer_data['phone_number'] = $customer_data['phone'];
                    unset($customer_data['phone']);
                }

                // Use the booking service to create the appointment (with full validation)
                $booking_result = $this->appointment_booking_service->create($appointment_data, $customer_data);

                if ($booking_result['success']) {
                    // Clear chat history on successful booking
                    $this->session->unset_userdata('ai_chat_history');

                    $response = [
                        'type' => 'create_appointment',
                        'success' => true,
                        'message' => 'Randevunuz başarıyla oluşturuldu! Randevu numaranız: ' . $booking_result['appointment_id'],
                        'appointment_id' => $booking_result['appointment_id'],
                    ];

                    // Add payment info if required
                    if (!empty($booking_result['payment_required']) && !empty($booking_result['payment_intent'])) {
                        $response['payment_required'] = true;
                        $response['payment_intent'] = $booking_result['payment_intent'];
                    }

                    json_response($response);
                } else {
                    // Booking failed — return error to LLM for context
                    $error_message = $booking_result['message'] ?? 'Randevu oluşturulamadı.';

                    $assistant_response = 'Randevu oluşturulurken hata oluştu: ' . $error_message . ' Lütfen farklı bir saat seçin veya daha sonra tekrar deneyin.';

                    $chat_history[] = [
                        'role' => 'assistant',
                        'content' => $assistant_response,
                    ];

                    $this->session->set_userdata('ai_chat_history', $chat_history);

                    json_response([
                        'type' => 'reply',
                        'text' => $assistant_response,
                        'error' => $booking_result['error'],
                    ]);
                }
            } else {
                // Unknown response type (defensive)
                $reply = 'Üzgünüm, isteğinizi işlerken bir hata oluştu.';

                $chat_history[] = [
                    'role' => 'assistant',
                    'content' => $reply,
                ];

                $this->session->set_userdata('ai_chat_history', $chat_history);

                json_response([
                    'type' => 'reply',
                    'text' => $reply,
                ]);
            }
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Check that a "create_appointment" LLM response carries every field
     * Appointment_booking_service::create() requires, before it is trusted with that data.
     *
     * @param array $response LLM response with type === 'create_appointment'.
     *
     * @return bool
     */
    private function is_valid_create_appointment_response(array $response): bool
    {
        foreach (['service_id', 'provider_id', 'date', 'time', 'customer'] as $key) {
            if (empty($response[$key])) {
                return false;
            }
        }

        if (!is_array($response['customer'])) {
            return false;
        }

        foreach (['first_name', 'last_name', 'email'] as $key) {
            if (empty($response['customer'][$key])) {
                return false;
            }
        }

        // Either key name is accepted here - Ai_assistant::chat() normalizes "phone" to
        // "phone_number" further down before calling Appointment_booking_service::create().
        if (empty($response['customer']['phone']) && empty($response['customer']['phone_number'])) {
            return false;
        }

        return true;
    }

    /**
     * Call Groq Whisper API to transcribe audio.
     *
     * @param string $file_path Path to the audio file.
     * @param string $api_key Groq API key.
     *
     * @return string|null Transcribed text, or null if failed.
     */
    private function call_groq_whisper(string $file_path, string $api_key): ?string
    {
        try {
            $api_url = 'https://api.groq.com/openai/v1/audio/transcriptions';

            $curl = curl_init();

            $cfile = curl_file_create($file_path, 'audio/mpeg', basename($file_path));

            curl_setopt_array($curl, [
                CURLOPT_URL => $api_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => [
                    'file' => $cfile,
                    'model' => 'whisper-large-v3-turbo',
                    'language' => 'tr',
                ],
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $api_key,
                ],
            ]);

            $response = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);

            curl_close($curl);

            if ($error) {
                log_message('error', 'Groq Whisper cURL error: ' . $error);

                return null;
            }

            if ($http_code !== 200) {
                $response_data = json_decode($response, true);
                $error_message = $response_data['error']['message'] ?? 'Unknown error';
                log_message('error', 'Groq Whisper API error (HTTP ' . $http_code . '): ' . $error_message);

                return null;
            }

            $response_data = json_decode($response, true);

            if (!isset($response_data['text'])) {
                log_message('error', 'Groq Whisper: missing "text" field in response');

                return null;
            }

            return $response_data['text'];
        } catch (Throwable $e) {
            log_message('error', 'Groq Whisper call failed: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Call OpenAI Whisper API to transcribe audio.
     *
     * @param string $file_path Path to the audio file.
     * @param string $api_key OpenAI API key.
     *
     * @return string|null Transcribed text, or null if failed.
     */
    private function call_openai_whisper(string $file_path, string $api_key): ?string
    {
        try {
            $api_url = 'https://api.openai.com/v1/audio/transcriptions';

            $curl = curl_init();

            $cfile = curl_file_create($file_path, 'audio/mpeg', basename($file_path));

            curl_setopt_array($curl, [
                CURLOPT_URL => $api_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => [
                    'file' => $cfile,
                    'model' => 'whisper-1',
                ],
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $api_key,
                ],
            ]);

            $response = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);

            curl_close($curl);

            if ($error) {
                log_message('error', 'OpenAI Whisper cURL error: ' . $error);

                return null;
            }

            if ($http_code !== 200) {
                $response_data = json_decode($response, true);
                $error_message = $response_data['error']['message'] ?? 'Unknown error';
                log_message('error', 'OpenAI Whisper API error (HTTP ' . $http_code . '): ' . $error_message);

                return null;
            }

            $response_data = json_decode($response, true);

            if (!isset($response_data['text'])) {
                log_message('error', 'OpenAI Whisper: missing "text" field in response');

                return null;
            }

            return $response_data['text'];
        } catch (Throwable $e) {
            log_message('error', 'OpenAI Whisper call failed: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Check if AI Assistant is enabled in settings.
     *
     * @return bool True if AI Assistant is enabled, false otherwise.
     */
    private function get_ai_assistant_enabled(): bool
    {
        try {
            $setting = $this->settings_model->query()
                ->where('name', 'ai_assistant_enabled')
                ->get()
                ->row_array();

            if (empty($setting)) {
                return false;
            }

            return (bool) $setting['value'];
        } catch (Throwable $e) {
            log_message('error', 'ai_assistant_enabled setting read failed: ' . $e->getMessage());

            return false;
        }
    }
}
