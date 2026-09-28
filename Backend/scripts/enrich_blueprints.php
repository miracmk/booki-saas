<?php
/**
 * Script to enrich and standardize BooKi SaaS blueprints into vertical-first architecture.
 */

$dir = __DIR__ . '/../application/seeders/blueprints/';

// Define complete blueprints for empty ones first
$law_firm_blueprint = [
    'industry' => [
        'code' => 'law_firm',
        'name' => 'Hukuk Bürosu & Avukatlık',
        'icon' => '⚖️',
        'service_type' => 'duration',
        'description' => 'Müvekkil randevuları, dava ve duruşma takibi, hukuki danışmanlık ve zaman çizelgesi yönetimi.',
    ],
    'family' => 'professional',
    'business_type' => 'law_firm',
    'terminology' => [
        'customer' => 'Müvekkil',
        'provider' => 'Avukat / Hukukçu',
        'appointment' => 'Danışmanlık / Duruşma',
        'service' => 'Hukuki Hizmet',
        'station' => 'Görüşme Odası',
        'product' => 'Dosya / Matbu Evrak',
        'order' => 'Hizmet Sözleşmesi',
        'reservation' => 'Randevu',
        'membership' => 'Hukuki Danışmanlık Paketi',
        'package' => 'Dava / Danışmanlık Paketi',
        'catalog' => 'Hukuki Hizmetler',
        'branch' => 'Büro / Ofis',
        'customer_label' => 'Müvekkil',
        'provider_label' => 'Avukat',
        'appointment_label' => 'Randevu',
        'station_label' => 'Görüşme Odası',
        'service_label' => 'Hukuki Hizmet',
    ],
    'enabled_modules' => [
        'appointments', 'calendar', 'stations', 'packages', 'finance', 'invoices',
        'expenses', 'reports', 'settings', 'crm', 'ai_agent'
    ],
    'default_settings' => [
        'business_type' => 'professional',
        'slot_interval' => 30,
        'future_booking_limit' => 60,
        'require_phone_number' => true,
        'currency_symbol' => '₺',
        'currency_code' => 'TRY',
    ],
    'service_categories' => [
        ['name' => 'Hukuki Danışmanlık', 'description' => 'Yüz yüze ve online hukuki danışmanlık seansları'],
        ['name' => 'Dava & Uyuşmazlık', 'description' => 'Ticaret, iş ve aile hukuku dava süreçleri'],
        ['name' => 'Sözleşme & Uyum', 'description' => 'Sözleşme hazırlama ve KVKK uyum süreçleri'],
    ],
    'services' => [
        ['name' => 'Genel Hukuki Danışmanlık (1 Saat)', 'category' => 'Hukuki Danışmanlık', 'duration' => 60, 'price' => 3500.00, 'color' => '#1e3a8a', 'description' => 'Kapsamlı hukuki durum analizi ve hukuki görüş.'],
        ['name' => 'Online Hukuki Danışmanlık (45 Dk)', 'category' => 'Hukuki Danışmanlık', 'duration' => 45, 'price' => 2500.00, 'color' => '#2563eb', 'description' => 'Görüntülü online hukuki danışmanlık.'],
        ['name' => 'Sözleşme İnceleme & Revizyon', 'category' => 'Sözleşme & Uyum', 'duration' => 90, 'price' => 5000.00, 'color' => '#0d9488', 'description' => 'Ticari sözleşmelerin risk analizi ve revizyonu.'],
        ['name' => 'Dava Dosyası Ön İnceleme', 'category' => 'Dava & Uyuşmazlık', 'duration' => 60, 'price' => 4000.00, 'color' => '#b45309', 'description' => 'Açılacak veya mevcut davaların delil ve strateji tespiti.'],
    ],
    'stations' => [
        ['name' => 'Başkanlık Görüşme Odası', 'notes' => 'Kıdemli ortak görüşme salonu'],
        ['name' => 'Görüşme Odası 1 (Adalet)', 'notes' => 'Müvekkil toplantı odası'],
        ['name' => 'Toplantı Salonu (Büyük)', 'notes' => 'Arabuluculuk ve çoklu toplantılar'],
    ],
    'demo' => [
        'admin' => ['first_name' => 'Kemal', 'last_name' => 'Yıldırım', 'email' => 'demo-law_firm-admin@kibusiness.co', 'phone_number' => '05321002030', 'notes' => 'Kurucu Ortak Avukat'],
        'providers' => [
            ['first_name' => 'Av. Selin', 'last_name' => 'Acar', 'email' => 'demo-law_firm-avukat1@kibusiness.co', 'phone_number' => '05321002031', 'notes' => 'Ticaret Hukuku Uzmanı', 'services' => ['Genel Hukuki Danışmanlık (1 Saat)', 'Sözleşme İnceleme & Revizyon'], 'stations' => ['Görüşme Odası 1 (Adalet)']],
            ['first_name' => 'Av. Burak', 'last_name' => 'Koç', 'email' => 'demo-law_firm-avukat2@kibusiness.co', 'phone_number' => '05321002032', 'notes' => 'İş ve Aile Hukuku', 'services' => ['Genel Hukuki Danışmanlık (1 Saat)', 'Online Hukuki Danışmanlık (45 Dk)'], 'stations' => ['Başkanlık Görüşme Odası']],
        ],
        'customers' => [
            ['first_name' => 'Ahmet', 'last_name' => 'Yılmaz', 'email' => 'demo-law_firm-muvekkil1@kibusiness.co', 'phone_number' => '05051234567', 'city' => 'İstanbul', 'notes' => 'Şirket ortağı'],
            ['first_name' => 'Canan', 'last_name' => 'Demir', 'email' => 'demo-law_firm-muvekkil2@kibusiness.co', 'phone_number' => '05051234568', 'city' => 'Ankara', 'notes' => 'Sözleşme danışmanlığı'],
        ],
        'appointments' => [
            ['day_offset' => 0, 'time' => '10:00', 'provider_idx' => 0, 'customer_idx' => 0, 'service_name' => 'Genel Hukuki Danışmanlık (1 Saat)', 'station_idx' => 0, 'status' => 'confirmed', 'payment_status' => 'collected', 'payment_method' => 'iban'],
            ['day_offset' => 1, 'time' => '14:00', 'provider_idx' => 1, 'customer_idx' => 1, 'service_name' => 'Online Hukuki Danışmanlık (45 Dk)', 'station_idx' => 1, 'status' => 'reserved', 'payment_status' => 'pending', 'payment_method' => 'virtual_pos'],
        ]
    ]
];

