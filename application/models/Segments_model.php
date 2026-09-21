<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi (Dalga 3 / Faz 3.3) - Marketing Segments Model
 *
 * Named customer lists. Each segment has a type + JSON rules; member_count is a
 * cached count that staff refresh explicitly (per segment or all). The actual
 * member resolution for a broadcast happens live in get_member_ids() so a stale
 * count never blocks a send.
 *
 * Types:
 *   vip      → {"min_appointments": 5}         customers with ≥N appointments
 *   inactive → {"inactive_days": 60}           customers with no appointment in N days
 *   birthday → {"days_ahead": 14}              customers whose birthday falls within N days
 *   all      → every customer
 *   custom   → {"customer_ids": [1,2,3]}       explicit list
 * ---------------------------------------------------------------------------- */

class Segments_model extends EA_Model
{
    protected array $casts = [
        'id' => 'integer',
        'member_count' => 'integer',
        'enabled' => 'integer',
    ];

    public const TYPES = ['vip', 'inactive', 'birthday', 'all', 'custom'];

    /**
     * Get a specific segment.
     *
     * @param int $segment_id Segment ID.
     * @return array The segment row.
     * @throws InvalidArgumentException
     */
    public function find(int $segment_id): array
    {
        $segment = $this->db->get_where('marketing_segments', ['id' => $segment_id])->row_array();

        if (!$segment) {
            throw new InvalidArgumentException('Segment bulunamadı: ' . $segment_id);
        }

        $this->cast($segment);

        return $segment;
    }

    /**
     * Get all segments, newest first.
     */
    public function get(): array
    {
        $segments = $this->db->order_by('updated_at', 'DESC')->get('marketing_segments')->result_array();

        foreach ($segments as &$segment) {
            $this->cast($segment);
        }

        return $segments;
    }

    /**
     * Save (insert or update) a segment.
     *
     * @return int Segment ID.
     * @throws InvalidArgumentException
     */
    public function save(array $segment): int
    {
        $this->validate($segment);

        $now = date('Y-m-d H:i:s');

        if (empty($segment['id'])) {
            unset($segment['id']);
            $segment['created_at'] = $now;
            $segment['updated_at'] = $now;
            $segment['member_count'] = 0;
            $segment['last_calculated'] = null;

            if (!$this->db->insert('marketing_segments', $segment)) {
                throw new RuntimeException('Segment eklenemedi.');
            }

            return (int) $this->db->insert_id();
        }

        $segment['updated_at'] = $now;

        if (!$this->db->update('marketing_segments', $segment, ['id' => $segment['id']])) {
            throw new RuntimeException('Segment güncellenemedi.');
        }

        return (int) $segment['id'];
    }

    /**
     * Delete a segment.
     */
    public function delete(int $segment_id): void
    {
        $this->db->delete('marketing_segments', ['id' => $segment_id]);
    }

    /**
     * Validate a segment row.
     *
     * @throws InvalidArgumentException
     */
    public function validate(array $segment): void
    {
        if (empty($segment['name'])) {
            throw new InvalidArgumentException('Segment adı zorunludur.');
        }

        if (!in_array($segment['type'] ?? '', self::TYPES, true)) {
            throw new InvalidArgumentException('Geçersiz segment türü: ' . ($segment['type'] ?? ''));
        }
    }

    /**
     * Resolve the member customer-IDs for a segment, live.
     *
     * @param array $segment Segment row.
     * @return int[] Customer IDs.
     */
    public function get_member_ids(array $segment): array
    {
        $rules = $this->parse_rules($segment);

        $type = $segment['type'];

        if ($type === 'custom') {
            return array_values(array_unique(array_map('intval', $rules['customer_ids'] ?? [])));
        }

        if ($type === 'all') {
            return $this->all_customer_ids();
        }

        if ($type === 'vip') {
            return $this->vip_customer_ids((int) ($rules['min_appointments'] ?? 5));
        }

        if ($type === 'inactive') {
            return $this->inactive_customer_ids((int) ($rules['inactive_days'] ?? 60));
        }

        if ($type === 'birthday') {
            return $this->birthday_customer_ids((int) ($rules['days_ahead'] ?? 14), $rules);
        }

        return [];
    }

    /**
     * Refresh a single segment's member_count from a live calculation.
     *
     * @return int The new member count.
     */
    public function refresh_count(int $segment_id): int
    {
        $segment = $this->find($segment_id);

        $member_ids = $this->get_member_ids($segment);

        $this->db->update('marketing_segments', [
            'member_count' => count($member_ids),
            'last_calculated' => date('Y-m-d H:i:s'),
        ], ['id' => $segment_id]);

        return count($member_ids);
    }

