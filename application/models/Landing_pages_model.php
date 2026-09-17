<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Landing Pages Model
 *
 * Dedicated marketing landing pages for ad campaigns and promotions.
 * Tracks view counts, conversion rates, links directly to bookable services.
 * ---------------------------------------------------------------------------- */

class Landing_pages_model extends EA_Model
{
    protected array $casts = [
        'id' => 'integer',
        'id_services' => 'integer',
        'views_count' => 'integer',
        'conversions_count' => 'integer',
        'is_active' => 'integer',
    ];

    /**
     * Get all landing pages, newest first.
     */
    public function get(): array
    {
        $pages = $this->db->order_by('created_at', 'DESC')->get('landing_pages')->result_array();

        foreach ($pages as &$p) {
            $this->cast($p);
            if (!empty($p['id_services'])) {
                $service = $this->db->select('name, price, currency')->get_where('services', ['id' => $p['id_services']])->row_array();
                $p['service_name'] = $service['name'] ?? null;
                $p['service_price'] = $service['price'] ?? null;
            } else {
                $p['service_name'] = null;
                $p['service_price'] = null;
            }
        }

        return $pages;
    }

    /**
     * Find landing page by ID.
     *
     * @throws InvalidArgumentException
     */
    public function find(int $id): array
    {
        $page = $this->db->get_where('landing_pages', ['id' => $id])->row_array();

        if (!$page) {
            throw new InvalidArgumentException('Açılış sayfası bulunamadı: ' . $id);
        }

        $this->cast($page);

        if (!empty($page['id_services'])) {
            $service = $this->db->select('name, price, currency')->get_where('services', ['id' => $page['id_services']])->row_array();
            $page['service_name'] = $service['name'] ?? null;
            $page['service_price'] = $service['price'] ?? null;
        }

        return $page;
    }

    /**
     * Find active landing page by slug.
     */
    public function find_by_slug(string $slug): ?array
    {
        $page = $this->db->get_where('landing_pages', ['slug' => $slug, 'is_active' => 1])->row_array();

        if (!$page) {
            return null;
        }

        $this->cast($page);

        if (!empty($page['id_services'])) {
            $service = $this->db->select('name, price, currency, description, duration')->get_where('services', ['id' => $page['id_services']])->row_array();
            $page['service_name'] = $service['name'] ?? null;
            $page['service_price'] = $service['price'] ?? null;
            $page['service_duration'] = $service['duration'] ?? null;
            $page['service_description'] = $service['description'] ?? null;
        }

        return $page;
    }

    /**
     * Save landing page (insert or update).
     *
     * @throws InvalidArgumentException|RuntimeException
     */
    public function save(array $data): int
    {
        $this->validate($data);

        $now = date('Y-m-d H:i:s');

        // Normalize slug
        if (!empty($data['slug'])) {
            $data['slug'] = url_title(convert_accented_characters($data['slug']), '-', true);
        }

        if (empty($data['id'])) {
            unset($data['id']);
            $data['created_at'] = $now;
            $data['updated_at'] = $now;
            $data['views_count'] = 0;
            $data['conversions_count'] = 0;
            $data['is_active'] = isset($data['is_active']) ? (int) $data['is_active'] : 1;
            $data['cta_text'] = !empty($data['cta_text']) ? $data['cta_text'] : 'Hemen Randevu Al';

            if (!$this->db->insert('landing_pages', $data)) {
                throw new RuntimeException('Açılış sayfası eklenemedi.');
            }

            return (int) $this->db->insert_id();
        }

        $data['updated_at'] = $now;

        if (!$this->db->update('landing_pages', $data, ['id' => $data['id']])) {
            throw new RuntimeException('Açılış sayfası güncellenemedi.');
        }

        return (int) $data['id'];
    }

    /**
     * Delete landing page.
     */
    public function delete(int $id): void
    {
        $this->db->delete('landing_pages', ['id' => $id]);
    }

    /**
     * Increment views count.
     */
    public function increment_views(int $id): void
    {
        $this->db->set('views_count', 'views_count + 1', false)
            ->where('id', $id)
            ->update('landing_pages');
    }

    /**
     * Increment conversions count.
     */
    public function increment_conversions(int $id): void
    {
        $this->db->set('conversions_count', 'conversions_count + 1', false)
            ->where('id', $id)
            ->update('landing_pages');
    }

    /**
     * Validate landing page fields.
     *
     * @throws InvalidArgumentException
     */
    public function validate(array $data): void
    {
        if (empty($data['title'])) {
            throw new InvalidArgumentException('Sayfa başlığı zorunludur.');
        }

        if (empty($data['slug'])) {
            throw new InvalidArgumentException('Sayfa slug (URL uzantısı) zorunludur.');
        }

        // Slug uniqueness check
        $this->db->where('slug', $data['slug']);
        if (!empty($data['id'])) {
            $this->db->where('id !=', (int) $data['id']);
        }
        $existing = $this->db->get('landing_pages')->row_array();
        if ($existing) {
            throw new InvalidArgumentException('Bu slug zaten kullanılıyor: ' . $data['slug']);
        }
    }
}