$consulting_blueprint = [
    'industry' => [
        'code' => 'consulting_agency',
        'name' => 'Danışmanlık & Ajans',
        'icon' => '💡',
        'service_type' => 'duration',
        'description' => 'Yönetim danışmanlığı, dijital strateji, İK ve kurumsal danışmanlık için randevu ve proje yönetim sistemi.',
    ],
    'family' => 'professional',
    'business_type' => 'consulting_agency',
    'terminology' => [
        'customer' => 'Danışan Firma / Müşteri',
        'provider' => 'Danışman / Stratejist',
        'appointment' => 'Danışmanlık Seansı',
        'service' => 'Danışmanlık Modülü',
        'station' => 'Toplantı Odası',
        'product' => 'Rapor / Strateji Dokümanı',
        'order' => 'Danışmanlık Sözleşmesi',
        'reservation' => 'Randevu',
        'membership' => 'Aylık Danışmanlık Paketi',
        'package' => 'Seans Paketi',
        'catalog' => 'Danışmanlık Kataloğu',
        'branch' => 'Ofis / Lokasyon',
        'customer_label' => 'Müşteri',
        'provider_label' => 'Danışman',
        'appointment_label' => 'Seans',
        'station_label' => 'Toplantı Odası',
        'service_label' => 'Danışmanlık Hizmeti',
    ],
    'enabled_modules' => [
        'appointments', 'calendar', 'stations', 'packages', 'finance', 'invoices',
        'expenses', 'reports', 'settings', 'crm', 'ai_agent'
    ],
    'default_settings' => [
        'business_type' => 'professional',
        'slot_interval' => 30,
        'future_booking_limit' => 60,
        'require_phone_number' => true,
        'currency_symbol' => '₺',
        'currency_code' => 'TRY',
    ],
    'service_categories' => [
        ['name' => 'Strateji & Büyüme', 'description' => 'Şirket büyüme, pazara giriş ve dönüşüm danışmanlığı'],
        ['name' => 'Operasyonel Mükemmellik', 'description' => 'Süreç analizi, verimlilik ve organizasyonel tasarım'],
    ],
    'services' => [
        ['name' => 'Büyüme Stratejisi Keşif Seansı', 'category' => 'Strateji & Büyüme', 'duration' => 60, 'price' => 3000.00, 'color' => '#6366f1', 'description' => 'Hedef pazar analizi ve büyüme yol haritası belirleme.'],
        ['name' => 'Süreç İyileştirme Çalıştayı (2 Saat)', 'category' => 'Operasyonel Mükemmellik', 'duration' => 120, 'price' => 6000.00, 'color' => '#8b5cf6', 'description' => 'Departman içi iş akışı optimizasyonu çalıştayı.'],
    ],
    'stations' => [
        ['name' => 'Strateji Odası A', 'notes' => 'Akıllı tahta donanımlı'],
        ['name' => 'Online Görüşme Kabini 1', 'notes' => 'Ses yalıtımlı podcast & video kabini'],
    ],
    'demo' => [
        'admin' => ['first_name' => 'Deniz', 'last_name' => 'Aras', 'email' => 'demo-consulting_agency-admin@kibusiness.co', 'phone_number' => '05332003040', 'notes' => 'Ajans Başkanı'],
        'providers' => [
            ['first_name' => 'Mert', 'last_name' => 'Güler', 'email' => 'demo-consulting-danisman1@kibusiness.co', 'phone_number' => '05332003041', 'notes' => 'Kıdemli Büyüme Danışmanı', 'services' => ['Büyüme Stratejisi Keşif Seansı'], 'stations' => ['Strateji Odası A']],
        ],
        'customers' => [
            ['first_name' => 'Banu', 'last_name' => 'Tekin', 'email' => 'demo-consulting-musteri1@kibusiness.co', 'phone_number' => '05353004050', 'city' => 'İzmir', 'notes' => 'E-ticaret marka yöneticisi'],
        ],
        'appointments' => [
            ['day_offset' => 0, 'time' => '11:00', 'provider_idx' => 0, 'customer_idx' => 0, 'service_name' => 'Büyüme Stratejisi Keşif Seansı', 'station_idx' => 0, 'status' => 'confirmed', 'payment_status' => 'collected', 'payment_method' => 'iban'],
        ]
    ]
];

