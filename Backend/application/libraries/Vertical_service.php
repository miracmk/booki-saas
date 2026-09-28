<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * BooKi Vertical Service.
 *
 * Standardizes the vertical hierarchy:
 * Family -> Business Type -> Blueprint -> Modules -> Navigation -> Roles -> Permissions
 *
 * Supported Families:
 * - beauty_wellness
 * - restaurant_food
 * - health_clinical
 * - sports_fitness
 * - automotive
 * - hospitality
 * - experience
 * - education
 * - professional
 */
class Vertical_service
{
    protected CI_Controller $CI;

    /**
     * Standard Vertical Families and their associated Business Types.
     */
    protected array $families = [
        'beauty_wellness' => [
            'name' => 'Güzellik, Bakım & Wellness',
            'icon' => '✨',
            'description' => 'Güzellik salonları, kuaförler, tırnak stüdyoları ve spa merkezleri.',
            'business_types' => [
                'beauty_salon' => 'Güzellik Salonu & Estetik',
                'barber' => 'Erkek Kuaförü & Berber',
                'nail_studio' => 'Nail Studio & Tırnak Tasarım',
                'massage_spa' => 'Spa, Masaj & Hamam',
            ],
        ],
        'restaurant_food' => [
            'name' => 'Restoran, Kafe & Gastronomi',
            'icon' => '🍽️',
            'description' => 'A la carte restoranlar, kafeler, barlar ve hızlı servis işletmeleri.',
            'business_types' => [
                'restaurant' => 'A la Carte Restoran & Bistro',
            ],
        ],
        'health_clinical' => [
            'name' => 'Klinik & Sağlık',
            'icon' => '🩺',
            'description' => 'Doktor muayenehaneleri, diş klinikleri, psikolog ve diyetisyen merkezleri.',
            'business_types' => [
                'doctor_clinic' => 'Doktor Kliniği & Muayenehane',
                'dentist' => 'Diş Kliniği & Ağız Sağlığı',
                'psychology_dietitian_clinic' => 'Psikoloji & Diyet Danışmanlık',
            ],
        ],
        'sports_fitness' => [
            'name' => 'Spor, Stüdyo & Fitness',
            'icon' => '🏋️',
            'description' => 'Spor salonları, pilates stüdyoları, PT merkezleri ve halı saha/kortlar.',
            'business_types' => [
                'gym' => 'Fitness & Spor Salonu',
                'pilates_studio' => 'Pilates & Yoga Stüdyosu',
                'pt_training' => 'Personal Training (PT) Stüdyosu',
                'sports_court' => 'Kort & Halı Saha Tesisleri',
            ],
        ],
        'automotive' => [
            'name' => 'Otomotiv & Detailing',
            'icon' => '🚗',
            'description' => 'Oto yıkama, ekspertiz, detailing ve özel servisler.',
            'business_types' => [
                'car_wash' => 'Oto Yıkama & Hızlı Temizlik',
                'auto_service_detailing' => 'Detailing, Seramik & Servis',
            ],
        ],
        'hospitality' => [
            'name' => 'Konaklama & Otelcilik',
            'icon' => '🏨',
            'description' => 'Butik oteller, pansiyonlar ve konaklama tesisleri.',
            'business_types' => [
                'hotel' => 'Butik Otel & Pansiyon',
            ],
        ],
        'experience' => [
            'name' => 'Deneyim, Eğlence & Biletleme',
            'icon' => '🎯',
            'description' => 'Kaçış odaları, atölyeler ve etkinlik alanları.',
            'business_types' => [
                'experience_escape_room' => 'Kaçış Evi & Macera Parkuru',
            ],
        ],
        'education' => [
            'name' => 'Eğitim & Kurs',
            'icon' => '🎓',
            'description' => 'Özel ders merkezleri, atölyeler ve sanat kursları.',
            'business_types' => [
                'education' => 'Eğitim & Kurs Merkezi',
            ],
        ],
        'professional' => [
            'name' => 'Profesyonel Danışmanlık & Hizmet',
            'icon' => '⚖️',
            'description' => 'Hukuk büroları, mali müşavirlik ve yönetim ajansları.',
            'business_types' => [
                'law_firm' => 'Hukuk Bürosu & Avukatlık',
                'consulting_agency' => 'Danışmanlık & Ajans',
            ],
        ],
    ];

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    /**
     * Get all vertical families.
     */
    public function get_families(): array
    {
        return $this->families;
    }

    /**
     * Get business types for a family (or all if family is null).
     */
    public function get_business_types(?string $family = null): array
    {
        if ($family !== null) {
            return $this->families[$family]['business_types'] ?? [];
        }

        $all = [];
        foreach ($this->families as $famKey => $famData) {
            foreach ($famData['business_types'] as $btKey => $btName) {
                $all[$btKey] = [
                    'code' => $btKey,
                    'name' => $btName,
                    'family' => $famKey,
                    'family_name' => $famData['name'],
                    'family_icon' => $famData['icon'],
                ];
            }
        }
        return $all;
    }

    /**
     * Resolve family for a business type / industry code.
     */
    public function resolve_family(?string $code = null): string
    {
        $code = $code ?: $this->current_business_type();

        foreach ($this->families as $famKey => $famData) {
            if (isset($famData['business_types'][$code])) {
                return $famKey;
            }
        }

        return 'beauty_wellness';
    }

    /**
     * Resolve business type for the active tenant.
     */
    public function current_business_type(): string
    {
        $code = setting('industry_code') ?: setting('business_type');
        return !empty($code) ? (string) $code : 'beauty_salon';
    }

    /**
     * Get the active blueprint for the current or given business type.
     */
    public function get_blueprint(?string $code = null): ?array
    {
        $code = $code ?: $this->current_business_type();
        $this->CI->load->library('blueprint_service');
        return $this->CI->blueprint_service->get_blueprint($code);
    }

    /**
     * Get the complete terminology dictionary for the active vertical.
     */
    public function get_terminology(?string $code = null): array
    {
        $bp = $this->get_blueprint($code);
        if ($bp && !empty($bp['terminology'])) {
            return $bp['terminology'];
        }

        return [
            'customer' => 'Müşteri',
            'provider' => 'Personel',
            'appointment' => 'Randevu',
            'service' => 'Hizmet',
            'station' => 'İstasyon / Oda',
            'product' => 'Ürün',
            'order' => 'Sipariş / Adisyon',
            'reservation' => 'Rezervasyon',
            'membership' => 'Üyelik',
            'package' => 'Paket',
            'catalog' => 'Katalog',
            'branch' => 'Şube',
        ];
    }
}
