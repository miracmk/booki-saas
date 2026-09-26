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
 * Accounts library.
 *
 * Handles account related functionality.
 *
 * @package Libraries
 */
class Accounts
{
    /**
     * @var App_Controller|CI_Controller
     */
    protected App_Controller|CI_Controller $CI;

    /**
     * Accounts constructor.
     */
    public function __construct()
    {
        $this->CI = &get_instance();

        $this->CI->load->model('users_model');
        $this->CI->load->model('roles_model');

        $this->CI->load->library('timezones');
    }

    /**
     * Authenticate the provided credentials.
     *
     * @param string $username Username.
     * @param string $password Password (non-hashed).
     *
     * @return array|null Returns an associative array with the PHP session data or NULL on failure.
     * @throws Exception
     */
    public function check_login(string $username, string $password): ?array
    {
        $user_settings = $this->CI->db
            ->get_where('user_settings', [
                'username' => $username,
            ])
            ->row_array();

        if (empty($user_settings)) {
            return null;
        }

        $salt = $user_settings['salt'] ?? '';
        $stored_hash = $user_settings['password'] ?? '';

        // Use the new verify_password function for secure comparison
        if (!verify_password($salt, $password, $stored_hash)) {
            return null;
        }

        // Rehash password if using legacy algorithm (upgrade to bcrypt)
        if (password_needs_rehash_check($stored_hash)) {
            $new_hash = hash_password($salt, $password);
            $this->CI->db->update(
                'user_settings',
                ['password' => $new_hash],
                ['id_users' => $user_settings['id_users']],
            );
        }

        $user = $this->CI->users_model->find($user_settings['id_users']);

        $role = $this->CI->roles_model->find($user['id_roles']);

        $default_timezone = $this->CI->timezones->get_default_timezone();

        return [
            'user_id' => $user['id'],
            'user_email' => $user['email'],
            'username' => $username,
            'timezone' => !empty($user['timezone']) ? $user['timezone'] : $default_timezone,
            'language' => !empty($user['language']) ? $user['language'] : Config::LANGUAGE,
            'role_slug' => $role['slug'],
        ];
    }

    /**
     * Get the user's salt value.
     *
     * @param string $username Username.
     *
     * @return string Returns the salt value.
     */
    public function get_salt_by_username(string $username): string
    {
        $user_settings = $this->CI->db->get_where('user_settings', ['username' => $username])->row_array();

        return $user_settings['salt'] ?? '';
    }

    /**
     * Get the user full name.
     *
     * @param int $user_id User ID.
     *
     * @return string Returns the user full name.
     */
    public function get_user_display_name(int $user_id): string
    {
        $user = $this->CI->users_model->find($user_id);

        return $user['first_name'] . ' ' . $user['last_name'];
    }

    /**
     * Regenerate the password of the user that matches the provided username and email.
     *
     * @param string $username Username.
     * @param string $email Email.
     *
     * @return string Returns the new password on success or FALSE on failure.
     *
     * @throws Exception
     */
    public function regenerate_password(string $username, string $email): string
    {
        $query = $this->CI->db
            ->select('users.id')
            ->from('users')
            ->join('user_settings', 'user_settings.id_users = users.id', 'inner')
            ->where('users.email', $email)
            ->where('user_settings.username', $username)
            ->get();

        if (!$query->num_rows()) {
            throw new RuntimeException('The user was not found in the database with the provided info.');
        }

        $user = $query->row_array();

        // Generate a new password for the user.
        $new_password = random_string('alnum', 12);

        $salt = $this->get_salt_by_username($username);

        $hash_password = hash_password($salt, $new_password);

        $this->CI->users_model->set_setting($user['id'], 'password', $hash_password);

        return $new_password;
    }

    /**
     * Check if a user account exists or not.
     *
     * @param int $user_id
     *
     * @return bool
     */
    public function does_account_exist(int $user_id): bool
    {
        return $this->CI->users_model
            ->query()
            ->where(['id' => $user_id])
            ->get()
            ->num_rows() > 0;
    }

    /**
     * Get a user record based on the provided username value
     *
     * @param string $username
     *
     * @return array|null
     */
    public function get_user_by_username(string $username): ?array
    {
        $user_settings = $this->CI->db->get_where('user_settings', ['username' => $username])->row_array();

        if (!$user_settings) {
            return null;
        }

        $user_id = $user_settings['id_users'];

        return $this->CI->users_model->find($user_id);
    }

