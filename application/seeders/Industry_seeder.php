<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Industry Seeder for BooKi SaaS.
 *
 * Can be invoked from controllers, CLI scripts, or tests to seed
 * full industry blueprint packs with 10 customers, 3-4 providers, and 15-20 appointments.
 */
class Industry_seeder
{
    /**
     * @var CI_Controller
     */
    protected CI_Controller $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->library('blueprint_service');
    }

    /**
     * Seed a specific industry or 'all' industries.
     *
     * @param string $code
     * @param bool $seed_demo
     * @return array
     */
    public function seed(string $code = 'beauty_salon', bool $seed_demo = true): array
    {
        if ($code === 'all') {
            $blueprints = $this->CI->blueprint_service->get_all_blueprints();
            $results = [];
            foreach ($blueprints as $bp) {
                $results[$bp['code']] = $this->CI->blueprint_service->apply_blueprint($bp['code'], $seed_demo);
            }
            return $results;
        }

        return $this->CI->blueprint_service->apply_blueprint($code, $seed_demo);
    }
}

