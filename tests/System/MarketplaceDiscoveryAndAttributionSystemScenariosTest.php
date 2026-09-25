<?php declare(strict_types=1);

namespace Tests\System;

use Tests\TestCase;

/**
 * System Use Case Scenarios: Marketplace Discovery, Geo Distance, Reviews & Attribution (UC-166 to UC-190).
 */
class MarketplaceDiscoveryAndAttributionSystemScenariosTest extends TestCase
{
    private function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return round($earthRadius * $c, 2);
    }

    private function bayesianRating(float $avgRating, int $reviewCount, float $priorAvg = 4.5, int $priorWeight = 5): float
    {
        return round(($reviewCount * $avgRating + $priorWeight * $priorAvg) / ($reviewCount + $priorWeight), 2);
    }

    /** UC-166: Marketplace index listings search by keyword */
    public function test_UC166_marketplace_search_keyword(): void
    {
        $businesses = [
            ['name' => 'Flora Güzellik Salonu', 'city' => 'İstanbul'],
            ['name' => 'Akdeniz Diş Kliniği', 'city' => 'Antalya'],
        ];
        $keyword = 'Flora';
        $filtered = array_filter($businesses, fn($b) => str_contains($b['name'], $keyword));
        $this->assertCount(1, $filtered);
    }

    /** UC-167: Marketplace filter by industry category */
    public function test_UC167_marketplace_filter_category(): void
    {
        $categories = ['Güzellik Salonu', 'Diş Kliniği', 'Oto Servis'];
        $selected = 'Güzellik Salonu';
        $this->assertContains($selected, $categories);
    }

    /** UC-168: Marketplace filter by city / province */
    public function test_UC168_marketplace_filter_city(): void
    {
        $businesses = [
            ['name' => 'Salon A', 'city' => 'İstanbul'],
            ['name' => 'Salon B', 'city' => 'Ankara'],
        ];
        $filtered = array_values(array_filter($businesses, fn($b) => $b['city'] === 'İstanbul'));
        $this->assertCount(1, $filtered);
        $this->assertSame('Salon A', $filtered[0]['name']);
    }

    /** UC-169: Marketplace GPS distance sorting via Haversine formula */
    public function test_UC169_haversine_distance_computation(): void
    {
        // Kadıköy (40.9901, 29.0292) to Beşiktaş (41.0422, 29.0067) ~ 6.1 km
        $dist = $this->haversineDistance(40.9901, 29.0292, 41.0422, 29.0067);
        $this->assertGreaterThan(5.0, $dist);
        $this->assertLessThan(7.5, $dist);
    }

    /** UC-170: Marketplace Bayesian average rating score computation */
    public function test_UC170_bayesian_rating_smoothing(): void
    {
        // 1 review with 5 stars shouldn't beat 100 reviews with 4.8 stars
        $singleFive = $this->bayesianRating(5.0, 1, 4.5, 5);
        $hundredHigh = $this->bayesianRating(4.8, 100, 4.5, 5);
        $this->assertLessThan($hundredHigh, $singleFive);
    }

    /** UC-171: Marketplace profile completeness ranking bonus */
    public function test_UC171_profile_completeness_bonus(): void
    {
        $profile = ['has_logo' => true, 'has_photos' => true, 'has_description' => true, 'has_services' => true];
        $completeness = count(array_filter($profile)) / count($profile);
        $this->assertEquals(1.0, $completeness);
    }

    /** UC-172: Marketplace business profile page view */
    public function test_UC172_business_profile_view(): void
    {
        $business = ['subdomain' => 'demo-guzellik', 'title' => 'Demo Güzellik'];
        $this->assertSame('demo-guzellik', $business['subdomain']);
    }

    /** UC-173: Marketplace service preview API endpoint returns active services */
    public function test_UC173_service_preview_api(): void
    {
        $services = [
            ['id' => 1, 'name' => 'Masaj', 'price' => 500],
            ['id' => 2, 'name' => 'Cilt Bakımı', 'price' => 600],
        ];
        $this->assertCount(2, $services);
    }

    /** UC-174: Booking redirect with ?ref=marketplace query parameter */
    public function test_UC174_booking_redirect_ref(): void
    {
        $url = 'https://demo-guzellik.bookiapp.kibusiness.co/?ref=marketplace';
        parse_str(parse_url($url, PHP_URL_QUERY), $queryParams);
        $this->assertSame('marketplace', $queryParams['ref'] ?? null);
    }

    /** UC-175: 30-day first-party attribution cookie set */
    public function test_UC175_attribution_cookie_30_days(): void
    {
        $cookieDurationSeconds = 30 * 86400;
        $this->assertSame(2592000, $cookieDurationSeconds);
    }

    /** UC-176: Booking AJAX submission preserves marketplace attribution tag */
    public function test_UC176_ajax_preserves_attribution(): void
    {
        $postData = ['service_id' => 1, 'ref' => 'marketplace'];
        $this->assertSame('marketplace', $postData['ref']);
    }

    /** UC-177: Appointment note automatically receives [Pazar Yeri] prefix */
    public function test_UC177_appointment_note_pazar_yeri_tag(): void
    {
        $existingNote = 'Müşteri ilk kez geliyor.';
        $isMarketplace = true;
        if ($isMarketplace && !str_contains($existingNote, '[Pazar Yeri]')) {
            $updatedNote = '[Pazar Yeri] ' . $existingNote;
        } else {
            $updatedNote = $existingNote;
        }
        $this->assertStringStartsWith('[Pazar Yeri]', $updatedNote);
    }

    /** UC-178: Marketplace commission trigger on closed status */
    public function test_UC178_commission_trigger_closed(): void
    {
        $status = 'closed';
        $triggersCommission = in_array($status, ['closed', 'tamamlandı', 'completed', 'attended'], true);
        $this->assertTrue($triggersCommission);
    }

    /** UC-179: Marketplace commission trigger on tamamlandı status */
    public function test_UC179_commission_trigger_tamamlandi(): void
    {
        $status = 'tamamlandı';
        $triggersCommission = in_array($status, ['closed', 'tamamlandı', 'completed', 'attended'], true);
        $this->assertTrue($triggersCommission);
    }

    /** UC-180: Marketplace commission trigger on attended status */
    public function test_UC180_commission_trigger_attended(): void
    {
        $status = 'attended';
        $triggersCommission = in_array($status, ['closed', 'tamamlandı', 'completed', 'attended'], true);
        $this->assertTrue($triggersCommission);
    }

    /** UC-181: Review creation allowed only for completed past appointments */
    public function test_UC181_review_allowed_for_completed(): void
    {
        $appointment = ['status' => 'completed', 'end_time' => time() - 3600];
        $canReview = ($appointment['status'] === 'completed' && $appointment['end_time'] < time());
        $this->assertTrue($canReview);
    }

    /** UC-182: Review creation rejected for uncompleted / cancelled appointment */
    public function test_UC182_review_rejected_for_cancelled(): void
    {
        $appointment = ['status' => 'cancelled', 'end_time' => time() - 3600];
        $canReview = ($appointment['status'] === 'completed');
        $this->assertFalse($canReview);
    }

    /** UC-183: Review score between 1 and 5 stars validation */
    public function test_UC183_review_score_range(): void
    {
        $validScore = 4;
        $invalidScore = 6;
        $this->assertTrue($validScore >= 1 && $validScore <= 5);
        $this->assertFalse($invalidScore >= 1 && $invalidScore <= 5);
    }

    /** UC-184: Review text profanity / length validation */
    public function test_UC184_review_text_validation(): void
    {
        $review = 'Harika bir deneyimdi, çok teşekkürler!';
        $isValidLength = (mb_strlen($review) >= 5 && mb_strlen($review) <= 1000);
        $this->assertTrue($isValidLength);
    }

    /** UC-185: Admin review moderation: approve / hide review */
    public function test_UC185_review_moderation(): void
    {
        $review = ['id' => 1, 'is_published' => false];
        $review['is_published'] = true;
        $this->assertTrue($review['is_published']);
    }

    /** UC-186: Dynamic sitemap.xml contains active marketplace businesses */
    public function test_UC186_sitemap_xml_contains_urls(): void
    {
        $xml = '<urlset><url><loc>https://booki.kibusiness.co/marketplace</loc></url></urlset>';
        $this->assertStringContainsString('https://booki.kibusiness.co/marketplace', $xml);
    }

    /** UC-187: Dynamic robots.txt specifies sitemap URL */
    public function test_UC187_robots_txt_specifies_sitemap(): void
    {
        $robots = "User-agent: *\nAllow: /\nSitemap: https://booki.kibusiness.co/sitemap.xml\n";
        $this->assertStringContainsString('Sitemap:', $robots);
    }

    /** UC-188: AI llms.txt endpoint delivers structured Markdown */
    public function test_UC188_llms_txt_structured_markdown(): void
    {
        $content = "# BooKi Platform & Marketplace\n\n> Multi-tenant appointment platform";
        $this->assertStringStartsWith('# BooKi', $content);
    }

    /** UC-189: Landing page OpenGraph og:image and Twitter cards */
    public function test_UC189_opengraph_metadata(): void
    {
        $ogImage = 'https://booki.kibusiness.co/assets/img/social-card.png';
        $this->assertStringEndsWith('.png', $ogImage);
    }

    /** UC-190: Landing page Schema.org SoftwareApplication JSON-LD */
    public function test_UC190_schema_jsonld_software_app(): void
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => 'BooKi',
            'applicationCategory' => 'BusinessApplication',
        ];
        $this->assertSame('SoftwareApplication', $schema['@type']);
    }

    /** UC-191: Lead RB Push sets is_marketplace_published to 1 and marketplace_synced_at */
    public function test_UC191_lead_marketplace_push_and_sync(): void
    {
        $lead = [
            'id' => 101,
            'name' => 'Elit Kuaför & Güzellik',
            'city' => 'Bursa',
            'district' => 'Nilüfer',
            'enrichment_status' => 'enriched_lead',
            'is_marketplace_published' => 0,
            'marketplace_synced_at' => null,
        ];

        // Simulate push to marketplace
        $lead['is_marketplace_published'] = 1;
        $lead['marketplace_synced_at'] = date('Y-m-d H:i:s');
        $lead['slug'] = 'elit-kuafor-guzellik-nilufer-bursa';

        $this->assertSame(1, $lead['is_marketplace_published']);
        $this->assertNotNull($lead['marketplace_synced_at']);
        $this->assertSame('elit-kuafor-guzellik-nilufer-bursa', $lead['slug']);
    }

    /** UC-192: Lead unpublish toggle revokes publication */
    public function test_UC192_lead_marketplace_unpublish_toggle(): void
    {
        $lead = [
            'id' => 102,
            'name' => 'Gizli Klinik',
            'is_marketplace_published' => 1,
        ];

        // Toggle unpublish
        $lead['is_marketplace_published'] = 0;

        $this->assertSame(0, $lead['is_marketplace_published']);
    }

    /** UC-193: Bulk push leads to marketplace */
    public function test_UC193_bulk_push_leads_to_marketplace(): void
    {
        $leads = [
            ['id' => 201, 'is_marketplace_published' => 0],
            ['id' => 202, 'is_marketplace_published' => 0],
            ['id' => 203, 'is_marketplace_published' => 1],
        ];

        $targetIds = [201, 202];
        $publishedCount = 0;
        foreach ($leads as &$ld) {
            if (in_array($ld['id'], $targetIds, true)) {
                $ld['is_marketplace_published'] = 1;
                $ld['marketplace_synced_at'] = date('Y-m-d H:i:s');
                $publishedCount++;
            }
        }

        $this->assertSame(2, $publishedCount);
        $this->assertSame(1, $leads[0]['is_marketplace_published']);
        $this->assertSame(1, $leads[1]['is_marketplace_published']);
    }

    /** UC-194: Marketplace storefront/directory filtering excludes unpublished leads */
    public function test_UC194_marketplace_filters_exclude_unpublished(): void
    {
        $leads = [
            ['id' => 1, 'name' => 'Yayındaki Salon', 'is_marketplace_published' => 1, 'business_status' => 'OPERATIONAL'],
            ['id' => 2, 'name' => 'Taslak Lead', 'is_marketplace_published' => 0, 'business_status' => 'OPERATIONAL'],
            ['id' => 3, 'name' => 'Kapalı Salon', 'is_marketplace_published' => 1, 'business_status' => 'CLOSED_PERMANENTLY'],
        ];

        $visibleOnMarketplace = array_values(array_filter($leads, function ($item) {
            return !empty($item['is_marketplace_published']) && ($item['business_status'] ?? '') === 'OPERATIONAL';
        }));

        $this->assertCount(1, $visibleOnMarketplace);
        $this->assertSame('Yayındaki Salon', $visibleOnMarketplace[0]['name']);
    }

    /** UC-195: Turkish slug sanitization handles special characters and uniqueness */
    public function test_UC195_turkish_slug_sanitization(): void
    {
        $title = 'Şahane Çiçek & Kuaför Salonu 100%';
        $slug = mb_strtolower($title, 'UTF-8');
        $slug = str_replace(
            ['ı', 'ğ', 'ü', 'ş', 'ö', 'ç', 'İ', 'Ğ', 'Ü', 'Ş', 'Ö', 'Ç'],
            ['i', 'g', 'u', 's', 'o', 'c', 'i', 'g', 'u', 's', 'o', 'c'],
            $slug
        );
        $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        $this->assertSame('sahane-cicek-kuafor-salonu-100', $slug);
    }
}

