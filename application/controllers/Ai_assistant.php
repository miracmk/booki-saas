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

            // Check if OpenAI API key is configured
            $openai_api_key = getenv('OPENAI_API_KEY');
            if (empty($openai_api_key)) {
                // Silent failure — return "not configured" response without raising error
                json_response([
                    'success' => false,
                    'error' => 'not_configured',
                    'message' => 'OpenAI API is not configured. Voice transcription is not available.',
                ], 200);
                return;
            }

            // Create a temporary copy of the uploaded file for API submission
            $temp_file = $file['tmp_name'];

            // Send file to OpenAI Whisper API
            $transcription = $this->call_openai_whisper($temp_file, $openai_api_key);

            json_response([
                'success' => true,
                'text' => $transcription,
                'message' => 'Audio transcribed successfully. AI Assistant will soon process this request.',
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Call OpenAI Whisper API to transcribe audio.
     *
     * @param string $file_path Path to the audio file.
     * @param string $api_key OpenAI API key.
     *
     * @return string Transcribed text.
     *
     * @throws RuntimeException
     */
    private function call_openai_whisper(string $file_path, string $api_key): string
    {
        $api_url = 'https://api.openai.com/v1/audio/transcriptions';

        // Initialize cURL
        $curl = curl_init();

        // Prepare the file for multipart upload
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
            throw new RuntimeException('cURL error: ' . $error);
        }

        if ($http_code !== 200) {
            $response_data = json_decode($response, true);
            $error_message = $response_data['error']['message'] ?? 'Unknown error from OpenAI API';
            throw new RuntimeException('OpenAI API error (HTTP ' . $http_code . '): ' . $error_message);
        }

        $response_data = json_decode($response, true);

        if (!isset($response_data['text'])) {
            throw new RuntimeException('Unexpected response from OpenAI API: missing "text" field.');
        }

        return $response_data['text'];
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
        } catch (Throwable) {
            return false;
        }
    }
}
