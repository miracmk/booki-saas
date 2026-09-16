<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Dalga 5 endpoint denetimi (2026-09-16).
 *
 * Branches.php (migration 107 civari, PRIV_BRANCHES = 'branches') hicbir zaman kendi "roles"
 * tablosu permission kolonunu almamisti - cannot('view', PRIV_BRANCHES) bu yuzden HER rolde
 * (admin dahil) hep true donuyordu, cunku Roles_model::get_permissions_by_slug() sadece "roles"
 * tablosunun GERCEKTEN VAR OLAN kolonlarini geziyor. Sonuc: /branches hicbir kiracida hicbir
 * rolle acilamiyordu (403 Forbidden).
 *
 * Ayni bug sinifi daha once Products/Packages'ta bulunmus ve migration 140 ile duzeltilmisti;
 * bu dosya onun birebir ayni desenidir. Ayrica bkz. 114 (waitlist), 117 (memberships),
 * 119 (invoices), 122 (pos).
 *
 * Izin seviyeleri: branches bir YAPILANDIRMA kutugudur (fiziksel lokasyon listesi), sagalayicinin
 * gunluk isinde kullandigi bir kaynak degil - en yakin emsal migration 073'teki "stations"
 * (provider => 0). Branches.php'nin kendi docblock'u da "Restricted to admin-level access" diyor
 * ve sayfanin backend menusunde hicbir girisi yok. Bu yuzden provider/customer = 0 (fail-closed);
 * ileride gerekirse yeni bir migration ile 1'e yukseltmek tek satir.
 * ---------------------------------------------------------------------------- */

class Migration_Add_branches_column_to_roles_table extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->field_exists('branches', 'roles')) {
            $this->dbforge->add_column('roles', [
                'branches' => [
                    'type' => 'INT',
                    'constraint' => '11',
                    'null' => true,
                ],
            ]);

            $this->db->update('roles', ['branches' => '15'], ['slug' => 'admin']);
            $this->db->update('roles', ['branches' => '15'], ['slug' => 'secretary']);
            $this->db->update('roles', ['branches' => '0'], ['slug' => 'provider']);
            $this->db->update('roles', ['branches' => '0'], ['slug' => 'customer']);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->field_exists('branches', 'roles')) {
            $this->dbforge->drop_column('roles', 'branches');
        }
    }
}
