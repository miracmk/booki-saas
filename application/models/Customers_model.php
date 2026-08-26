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
 * Customers model.
 *
 * Handles all the database operations of the customer resource.
 *
 * @package Models
 */
class Customers_model extends EA_Model
{
    /**
     * Salon Flora customization (2026-08-24, KVKK hardening) - forked from stock ONLY to add
     * column-level encryption for email/phone_number/address/state/zip_code/notes (see
     * salonflora_crypto_helper.php). first_name/last_name stay plaintext (see the helper's docblock -
     * partial name search is a daily staff workflow). Every write path (insert/update) encrypts these
     * fields before they reach the database; every read path (find/get/search/value) decrypts them
     * back before returning, so every OTHER file in the codebase keeps working with plain values as
     * if nothing changed - encryption is entirely a model-layer concern. search() additionally swaps
     * the old LIKE-based email/phone_number matching for an exact-match lookup against the
     * email_hash/phone_number_hash columns (a LIKE can't run against ciphertext) and drops
     * address/state/zip_code/notes from search entirely (accepted tradeoff - see project notes).
     */
    private const ENCRYPTED_ONLY_FIELDS = ['address', 'state', 'zip_code', 'notes', 'social_links'];

    private const ENCRYPTED_AND_HASHED_FIELDS = ['email', 'phone_number'];

    /**
     * Encrypt the plaintext PII fields present in $data (in place), and set the matching _hash column
     * for email/phone_number. Fields absent from $data are left untouched (so a partial update array
     * doesn't have to carry every field).
     *
     * @param array $data
     *
     * @return array
     */
    private function encrypt_pii(array $data): array
    {
        foreach (self::ENCRYPTED_ONLY_FIELDS as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = sf_pii_encrypt($data[$field]);
            }
        }

