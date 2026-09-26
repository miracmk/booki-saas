<?php use JetBrains\PhpStorm\NoReturn;

defined('BASEPATH') or exit('No direct script access allowed');

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
 * Api library.
 *
 * Handles API related functionality.
 *
 * @package Libraries
 */
class Api
{
    /**
     * @var App_Controller|CI_Controller
     */
    protected App_Controller|CI_Controller $CI;

    /**
     * @var int
     */
    protected int $default_length = 20;

    /**
     * @var App_Model
     */
    protected App_Model $model;

    /**
     * Api constructor.
     */
    public function __construct()
    {
        $this->CI = &get_instance();

        $this->CI->load->library('accounts');
    }

    /**
     * Load and use the provided model class.
     *
     * @param string $model
     */
    public function model(string $model): void
    {
        $this->CI->load->model($model);

        $this->model = $this->CI->{$model};
    }

    /**
     * Authorize the API request (Basic Auth, Static Bearer Token, or User JWT Token supported).
     */
    public function auth(): void
    {
        try {
            // Bearer token.
            $provided_token = $this->get_bearer_token();

            if (!empty($provided_token)) {
                // 1. Static API token check
                $api_token = setting('api_token');
                if (!empty($api_token) && hash_equals($api_token, $provided_token)) {
                    return;
                }

                // 2. User JWT token check (Mobile App Auth)
                if ($this->validate_user_token($provided_token)) {
                    return;
                }
            }

            // Basic auth.
            $username = $_SERVER['PHP_AUTH_USER'] ?? null;

            $password = $_SERVER['PHP_AUTH_PW'] ?? null;

            if (empty($username) || empty($password)) {
                throw new RuntimeException('Missing required credentials', 401);
            }

            $user_data = $this->CI->accounts->check_login($username, $password);

            if (empty($user_data['role_slug']) || $user_data['role_slug'] !== DB_SLUG_ADMIN) {
                throw new RuntimeException('The provided credentials do not match any admin user', 401);
            }
        } catch (Throwable) {
            $this->request_authentication();
        }
    }

    /**
     * Validate a mobile user JWT token and establish session.
     */
    public function validate_user_token(string $jwt): bool
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return false;
        }

        [$header64, $payload64, $sig64] = $parts;
        $secret = config_item('encryption_key') ?: 'booki-secret-jwt-key-2026';
        $expected_sig = hash_hmac('sha256', "$header64.$payload64", $secret, true);
        $expected_sig64 = rtrim(strtr(base64_encode($expected_sig), '+/', '-_'), '=');

        if (!hash_equals($expected_sig64, $sig64)) {
            return false;
        }

        $payload_json = base64_decode(strtr($payload64, '-_', '+/'));
        $payload = json_decode($payload_json, true);

        if (empty($payload) || !isset($payload['exp']) || $payload['exp'] < time()) {
            return false;
        }

        // Establish session for downstream controllers and models
        $this->CI->session->set_userdata([
            'user_id' => $payload['user_id'],
            'role_slug' => $payload['role_slug'],
            'user_email' => $payload['email'] ?? '',
        ]);

        return true;
    }

    /**
     * Generate a signed mobile user JWT token.
     */
    public static function generate_user_token(array $payload_data): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $payload = array_merge([
            'iat' => time(),
            'exp' => time() + (86400 * 90), // 90 days
        ], $payload_data);

        $header64 = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
        $payload64 = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');

        $secret = config_item('encryption_key') ?: 'booki-secret-jwt-key-2026';
        $sig = hash_hmac('sha256', "$header64.$payload64", $secret, true);
        $sig64 = rtrim(strtr(base64_encode($sig), '+/', '-_'), '=');

        return "$header64.$payload64.$sig64";
    }

    /**
     * Returns the bearer token value.
     *
     * @return string|null
     */
    protected function get_bearer_token(): ?string
    {
        $headers = $this->get_authorization_header();

        // HEADER: Get the access token from the header

        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Returns the authorization header.
     *
     * @return string|null
     */
    protected function get_authorization_header(): ?string
    {
        $headers = null;

        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER['Authorization']);
        } else {
            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                // Nginx or fast CGI
                $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
            } elseif (function_exists('apache_request_headers')) {
                $requestHeaders = apache_request_headers();

                // Server-side fix for bug in old Android versions (a nice side effect of this fix means we don't care
                // about capitalization for Authorization).
                $requestHeaders = array_combine(
                    array_map('ucwords', array_keys($requestHeaders)),
                    array_values($requestHeaders),
                );

                if (isset($requestHeaders['Authorization'])) {
                    $headers = trim($requestHeaders['Authorization']);
                }
            }
        }

        return $headers;
    }

    /**
     * Sets request authentication headers.
     */
    #[NoReturn]
    public function request_authentication(): void
    {
        header('WWW-Authenticate: Basic realm="BooKi"');
        header('HTTP/1.0 401 Unauthorized');
        exit('You are not authorized to use the API.');
    }

    /**
     * Get the search keyword value of the current request.
     *
     * @return string|null
     */
    public function request_keyword(): ?string
    {
        return request('q');
    }

    /**
     * Get the limit value of the current request.
     *
     * @return int|null
     */
    public function request_limit(): ?int
    {
        return request('length', $this->default_length);
    }

    /**
     * Get the limit value of the current request.
     *
     * @return int|null
     */
    public function request_offset(): ?int
    {
        $page = request('page', 1);

        $length = request('length', $this->default_length);

        return ($page - 1) * $length;
    }

    /**
     * Get the order by value of the current request.
     *
     * @return string|null
     */
    public function request_order_by(): ?string
    {
        $sort = request('sort');

        if (!$sort) {
            return null;
        }

        $sort_tokens = array_map('trim', explode(',', $sort));

        $order_by = [];

        foreach ($sort_tokens as $sort_token) {
            $api_field = substr($sort_token, 1);

            $direction_operator = substr($sort_token, 0, 1);

            if (!in_array($direction_operator, ['-', '+'])) {
                $direction_operator = '+';
                $api_field = $sort_token;
            }

            $db_field = $this->model->db_field($api_field);

            // Skip invalid fields (security: only allow mapped fields)
            if ($db_field === null) {
                continue;
            }

            $direction = $direction_operator === '-' ? 'DESC' : 'ASC';

            $order_by[] = $db_field . ' ' . $direction;
        }

        return !empty($order_by) ? implode(', ', $order_by) : null;
    }

    /**
     * Get the chosen "fields" array of the current request.
     *
     * @return array|null
     */
    public function request_fields(): ?array
    {
        $fields = request('fields');

        if (!$fields) {
            return null;
        }

        return array_map('trim', explode(',', $fields));
    }

    /**
     * Get the provided "with" array of the current request.
     *
     * @return array|null
     */
    public function request_with(): ?array
    {
        $with = request('with') ?? request('with[]');

        if (empty($with)) {
            return null;
        }

        if (is_array($with)) {
            return array_values(array_filter(array_map('trim', $with)));
        }

        return array_values(array_filter(array_map('trim', explode(',', (string) $with))));
    }
}