    /**
     * Refresh member_count for every enabled segment and return segment-id=>count.
     *
     * @return array<string,int>
     */
    public function refresh_all_counts(): array
    {
        $counts = [];

        $segments = $this->db->where('enabled', 1)->get('marketing_segments')->result_array();

        foreach ($segments as $segment) {
            $this->cast($segment);
            $counts[(string) $segment['id']] = $this->refresh_count($segment['id']);
        }

        return $counts;
    }

    /* ---------------------------------------------------------------- */

    /**
     * Parse a segment's JSON rules into an array (always returns an array).
     */
    protected function parse_rules(array $segment): array
    {
        $rules = json_decode((string) ($segment['rules'] ?? ''), true);

        return is_array($rules) ? $rules : [];
    }

    /**
     * Every customer ID in the system.
     *
     * @return int[]
     */
    protected function all_customer_ids(): array
    {
        $rows = $this->db
            ->select('users.id')
            ->from('users')
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->where('roles.slug', DB_SLUG_CUSTOMER)
            ->get()
            ->result_array();

        return array_map('intval', array_column($rows, 'id'));
    }

    /**
     * Customers with at least $min_auth appointments (any non-cancelled status).
     *
     * @return int[]
     */
    protected function vip_customer_ids(int $min_appointments): array
    {
        if ($min_appointments <= 0) {
            return $this->all_customer_ids();
        }

        $this->db
            ->select('u.id')
            ->from('users u')
            ->join('roles r', 'r.id = u.id_roles', 'inner')
            ->join('appointments a', 'a.id_users_customer = u.id', 'inner')
            ->where('r.slug', DB_SLUG_CUSTOMER)
            ->group_by('u.id')
            ->having('COUNT(a.id) >=', (string) $min_appointments, false);

        $rows = $this->db->get()->result_array();

        return array_map('intval', array_column($rows, 'id'));
    }

    /**
     * Customers whose most recent appointment is older than $inactive_days.
     *
     * @return int[]
     */
    protected function inactive_customer_ids(int $inactive_days): array
    {
        $cutoff = date('Y-m-d H:i:s', strtotime('-' . max(0, $inactive_days) . ' days'));

        $active_ids = $this->db
            ->select('a.id_users_customer')
            ->from('appointments a')
            ->where('a.start_datetime >=', $cutoff)
            ->where('a.status !=', 'cancelled')
            ->distinct()
            ->get()
            ->result_array();

        $active_ids = array_map('intval', array_column($active_ids, 'id_users_customer'));

        $all = $this->all_customer_ids();

        return array_values(array_diff($all, $active_ids));
    }

    /**
     * Customers whose birthday (custom_field with a date) falls within the next $days_ahead days.
     * The field is configurable per segment via rules["field"] (default custom_field_1).
     *
     * @return int[]
     */
    protected function birthday_customer_ids(int $days_ahead, array $rules = []): array
    {
        $rule_field = $this->birthday_field($rules);

        // Every candidate customer that has any value in the date field.
        $rows = $this->db
            ->select('u.id, u.' . $rule_field . ' AS birthdate')
            ->from('users u')
            ->join('roles r', 'r.id = u.id_roles', 'inner')
            ->where('r.slug', DB_SLUG_CUSTOMER)
            ->where('u.' . $rule_field . ' IS NOT NULL')
            ->where('u.' . $rule_field . ' !=', '')
            ->get()
            ->result_array();

        $window = max(0, $days_ahead);
        $today = (new DateTimeImmutable())->setTime(0, 0);
        $ids = [];

        foreach ($rows as $row) {
            $birth_ts = strtotime((string) $row['birthdate']);

            if ($birth_ts === false) {
                continue;
            }

            $birth = date('m-d', $birth_ts);

            for ($i = 0; $i <= $window; $i++) {
                $candidate = $today->modify('+' . $i . ' days')->format('m-d');

                if ($birth === $candidate) {
                    $ids[] = (int) $row['id'];
                    break;
                }
            }
        }

        return $ids;
    }

    /**
     * Which user column holds the birthday date (rule override supported).
     *
     * @param array $rules Parsed segment rules.
     */
    protected function birthday_field(array $rules = []): string
    {
        $field = $rules['field'] ?? '';

        if (in_array($field, ['custom_field_1', 'custom_field_2', 'custom_field_3', 'custom_field_4', 'custom_field_5'], true)) {
            return $field;
        }

        return 'custom_field_1';
    }
}