    /**
     * Generate a password reset token for the user that matches the provided username and email.
     *
     * @param string $username Username.
     * @param string $email Email.
     *
     * @return array Returns an array with 'token' and 'email' on success.
     *
     * @throws RuntimeException If the user was not found.
     */
    public function generate_reset_token(string $username, string $email): array
    {
        // Salon Flora customization (2026-08-24, KVKK hardening) - email is encrypted at rest, match
        // via the exact-match hash index (see salonflora_crypto_helper.php) instead of a raw compare,
        // which could never match ciphertext.
        $query = $this->CI->db
            ->select('users.id, users.email')
            ->from('users')
            ->join('user_settings', 'user_settings.id_users = users.id', 'inner')
            ->where('users.email_hash', sf_pii_hash($email))
            ->where('user_settings.username', $username)
            ->get();

        if (!$query->num_rows()) {
            throw new RuntimeException('The user was not found in the database with the provided info.');
        }

        $user = $query->row_array();
        $user['email'] = sf_pii_is_encrypted($user['email']) ? sf_pii_decrypt($user['email']) : $user['email'];

        // Generate a secure random token
        $token = bin2hex(random_bytes(32));

        // Hash the token for storage (we store the hash, send the plain token)
        $token_hash = hash('sha256', $token);

        // Set expiration to 1 hour from now
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Store the hashed token and expiration
        $this->CI->db->update(
            'user_settings',
            [
                'password_reset_token' => $token_hash,
                'password_reset_expires' => $expires,
            ],
            ['id_users' => $user['id']],
        );

        return [
            'token' => $token,
            'email' => $user['email'],
        ];
    }

    /**
     * Validate a password reset token.
     *
     * @param string $token The plain text token to validate.
     *
     * @return array|null Returns user data if valid, null otherwise.
     */
    public function validate_reset_token(string $token): ?array
    {
        $token_hash = hash('sha256', $token);

        $query = $this->CI->db
            ->select('users.id, users.email, users.first_name, users.last_name, user_settings.username')
            ->from('users')
            ->join('user_settings', 'user_settings.id_users = users.id', 'inner')
            ->where('user_settings.password_reset_token', $token_hash)
            ->where('user_settings.password_reset_expires >', date('Y-m-d H:i:s'))
            ->get();

        if (!$query->num_rows()) {
            return null;
        }

        $user = $query->row_array();

        // Salon Flora customization (2026-08-24, KVKK hardening) - email is encrypted at rest.
        $user['email'] = sf_pii_is_encrypted($user['email']) ? sf_pii_decrypt($user['email']) : $user['email'];

        return $user;
    }

    /**
     * Reset the password using a valid token.
     *
     * @param string $token The plain text token.
     * @param string $new_password The new password.
     *
     * @return bool Returns true on success.
     *
     * @throws RuntimeException If the token is invalid or expired.
     */
    public function reset_password_with_token(string $token, string $new_password): bool
    {
        $user = $this->validate_reset_token($token);

        if (!$user) {
            throw new RuntimeException('Invalid or expired password reset token.');
        }

        $salt = $this->get_salt_by_username($user['username']);

        $hash_password = hash_password($salt, $new_password);

        // Update the password and clear the reset token
        $this->CI->db->update(
            'user_settings',
            [
                'password' => $hash_password,
                'password_reset_token' => null,
                'password_reset_expires' => null,
            ],
            ['id_users' => $user['id']],
        );
        return true;
    }

    /**
     * Check if TOTP is enabled for a user.
     *
     * @param int $user_id User ID.
     *
     * @return bool
     */
    public function totp_is_enabled(int $user_id): bool
    {
        $user_settings = $this->CI->db
            ->select('totp_enabled')
            ->get_where('user_settings', ['id_users' => $user_id])
            ->row_array();

        return (bool) ($user_settings['totp_enabled'] ?? false);
    }

