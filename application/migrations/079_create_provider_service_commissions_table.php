<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Salon Flora customization - per-provider, per-service commission override.
 * users.commission_type/commission_value remains the provider's default rate;
 * a row in this table overrides that default for one specific service
 * (duration variant), e.g. a therapist may earn a different fixed amount for
 * a 60-minute vs a 90-minute massage, and different therapists may earn
 * different amounts for the same service.
 * ---------------------------------------------------------------------------- */

class Migration_Create_provider_service_commissions_table extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->table_exists('provider_service_commissions')) {
            $this->dbforge->add_field([
                'id_users' => [
                    'type' => 'INT',
                    'constraint' => '11',
                    'null' => false,
                ],
                'id_services' => [
                    'type' => 'INT',
                    'constraint' => '11',
                    'null' => false,
                ],
                'commission_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => '32',
                    'null' => false,
                    'default' => 'percentage',
                ],
                'commission_value' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'null' => false,
                    'default' => 0,
                ],
            ]);

            $this->dbforge->add_key('id_users', true);
            $this->dbforge->add_key('id_services', true);

            $this->dbforge->create_table('provider_service_commissions', true, ['engine' => 'InnoDB']);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->table_exists('provider_service_commissions')) {
            $this->dbforge->drop_table('provider_service_commissions');
        }
    }
}
