<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Dalga 1-4 endpoint denetimi (2026-09-16).
 *
 * Products.php (inventory, migration 107) ve Packages.php (migration 118 civari) hicbir
 * zaman kendi "roles" tablosu permission kolonunu almamisti - cannot('view', PRIV_PRODUCTS)/
 * cannot('view', PRIV_PACKAGES) bu yuzden HER rolde (admin dahil) hep false donuyordu, cunku
 * Roles_model::get_permissions_by_slug() sadece "roles" tablosunun GERCEKTEN VAR OLAN
 * kolonlarini geziyor. Sonuc: bu iki sayfa hicbir kiracida hicbir zaman acilamiyordu (403
 * Forbidden) - taze bir test kiracisinda (waveaudit) yapilan uctan uca denetimde bulundu.
 * Ayni desen: 114_add_waitlist_column_to_roles_table.php.
 * ---------------------------------------------------------------------------- */

class Migration_Add_products_and_packages_columns_to_roles_table extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->field_exists('products', 'roles')) {
            $this->dbforge->add_column('roles', [
                'products' => [
                    'type' => 'INT',
                    'constraint' => '11',
                    'null' => true,
                ],
            ]);

            $this->db->update('roles', ['products' => '15'], ['slug' => 'admin']);
            $this->db->update('roles', ['products' => '15'], ['slug' => 'secretary']);
            $this->db->update('roles', ['products' => '1'], ['slug' => 'provider']);
            $this->db->update('roles', ['products' => '0'], ['slug' => 'customer']);
        }

        if (!$this->db->field_exists('packages', 'roles')) {
            $this->dbforge->add_column('roles', [
                'packages' => [
                    'type' => 'INT',
                    'constraint' => '11',
                    'null' => true,
                ],
            ]);

            $this->db->update('roles', ['packages' => '15'], ['slug' => 'admin']);
            $this->db->update('roles', ['packages' => '15'], ['slug' => 'secretary']);
            $this->db->update('roles', ['packages' => '1'], ['slug' => 'provider']);
            $this->db->update('roles', ['packages' => '0'], ['slug' => 'customer']);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->field_exists('products', 'roles')) {
            $this->dbforge->drop_column('roles', 'products');
        }

        if ($this->db->field_exists('packages', 'roles')) {
            $this->dbforge->drop_column('roles', 'packages');
        }
    }
}
