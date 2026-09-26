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
 * Skills model.
 *
 * Handles the provider skills/specialties catalog (e.g. "Derin Doku Masajı",
 * "Aromaterapi") and their many-to-many assignment to providers. Mirrors the
 * stations checkbox pattern (see Stations_model::to_options(),
 * Providers_model::get_station_ids()/set_station_ids()) but carries no
 * availability/booking logic - it is a tagging vocabulary only.
 *
 * @package Models
 */
class Skills_model extends App_Model
{
    /**
     * Return every skill as a {value, label} option list, for checkbox rendering.
     */
    public function to_options(): array
    {
        $skills = $this->db->select('id, name')->from('provider_skills')->order_by('name')->get()->result_array();

        $options = [];

        foreach ($skills as $skill) {
            $options[] = [
                'value' => (int) $skill['id'],
                'label' => $skill['name'],
            ];
        }

        return $options;
    }

    /**
     * Find an existing skill by (case-insensitive) name or create it.
     *
     * Used by Providers::create_skill() so an admin can add a new skill inline while assigning it to
     * a provider, without a dedicated catalog management page.
     *
     * @param string $name Skill name.
     *
     * @return array {id, name} of the found/created skill.
     *
     * @throws InvalidArgumentException
     */
    public function find_or_create_by_name(string $name): array
    {
        $name = trim($name);

        if ($name === '') {
            throw new InvalidArgumentException('Skill name cannot be empty.');
        }

        $existing = $this->db->get_where('provider_skills', ['name' => $name])->row_array();

        if ($existing) {
            return ['id' => (int) $existing['id'], 'name' => $existing['name']];
        }

        $this->db->insert('provider_skills', ['name' => $name, 'created_at' => date('Y-m-d H:i:s')]);

        return ['id' => $this->db->insert_id(), 'name' => $name];
    }

    /**
     * Get the skill IDs assigned to a provider.
     */
    public function get_skill_ids(int $provider_id): array
    {
        $assignments = $this->db
            ->get_where('provider_skill_assignments', ['id_users' => $provider_id])
            ->result_array();

        return array_map(static fn(array $row) => (int) $row['id_provider_skills'], $assignments);
    }

    /**
     * Replace the skill assignments for a provider.
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
     * Get the provider IDs assigned to a skill, and their names - used by the customer CRM insight
     * panel to suggest a matching provider for a requested service (see customers.js:renderInsights()).
     *
     * @return array<int, string> Provider ID => "First Last" display name.
     */
    public function get_providers_by_skill_ids(array $skill_ids): array
    {
        if (empty($skill_ids)) {
            return [];
        }

        $rows = $this->db
            ->select('u.id, u.first_name, u.last_name')
            ->from('provider_skill_assignments psa')
            ->join('users u', 'u.id = psa.id_users')
            ->where_in('psa.id_provider_skills', $skill_ids)
            ->group_by('u.id')
            ->get()
            ->result_array();

        $providers = [];

        foreach ($rows as $row) {
            $providers[(int) $row['id']] = trim($row['first_name'] . ' ' . $row['last_name']);
        }

        return $providers;
    }
}
