<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Salon Flora customization - Stations model.
 *
 * Handles the database operations for the "stations" resource (physical
 * treatment rooms/tables). Mirrors the structure of Services_model.php.
 * ---------------------------------------------------------------------------- */

class Stations_model extends EA_Model
{
    protected array $casts = [
        'id' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Save (insert or update) a station.
     *
     * @param array $station Associative array with the station data.
     *
     * @return int Returns the station ID.
     *
     * @throws InvalidArgumentException
     */
    public function save(array $station): int
    {
        $this->validate($station);

        // Salon Flora customization - which services this station can host is a separate many-to-many
        // relationship (stations_services), not a column on the stations table itself - pull it out before the
        // insert/update below and persist it once the station ID is known either way.
        $service_ids = $station['services'] ?? null;
        unset($station['services']);

        if (empty($station['id'])) {
            $station_id = $this->insert($station);
        } else {
            $station_id = $this->update($station);
        }

        if ($service_ids !== null) {
            $this->save_service_assignments($station_id, $service_ids);
        }

        return $station_id;
    }

    /**
     * Salon Flora customization - replace the set of services a station can host. An empty array means the
     * station is open to every service (fail-open - see the migration 082 docblock).
     *
     * @param int $station_id Station ID.
     * @param array $service_ids Service IDs the station should be restricted to.
     */
    protected function save_service_assignments(int $station_id, array $service_ids): void
    {
        $this->db->delete('stations_services', ['id_stations' => $station_id]);

        if (empty($service_ids)) {
            return;
        }

        $rows = array_map(
            static fn($service_id) => ['id_stations' => $station_id, 'id_services' => (int) $service_id],
            $service_ids,
        );

        $this->db->insert_batch('stations_services', $rows);
    }

    /**
     * Validate the station data.
     *
     * @param array $station Associative array with the station data.
     *
     * @throws InvalidArgumentException
     */
    public function validate(array $station): void
    {
        if (!empty($station['id'])) {
            $count = $this->db->get_where('stations', ['id' => $station['id']])->num_rows();

            if (!$count) {
                throw new InvalidArgumentException(
                    'The provided station ID does not exist in the database: ' . $station['id'],
                );
            }
        }

        if (empty($station['name'])) {
            throw new InvalidArgumentException('Not all required fields are provided: ' . print_r($station, true));
        }
    }

    /**
     * Insert a new station into the database.
     *
     * @param array $station Associative array with the station data.
     *
     * @return int Returns the station ID.
     *
     * @throws RuntimeException
     */
    protected function insert(array $station): int
    {
        $station['create_datetime'] = date('Y-m-d H:i:s');
        $station['update_datetime'] = date('Y-m-d H:i:s');

        if (!$this->db->insert('stations', $station)) {
            throw new RuntimeException('Could not insert station.');
        }

        return $this->db->insert_id();
    }

    /**
     * Update an existing station.
     *
     * @param array $station Associative array with the station data.
     *
     * @return int Returns the station ID.
     *
     * @throws RuntimeException
     */
    protected function update(array $station): int
    {
        $station['update_datetime'] = date('Y-m-d H:i:s');

        if (!$this->db->update('stations', $station, ['id' => $station['id']])) {
            throw new RuntimeException('Could not update station.');
        }

        return $station['id'];
    }

    /**
     * Remove an existing station from the database.
     *
     * @param int $station_id Station ID.
     */
    public function delete(int $station_id): void
    {
        // Detach the station from any providers currently assigned to it (Salon Flora customization: now a
        // many-to-many assignment, see stations_providers).
        $this->db->delete('stations_providers', ['id_stations' => $station_id]);

        // Salon Flora customization - detach the station from any services it was restricted to.
        $this->db->delete('stations_services', ['id_stations' => $station_id]);

        // Clear the station reference from past/future appointments that used it.
        $this->db->update('appointments', ['id_stations' => null], ['id_stations' => $station_id]);

        $this->db->delete('stations', ['id' => $station_id]);
    }

    /**
     * Get a specific station from the database.
     *
     * @param int $station_id The ID of the record to be returned.
     *
     * @return array Returns an array with the station data.
     *
     * @throws InvalidArgumentException
     */
    public function find(int $station_id): array
    {
        $station = $this->db->get_where('stations', ['id' => $station_id])->row_array();

        if (!$station) {
            throw new InvalidArgumentException('The provided station ID was not found in the database: ' . $station_id);
        }

        $this->cast($station);

        $station['services'] = $this->get_service_ids($station_id);

        return $station;
    }

    /**
     * Get all stations that match the provided criteria.
     *
     * @param array|string|null $where Where conditions
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @param string|null $order_by Order by.
     *
     * @return array Returns an array of stations.
     */
    public function get(
        array|string|null $where = null,
        ?int $limit = null,
        ?int $offset = null,
        ?string $order_by = null,
    ): array {
        if ($where !== null) {
            $this->db->where($where);
        }

        if ($order_by !== null) {
            $this->db->order_by($this->quote_order_by($order_by));
        }

        $stations = $this->db->get('stations', $limit, $offset)->result_array();

        foreach ($stations as &$station) {
            $this->cast($station);
        }

        return $stations;
    }

    /**
     * Search stations by the provided keyword.
     *
     * @param string $keyword Search keyword.
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @param string|null $order_by Order by.
     *
     * @return array Returns an array of stations.
     */
    public function search(string $keyword, ?int $limit = null, ?int $offset = null, ?string $order_by = null): array
    {
        $stations = $this->db
            ->select()
            ->from('stations')
            ->group_start()
            ->like('name', $keyword)
            ->or_like('notes', $keyword)
            ->group_end()
            ->limit($limit)
            ->offset($offset)
            ->order_by($this->quote_order_by($order_by ?? 'name ASC'))
            ->get()
            ->result_array();

        foreach ($stations as &$station) {
            $this->cast($station);
        }

        return $stations;
    }

    /**
     * Get stations as options for dropdowns.
     *
     * @return array Returns an array of options with 'value' and 'label' keys.
     */
    public function to_options(): array
    {
        $stations = $this->db
            ->select('id, name')
            ->from('stations')
            ->where('is_active', true)
            ->order_by('name')
            ->get()
            ->result_array();

        $options = [];

        foreach ($stations as $station) {
            $options[] = [
                'value' => (int) $station['id'],
                'label' => $station['name'],
            ];
        }

        return $options;
    }

    /**
     * Get the IDs of the providers currently assigned to a station (including the given provider itself).
     *
     * @param int $station_id Station ID.
     * @param int|null $exclude_provider_id Provider ID to exclude from the result.
     *
     * @return array Returns an array of provider (user) IDs.
     */
    public function get_provider_ids(int $station_id, ?int $exclude_provider_id = null): array
    {
        // Salon Flora customization: a provider may now be assigned to more than one station, so this reads from
        // the stations_providers join table instead of the old single users.id_stations column.
        $this->db->select('id_users')->from('stations_providers')->where('id_stations', $station_id);

        if ($exclude_provider_id) {
            $this->db->where('id_users !=', $exclude_provider_id);
        }

        $rows = $this->db->get()->result_array();

        return array_map(static fn($row) => (int) $row['id_users'], $rows);
    }

    /**
     * Salon Flora customization - the service IDs a station is restricted to. An empty array means the station is
     * open to every service (fail-open - a station with no explicit restriction was never deliberately limited).
     *
     * @param int $station_id Station ID.
     *
     * @return array Service IDs, or an empty array if the station is unrestricted.
     */
    public function get_service_ids(int $station_id): array
    {
        $rows = $this->db->select('id_services')->from('stations_services')->where('id_stations', $station_id)->get()->result_array();

        return array_map(static fn($row) => (int) $row['id_services'], $rows);
    }

    /**
     * Salon Flora customization - which active stations can host the given service. This is the standard,
     * default-open resolution: a station with no stations_services rows at all is available to every service
     * (fail-open), and a station that DOES restrict itself only shows up here for the services it lists.
     *
     * @param int $service_id Service ID.
     *
     * @return array Station IDs that can host this service.
     */
    public function get_station_ids_for_service(int $service_id): array
    {
        $restricted_station_ids = array_map(
            static fn($row) => (int) $row['id_stations'],
            $this->db
                ->select('stations_services.id_stations')
                ->from('stations_services')
                ->join('stations', 'stations.id = stations_services.id_stations')
                ->where('stations_services.id_services', $service_id)
                ->where('stations.is_active', true)
                ->get()
                ->result_array(),
        );

        $unrestricted_station_ids = array_map(
            static fn($row) => (int) $row['id'],
            $this->db
                ->select('id')
                ->from('stations')
                ->where('is_active', true)
                ->where(
                    'id NOT IN (SELECT DISTINCT id_stations FROM ' . $this->db->dbprefix('stations_services') . ')',
                    null,
                    false,
                )
                ->get()
                ->result_array(),
        );

        return array_values(array_unique(array_merge($restricted_station_ids, $unrestricted_station_ids)));
    }

    /**
     * Salon Flora customization - the single source of truth for "which stations can this appointment use", given
     * its service and provider. Standard rule: every station the service is available in (get_station_ids_for_service()).
     * Optional override: if the provider has station_restriction_enabled=1 (an explicit staff decision to confine
     * a specific provider to specific rooms - e.g. a provider who only ever uses one particular station), the
     * result is narrowed to the intersection with their ea_stations_providers assignments.
     *
     * @param int $service_id Service ID.
     * @param int $provider_id Provider (user) ID.
     *
     * @return array Candidate station IDs, in no particular order.
     */
    public function get_candidate_station_ids(int $service_id, int $provider_id): array
    {
        $service_station_ids = $this->get_station_ids_for_service($service_id);

        $restriction_enabled = (bool) $this->db->select('station_restriction_enabled')->get_where('users', ['id' => $provider_id])->row('station_restriction_enabled');

        if (!$restriction_enabled) {
            return $service_station_ids;
        }

        $this->load->model('providers_model');

        $provider_station_ids = $this->providers_model->get_station_ids($provider_id);

        return array_values(array_intersect($service_station_ids, $provider_station_ids));
    }

    /**
     * Salon Flora customization - check whether a single station is free during the given period (no OTHER
     * appointment - regardless of provider - is using that physical room/table at that time). Provider-agnostic:
     * a station is a shared physical resource, so any appointment using it blocks it for everyone.
     */
    public function is_station_free(
        int $station_id,
        string $start_datetime,
        string $end_datetime,
        ?int $exclude_appointment_id = null,
    ): bool {
        $this->load->model('appointments_model');

        return !$this->appointments_model->has_station_conflict($station_id, $start_datetime, $end_datetime, $exclude_appointment_id);
    }

    /**
     * Salon Flora customization - among the given candidate stations, which ones are free during the given
     * period. Used to populate the station dropdown with "available now" options (and to mark occupied ones as
     * unavailable rather than hiding them, so staff understand WHY a station is missing).
     *
     * @param array $candidate_station_ids
     * @param string $start_datetime
     * @param string $end_datetime
     * @param int|null $exclude_appointment_id
     *
     * @return array Free station IDs (subset of the candidates).
     */
    public function get_free_station_ids(
        array $candidate_station_ids,
        string $start_datetime,
        string $end_datetime,
        ?int $exclude_appointment_id = null,
    ): array {
        return array_values(
            array_filter(
                $candidate_station_ids,
                fn($station_id) => $this->is_station_free((int) $station_id, $start_datetime, $end_datetime, $exclude_appointment_id),
            ),
        );
    }

    /**
     * Salon Flora customization - the first free station among the candidates, for automatic assignment. Returns
     * null if every candidate is occupied (or there are no candidates at all - can only happen when a provider's
     * station_restriction_enabled override leaves zero stations in common with the service's stations).
     */
    public function find_free_station(
        array $candidate_station_ids,
        string $start_datetime,
        string $end_datetime,
        ?int $exclude_appointment_id = null,
    ): ?int {
        $free = $this->get_free_station_ids($candidate_station_ids, $start_datetime, $end_datetime, $exclude_appointment_id);

        return $free[0] ?? null;
    }

    /**
     * Salon Flora customization - acquire a MySQL named lock per candidate station id (ascending order, to avoid
     * a deadlock between two requests locking the same pair of stations in opposite order). Guards against the
     * check-then-save race between find_free_station() reporting a station free and the appointment that claims
     * it actually being persisted: two concurrent bookings racing for the same physical room could otherwise
     * both read "free" and both save. The lock is intentionally held by the CALLER across that whole span (see
     * release_station_locks()) - a bare 5s timeout per lock keeps a stuck/slow request from blocking others
     * indefinitely, and any lock still held when the DB connection closes at request end is released by MySQL
     * automatically, so a forgotten release() can never leak past a single request.
     *
     * @param array $candidate_station_ids
     *
     * @return bool True if every lock was acquired; false (with none left held) if any timed out.
     */
    public function acquire_station_locks(array $candidate_station_ids): bool
    {
        $ids = array_unique(array_map('intval', $candidate_station_ids));
        sort($ids);

        $acquired = [];

        foreach ($ids as $station_id) {
            $result = $this->db->query('SELECT GET_LOCK(?, 5) AS acquired', ['salonflora_station_' . $station_id]);

            if ((int) ($result->row_array()['acquired'] ?? 0) !== 1) {
                $this->release_station_locks($acquired);

                return false;
            }

            $acquired[] = $station_id;
        }

        return true;
    }

    /**
     * Salon Flora customization - release locks taken by acquire_station_locks(). Safe to call with an empty or
     * already-released set.
     *
     * @param array $candidate_station_ids
     */
    public function release_station_locks(array $candidate_station_ids): void
    {
        foreach (array_unique(array_map('intval', $candidate_station_ids)) as $station_id) {
            $this->db->query('SELECT RELEASE_LOCK(?)', ['salonflora_station_' . $station_id]);
        }
    }
}
