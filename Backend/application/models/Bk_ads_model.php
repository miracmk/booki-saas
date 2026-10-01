<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Model: Bk_ads_model
 * 
 * Manages RandevuBurada Sponsored Ads & Search Ranking Boosts.
 * Boost formula: Adds +50.0 sponsored_rank to search algorithm.
 */
class Bk_ads_model extends CI_Model
{
    private string $table = 'bk_marketplace_ads';

    public const PACKAGES = [
        'category_weekly' => [
            'key'         => 'category_weekly',
            'name'        => 'Kategori Liderliği (Haftalık)',
            'duration'    => 7, // days
            'price'       => 750.00,
            'boost_score' => 50,
            'description' => 'İlçenizde ve kategorinizde arama sonuçlarında 7 gün boyunca en üst sırada sponsorlu rozetiyle yer alın.'
        ],
        'category_monthly' => [
            'key'         => 'category_monthly',
            'name'        => 'Kategori Liderliği (Aylık - Avantajlı)',
            'duration'    => 30, // days
            'price'       => 2500.00,
            'boost_score' => 50,
            'description' => 'Kategorinizde 30 gün boyunca kesintisiz liderlik ve aramalarda en üst sırada listelenme.'
        ],
        'homepage_monthly' => [
            'key'         => 'homepage_monthly',
            'name'        => 'Ana Sayfa Vitrin Sponsorluğu (Aylık)',
            'duration'    => 30, // days
            'price'       => 4500.00,
            'boost_score' => 100,
            'description' => 'RandevuBurada ana sayfasında Trend İşletmeler bandında sabit gösterim ve +100 sıralama gücü.'
        ]
    ];

    public function __construct()
    {
        $this->load->database();
    }

    /**
     * Create an active sponsored ad record after payment confirmation.
     */
    public function create_ad(
        int $tenantId,
        string $packageKey,
        string $adType,
        ?string $category,
        ?string $city,
        ?string $district,
        string $orderId
    ): int {
        if (!isset(self::PACKAGES[$packageKey])) {
            throw new InvalidArgumentException("Geçersiz reklam paketi: {$packageKey}");
        }

        $pkg = self::PACKAGES[$packageKey];
        $now = date('Y-m-d H:i:s');
        $endsAt = date('Y-m-d H:i:s', strtotime("+{$pkg['duration']} days"));

        $this->db->query("
            INSERT INTO `{$this->table}` (
                `id_tenants`, `ad_package_key`, `ad_type`, `category`, `city`, `district`,
                `sponsored_rank_boost`, `price_paid`, `starts_at`, `ends_at`, `status`,
                `tosla_order_id`, `created_at`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?)
        ", [
            $tenantId,
            $packageKey,
            $adType,
            $category,
            $city,
            $district,
            $pkg['boost_score'],
            $pkg['price'],
            $now,
            $endsAt,
            $orderId,
            $now
        ]);

        return (int) $this->db->insert_id();
    }

    /**
     * Get active boosted tenant IDs for search queries.
     * Returns map [tenant_id => max_boost]
     */
    public function get_active_boosts(?string $category = null, ?string $city = null): array
    {
        $now = date('Y-m-d H:i:s');
        $sql = "
            SELECT `id_tenants`, MAX(`sponsored_rank_boost`) as boost
            FROM `{$this->table}`
            WHERE `status` = 'active'
              AND `starts_at` <= ?
              AND `ends_at` >= ?
        ";
        $params = [$now, $now];

        if (!empty($category)) {
            $sql .= " AND (`category` IS NULL OR `category` = ?)";
            $params[] = $category;
        }

        if (!empty($city)) {
            $sql .= " AND (`city` IS NULL OR `city` = ?)";
            $params[] = $city;
        }

        $sql .= " GROUP BY `id_tenants`";
        $rows = $this->db->query($sql, $params)->result_array();

        $boosts = [];
        foreach ($rows as $r) {
            $boosts[(int)$r['id_tenants']] = (int)$r['boost'];
        }

        return $boosts;
    }

    /**
     * Get tenant active ads list for BooKi Vitrin Yönetimi panel.
     */
    public function get_tenant_ads(int $tenantId): array
    {
        return $this->db->query("
            SELECT * FROM `{$this->table}`
            WHERE `id_tenants` = ?
            ORDER BY `created_at` DESC
        ", [$tenantId])->result_array();
    }
}