    /**
     * Create a TOTP challenge token for a user.
     *
     * @param int $user_id User ID.
     * @param string|null $ip_address IP address.
     *
     * @return string Plain text token to return to the user.
     */
    public function create_totp_challenge(int $user_id, ?string $ip_address = null): string
    {
        // Generate a secure random token
        $token = bin2hex(random_bytes(32));

        // Hash the token for storage
        $token_hash = hash('sha256', $token);

        // Set expiration to 5 minutes from now
        $expires = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        // Store the challenge
        $this->CI->db->insert('totp_challenges', [
            'id_users' => $user_id,
            'token_hash' => $token_hash,
            'expires' => $expires,
            'attempts' => 0,
            'ip_address' => $ip_address,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $token;
    }

    /**
     * Validate a TOTP challenge token.
     *
     * @param string $token The plain text token to validate.
     *
     * @return array|null Returns the challenge row if valid, null otherwise.
     */
    public function validate_totp_challenge(string $token): ?array
    {
        $token_hash = hash('sha256', $token);

        $challenge = $this->CI->db
            ->get_where('totp_challenges', ['token_hash' => $token_hash])
            ->row_array();

        if (empty($challenge)) {
            return null;
        }

        // Check if challenge has expired
        if (strtotime($challenge['expires']) < time()) {
            return null;
        }

        return $challenge;
    }

    /**
     * Verify a TOTP code against a user's secret.
     *
     * @param int $user_id User ID.
     * @param string $code The TOTP code to verify.
     *
     * @return bool True if valid, false otherwise.
     */
    public function verify_totp_code(int $user_id, string $code): bool
    {
        $user_settings = $this->CI->db
            ->select('totp_secret, totp_last_step')
            ->get_where('user_settings', ['id_users' => $user_id])
            ->row_array();

        if (empty($user_settings) || empty($user_settings['totp_secret'])) {
            return false;
        }

        // Decrypt the TOTP secret
        $secret = sf_pii_decrypt($user_settings['totp_secret']);

        if (empty($secret)) {
            return false;
        }

        // Verify the code using RobThree/TwoFactorAuth library. The constructor's first argument
        // is a mandatory IQRCodeProvider - NOT the issuer string (confirmed by actually installing
        // and running this library; the library's own real signature differs from what an
        // assumed/typical constructor shape would suggest). getQRText()/verifyCode() never call
        // the provider, so any IQRCodeProvider instance satisfies the constructor here.
        $tfa = new \RobThree\Auth\TwoFactorAuth($this->totp_qr_provider(), 'BooKi');

        // Verify with ±1 time step window (30s drift each direction)
        $isValid = $tfa->verifyCode($secret, $code, 1);

        if (!$isValid) {
            return false;
        }

        // Check for replay attack (same code within the same time window). $user_settings was
        // fetched via a raw query, not a model's cast() - totp_last_step comes back as a string
        // from the DB driver, so a strict === against the int $currentStep would never match,
        // silently disabling this replay guard. Cast explicitly.
        $currentStep = intval(time() / 30);
        $last_step = $user_settings['totp_last_step'] !== null ? (int) $user_settings['totp_last_step'] : null;

        if ($last_step !== null && $last_step === $currentStep) {
            // Code is being replayed
            return false;
        }

        // Update the last step to prevent replay
        $this->CI->db->update(
            'user_settings',
            ['totp_last_step' => $currentStep],
            ['id_users' => $user_id],
        );

        return true;
    }

    /**
     * Verify a backup code against a user's backup codes.
     *
     * @param int $user_id User ID.
     * @param string $code The backup code to verify.
     *
     * @return bool True if valid and removed, false otherwise.
     */
    public function verify_backup_code(int $user_id, string $code): bool
    {
        $user_settings = $this->CI->db
            ->select('totp_backup_codes')
            ->get_where('user_settings', ['id_users' => $user_id])
            ->row_array();

        if (empty($user_settings) || empty($user_settings['totp_backup_codes'])) {
            return false;
        }

        // Decode the JSON array of hashes
        $backup_codes_hashes = json_decode($user_settings['totp_backup_codes'], true);

        if (!is_array($backup_codes_hashes)) {
            return false;
        }

        // Hash the provided code
        $code_hash = hash('sha256', $code);

        // Check if the code exists in the array
        $key = array_search($code_hash, $backup_codes_hashes, true);

        if ($key === false) {
            return false;
        }

        // Remove the code from the array (single-use)
        unset($backup_codes_hashes[$key]);

        // Update the backup codes
        $updated_codes = json_encode(array_values($backup_codes_hashes));

        $this->CI->db->update(
            'user_settings',
            ['totp_backup_codes' => $updated_codes],
            ['id_users' => $user_id],
        );

        return true;
    }

    /**
     * Consume/delete a TOTP challenge after successful verification.
     *
     * @param string $token The plain text token.
     *
     * @return void
     */
    public function consume_totp_challenge(string $token): void
    {
        $token_hash = hash('sha256', $token);

        $this->CI->db->delete('totp_challenges', ['token_hash' => $token_hash]);
    }

    /**
     * Increment the TOTP challenge attempt counter.
     *
     * @param string $token The plain text token.
     *
     * @return int The new attempts count. If >= 5, the challenge is deleted.
     */
    public function increment_totp_challenge_attempts(string $token): int
    {
        $token_hash = hash('sha256', $token);

        $challenge = $this->CI->db
            ->get_where('totp_challenges', ['token_hash' => $token_hash])
            ->row_array();

        if (empty($challenge)) {
            return 0;
        }

        $new_attempts = $challenge['attempts'] + 1;

        if ($new_attempts >= 5) {
            // Too many attempts, delete the challenge
            $this->CI->db->delete('totp_challenges', ['token_hash' => $token_hash]);
            return 5;
        }

        // Update the attempts count
        $this->CI->db->update(
            'totp_challenges',
            ['attempts' => $new_attempts],
            ['token_hash' => $token_hash],
        );

        return $new_attempts;
    }

    /**
     * QR code provider for RobThree\Auth\TwoFactorAuth's constructor. SVG output avoids requiring
     * the Imagick PHP extension (the library's PNG/GIF/JPEG backends need it, SVG doesn't) - kept
     * as its own method since the constructor's first argument is easy to get wrong (see the
     * comments at both call sites).
     */
    private function totp_qr_provider(): \RobThree\Auth\Providers\Qr\BaconQrCodeProvider
    {
        return new \RobThree\Auth\Providers\Qr\BaconQrCodeProvider(4, '#ffffff', '#000000', 'svg');
    }

    /**
     * Generate a new TOTP secret for enrollment.
     *
     * @param int $user_id User ID.
     *
     * @return array Array with 'secret' (plaintext base32) and 'otpauth_uri'.
     */
    public function generate_totp_secret(int $user_id): array
    {
        $tfa = new \RobThree\Auth\TwoFactorAuth($this->totp_qr_provider(), 'BooKi');

        // Generate a new secret
        $secret = $tfa->createSecret(160); // 160 bits for stronger entropy

        // Encrypt and store the secret
        $encrypted_secret = sf_pii_encrypt($secret);

        // Get user info for the otpauth URI
        $user = $this->CI->users_model->find($user_id);
        $user_settings = $this->CI->db
            ->get_where('user_settings', ['id_users' => $user_id])
            ->row_array();

        $email = sf_pii_is_encrypted($user['email']) ? sf_pii_decrypt($user['email']) : $user['email'];
        $username = $user_settings['username'] ?? '';

        // Update user_settings with the encrypted secret (totp_enabled stays 0 for now)
        $this->CI->db->update(
            'user_settings',
            ['totp_secret' => $encrypted_secret],
            ['id_users' => $user_id],
        );

        // Generate the otpauth URI
        $otpauth_uri = $tfa->getQRCodeImageAsDataUri(
            'BooKi (' . $email . ')',
            $secret,
        );

        // Also provide a plain URI for manual entry
        $plain_uri = 'otpauth://totp/Ki%20Reservation%20(' . urlencode($email) . ')?secret=' . $secret . '&issuer=Ki%20Reservation';

        return [
            'secret' => $secret,
            'otpauth_uri' => $otpauth_uri,
            'plain_uri' => $plain_uri,
        ];
    }

    /**
     * Enable TOTP after successful code verification during enrollment.
     *
     * @param int $user_id User ID.
     * @param string $code The TOTP code to verify.
     *
     * @return array Array with 'success' (bool) and 'backup_codes' (array|null).
     *
     * @throws InvalidArgumentException If the code is invalid.
     */
    public function enable_totp(int $user_id, string $code): array
    {
        // Verify the code first
        if (!$this->verify_totp_code($user_id, $code)) {
            throw new InvalidArgumentException('Invalid TOTP code.');
        }

        // Generate 10 backup codes
        $backup_codes = [];
        $backup_codes_hashes = [];

        for ($i = 0; $i < 10; $i++) {
            $code = bin2hex(random_bytes(5)); // 10 character hex string
            $backup_codes[] = $code;
            $backup_codes_hashes[] = hash('sha256', $code);
        }

        // Update user_settings
        $this->CI->db->update(
            'user_settings',
            [
                'totp_enabled' => 1,
                'totp_confirmed_at' => date('Y-m-d H:i:s'),
                'totp_backup_codes' => json_encode($backup_codes_hashes),
            ],
            ['id_users' => $user_id],
        );

        return [
            'success' => true,
            'backup_codes' => $backup_codes,
        ];
    }

    /**
     * Disable TOTP for a user.
     *
     * @param int $user_id User ID.
     *
     * @return void
     */
    public function disable_totp(int $user_id): void
    {
        $this->CI->db->update(
            'user_settings',
            [
                'totp_secret' => null,
                'totp_enabled' => 0,
                'totp_confirmed_at' => null,
                'totp_backup_codes' => null,
                'totp_last_step' => null,
            ],
            ['id_users' => $user_id],
        );
    }
}