        foreach (self::ENCRYPTED_AND_HASHED_FIELDS as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field . '_hash'] = sf_pii_hash($data[$field]);
                $data[$field] = sf_pii_encrypt($data[$field]);
            }
        }

        return $data;
    }

    /**
     * Decrypt the PII fields present in $row (in place). Safe to call on a row that's already
     * plaintext (sf_pii_decrypt() only transforms values carrying the "SFENC1:" tag - see
     * salonflora_crypto_helper.php) and on a partial row missing some of the fields.
     *
     * @param array $row
     *
     * @return array
     */
    private function decrypt_pii(array $row): array
    {
        foreach ([...self::ENCRYPTED_ONLY_FIELDS, ...self::ENCRYPTED_AND_HASHED_FIELDS] as $field) {
            if (array_key_exists($field, $row) && sf_pii_is_encrypted($row[$field])) {
                $row[$field] = sf_pii_decrypt($row[$field]);
            }
        }

        return $row;
    }

    /**
     * @var array
     */
    protected array $casts = [
        'id' => 'integer',
        'id_roles' => 'integer',
    ];

    /**
     * @var array
     */
    protected array $api_resource = [
        'id' => 'id',
        'firstName' => 'first_name',
        'lastName' => 'last_name',
        'email' => 'email',
        'phone' => 'phone_number',
        'address' => 'address',
        'city' => 'city',
        'zip' => 'zip_code',
        'timezone' => 'timezone',
        'language' => 'language',
        'customField1' => 'custom_field_1',
        'customField2' => 'custom_field_2',
        'customField3' => 'custom_field_3',
        'customField4' => 'custom_field_4',
        'customField5' => 'custom_field_5',
        'notes' => 'notes',
        'ldapDn' => 'ldap_dn',
    ];

    /**
     * Save (insert or update) a customer.
     *
     * @param array $customer Associative array with the customer data.
     *
     * @return int Returns the customer ID.
     *
     * @throws InvalidArgumentException
     */
    public function save(array $customer): int
    {
        $this->validate($customer);

        if ($this->exists($customer) && empty($customer['id'])) {
            $customer['id'] = $this->find_record_id($customer);
        }

        if (empty($customer['id'])) {
            return $this->insert($customer);
        } else {
            return $this->update($customer);
        }
    }

    /**
     * Ki Reservation (2026-08-26) - "Müşteri Paneli": customers, unlike admin/provider/secretary,
     * never had login credentials before - this is the one place that sets them. Mirrors
     * Providers_model::set_settings()'s salt+hash pattern; `user_settings` is a generic per-user
     * table already shared across every role, nothing new needed there.
     *
     * @throws InvalidArgumentException If the username is already taken by another user.
     */
    public function set_login_credentials(int $customer_id, string $username, string $password): void
    {
        $username = trim($username);

        if ($username === '' || strlen($password) < 8) {
            throw new InvalidArgumentException('Kullanıcı adı boş olamaz, şifre en az 8 karakter olmalı.');
        }

        $existing = $this->db
            ->get_where('user_settings', ['username' => $username])
            ->row_array();

        if ($existing && (int) $existing['id_users'] !== $customer_id) {
            throw new InvalidArgumentException('Bu kullanıcı adı zaten kullanımda.');
        }

        $salt = generate_salt();

        if ($this->db->get_where('user_settings', ['id_users' => $customer_id])->num_rows() === 0) {
            $this->db->insert('user_settings', ['id_users' => $customer_id]);
        }

        $this->db->update(
            'user_settings',
            ['username' => $username, 'password' => hash_password($salt, $password), 'salt' => $salt],
            ['id_users' => $customer_id],
        );
    }

    /**
     * Validate the customer data.
     *
     * @param array $customer Associative array with the customer data.
     *
     * @throws InvalidArgumentException
     */
    public function validate(array $customer): void
    {
        // If a customer ID is provided then check whether the record really exists in the database.
        if (!empty($customer['id'])) {
            $count = $this->db->get_where('users', ['id' => $customer['id']])->num_rows();

            if (!$count) {
                throw new InvalidArgumentException(
                    'The provided customer ID does not exist in the database: ' . $customer['id'],
                );
            }
        }

        // Make sure all required fields are provided.
        $require_first_name = filter_var(setting('require_first_name'), FILTER_VALIDATE_BOOLEAN);
        $require_last_name = filter_var(setting('require_last_name'), FILTER_VALIDATE_BOOLEAN);
        $require_email = filter_var(setting('require_email'), FILTER_VALIDATE_BOOLEAN);
        $require_phone_number = filter_var(setting('require_phone_number'), FILTER_VALIDATE_BOOLEAN);
        $require_address = filter_var(setting('require_address'), FILTER_VALIDATE_BOOLEAN);
        $require_city = filter_var(setting('require_city'), FILTER_VALIDATE_BOOLEAN);
        $require_zip_code = filter_var(setting('require_zip_code'), FILTER_VALIDATE_BOOLEAN);

        if (
            (empty($customer['first_name']) && $require_first_name) ||
            (empty($customer['last_name']) && $require_last_name) ||
            (empty($customer['email']) && $require_email) ||
            (empty($customer['phone_number']) && $require_phone_number) ||
            (empty($customer['address']) && $require_address) ||
            (empty($customer['city']) && $require_city) ||
            (empty($customer['zip_code']) && $require_zip_code)
        ) {
            throw new InvalidArgumentException('Not all required fields are provided: ' . print_r($customer, true));
        }

        if (!empty($customer['email'])) {
            // Validate the email address.
            if (!filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Invalid email address provided: ' . $customer['email']);
            }

            // Make sure the email address is unique.
            $customer_id = $customer['id'] ?? null;

            $count = $this->db
                ->select()
                ->from('users')
                ->join('roles', 'roles.id = users.id_roles', 'inner')
                ->where('roles.slug', DB_SLUG_CUSTOMER)
                // Salon Flora customization - email is encrypted at rest, match via the exact-match hash index.
                ->where('users.email_hash', sf_pii_hash($customer['email']))
                ->where('users.id !=', $customer_id)
                ->get()
                ->num_rows();

            if ($count > 0) {
                throw new InvalidArgumentException(
                    'The provided email address is already in use, please use a different one.',
                );
            }
        }
    }

    /**
     * Get all customers that match the provided criteria.
     *
     * @param array|string|null $where Where conditions.
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @param string|null $order_by Order by.
     *
     * @return array Returns an array of customers.
     */
    public function get(
        array|string|null $where = null,
        ?int $limit = null,
        ?int $offset = null,
        ?string $order_by = null,
    ): array {
        $role_id = $this->get_customer_role_id();

        if ($where !== null) {
            $this->db->where($where);
        }

        if ($order_by !== null) {
            $this->db->order_by($this->quote_order_by($order_by));
        }

        $customers = $this->db->get_where('users', ['id_roles' => $role_id], $limit, $offset)->result_array();

        foreach ($customers as &$customer) {
            $this->cast($customer);
            $customer = $this->decrypt_pii($customer);
        }

        return $customers;
    }

    /**
     * Get the customer role ID.
     *
     * @return int Returns the role ID.
     */
    public function get_customer_role_id(): int
    {
        $role = $this->db->get_where('roles', ['slug' => DB_SLUG_CUSTOMER])->row_array();

        if (empty($role)) {
            throw new RuntimeException('The customer role was not found in the database.');
        }

        return $role['id'];
    }

    /**
     * Check if a particular customer record already exists in the database.
     *
     * @param array $customer Associative array with the customer data.
     *
     * @return bool Returns whether there is a record matching the provided one or not.
     *
     * @throws InvalidArgumentException
     */
    public function exists(array $customer): bool
    {
        if (empty($customer['email'])) {
            return false;
        }

        $count = $this->db
            ->select()
            ->from('users')
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->where('users.email_hash', sf_pii_hash($customer['email']))
            ->where('roles.slug', DB_SLUG_CUSTOMER)
            ->get()
            ->num_rows();

        return $count > 0;
    }

    /**
     * Find the record ID of a customer.
     *
     * @param array $customer Associative array with the customer data.
     *
     * @return int Returns the ID of the record that matches the provided argument.
     *
     * @throws InvalidArgumentException
     */
    public function find_record_id(array $customer): int
    {
        if (empty($customer['email'])) {
            throw new InvalidArgumentException('The customer email was not provided: ' . print_r($customer, true));
        }

        $customer = $this->db
            ->select('users.id')
            ->from('users')
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->where('users.email_hash', sf_pii_hash($customer['email']))
            ->where('roles.slug', DB_SLUG_CUSTOMER)
            ->get()
            ->row_array();

        if (empty($customer)) {
            throw new InvalidArgumentException('Could not find customer record id.');
        }

        return (int) $customer['id'];
    }

    /**
     * Insert a new customer into the database.
     *
     * @param array $customer Associative array with the customer data.
     *
     * @return int Returns the customer ID.
     *
     * @throws RuntimeException
     */
    protected function insert(array $customer): int
    {
        $customer['create_datetime'] = date('Y-m-d H:i:s');
        $customer['update_datetime'] = date('Y-m-d H:i:s');
        $customer['id_roles'] = $this->get_customer_role_id();

        $customer = $this->encrypt_pii($customer);

        if (!$this->db->insert('users', $customer)) {
            throw new RuntimeException('Could not insert customer.');
        }

        return $this->db->insert_id();
    }

    /**
     * Update an existing customer.
     *
     * @param array $customer Associative array with the customer data.
     *
     * @return int Returns the customer ID.
     *
     * @throws RuntimeException
     */
    protected function update(array $customer): int
    {
        $customer['update_datetime'] = date('Y-m-d H:i:s');

        $customer = $this->encrypt_pii($customer);

        if (!$this->db->update('users', $customer, ['id' => $customer['id']])) {
            throw new RuntimeException('Could not update customer.');
        }

        return $customer['id'];
    }

    /**
     * Remove an existing customer from the database.
     *
     * @param int $customer_id Customer ID.
     *
     * @throws RuntimeException
     */
    public function delete(int $customer_id): void
    {
        $this->anonymize_related_consents($customer_id);

        $this->db->delete('users', ['id' => $customer_id]);
    }

    /**
     * Salon Flora customization (2026-08-24, KVKK data retention) - anonymize a customer IN PLACE
     * (never delete the row - appointments.id_users_customer is ON DELETE CASCADE, so deleting would
     * destroy their appointment/payment history too, which conflicts with VUK record-keeping
     * requirements). Used both by the nightly automated retention job
     * (Console::anonymize_stale_customers()) and by a manual "KVKK - Unutulma Hakkı" admin action.
     * Idempotent - anonymizing an already-anonymized customer just re-runs harmlessly.
     *
     * @param int $customer_id
     */
    public function anonymize(int $customer_id): void
    {
        $this->anonymize_related_consents($customer_id);

        $this->db->update(
            'users',
            [
                'first_name' => 'Silinmiş',
                'last_name' => 'Müşteri',
                'email' => null,
                'email_hash' => null,
                'phone_number' => null,
                'phone_number_hash' => null,
                'address' => null,
                'state' => null,
                'zip_code' => null,
                'notes' => null,
                'custom_field_1' => null,
                'custom_field_2' => null,
                'custom_field_3' => null,
                'custom_field_4' => null,
                'custom_field_5' => null,
                'anonymized_at' => date('Y-m-d H:i:s'),
                'update_datetime' => date('Y-m-d H:i:s'),
            ],
            ['id' => $customer_id],
        );
    }

    /**
     * Anonymize consent records tied to a customer, by both id_users and (decrypted) email - shared by
     * delete() and anonymize().
     *
     * @param int $customer_id
     */
    private function anonymize_related_consents(int $customer_id): void
    {
        // Anonymize consent records before deleting/anonymizing the customer (GDPR/KVKK compliance)
        $this->db->where('id_users', $customer_id);
        $this->db->update('consents', [
            'id_users' => null,
            'first_name' => '[DELETED]',
            'last_name' => '[DELETED]',
            'email' => '[DELETED]',
        ]);

        // Also anonymize any consents that match the customer's email
        $customer = $this->decrypt_pii($this->db->get_where('users', ['id' => $customer_id])->row_array());

        if (!empty($customer['email'])) {
            // consents.email is a separate, unencrypted table - matched against the decrypted plaintext.
            $this->db->where('email', $customer['email']);
            $this->db->where('id_users IS NULL', null, false);
            $this->db->update('consents', [
                'first_name' => '[DELETED]',
                'last_name' => '[DELETED]',
                'email' => '[DELETED]',
            ]);
        }
    }

    /**
     * Get a specific customer from the database.
     *
     * @param int $customer_id The ID of the record to be returned.
     *
     * @return array Returns an array with the customer data.
     */
    public function find(int $customer_id): array
    {
        $customer = $this->db->get_where('users', ['id' => $customer_id])->row_array();

        if (!$customer) {
            throw new InvalidArgumentException(
                'The provided customer ID was not found in the database: ' . $customer_id,
            );
        }

        $this->cast($customer);

        return $this->decrypt_pii($customer);
    }

    /**
     * Get a specific field value from the database.
     *
     * @param int $customer_id Customer ID.
     * @param string $field Name of the value to be returned.
     *
     * @return mixed Returns the selected customer value from the database.
     *
     * @throws InvalidArgumentException
     */
    public function value(int $customer_id, string $field): mixed
    {
        if (empty($field)) {
            throw new InvalidArgumentException('The field argument is cannot be empty.');
        }

        if (empty($customer_id)) {
            throw new InvalidArgumentException('The customer ID argument cannot be empty.');
        }

        // Check whether the customer exists.
        $query = $this->db->get_where('users', ['id' => $customer_id]);

        if (!$query->num_rows()) {
            throw new InvalidArgumentException(
                'The provided customer ID was not found in the database: ' . $customer_id,
            );
        }

        // Check if the required field is part of the customer data.
        $customer = $query->row_array();

        $this->cast($customer);
        $customer = $this->decrypt_pii($customer);

        if (!array_key_exists($field, $customer)) {
            throw new InvalidArgumentException('The requested field was not found in the customer data: ' . $field);
        }

        return $customer[$field];
    }

    /**
     * Get the query builder interface, configured for use with the users (customer-filtered) table.
     *
     * @return CI_DB_query_builder
     */
    public function query(): CI_DB_query_builder
    {
        $role_id = $this->get_customer_role_id();

        return $this->db->from('users')->where('id_roles', $role_id);
    }

    /**
     * Search customers by the provided keyword.
     *
     * @param string $keyword Search keyword.
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @param string|null $order_by Order by.
     *
     * @return array Returns an array of customers.
     */
    public function search(string $keyword, ?int $limit = null, ?int $offset = null, ?string $order_by = null): array
    {
        $role_id = $this->get_customer_role_id();

        // Salon Flora customization - email/phone_number are encrypted at rest, so a LIKE can no longer
        // match them; the best available substitute is an EXACT match against their hash index (only
        // fires when $keyword is the complete value, e.g. a full phone number or email address).
        // address/state/zip_code/notes are also encrypted and have no search substitute at all - they're
        // dropped from search entirely (accepted tradeoff, see project notes). first_name/last_name/
        // mobile_number/city stay plaintext and keep their original partial (LIKE) matching.
        $keyword_hash = sf_pii_hash($keyword);

        $customers = $this->db
            ->select()
            ->from('users')
            ->where('id_roles', $role_id)
            ->group_start()
            ->like('first_name', $keyword)
            ->or_like('last_name', $keyword)
            ->or_like('CONCAT_WS(" ", first_name, last_name)', $keyword)
            ->or_where('email_hash', $keyword_hash)
            ->or_where('phone_number_hash', $keyword_hash)
            ->or_like('mobile_number', $keyword)
            ->or_like('city', $keyword)
            ->group_end()
            ->limit($limit)
            ->offset($offset)
            ->order_by($this->quote_order_by($order_by))
            ->get()
            ->result_array();

        foreach ($customers as &$customer) {
            $this->cast($customer);
            $customer = $this->decrypt_pii($customer);
        }

        return $customers;
    }

    /**
     * Get customers as options for dropdowns.
     *
     * @param array|string|null $where Where conditions.
     *
     * @return array Returns an array of options with 'value' and 'label' keys.
     */
    public function to_options(array|string|null $where = null): array
    {
        $role_id = $this->get_customer_role_id();

        if ($where !== null) {
            $this->db->where($where);
        }

        $customers = $this->db
            ->select('id, first_name, last_name')
            ->from('users')
            ->where('id_roles', $role_id)
            ->order_by('first_name, last_name')
            ->get()
            ->result_array();

        $options = [];

        foreach ($customers as $customer) {
            $options[] = [
                'value' => (int) $customer['id'],
                'label' => trim($customer['first_name'] . ' ' . $customer['last_name']),
            ];
        }

        return $options;
    }

    /**
     * Load related resources to a customer.
     *
     * @param array $customer Associative array with the customer data.
     * @param array $resources Resource names to be attached.
     *
     * @throws InvalidArgumentException
     */
    public function load(array &$customer, array $resources)
    {
        // Customers do not currently have any related resources.
    }

    /**
     * Convert the database customer record to the equivalent API resource.
     *
     * @param array $customer Customer data.
     */
    public function api_encode(array &$customer): void
    {
        $encoded_resource = [
            'id' => array_key_exists('id', $customer) ? (int) $customer['id'] : null,
            'firstName' => $customer['first_name'],
            'lastName' => $customer['last_name'],
            'email' => $customer['email'],
            'phone' => $customer['phone_number'],
            'address' => $customer['address'],
            'city' => $customer['city'],
            'zip' => $customer['zip_code'],
            'notes' => $customer['notes'],
            'timezone' => $customer['timezone'],
            'language' => $customer['language'],
            'customField1' => $customer['custom_field_1'],
            'customField2' => $customer['custom_field_2'],
            'customField3' => $customer['custom_field_3'],
            'customField4' => $customer['custom_field_4'],
            'customField5' => $customer['custom_field_5'],
            'ldapDn' => $customer['ldap_dn'],
        ];

        $customer = $encoded_resource;
    }

    /**
     * Convert the API resource to the equivalent database admin record.
     *
     * @param array $customer API resource.
     * @param array|null $base Base customer data to be overwritten with the provided values (useful for updates).
     */
    public function api_decode(array &$customer, ?array $base = null): void
    {
        $decoded_resource = $base ?: [];

        if (array_key_exists('id', $customer)) {
            $decoded_resource['id'] = $customer['id'];
        }

        if (array_key_exists('firstName', $customer)) {
            $decoded_resource['first_name'] = $customer['firstName'];
        }

        if (array_key_exists('lastName', $customer)) {
            $decoded_resource['last_name'] = $customer['lastName'];
        }

        if (array_key_exists('email', $customer)) {
            $decoded_resource['email'] = $customer['email'];
        }

        if (array_key_exists('phone', $customer)) {
            $decoded_resource['phone_number'] = $customer['phone'];
        }

        if (array_key_exists('address', $customer)) {
            $decoded_resource['address'] = $customer['address'];
        }

        if (array_key_exists('city', $customer)) {
            $decoded_resource['city'] = $customer['city'];
        }

        if (array_key_exists('zip', $customer)) {
            $decoded_resource['zip_code'] = $customer['zip'];
        }

        if (array_key_exists('language', $customer)) {
            $decoded_resource['language'] = $customer['language'];
        }

        if (array_key_exists('timezone', $customer)) {
            $decoded_resource['timezone'] = $customer['timezone'];
        }

        if (array_key_exists('customField1', $customer)) {
            $decoded_resource['custom_field_1'] = $customer['customField1'];
        }

        if (array_key_exists('customField2', $customer)) {
            $decoded_resource['custom_field_2'] = $customer['customField2'];
        }

        if (array_key_exists('customField3', $customer)) {
            $decoded_resource['custom_field_3'] = $customer['customField3'];
        }

        if (array_key_exists('customField4', $customer)) {
            $decoded_resource['custom_field_4'] = $customer['customField4'];
        }

        if (array_key_exists('customField5', $customer)) {
            $decoded_resource['custom_field_5'] = $customer['customField5'];
        }

        if (array_key_exists('ldapDn', $customer)) {
            $decoded_resource['ldap_dn'] = $customer['ldapDn'];
        }

        if (array_key_exists('notes', $customer)) {
            $decoded_resource['notes'] = $customer['notes'];
        }

        $customer = $decoded_resource;
    }
}
