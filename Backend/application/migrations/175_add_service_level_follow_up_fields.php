<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migration 174: Service-Level Follow-Up Fields.
 *
 * Adds granular follow-up configuration to the `services` table so each service
 * can define its own follow-up requirement, category, priority, delay override
 * and custom message template.
 *
 * Priority Sectors (follow_up_required = 1 by default):
 *   - health_clinical: dentist, doctor_clinic, psychology_dietitian_clinic
 *   - beauty_wellness (invasive only): laser, peeling, kalıcı makyaj
 *   - automotive: ekspertiz, detailing, mekanik servis
 *   - education (workshop): ceramic, sculpture, glass artwork
 *
 * Other Sectors: follow_up optional (retention_marketing / review_nps)
 */
class Migration_Add_service_level_follow_up_fields extends App_Migration
{
    public function up(): void
    {
        if (!$this->db->table_exists('services')) {
            return;
        }

        // 1. follow_up_required: Whether this service requires a post-service follow-up
        if (!$this->db->field_exists('follow_up_required', 'services')) {
            $this->dbforge->add_column('services', [
                'follow_up_required' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                    'null' => false,
                    'after' => 'description',
                ],
            ]);
        }

        // 2. follow_up_category: The follow-up category type
        if (!$this->db->field_exists('follow_up_category', 'services')) {
            $this->dbforge->add_column('services', [
                'follow_up_category' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                    'default' => null,
                    'after' => 'follow_up_required',
                ],
            ]);
        }

        // 3. follow_up_priority: critical / standard / optional
        if (!$this->db->field_exists('follow_up_priority', 'services')) {
            $this->dbforge->add_column('services', [
                'follow_up_priority' => [
                    'type' => 'ENUM',
                    'constraint' => ['critical', 'standard', 'optional'],
                    'default' => 'optional',
                    'null' => false,
                    'after' => 'follow_up_category',
                ],
            ]);
        }

        // 4. follow_up_delay_override: Service-specific delay override
        if (!$this->db->field_exists('follow_up_delay_override', 'services')) {
            $this->dbforge->add_column('services', [
                'follow_up_delay_override' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                    'default' => null,
                    'after' => 'follow_up_priority',
                ],
            ]);
        }

        // 5. follow_up_message_override: Service-specific WhatsApp message template
        if (!$this->db->field_exists('follow_up_message_override', 'services')) {
            $this->dbforge->add_column('services', [
                'follow_up_message_override' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'follow_up_delay_override',
                ],
            ]);
        }

        // 6. Add composite index for efficient queries
        try {
            $this->db->query('CREATE INDEX idx_services_follow_up ON services (follow_up_required, follow_up_category)');
        } catch (Throwable $e) {
            // Index may already exist
            log_message('debug', 'Migration 174: Index creation skipped - ' . $e->getMessage());
        }
    }

    public function down(): void
    {
        if (!$this->db->table_exists('services')) {
            return;
        }

        $columns_to_drop = [
            'follow_up_required',
            'follow_up_category',
            'follow_up_priority',
            'follow_up_delay_override',
            'follow_up_message_override',
        ];

        foreach ($columns_to_drop as $col) {
            if ($this->db->field_exists($col, 'services')) {
                $this->dbforge->drop_column('services', $col);
            }
        }

        try {
            $this->db->query('DROP INDEX idx_services_follow_up ON services');
        } catch (Throwable $e) {
            log_message('debug', 'Migration 174 down: Index drop skipped - ' . $e->getMessage());
        }
    }
}