file_put_contents($dir . 'law_firm.json', json_encode($law_firm_blueprint, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
file_put_contents($dir . 'consulting_agency.json', json_encode($consulting_blueprint, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// Family & Business Type mapping
$family_map = [
    'beauty_salon' => ['family' => 'beauty_wellness', 'business_type' => 'beauty_salon'],
    'barber' => ['family' => 'beauty_wellness', 'business_type' => 'barber'],
    'nail_studio' => ['family' => 'beauty_wellness', 'business_type' => 'nail_studio'],
    'massage_spa' => ['family' => 'beauty_wellness', 'business_type' => 'massage_spa'],

    'restaurant' => ['family' => 'restaurant_food', 'business_type' => 'restaurant'],

    'doctor_clinic' => ['family' => 'health_clinical', 'business_type' => 'doctor_clinic'],
    'dentist' => ['family' => 'health_clinical', 'business_type' => 'dentist'],
    'psychology_dietitian_clinic' => ['family' => 'health_clinical', 'business_type' => 'psychology_dietitian_clinic'],

    'gym' => ['family' => 'sports_fitness', 'business_type' => 'gym'],
    'pilates_studio' => ['family' => 'sports_fitness', 'business_type' => 'pilates_studio'],
    'pt_training' => ['family' => 'sports_fitness', 'business_type' => 'pt_training'],
    'sports_court' => ['family' => 'sports_fitness', 'business_type' => 'sports_court'],

    'car_wash' => ['family' => 'automotive', 'business_type' => 'car_wash'],
    'auto_service_detailing' => ['family' => 'automotive', 'business_type' => 'auto_service_detailing'],

    'hotel' => ['family' => 'hospitality', 'business_type' => 'hotel'],
    'experience_escape_room' => ['family' => 'experience', 'business_type' => 'experience_escape_room'],

    'law_firm' => ['family' => 'professional', 'business_type' => 'law_firm'],
    'consulting_agency' => ['family' => 'professional', 'business_type' => 'consulting_agency'],
];

// Terminology templates per vertical family / business type
$terminology_presets = [
    'beauty_salon' => [
        'customer' => 'Danışan',
        'provider' => 'Uzman Estetisyen',
        'appointment' => 'Seans / Randevu',
        'service' => 'Bakım / İşlem',
        'station' => 'Kabin / Cihaz',
        'product' => 'Kozmetik Ürünü',
        'order' => 'Adisyon',
        'reservation' => 'Randevu',
        'membership' => 'Güzellik Kulübü',
        'package' => 'Seans Paketi',
        'catalog' => 'Hizmetler & Bakım Kataloğu',
        'branch' => 'Şube / Salon',
    ],
    'barber' => [
        'customer' => 'Müşteri',
        'provider' => 'Berber / Usta',
        'appointment' => 'Randevu',
        'service' => 'Traş & Bakım',
        'station' => 'Koltuk',
        'product' => 'Saç / Sakal Bakım Ürünü',
        'order' => 'Kasa Satışı',
        'reservation' => 'Randevu',
        'membership' => 'VIP Üyelik',
        'package' => 'Bakım Paketi',
        'catalog' => 'Hizmet Kataloğu',
        'branch' => 'Şube / Dükkan',
    ],
    'nail_studio' => [
        'customer' => 'Danışan',
        'provider' => 'Nail Artist',
        'appointment' => 'Seans',
        'service' => 'Tırnak & Bakım',
        'station' => 'Tırnak Masası / Koltuk',
        'product' => 'Tırnak & El Bakım Ürünü',
        'order' => 'Adisyon',
        'reservation' => 'Randevu',
        'membership' => 'Nail Club',
        'package' => 'Seans Paketi',
        'catalog' => 'Tırnak Tasarım Kataloğu',
        'branch' => 'Stüdyo / Şube',
    ],
    'massage_spa' => [
        'customer' => 'Misafir',
        'provider' => 'Terapist',
        'appointment' => 'Masaj Seansı',
        'service' => 'Masaj & Terapi',
        'station' => 'Masaj Odası / Hamam',
        'product' => 'Aromaterapi & Bakım Yağı',
        'order' => 'Adisyon & Sipariş',
        'reservation' => 'Spa Rezervasyonu',
        'membership' => 'Spa & Hamam Üyeliği',
        'package' => 'Terapi Paketi',
        'catalog' => 'Spa & Masaj Menüsü',
        'branch' => 'Spa Merkezi / Şube',
    ],
    'restaurant' => [
        'customer' => 'Misafir',
        'provider' => 'Garson / Servis Personeli',
        'appointment' => 'Masa Rezervasyonu',
        'service' => 'Menü & Deneyim',
        'station' => 'Masa / Bölüm',
        'product' => 'Yiyecek & İçecek',
        'order' => 'Adisyon / Masa Siparişi',
        'reservation' => 'Masa Rezervasyonu',
        'membership' => 'Müdavim Kulübü',
        'package' => 'Fix Menü / Tadım Paketi',
        'catalog' => 'Restoran Menüsü & Gruplar',
        'branch' => 'Restoran / Şube',
    ],
    'doctor_clinic' => [
        'customer' => 'Hasta',
        'provider' => 'Hekim / Doktor',
        'appointment' => 'Muayene / Tedavi',
        'service' => 'Muayene & Tıbbi İşlem',
        'station' => 'Muayene Odası / Poliklinik',
        'product' => 'Medikal Destek Ürünü',
        'order' => 'Sağlık Hizmet Protokolü',
        'reservation' => 'Hasta Randevusu',
        'membership' => 'Sağlık Takip Programı',
        'package' => 'Tedavi Paketi',
        'catalog' => 'Klinik İşlemleri & Tedaviler',
        'branch' => 'Klinik / Şube',
    ],
    'dentist' => [
        'customer' => 'Hasta',
        'provider' => 'Diş Hekimi',
        'appointment' => 'Diş Randevusu',
        'service' => 'Diş Tedavisi & İmplant',
        'station' => 'Diş Üniti',
        'product' => 'Ağız & Diş Sağlığı Ürünü',
        'order' => 'Tedavi Sözleşmesi',
        'reservation' => 'Hasta Randevusu',
        'membership' => 'Yıllık Diş Bakım Planı',
        'package' => 'Ortodonti / İmplant Paketi',
        'catalog' => 'Tedavi & İşlem Kataloğu',
        'branch' => 'Klinik / Şube',
    ],
    'psychology_dietitian_clinic' => [
        'customer' => 'Danışan',
        'provider' => 'Psikolog / Diyetisyen',
        'appointment' => 'Seans / Görüşme',
        'service' => 'Terapi / Beslenme Seansı',
        'station' => 'Görüşme Odası',
        'product' => 'Takviye Gıda / Analiz Kiti',
        'order' => 'Danışmanlık Protokolü',
        'reservation' => 'Seans Randevusu',
        'membership' => 'Aylık Takip Programı',
        'package' => 'Çoklu Seans Paketi',
        'catalog' => 'Seans & Protokol Kataloğu',
        'branch' => 'Merkez / Şube',
    ],
    'gym' => [
        'customer' => 'Üye / Sporcu',
        'provider' => 'Antrenör / Eğitmen',
        'appointment' => 'Ders / Antrenman',
        'service' => 'Grup Dersi / PT Seansı',
        'station' => 'Stüdyo / Alan',
        'product' => 'Spor Takviyesi / Ekipman',
        'order' => 'Üye Satışı',
        'reservation' => 'Ders Rezervasyonu',
        'membership' => 'Salon Üyeliği',
        'package' => 'Ders / Seans Paketi',
        'catalog' => 'Dersler & Program Kataloğu',
        'branch' => 'Şube / Tesis',
    ],
    'pilates_studio' => [
        'customer' => 'Danışan / Üye',
        'provider' => 'Pilates Eğitmeni',
        'appointment' => 'Pilates Seansı',
        'service' => 'Reformer / Mat Seansı',
        'station' => 'Reformer Cihazı / Stüdyo',
        'product' => 'Pilates Çorabı / Ekipman',
        'order' => 'Seans Satışı',
        'reservation' => 'Ders Rezervasyonu',
        'membership' => 'Stüdyo Üyeliği',
        'package' => '10lu / 20li Seans Paketi',
        'catalog' => 'Dersler & Seans Kataloğu',
        'branch' => 'Stüdyo / Şube',
    ],
    'pt_training' => [
        'customer' => 'Sporcu / Danışan',
        'provider' => 'Personal Trainer',
        'appointment' => 'Birebir Antrenman',
        'service' => 'Özel PT Seansı',
        'station' => 'Antrenman Alanı',
        'product' => 'Protein & Sporcu Gıdası',
        'order' => 'Paket Satışı',
        'reservation' => 'Seans Randevusu',
        'membership' => 'Aylık PT Üyeliği',
        'package' => 'Birebir Seans Paketi',
        'catalog' => 'Antrenman & Program Kataloğu',
        'branch' => 'Stüdyo / Tesis',
    ],
    'sports_court' => [
        'customer' => 'Sporcu / Takım',
        'provider' => 'Saha Hakemi / Görevli',
        'appointment' => 'Saha / Maç Saati',
        'service' => 'Kort / Saha Kiralama',
        'station' => 'Kort / Halı Saha',
        'product' => 'Top / Raket / İçecek',
        'order' => 'Kira & Satış Adisyonu',
        'reservation' => 'Saha Rezervasyonu',
        'membership' => 'Kort Aboneliği',
        'package' => 'Aylık Maç Paketi',
        'catalog' => 'Kort & Hizmet Kataloğu',
        'branch' => 'Tesis / Kort Alanı',
    ],
    'car_wash' => [
        'customer' => 'Araç Sahibi',
        'provider' => 'Yıkama / Detailing Ustası',
        'appointment' => 'Araç Kabul Randevusu',
        'service' => 'Yıkama / Temizlik Hizmeti',
        'station' => 'Yıkama Peronu / İstasyon',
        'product' => 'Oto Kokusu / Bakım Ürünü',
        'order' => 'İş Emri / Adisyon',
        'reservation' => 'Araç Randevusu',
        'membership' => 'Aylık Yıkama Aboneliği',
        'package' => 'Çoklu Yıkama Paketi',
        'catalog' => 'Oto Bakım Kataloğu',
        'branch' => 'İstasyon / Şube',
    ],
    'auto_service_detailing' => [
        'customer' => 'Araç Sahibi',
        'provider' => 'Detailing / Servis Teknisyeni',
        'appointment' => 'Ekspertiz / Servis Randevusu',
        'service' => 'Seramik Kaplama / Detailing',
        'station' => 'Servis Lifti / Detailing Alanı',
        'product' => 'Oto Kimyasalı / Yedek Parça',
        'order' => 'Servis İş Emri',
        'reservation' => 'Servis Randevusu',
        'membership' => 'Filo / VIP Bakım Kulübü',
        'package' => 'Periyodik Bakım Paketi',
        'catalog' => 'Servis & İşlem Kataloğu',
        'branch' => 'Servis / Şube',
    ],
    'hotel' => [
        'customer' => 'Misafir',
        'provider' => 'Resepsiyonist / Kat Görevlisi',
        'appointment' => 'Oda Giriş / Çıkış (Check-in)',
        'service' => 'Konaklama / Ekstra Hizmet',
        'station' => 'Oda / Suit',
        'product' => 'Minibar / Oda Servisi',
        'order' => 'Folyo / Oda Hesabı',
        'reservation' => 'Oda Rezervasyonu',
        'membership' => 'Sadakat Programı',
        'package' => 'Tatil / Konaklama Paketi',
        'catalog' => 'Oda Tipleri & Ekstra Hizmetler',
        'branch' => 'Otel / Tesis',
    ],
    'experience_escape_room' => [
        'customer' => 'Oyuncu / Misafir',
        'provider' => 'Oyun Yöneticisi (Game Master)',
        'appointment' => 'Oyun Seansı',
        'service' => 'Kaçış Oyunu / Deneyim',
        'station' => 'Oyun Odası / Parkur',
        'product' => 'Hatıra Ürünü / Fotoğraf',
        'order' => 'Bilet & İçecek Siparişi',
        'reservation' => 'Oyun Rezervasyonu',
        'membership' => 'Deneyim Kulübü',
        'package' => 'Grup / Sezon Paketi',
        'catalog' => 'Oyunlar & Deneyim Kataloğu',
        'branch' => 'Şube / Kaçış Evi',
    ],
];

// Iterate all blueprint files and enrich
$files = glob($dir . '*.json');
foreach ($files as $file) {
    $code = basename($file, '.json');
    $content = file_get_contents($file);
    $bp = json_decode($content, true);
    if (!$bp) continue;

    $family_info = $family_map[$code] ?? ['family' => 'beauty_wellness', 'business_type' => $code];
    $bp['family'] = $family_info['family'];
    $bp['business_type'] = $family_info['business_type'];

    // Update terminology with 12 standard keys + preserve legacy _label keys
    $preset = $terminology_presets[$code] ?? [
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

    $existing_terms = $bp['terminology'] ?? [];
    $merged_terms = array_merge($preset, $existing_terms);
    // Backward compatibility keys
    $merged_terms['customer_label'] = $existing_terms['customer_label'] ?? $merged_terms['customer'];
    $merged_terms['provider_label'] = $existing_terms['provider_label'] ?? $merged_terms['provider'];
    $merged_terms['appointment_label'] = $existing_terms['appointment_label'] ?? $merged_terms['appointment'];
    $merged_terms['station_label'] = $existing_terms['station_label'] ?? $merged_terms['station'];
    $merged_terms['service_label'] = $existing_terms['service_label'] ?? $merged_terms['service'];
    $bp['terminology'] = $merged_terms;

    // AI Policy
    $company_name = $bp['industry']['name'] ?? 'İşletme';
    $bp['ai_policy'] = [
        'brand_name' => $company_name,
        'tone' => ($bp['family'] === 'health_clinical' || $bp['family'] === 'professional') ? 'formal_empathetic' : 'friendly_professional',
        'language' => 'tr',
        'greeting_style' => "Merhaba, {$company_name} asistanıyım. Size randevu, hizmet ve operasyonel konularda nasıl yardımcı olabilirim?",
        'allowed_terms' => array_values(array_slice($merged_terms, 0, 8)),
        'forbidden_terms' => ['garantili sonuç', 'yüzde yüz kesin', 'hukuki taahhüt', 'tıbbi teşhis'],
        'do_rules' => [
            "Her zaman '{$merged_terms['customer']}' ve '{$merged_terms['service']}' terminolojisini kullan.",
            "Randevuları doğrulamadan doğrudan onaylama; propose_* araçlarıyla yönetici onayına sun.",
            "Yetki kapsamın dışındaki finansal ve hassas talepleri insan personele eskalasyon yap.",
        ],
        'dont_rules' => [
            "Kullanıcının yetkisi olmayan verilere erişmesine izin verme.",
            "Onaysız veri silme veya para iadesi işlemi yapma.",
            "Tıbbi reçete veya hukuki kesin taahhüt verme.",
        ],
        'business_rules' => [
            'allow_online_booking' => true,
            'max_advance_days' => 60,
            'require_phone' => true,
        ],
        'cancellation_policy' => "Randevudan en geç 4 saat öncesine kadar ücretsiz iptal edilebilir.",
        'refund_policy' => "İadeler yönetici onayı sonrasında 3 iş günü içinde orijinal ödeme yöntemine yapılır.",
        'discount_policy' => "Yalnızca aktif kampanyalar ve yönetici yetkisi dahilinde indirim uygulanabilir.",
        'escalation_rules' => [
            'medical' => ['trigger_keywords' => ['ilaç', 'yan etki', 'alerji', 'kanama', 'enfeksiyon', 'ağrı'], 'action' => 'escalate_to_doctor'],
            'legal' => ['trigger_keywords' => ['dava', 'ihtarname', 'avukat', 'tazminat', 'mahkeme'], 'action' => 'escalate_to_lawyer'],
            'payment' => ['trigger_keywords' => ['fazla çekim', 'dolandırıcılık', 'itiraz', 'chargeback'], 'action' => 'escalate_to_manager'],
            'angry_customer' => ['trigger_keywords' => ['şikayetçiyim', 'berbat', 'dava edeceğim', 'rezalet', 'tüketici hakem'], 'action' => 'escalate_to_owner'],
            'uncertainty' => ['threshold' => 0.65, 'action' => 'escalate_to_staff'],
        ],
        'allowed_actions' => ['search_customers', 'get_customer', 'list_appointments', 'get_services', 'check_availability'],
        'approval_required_actions' => ['propose_appointment_create', 'propose_appointment_update', 'propose_appointment_cancel', 'propose_customer_update'],
        'forbidden_actions' => ['direct_delete_customer', 'direct_refund_payment', 'change_security_settings'],
    ];

    // Dashboard config
    $bp['dashboard'] = [
        'owner' => [
            'kpis' => [
                ['key' => 'today_appointments', 'label' => "Bugünkü {$merged_terms['appointment']}lar", 'icon' => 'calendar-check', 'color' => 'primary'],
                ['key' => 'today_revenue', 'label' => 'Bugünkü Hasılat', 'icon' => 'wallet', 'color' => 'success'],
                ['key' => 'active_sessions', 'label' => "Canlı {$merged_terms['station']} Doluluğu", 'icon' => 'door-open', 'color' => 'info'],
                ['key' => 'customer_growth', 'label' => "Yeni {$merged_terms['customer']}lar", 'icon' => 'user-plus', 'color' => 'warning'],
            ],
            'quick_actions' => [
                ['label' => "+ Yeni {$merged_terms['appointment']}", 'route' => 'calendar', 'icon' => 'calendar-plus', 'class' => 'btn-primary'],
                ['label' => "+ Yeni {$merged_terms['customer']}", 'route' => 'customers', 'icon' => 'user-plus', 'class' => 'btn-success'],
                ['label' => "⚡ Hızlı Satış", 'route' => 'pos', 'icon' => 'cash-register', 'class' => 'btn-warning'],
                ['label' => "📊 Günlük Rapor", 'route' => 'reports', 'icon' => 'chart-line', 'class' => 'btn-info'],
            ],
        ],
        'staff' => [
            'kpis' => [
                ['key' => 'my_today_appointments', 'label' => "Bugünkü Programım", 'icon' => 'calendar-day', 'color' => 'primary'],
                ['key' => 'my_completed_tasks', 'label' => "Tamamlanan {$merged_terms['appointment']}lar", 'icon' => 'check-circle', 'color' => 'success'],
                ['key' => 'my_station_status', 'label' => "{$merged_terms['station']} Durumu", 'icon' => 'door-open', 'color' => 'info'],
            ],
            'quick_actions' => [
                ['label' => "📅 Takvimim", 'route' => 'calendar', 'icon' => 'calendar', 'class' => 'btn-primary'],
                ['label' => "+ {$merged_terms['customer']} Notu Ekle", 'route' => 'customers', 'icon' => 'file-alt', 'class' => 'btn-outline-primary'],
            ],
        ],
    ];

    // Roles based on vertical
    if ($bp['family'] === 'restaurant_food') {
        $bp['demo_roles'] = [
            ['slug' => 'owner', 'name' => 'İşletme Sahibi (Owner)', 'icon' => 'crown', 'description' => 'Tüm restoran, finans ve operasyon yetkisi'],
            ['slug' => 'general_manager', 'name' => 'Genel Müdür', 'icon' => 'user-tie', 'description' => 'Operasyonel ve şube yönetimi'],
            ['slug' => 'floor_manager', 'name' => 'Salon Şefi', 'icon' => 'tasks', 'description' => 'Masa planı, rezervasyon ve servis akışı'],
            ['slug' => 'waiter', 'name' => 'Garson', 'icon' => 'utensils', 'description' => 'Masa siparişi, adisyon ve servis'],
            ['slug' => 'cashier', 'name' => 'Kasiyer', 'icon' => 'cash-register', 'description' => 'Kasa tahsilatı ve hesap kapatma'],
            ['slug' => 'kitchen', 'name' => 'Mutfak (KDS)', 'icon' => 'fire', 'description' => 'Gelen sipariş hazırlama ekranı'],
            ['slug' => 'bar', 'name' => 'Bar Personeli', 'icon' => 'cocktail', 'description' => 'Bar ve içecek sipariş ekranı'],
        ];
    } elseif ($bp['family'] === 'health_clinical') {
        $bp['demo_roles'] = [
            ['slug' => 'owner', 'name' => 'Klinik Sahibi (Owner)', 'icon' => 'crown', 'description' => 'Klinik başhekimi ve tam yetkili'],
            ['slug' => 'clinic_manager', 'name' => 'Klinik Müdürü', 'icon' => 'user-tie', 'description' => 'İdari ve operasyonel yönetim'],
            ['slug' => 'doctor', 'name' => 'Hekim / Doktor', 'icon' => 'user-md', 'description' => 'Hasta muayenesi, SOAP ve reçete'],
            ['slug' => 'nurse', 'name' => 'Hemşire', 'icon' => 'plus-square', 'description' => 'Hasta kabul ve tıbbi destek'],
            ['slug' => 'reception', 'name' => 'Resepsiyon', 'icon' => 'concierge-bell', 'description' => 'Randevu ve hasta karşılama'],
            ['slug' => 'cashier', 'name' => 'Kasa / Tahsilat', 'icon' => 'cash-register', 'description' => 'Fatura ve ödeme işlemleri'],
        ];
    } elseif ($bp['family'] === 'sports_fitness') {
        $bp['demo_roles'] = [
            ['slug' => 'owner', 'name' => 'Kulüp Sahibi (Owner)', 'icon' => 'crown', 'description' => 'Tesis ve finans tam yetkilisi'],
            ['slug' => 'manager', 'name' => 'Tesis Müdürü', 'icon' => 'user-tie', 'description' => 'Salon ve ekip operasyonu'],
            ['slug' => 'trainer', 'name' => 'Personal Trainer (PT)', 'icon' => 'dumbbell', 'description' => 'Birebir antrenman ve danışan takibi'],
            ['slug' => 'group_instructor', 'name' => 'Grup Dersi Eğitmeni', 'icon' => 'users', 'description' => 'Stüdyo ve grup seansları'],
            ['slug' => 'reception', 'name' => 'Danışma / Turnike', 'icon' => 'id-card', 'description' => 'Üye girişi ve check-in'],
            ['slug' => 'cashier', 'name' => 'Kasiyer', 'icon' => 'cash-register', 'description' => 'Paket ve üyelik satışı'],
        ];
    } elseif ($code === 'massage_spa') {
        $bp['demo_roles'] = [
            ['slug' => 'owner', 'name' => 'Spa Sahibi (Owner)', 'icon' => 'crown', 'description' => 'Merkez ve finans yönetimi'],
            ['slug' => 'manager', 'name' => 'Spa Müdürü', 'icon' => 'user-tie', 'description' => 'Terapist ve oda koordinasyonu'],
            ['slug' => 'therapist', 'name' => 'Masaj Terapisti', 'icon' => 'hands', 'description' => 'Masaj ve terapi uygulamaları'],
            ['slug' => 'hamam_staff', 'name' => 'Hamam Görevlisi', 'icon' => 'water', 'description' => 'Kese & köpük bakımları'],
            ['slug' => 'reception', 'name' => 'Resepsiyon', 'icon' => 'concierge-bell', 'description' => 'Misafir karşılama ve rezervasyon'],
            ['slug' => 'cashier', 'name' => 'Kasa', 'icon' => 'cash-register', 'description' => 'Adisyon ve ödeme alma'],
        ];
    } else {
        // Beauty & Universal roles
        $bp['demo_roles'] = [
            ['slug' => 'owner', 'name' => 'İşletme Sahibi (Owner)', 'icon' => 'crown', 'description' => 'Tam yetkili salon yöneticisi'],
            ['slug' => 'manager', 'name' => 'Müdür', 'icon' => 'user-tie', 'description' => 'Operasyon ve ekip yönetimi'],
            ['slug' => 'professional', 'name' => 'Uzman / Estetisyen', 'icon' => 'magic', 'description' => 'Hizmet ve seans uygulamaları'],
            ['slug' => 'reception', 'name' => 'Resepsiyon', 'icon' => 'concierge-bell', 'description' => 'Danışan karşılama ve randevu'],
            ['slug' => 'cashier', 'name' => 'Kasa / POS', 'icon' => 'cash-register', 'description' => 'Ödeme ve adisyon takibi'],
            ['slug' => 'inventory', 'name' => 'Depo / Envanter', 'icon' => 'boxes', 'description' => 'Sarf malzeme ve ürün takibi'],
        ];
    }

    // Demo users mapping
    $bp['demo_users'] = [];
    foreach ($bp['demo_roles'] as $dr) {
        $slug = $dr['slug'];
        $bp['demo_users'][$slug] = [
            'first_name' => explode(' ', $dr['name'])[0],
            'last_name' => 'Demo',
            'email' => "demo-{$code}-{$slug}@kibusiness.co",
            'role_slug' => $slug,
            'job_title' => $dr['name'],
        ];
    }

    file_put_contents($file, json_encode($bp, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "Enriched blueprint: {$code} ({$bp['family']} -> {$bp['business_type']})\n";
}

echo "All blueprints successfully enriched!\n";
