<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * BooKi (2026-09-17) - short review links. The 64-hex review token is unwieldy over
 * SMS/WhatsApp; `short_code` is a short, unguessable alias resolved by Review::short()
 * (route `r/(:any)`) via a 302 redirect to the real `review/index/{token}` URL. The
 * token itself stays the one real credential - short_code is never accepted as a
 * substitute auth mechanism anywhere else.
 */
class Migration_Add_short_code_to_reviews extends EA_Migration
{
    public function up(): void
    {
        if ($this->db->table_exists('reviews')
            && $this->db->field_exists('token', 'reviews')
            && !$this->db->field_exists('short_code', 'reviews')) {
            $this->dbforge->add_column('reviews', [
                'short_code' => [
                    'type' => 'VARCHAR',
                    'constraint' => 10,
                    'null' => true,
                    'after' => 'token',
                ],
            ]);

            $table = $this->db->protect_identifiers($this->db->dbprefix('reviews'));
            $this->db->query("ALTER TABLE {$table} ADD UNIQUE KEY `uq_reviews_short_code` (`short_code`)");
        }
    }

    public function down(): void
    {
        if ($this->db->table_exists('reviews') && $this->db->field_exists('short_code', 'reviews')) {
            $this->dbforge->drop_column('reviews', 'short_code');
        }
    }
}
