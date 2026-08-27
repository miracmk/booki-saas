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

/* ----------------------------------------------------------------------------
 * Salon Flora customization - a provider may be assigned to more than one
 * station (stations_providers join table, mirrors services_providers) and may
 * have a per-service commission override (provider_service_commissions,
 * falling back to the provider's default commission_type/commission_value).
 * ---------------------------------------------------------------------------- */

/**
 * Providers model.
 *
 * Handles all the database operations of the provider resource.
 *
 * @package Models
 */
class Providers_model extends EA_Model
{
    /**
     * Salon Flora customization (2026-08-24, KVKK hardening) - column-level encryption for
     * email/phone_number/address/state/zip_code/notes, mirroring Customers_model's encrypt_pii()/
     * decrypt_pii() (see that file's docblock for the full rationale - same scheme, same shared
     * `users` table, kept as two copies rather than a shared trait/base method because EA_Model
     * doesn't define one and this codebase doesn't otherwise use PHP traits).
     */
    private const ENCRYPTED_ONLY_FIELDS = ['address', 'state', 'zip_code', 'notes'];

    private const ENCRYPTED_AND_HASHED_FIELDS = ['email', 'phone_number'];

    /**
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
        'is_private' => 'boolean',
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
        'mobile' => 'mobile_number',
        'phone' => 'phone_number',
        'address' => 'address',
        'city' => 'city',
        'state' => 'state',
        'zip' => 'zip_code',
        'timezone' => 'timezone',
        'language' => 'language',
        'notes' => 'notes',
        'isPrivate' => 'is_private',
        'ldapDn' => 'ldap_dn',
        'roleId' => 'id_roles',
    ];

    /**
     * Save (insert or update) a provider.
     *
     * @param array $provider Associative array with the provider data.
     *
     * @return int Returns the provider ID.
     *
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function save(array $provider): int
    {
        $this->validate($provider);

        if (empty($provider['id'])) {
            return $this->insert($provider);
        } else {
            return $this->update($provider);
        }
    }

    /**
     * Validate the provider data.
     *
     * @param array $provider Associative array with the provider data.
     *
     * @throws InvalidArgumentException
     */
    public function validate(array $provider): void
    {
        // If a provider ID is provided then check whether the record really exists in the database.
        if (!empty($provider['id'])) {
            $count = $this->db->get_where('users', ['id' => $provider['id']])->num_rows();

            if (!$count) {
                throw new InvalidArgumentException(
                    'The provided provider ID does not exist in the database: ' . $provider['id'],
                );
            }
        }

        // Make sure all required fields are provided.
        if (empty($provider['first_name']) || empty($provider['last_name']) || empty($provider['email'])) {
            throw new InvalidArgumentException('Not all required fields are provided: ' . print_r($provider, true));
        }

        // Validate the email address.
        if (!filter_var($provider['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email address provided: ' . $provider['email']);
        }

        // Validate provider services.
        if (!empty($provider['services'])) {
            // Make sure the provided service entries are numeric values.
            foreach ($provider['services'] as $service_id) {
                if (!is_numeric($service_id)) {
                    throw new InvalidArgumentException(
                        'The provided provider services are invalid: ' . print_r($provider, true),
                    );
                }
            }
        }

        // Salon Flora customization: validate provider stations.
        if (!empty($provider['stations'])) {
            foreach ($provider['stations'] as $station_id) {
                if (!is_numeric($station_id)) {
                    throw new InvalidArgumentException(
                        'The provided provider stations are invalid: ' . print_r($provider, true),
                    );
                }
            }
        }

        // Salon Flora customization: validate per-service commission overrides.
        if (!empty($provider['service_commissions'])) {
            $assigned_service_ids = array_map('intval', $provider['services'] ?? []);

            foreach ($provider['service_commissions'] as $commission) {
                if (!isset($commission['id_services']) || !is_numeric($commission['id_services'])) {
                    throw new InvalidArgumentException(
                        'The provided service commission is missing a valid service ID: ' .
                            print_r($commission, true),
                    );
                }

                if (!in_array((int) $commission['id_services'], $assigned_service_ids, true)) {
                    throw new InvalidArgumentException(
                        'The provided service commission targets a service that is not assigned to the provider: ' .
                            print_r($commission, true),
                    );
                }

                // Salon Flora bugfix - 'hourly' is a valid commission type at the provider-default level
                // (Providers.php validate()) and Reports.php already computes payout for an 'hourly' override
                // here too - this whitelist had simply never been updated to match.
                if (!in_array($commission['commission_type'] ?? 'percentage', ['percentage', 'fixed', 'hourly'], true)) {
                    throw new InvalidArgumentException(
                        'The provided service commission type is invalid: ' . print_r($commission, true),
                    );
                }

                if ((float) ($commission['commission_value'] ?? 0) < 0) {
                    throw new InvalidArgumentException(
                        'The provided service commission value cannot be negative: ' . print_r($commission, true),
                    );
                }
            }
        }

        // Make sure the username is unique.
        if (!empty($provider['settings']['username'])) {
            $provider_id = $provider['id'] ?? null;

            if (!$this->validate_username($provider['settings']['username'], $provider_id)) {
                throw new InvalidArgumentException(
                    'The provided username is already in use, please use a different one.',
                );
            }
        }

        // Validate the password.
        if (!empty($provider['settings']['password'])) {
            if (strlen($provider['settings']['password']) < MIN_PASSWORD_LENGTH) {
                throw new InvalidArgumentException(
                    'The provider password must be at least ' . MIN_PASSWORD_LENGTH . ' characters long.',
                );
            }
        }

        // New users must always have a password value set.
        if (empty($provider['id']) && empty($provider['settings']['password'])) {
            throw new InvalidArgumentException('The provider password cannot be empty when inserting a new record.');
        }

        // Validate calendar view type value.
        if (
            !empty($provider['settings']['calendar_view']) &&
            !in_array($provider['settings']['calendar_view'], [CALENDAR_VIEW_DEFAULT, CALENDAR_VIEW_TABLE])
        ) {
            throw new InvalidArgumentException(
                'The provided calendar view is invalid: ' . $provider['settings']['calendar_view'],
            );
        }

        // Make sure the email address is unique.
        $provider_id = $provider['id'] ?? null;

        $count = $this->db
            ->select()
            ->from('users')
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->where('roles.slug', DB_SLUG_PROVIDER)
            // Salon Flora customization - email is encrypted at rest, match via the exact-match hash index.
            ->where('users.email_hash', sf_pii_hash($provider['email']))
            ->where('users.id !=', $provider_id)
            ->get()
            ->num_rows();

        if ($count > 0) {
            throw new InvalidArgumentException(
                'The provided email address is already in use, please use a different one.',
            );
        }
    }

    /**
     * Validate the provider username.
     *
     * @param string $username Provider username.
     * @param int|null $provider_id Provider ID.
     *
     * @return bool Returns the validation result.
     */
    public function validate_username(string $username, ?int $provider_id = null): bool
    {
        if (!empty($provider_id)) {
            $this->db->where('id_users !=', $provider_id);
        }

        return $this->db
            ->from('users')
            ->join('user_settings', 'user_settings.id_users = users.id', 'inner')
            ->where(['username' => $username])
            ->get()
            ->num_rows() === 0;
    }

    /**
     * Get all providers that match the provided criteria.
     *
     * @param array|string|null $where Where conditions
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @param string|null $order_by Order by.
     *
     * @return array Returns an array of providers.
     */
    public function get(
        array|string|null $where = null,
        ?int $limit = null,
        ?int $offset = null,
        ?string $order_by = null,
    ): array {
        $role_id = $this->get_provider_role_id();

        if ($where !== null) {
            $this->db->where($where);
        }

        if ($order_by !== null) {
            $this->db->order_by($this->quote_order_by($order_by));
        }

        $providers = $this->db->get_where('users', ['id_roles' => $role_id], $limit, $offset)->result_array();

        foreach ($providers as &$provider) {
            $this->cast($provider);
            $provider = $this->decrypt_pii($provider);
            $provider['settings'] = $this->get_settings($provider['id']);
            $provider['services'] = $this->get_service_ids($provider['id']);
            $provider['stations'] = $this->get_station_ids($provider['id']); // Salon Flora customization
        $provider['skills'] = $this->get_skill_ids($provider['id']); // Ki Reservation (2026-08-26)
            $provider['service_commissions'] = $this->get_service_commissions($provider['id']); // Salon Flora customization
        }

        return $providers;
    }

    /**
     * Get the provider role ID.
     *
     * @return int Returns the role ID.
     */
    public function get_provider_role_id(): int
    {
        $role = $this->db->get_where('roles', ['slug' => DB_SLUG_PROVIDER])->row_array();

        if (empty($role)) {
            throw new RuntimeException('The provider role was not found in the database.');
        }

        return $role['id'];
    }

    /**
     * Get the provider settings.
     *
     * @param int $provider_id Provider ID.
     *
     * @throws InvalidArgumentException
     */
    public function get_settings(int $provider_id): array
    {
        $settings = $this->db->get_where('user_settings', ['id_users' => $provider_id])->row_array();

        unset($settings['id_users'], $settings['password'], $settings['salt']);

        // Get working plan exceptions from the new table in array format
        $this->load->model('working_plan_exceptions_model');
        $exceptions = $this->working_plan_exceptions_model->get_all_by_provider($provider_id);
        $settings['working_plan_exceptions'] = json_encode($exceptions);

        return $settings;
    }

    /**
     * Get the provider service IDs.
     *
     * @param int $provider_id Provider ID.
     */
    public function get_service_ids(int $provider_id): array
    {
        $service_provider_connections = $this->db
            ->get_where('services_providers', ['id_users' => $provider_id])
            ->result_array();

        $service_ids = [];

        foreach ($service_provider_connections as $service_provider_connection) {
            $service_ids[] = (int) $service_provider_connection['id_services'];
        }

        return $service_ids;
    }

    /**
     * Salon Flora customization - get the station IDs a provider is assigned to.
     *
     * @param int $provider_id Provider ID.
     */
    public function get_station_ids(int $provider_id): array
    {
        $station_provider_connections = $this->db
            ->get_where('stations_providers', ['id_users' => $provider_id])
            ->result_array();

        $station_ids = [];

        foreach ($station_provider_connections as $station_provider_connection) {
            $station_ids[] = (int) $station_provider_connection['id_stations'];
        }

        return $station_ids;
    }

    /**
     * Salon Flora customization - get the per-service commission overrides for a provider.
     *
     * @param int $provider_id Provider ID.
     */
    public function get_service_commissions(int $provider_id): array
    {
        $rows = $this->db
            ->select('id_services, commission_type, commission_value')
            ->get_where('provider_service_commissions', ['id_users' => $provider_id])
            ->result_array();

        return array_map(
            static fn($row) => [
                'id_services' => (int) $row['id_services'],
                'commission_type' => $row['commission_type'],
                'commission_value' => (float) $row['commission_value'],
            ],
            $rows,
        );
    }

    /**
     * Ki Reservation (2026-08-26) - get the skill IDs a provider is assigned to. Mirrors
     * get_station_ids() exactly, but skills carry no availability logic.
     *
     * @param int $provider_id Provider ID.
     */
    public function get_skill_ids(int $provider_id): array
    {
        $skill_assignments = $this->db
            ->get_where('provider_skill_assignments', ['id_users' => $provider_id])
            ->result_array();

        $skill_ids = [];

        foreach ($skill_assignments as $skill_assignment) {
            $skill_ids[] = (int) $skill_assignment['id_provider_skills'];
        }

        return $skill_ids;
    }

    /**
     * Ki Reservation (2026-08-26) - replace the skill assignments for a provider. Mirrors
     * set_station_ids() exactly.
     *
     * @param int $provider_id Provider ID.
     * @param array $skill_ids Skill IDs.
     */
    public function set_skill_ids(int $provider_id, array $skill_ids): void
    {
        $this->db->delete('provider_skill_assignments', ['id_users' => $provider_id]);

        foreach ($skill_ids as $skill_id) {
            $this->db->insert('provider_skill_assignments', [
                'id_users' => $provider_id,
                'id_provider_skills' => (int) $skill_id,
            ]);
        }
    }

    /**
     * Insert a new provider into the database.
     *
     * @param array $provider Associative array with the provider data.
     *
     * @return int Returns the provider ID.
     *
     * @throws RuntimeException|Exception
     */
    protected function insert(array $provider): int
    {
        $provider['create_datetime'] = date('Y-m-d H:i:s');
        $provider['update_datetime'] = date('Y-m-d H:i:s');
        $provider['id_roles'] = $this->get_provider_role_id();

        $service_ids = $provider['services'];
        $station_ids = $provider['stations'] ?? []; // Salon Flora customization
        $service_commissions = $provider['service_commissions'] ?? []; // Salon Flora customization
        $skill_ids = $provider['skills'] ?? []; // Ki Reservation (2026-08-26)

        $settings = $provider['settings'];

        unset(
            $provider['services'],
            $provider['settings'],
            $provider['stations'],
            $provider['service_commissions'],
            $provider['skills'],
        );

        $provider = $this->encrypt_pii($provider);

        if (!$this->db->insert('users', $provider)) {
            throw new RuntimeException('Could not insert provider.');
        }

        $provider['id'] = $this->db->insert_id();
        $settings['salt'] = generate_salt();
        $settings['password'] = hash_password($settings['salt'], $settings['password']);

        $this->set_settings($provider['id'], $settings);
        $this->set_service_ids($provider['id'], $service_ids);
        $this->set_station_ids($provider['id'], $station_ids); // Salon Flora customization
        $this->set_service_commissions($provider['id'], $service_commissions); // Salon Flora customization
        $this->set_skill_ids($provider['id'], $skill_ids); // Ki Reservation (2026-08-26)

        return $provider['id'];
    }

    /**
     * Save the provider settings.
     *
     * @param int $provider_id Provider ID.
     * @param array $settings Associative array with the settings data.
     *
     * @throws InvalidArgumentException
     */
    public function set_settings(int $provider_id, array $settings): void
    {
        if (empty($settings)) {
            throw new InvalidArgumentException('The settings argument cannot be empty.');
        }

        // Make sure the settings record exists in the database.
        $count = $this->db->get_where('user_settings', ['id_users' => $provider_id])->num_rows();

        if (!$count) {
            $this->db->insert('user_settings', ['id_users' => $provider_id]);
        }

        foreach ($settings as $name => $value) {
            // Working plan exceptions are now stored in a separate table
            if ($name === 'working_plan_exceptions') {
                $this->load->model('working_plan_exceptions_model');

                $exceptions = json_decode($value, true);

                if (!$exceptions) {
                    $exceptions = [];
                }

                // Get existing exception IDs for this provider
                $existing_exceptions = $this->db
                    ->select('id')
                    ->from('working_plan_exceptions')
                    ->where('id_users_provider', $provider_id)
                    ->get()
                    ->result_array();

                $existing_ids = array_column($existing_exceptions, 'id');
                $new_ids = [];

                // Save or update exceptions
                foreach ($exceptions as $exception) {
                    $exception_id = $this->save_working_plan_exception($provider_id, $exception);
                    $new_ids[] = $exception_id;
                }

                // Delete exceptions that were not in the new list
                $ids_to_delete = array_diff($existing_ids, $new_ids);
                if (!empty($ids_to_delete)) {
                    $this->db->where_in('id', $ids_to_delete)->delete('working_plan_exceptions');
                }

                continue;
            }

            $this->set_setting($provider_id, $name, $value);
        }
    }

    /**
     * Set the value of a provider setting.
     *
     * @param int $provider_id Provider ID.
     * @param string $name Setting name.
     * @param mixed|null $value Setting value.
     */
    public function set_setting(int $provider_id, string $name, mixed $value = null): void
    {
        if (!$this->db->update('user_settings', [$name => $value], ['id_users' => $provider_id])) {
            throw new RuntimeException('Could not set the new provider setting value: ' . $name);
        }
    }

    /**
     * Update an existing provider.
     *
     * @param array $provider Associative array with the provider data.
     *
     * @return int Returns the provider ID.
     *
     * @throws RuntimeException|Exception
     */
    protected function update(array $provider): int
    {
        $provider['update_datetime'] = date('Y-m-d H:i:s');

        $service_ids = $provider['services'];
        $station_ids = $provider['stations'] ?? []; // Salon Flora customization
        $service_commissions = $provider['service_commissions'] ?? []; // Salon Flora customization
        $skill_ids = $provider['skills'] ?? []; // Ki Reservation (2026-08-26)

        $settings = $provider['settings'];

        unset(
            $provider['services'],
            $provider['settings'],
            $provider['stations'],
            $provider['service_commissions'],
            $provider['skills'],
        );

        if (isset($settings['password'])) {
            $existing_settings = $this->db->get_where('user_settings', ['id_users' => $provider['id']])->row_array();

            if (empty($existing_settings)) {
                throw new RuntimeException('No settings record found for provider with ID: ' . $provider['id']);
            }

            if (empty($existing_settings['salt'])) {
                $existing_settings['salt'] = $settings['salt'] = generate_salt();
            }

            $settings['password'] = hash_password($existing_settings['salt'], $settings['password']);
        }

        $provider = $this->encrypt_pii($provider);

        if (!$this->db->update('users', $provider, ['id' => $provider['id']])) {
            throw new RuntimeException('Could not update provider.');
        }

        $this->set_settings($provider['id'], $settings);
        $this->set_service_ids($provider['id'], $service_ids);
        $this->set_station_ids($provider['id'], $station_ids); // Salon Flora customization
        $this->set_service_commissions($provider['id'], $service_commissions); // Salon Flora customization
        $this->set_skill_ids($provider['id'], $skill_ids); // Ki Reservation (2026-08-26)

        return $provider['id'];
    }

    /**
     * Save the provider service IDs.
     *
     * @param int $provider_id Provider ID.
     * @param array $service_ids Service IDs.
     */
    public function set_service_ids(int $provider_id, array $service_ids): void
    {
        // Re-insert the provider-service connections.
        $this->db->delete('services_providers', ['id_users' => $provider_id]);

        foreach ($service_ids as $service_id) {
            $service_provider_connection = [
                'id_users' => $provider_id,
                'id_services' => $service_id,
            ];

            $this->db->insert('services_providers', $service_provider_connection);
        }
    }

    /**
     * Salon Flora customization - save the provider station IDs (a provider may be assigned to more than one
     * physical station).
     *
     * @param int $provider_id Provider ID.
     * @param array $station_ids Station IDs.
     */
    public function set_station_ids(int $provider_id, array $station_ids): void
    {
        $this->db->delete('stations_providers', ['id_users' => $provider_id]);

        foreach ($station_ids as $station_id) {
            $this->db->insert('stations_providers', [
                'id_users' => $provider_id,
                'id_stations' => $station_id,
            ]);
        }
    }

    /**
     * Salon Flora customization - save the per-service commission overrides for a provider. Only overrides for
     * services still assigned to the provider are kept; anything else is discarded to avoid orphan rows.
     *
     * @param int $provider_id Provider ID.
     * @param array $service_commissions Array of ['id_services' => int, 'commission_type' => string, 'commission_value' => float].
     */
    public function set_service_commissions(int $provider_id, array $service_commissions): void
    {
        $this->db->delete('provider_service_commissions', ['id_users' => $provider_id]);

        $assigned_service_ids = $this->get_service_ids($provider_id);

        foreach ($service_commissions as $commission) {
            $service_id = (int) ($commission['id_services'] ?? 0);

            if (!in_array($service_id, $assigned_service_ids, true)) {
                continue;
            }

            $this->db->insert('provider_service_commissions', [
                'id_users' => $provider_id,
                'id_services' => $service_id,
                'commission_type' => $commission['commission_type'] ?? 'percentage',
                'commission_value' => (float) ($commission['commission_value'] ?? 0),
            ]);
        }
    }

    /**
     * Remove an existing provider from the database.
     *
     * @param int $provider_id Provider ID.
     *
     * @throws RuntimeException
     */
    public function delete(int $provider_id): void
    {
        // Salon Flora customization: clean up the station assignments and commission overrides, no FK cascade.
        $this->db->delete('stations_providers', ['id_users' => $provider_id]);
        $this->db->delete('provider_service_commissions', ['id_users' => $provider_id]);
        $this->db->delete('provider_skill_assignments', ['id_users' => $provider_id]); // Ki Reservation (2026-08-26)

        $this->db->delete('users', ['id' => $provider_id]);
    }

    /**
     * Salon Flora customization (2026-08-24, KVKK data retention) - anonymize an (ex-)provider IN
     * PLACE for a "KVKK - Unutulma Hakkı" request, without deleting the row - appointments.id_users_provider
     * is ON DELETE CASCADE, so deleting would destroy their appointment/commission/payout history too,
     * which the business needs for accounting. No automated job runs this for providers (staff
     * departure is a manual HR action, not a time-based rule like customer inactivity) - it's exposed
     * only via a manual admin action.
     *
     * @param int $provider_id
     */
    public function anonymize(int $provider_id): void
    {
        $this->db->update(
            'users',
            [
                'first_name' => 'Silinmiş',
                'last_name' => 'Terapist',
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
            ['id' => $provider_id],
        );
    }

    /**
     * Get a specific field value from the database.
     *
     * @param int $provider_id Provider ID.
     * @param string $field Name of the value to be returned.
     *
     * @return mixed Returns the selected provider value from the database.
     *
     * @throws InvalidArgumentException
     */
    public function value(int $provider_id, string $field): mixed
    {
        if (empty($field)) {
            throw new InvalidArgumentException('The field argument is cannot be empty.');
        }

        if (empty($provider_id)) {
            throw new InvalidArgumentException('The provider ID argument cannot be empty.');
        }

        // Check whether the provider exists.
        $query = $this->db->get_where('users', ['id' => $provider_id]);

        if (!$query->num_rows()) {
            throw new InvalidArgumentException(
                'The provided provider ID was not found in the database: ' . $provider_id,
            );
        }

        // Check if the required field is part of the provider data.
        $provider = $query->row_array();

        $this->cast($provider);
        $provider = $this->decrypt_pii($provider);

        if (!array_key_exists($field, $provider)) {
            throw new InvalidArgumentException('The requested field was not found in the provider data: ' . $field);
        }

        return $provider[$field];
    }

    /**
     * Get the value of a provider setting.
     *
     * @param int $provider_id Provider ID.
     * @param string $name Setting name.
     *
     * @return string Returns the value of the requested user setting.
     */
    public function get_setting(int $provider_id, string $name): string
    {
        $settings = $this->db->get_where('user_settings', ['id_users' => $provider_id])->row_array();

        if (!array_key_exists($name, $settings)) {
            throw new RuntimeException('The requested setting value was not found: ' . $provider_id);
        }

        return $settings[$name];
    }

    /**
     * Save a new or existing working plan exception.
     *
     * @param int $provider_id Provider ID.
     * @param array $working_plan_exception Associative array with the working plan exception data (startDate, endDate, startTime, endTime, breaks, id).
     *
     * @return int Returns the exception ID.
     *
     * @throws Exception
     */
    public function save_working_plan_exception(int $provider_id, array $working_plan_exception): int
    {
        // Validate the working plan exception data.
        $start_date = $working_plan_exception['startDate'] ?? null;
        $end_date = $working_plan_exception['endDate'] ?? $start_date;
        $start_time = $working_plan_exception['startTime'] ?? null;
        $end_time = $working_plan_exception['endTime'] ?? null;
        $breaks = $working_plan_exception['breaks'] ?? [];
        $id = $working_plan_exception['id'] ?? null;

        if (empty($start_date) || empty($end_date)) {
            throw new InvalidArgumentException('Start date and end date are required for working plan exception.');
        }

        if (strtotime($start_date) > strtotime($end_date)) {
            throw new InvalidArgumentException('Working plan exception start date must be before or equal to end date.');
        }

        // If start_time and end_time are provided, validate them
        if (!empty($start_time) && !empty($end_time)) {
            $start = date('H:i', strtotime($start_time));
            $end = date('H:i', strtotime($end_time));

            if ($start > $end) {
                throw new InvalidArgumentException('Working plan exception start time must be before end time.');
            }
        }

        // Make sure the provider record exists.
        $where = [
            'id' => $provider_id,
            'id_roles' => $this->db->get_where('roles', ['slug' => DB_SLUG_PROVIDER])->row()->id,
        ];

        if ($this->db->get_where('users', $where)->num_rows() === 0) {
            throw new InvalidArgumentException('Provider ID was not found in the database: ' . $provider_id);
        }

        $this->load->model('working_plan_exceptions_model');

        $exception_data = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'id_users_provider' => $provider_id,
            'start_time' => !empty($start_time) ? date('H:i', strtotime($start_time)) : null,
            'end_time' => !empty($end_time) ? date('H:i', strtotime($end_time)) : null,
            'breaks' => !empty($breaks) ? json_encode($breaks) : null,
        ];

        if ($id) {
            $exception_data['id'] = $id;
        }

        return $this->working_plan_exceptions_model->save($exception_data);
    }

    /**
     * Get a specific provider from the database.
     *
     * @param int $provider_id The ID of the record to be returned.
     *
     * @return array Returns an array with the provider data.
     *
     * @throws InvalidArgumentException
     */
    public function find(int $provider_id): array
    {
        $provider = $this->db->get_where('users', ['id' => $provider_id])->row_array();

        if (!$provider) {
            throw new InvalidArgumentException(
                'The provided provider ID was not found in the database: ' . $provider_id,
            );
        }

        $this->cast($provider);
        $provider = $this->decrypt_pii($provider);
        $provider['settings'] = $this->get_settings($provider['id']);
        $provider['services'] = $this->get_service_ids($provider['id']);
        $provider['stations'] = $this->get_station_ids($provider['id']); // Salon Flora customization
        $provider['skills'] = $this->get_skill_ids($provider['id']); // Ki Reservation (2026-08-26)
        $provider['service_commissions'] = $this->get_service_commissions($provider['id']); // Salon Flora customization

        return $provider;
    }

    /**
     * Delete a provider working plan exception.
     *
     * @param string $date The working plan exception date (in YYYY-MM-DD format).
     * @param int $provider_id The selected provider record id.
     *
     * @throws Exception If $provider_id argument is invalid.
     */
    public function delete_working_plan_exception(int $provider_id, string $date): void
    {
        $this->load->model('working_plan_exceptions_model');

        $this->working_plan_exceptions_model->delete_by_provider_and_date($provider_id, $date);
    }

    /**
     * Get all the provider records that are assigned to at least one service.
     *
     * @param bool $without_private Only include the public providers.
     *
     * @return array Returns an array of providers.
     */
    public function get_available_providers(bool $without_private = false, ?int $branch_id = null): array
    {
        if ($without_private) {
            $this->db->where('users.is_private', false);
        }

        // Multi-branch support: apply branch filter only if provided and branch count is > 1
        if ($branch_id !== null) {
            $this->load->model('branches_model');
            if ($this->branches_model->count_active() > 1) {
                $this->db->where('users.id_branches', $branch_id);
            }
        }

        $providers = $this->db
            ->select('users.*')
            ->from('users')
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->join('services_providers', 'services_providers.id_users = users.id', 'inner')
            ->where('roles.slug', DB_SLUG_PROVIDER)
            ->order_by('first_name ASC, last_name ASC, email ASC')
            ->group_by('users.id')
            ->get()
            ->result_array();

        foreach ($providers as &$provider) {
            $this->cast($provider);
            $provider = $this->decrypt_pii($provider);
            $provider['settings'] = $this->get_settings($provider['id']);
            $provider['services'] = $this->get_service_ids($provider['id']);
            $provider['stations'] = $this->get_station_ids($provider['id']); // Salon Flora customization
        $provider['skills'] = $this->get_skill_ids($provider['id']); // Ki Reservation (2026-08-26)
            $provider['service_commissions'] = $this->get_service_commissions($provider['id']); // Salon Flora customization
        }

        return $providers;
    }

    /**
     * Get the query builder interface, configured for use with the users (provider-filtered) table.
     *
     * @return CI_DB_query_builder
     */
    public function query(): CI_DB_query_builder
    {
        $role_id = $this->get_provider_role_id();

        return $this->db->from('users')->where('id_roles', $role_id);
    }

    /**
     * Search providers by the provided keyword.
     *
     * @param string $keyword Search keyword.
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @param string|null $order_by Order by.
     *
     * @return array Returns an array of providers.
     */
    public function search(string $keyword, ?int $limit = null, ?int $offset = null, ?string $order_by = null): array
    {
        $role_id = $this->get_provider_role_id();

        // Salon Flora customization - see Customers_model::search() for the full rationale: encrypted
        // fields can only match exactly (via their hash index), address/state/zip_code/notes have no
        // search substitute at all and are dropped.
        $keyword_hash = sf_pii_hash($keyword);

        $providers = $this->db
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

        foreach ($providers as &$provider) {
            $this->cast($provider);
            $provider = $this->decrypt_pii($provider);
            $provider['settings'] = $this->get_settings($provider['id']);
            $provider['services'] = $this->get_service_ids($provider['id']);
            $provider['stations'] = $this->get_station_ids($provider['id']); // Salon Flora customization
        $provider['skills'] = $this->get_skill_ids($provider['id']); // Ki Reservation (2026-08-26)
            $provider['service_commissions'] = $this->get_service_commissions($provider['id']); // Salon Flora customization
        }

        return $providers;
    }

    /**
     * Get providers as options for dropdowns.
     *
     * @param array|string|null $where Where conditions.
     *
     * @return array Returns an array of options with 'value' and 'label' keys.
     */
    public function to_options(array|string|null $where = null): array
    {
        $role_id = $this->get_provider_role_id();

        if ($where !== null) {
            $this->db->where($where);
        }

        $providers = $this->db
            ->select('id, first_name, last_name')
            ->from('users')
            ->where('id_roles', $role_id)
            ->order_by('first_name, last_name')
            ->get()
            ->result_array();

        $options = [];

        foreach ($providers as $provider) {
            $options[] = [
                'value' => (int) $provider['id'],
                'label' => trim($provider['first_name'] . ' ' . $provider['last_name']),
            ];
        }

        return $options;
    }

    /**
     * Load related resources to a provider.
     *
     * @param array $provider Associative array with the provider data.
     * @param array $resources Resource names to be attached ("services" supported).
     *
     * @throws InvalidArgumentException
     */
    public function load(array &$provider, array $resources): void
    {
        if (empty($provider) || empty($resources)) {
            return;
        }

        foreach ($resources as $resource) {
            $provider['services'] = match ($resource) {
                'services' => $this->db
                    ->select('services.*')
                    ->from('services')
                    ->join('services_providers', 'services_providers.id_services = services.id', 'inner')
                    ->where('id_users', $provider['id'])
                    ->get()
                    ->result_array(),
                default => throw new InvalidArgumentException(
                    'The requested provider relation is not supported: ' . $resource,
                ),
            };
        }
    }

    /**
     * Convert the database provider record to the equivalent API resource.
     *
     * @param array $provider Provider data.
     */
    public function api_encode(array &$provider): void
    {
        $encoded_resource = [
            'id' => array_key_exists('id', $provider) ? (int) $provider['id'] : null,
            'firstName' => $provider['first_name'],
            'lastName' => $provider['last_name'],
            'email' => $provider['email'],
            'mobile' => $provider['mobile_number'],
            'phone' => $provider['phone_number'],
            'address' => $provider['address'],
            'city' => $provider['city'],
            'state' => $provider['state'],
            'zip' => $provider['zip_code'],
            'notes' => $provider['notes'],
            'isPrivate' => $provider['is_private'],
            'ldapDn' => $provider['ldap_dn'],
            'timezone' => $provider['timezone'],
            'language' => $provider['language'],
        ];

        if (array_key_exists('services', $provider)) {
            $encoded_resource['services'] = $provider['services'];
        }

        if (array_key_exists('settings', $provider)) {
            $encoded_resource['settings'] = [
                'username' => $provider['settings']['username'],
                'notifications' => filter_var($provider['settings']['notifications'], FILTER_VALIDATE_BOOLEAN),
                'calendarView' => $provider['settings']['calendar_view'],
                'googleSync' => array_key_exists('google_sync', $provider['settings'])
                    ? filter_var($provider['settings']['google_sync'], FILTER_VALIDATE_BOOLEAN)
                    : null,
                'googleToken' => array_key_exists('google_token', $provider['settings'])
                    ? $provider['settings']['google_token']
                    : null,
                'googleCalendar' => array_key_exists('google_calendar', $provider['settings'])
                    ? $provider['settings']['google_calendar']
                    : null,
                'caldavSync' => array_key_exists('caldav_sync', $provider['settings'])
                    ? filter_var($provider['settings']['caldav_sync'], FILTER_VALIDATE_BOOLEAN)
                    : null,
                'caldavUrl' => array_key_exists('caldav_url', $provider['settings'])
                    ? $provider['settings']['caldav_url']
                    : null,
                'caldavUsername' => array_key_exists('caldav_username', $provider['settings'])
                    ? $provider['settings']['caldav_username']
                    : null,
                'caldavPassword' => array_key_exists('caldav_password', $provider['settings'])
                    ? $provider['settings']['caldav_password']
                    : null,
                'syncFutureDays' => array_key_exists('sync_future_days', $provider['settings'])
                    ? (int) $provider['settings']['sync_future_days']
                    : null,
                'syncPastDays' => array_key_exists('sync_past_days', $provider['settings'])
                    ? (int) $provider['settings']['sync_past_days']
                    : null,
                'workingPlan' => array_key_exists('working_plan', $provider['settings'])
                    ? json_decode($provider['settings']['working_plan'], true)
                    : null,
                'workingPlanExceptions' => array_key_exists('working_plan_exceptions', $provider['settings'])
                    ? json_decode($provider['settings']['working_plan_exceptions'], true)
                    : null,
            ];
        }

        $provider = $encoded_resource;
    }

    /**
     * Convert the API resource to the equivalent database provider record.
     *
     * @param array $provider API resource.
     * @param array|null $base Base provider data to be overwritten with the provided values (useful for updates).
     */
    public function api_decode(array &$provider, ?array $base = null): void
    {
        $decoded_resource = $base ?: [];

        if (array_key_exists('id', $provider)) {
            $decoded_resource['id'] = $provider['id'];
        }

        if (array_key_exists('firstName', $provider)) {
            $decoded_resource['first_name'] = $provider['firstName'];
        }

        if (array_key_exists('lastName', $provider)) {
            $decoded_resource['last_name'] = $provider['lastName'];
        }

        if (array_key_exists('email', $provider)) {
            $decoded_resource['email'] = $provider['email'];
        }

        if (array_key_exists('mobile', $provider)) {
            $decoded_resource['mobile_number'] = $provider['mobile'];
        }

        if (array_key_exists('phone', $provider)) {
            $decoded_resource['phone_number'] = $provider['phone'];
        }

        if (array_key_exists('address', $provider)) {
            $decoded_resource['address'] = $provider['address'];
        }

        if (array_key_exists('city', $provider)) {
            $decoded_resource['city'] = $provider['city'];
        }

        if (array_key_exists('state', $provider)) {
            $decoded_resource['state'] = $provider['state'];
        }

        if (array_key_exists('zip', $provider)) {
            $decoded_resource['zip_code'] = $provider['zip'];
        }

        if (array_key_exists('notes', $provider)) {
            $decoded_resource['notes'] = $provider['notes'];
        }

        if (array_key_exists('timezone', $provider)) {
            $decoded_resource['timezone'] = $provider['timezone'];
        }

        if (array_key_exists('language', $provider)) {
            $decoded_resource['language'] = $provider['language'];
        }

        if (array_key_exists('services', $provider)) {
            $decoded_resource['services'] = $provider['services'];
        }

        if (array_key_exists('isPrivate', $provider)) {
            $decoded_resource['is_private'] = (bool) $provider['isPrivate'];
        }

        if (array_key_exists('ldapDn', $provider)) {
            $decoded_resource['ldap_dn'] = $provider['ldapDn'];
        }

        if (array_key_exists('settings', $provider)) {
            if (empty($decoded_resource['settings'])) {
                $decoded_resource['settings'] = [];
            }

            if (array_key_exists('username', $provider['settings'])) {
                $decoded_resource['settings']['username'] = $provider['settings']['username'];
            }

            if (array_key_exists('password', $provider['settings'])) {
                $decoded_resource['settings']['password'] = $provider['settings']['password'];
            }

            if (array_key_exists('calendarView', $provider['settings'])) {
                $decoded_resource['settings']['calendar_view'] = $provider['settings']['calendarView'];
            }

            if (array_key_exists('notifications', $provider['settings'])) {
                $decoded_resource['settings']['notifications'] = filter_var(
                    $provider['settings']['notifications'],
                    FILTER_VALIDATE_BOOLEAN,
                );
            }

            if (array_key_exists('googleSync', $provider['settings'])) {
                $decoded_resource['settings']['google_sync'] = filter_var(
                    $provider['settings']['googleSync'],
                    FILTER_VALIDATE_BOOLEAN,
                );
            }

            if (array_key_exists('googleCalendar', $provider['settings'])) {
                $decoded_resource['settings']['google_calendar'] = $provider['settings']['googleCalendar'];
            }

            if (array_key_exists('googleToken', $provider['settings'])) {
                $decoded_resource['settings']['google_token'] = $provider['settings']['googleToken'];
            }

            if (array_key_exists('caldavSync', $provider['settings'])) {
                $decoded_resource['settings']['caldav_sync'] = $provider['settings']['caldavSync'];
            }

            if (array_key_exists('caldavUrl', $provider['settings'])) {
                $decoded_resource['settings']['caldav_url'] = $provider['settings']['caldavUrl'];
            }

            if (array_key_exists('caldavUsername', $provider['settings'])) {
                $decoded_resource['settings']['caldav_username'] = $provider['settings']['caldavUsername'];
            }

            if (array_key_exists('caldavPassword', $provider['settings'])) {
                $decoded_resource['settings']['caldav_password'] = $provider['settings']['caldavPassword'];
            }

            if (array_key_exists('syncFutureDays', $provider['settings'])) {
                $decoded_resource['settings']['sync_future_days'] = $provider['settings']['syncFutureDays'];
            }

            if (array_key_exists('syncPastDays', $provider['settings'])) {
                $decoded_resource['settings']['sync_past_days'] = $provider['settings']['syncPastDays'];
            }

            if (array_key_exists('workingPlan', $provider['settings'])) {
                $decoded_resource['settings']['working_plan'] = json_encode($provider['settings']['workingPlan']);
            }

            if (array_key_exists('workingPlanExceptions', $provider['settings'])) {
                $decoded_resource['settings']['working_plan_exceptions'] = json_encode(
                    $provider['settings']['workingPlanExceptions'],
                );
            }
        }

        $provider = $decoded_resource;
    }

    /**
     * Quickly check if a service is assigned to a provider.
     *
     * @param int $provider_id
     * @param int $service_id
     *
     * @return bool
     */
    public function is_service_supported(int $provider_id, int $service_id): bool
    {
        $provider = $this->find($provider_id);

        return in_array($service_id, $provider['services']);
    }
}
