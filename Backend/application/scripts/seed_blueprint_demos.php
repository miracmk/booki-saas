<?php
/**
 * BooKi - Blueprint Demo Tenants Provisioner & Seeder
 * 
 * 1. Drops all existing demo/tenant databases and clears master catalog.
 * 2. Creates a dedicated tenant database for each of the 18 sector blueprints.
 * 3. Sets subdomain to {blueprint}-bookiapp and custom_domain to {blueprint}-bookiapp.kibusiness.co.
 * 4. Configures RandevuBurada listing (marketplace_opt_in = 1, Bursa address, stock cover photo, logo, description, premium plan).
 * 5. Seeds 4-12 services, 2-6 stations/tables/rooms/courts.
 * 6. Seeds sector-appropriate staff (1 to 30 personnel) with realistic working hours and break times.
 * 7. Seeds 40-50 customers with encrypted name, phone, address, and email.
 * 8. Seeds > 200 appointments (>200 required) spread over past and future months.
 * 9. Seeds products, consumables recipes, appointment consumables, digital waivers & signatures, tracking records.
 * 10. Enables BooKi AI & AI Assistant with vertical-tailored persona, policies, and knowledge base.
 */

ini_set('memory_limit', '2048M');
set_time_limit(0);

require_once __DIR__ . '/../../config.php';

$masterHost = Config::DB_HOST;
$masterUser = Config::DB_USERNAME;
$masterPass = Config::DB_PASSWORD;
$masterDb   = Config::DB_NAME;

$tenantMasterKeyB64 = getenv('TENANT_MASTER_KEY') ?: '2VFsYc907I3cuEOcWcMKEy31n8Do6hX6e8Ic7UiZ9cU=';
$tenantMasterKeyRaw = base64_decode($tenantMasterKeyB64);

function tm_encrypt(string $plaintext, string $keyRaw): string {
    $nonce = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $keyRaw, OPENSSL_RAW_DATA, $nonce, $tag);
    return 'TMENC1:' . base64_encode($nonce . $tag . $ciphertext);
}

function sf_encrypt(?string $plaintext, string $keyRaw): ?string {
    if ($plaintext === null || $plaintext === '') return null;
    $nonce = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $keyRaw, OPENSSL_RAW_DATA, $nonce, $tag);
    return 'SFENC1:' . base64_encode($nonce . $tag . $ciphertext);
}

function sf_hash(?string $plaintext, string $hashKeyRaw): ?string {
    if ($plaintext === null || $plaintext === '') return null;
    return hash_hmac('sha256', mb_strtolower(trim($plaintext), 'UTF-8'), $hashKeyRaw);
}

$firstNamesMale = [
    'Ahmet', 'Mehmet', 'Can', 'Burak', 'Emre', 'Murat', 'Serkan', 'Hakan', 'Onur', 'Tolga',
    'Deniz', 'Cem', 'Kerem', 'Kaan', 'Berke', 'Sinan', 'Baran', 'Mert', 'Ozan', 'Volkan',
    'Arda', 'Tayfun', 'Umut', 'Koray', 'Alper', 'Barış', 'Eren', 'Kadir', 'Fatih', 'Yusuf',
    'İbrahim', 'Mustafa', 'Gökhan', 'Oğuz', 'Tuğrul', 'Selim', 'Batuhan', 'Doruk', 'Ege', 'Yiğit',
    'Cihan', 'Semih', 'Tuna', 'Tarık', 'Ufuk', 'Ferhat', 'Cüneyt', 'Bora', 'Çağlar', 'Levent'
];

$firstNamesFemale = [
    'Ayşe', 'Fatma', 'Elif', 'Zeynep', 'Merve', 'Selin', 'Melis', 'Pelin', 'İrem', 'Damla',
    'Nazlı', 'Aslı', 'Seda', 'Bengü', 'Tuğba', 'Derya', 'Ceren', 'Gizem', 'Büşra', 'Ebru',
    'Duygu', 'Gamze', 'Berna', 'Sinem', 'Hande', 'Ece', 'Gözde', 'Simge', 'İpek', 'Ezgi',
    'Başak', 'Burcu', 'Özge', 'Cansu', 'Eylül', 'Defne', 'Aylin', 'Dilara', 'Deniz', 'Miray',
    'Beren', 'Nehir', 'Melike', 'Buse', 'Aleyna', 'Rüya', 'Işıl', 'Gülşah', 'Nihan', 'Sude'
];

$lastNames = [
    'Yılmaz', 'Kaya', 'Demir', 'Çelik', 'Şahin', 'Yıldız', 'Yıldırım', 'Öztürk', 'Aydın', 'Özdemir',
    'Arslan', 'Doğan', 'Kılıç', 'Aslan', 'Çetin', 'Kara', 'Koç', 'Kurt', 'Özkan', 'Şimşek',
    'Polat', 'Korkmaz', 'Acar', 'Bulut', 'Yüksel', 'Yavuz', 'Bilgin', 'Gül', 'Avcı', 'Güler',
    'Aksoy', 'Eren', 'Güneş', 'Bozkurt', 'Coşkun', 'Keskin', 'Dağ', 'Taş', 'Koçak', 'Şen',
    'Uçar', 'Ateş', 'Tekin', 'Aktaş', 'Erdoğan', 'Kalkan', 'Güngör', 'Albayrak', 'Işık', 'Varol',
    'Yalçın', 'Sarı', 'Duran', 'Büyük', 'Küçük', 'Gündoğdu', 'Çakır', 'Duman', 'Sezer', 'Kahraman'
];

$niluferDistricts = [
    ['Ahmet Taner Kışlalı Bulv. No:', 'Özlüce', '16120'],
    ['Fatih Sultan Mehmet Bulv. No:', 'İhsaniye', '16130'],
    ['Atatürk Cad. No:', 'Görükle', '16285'],
    ['Bey Sok. No:', 'Balat', '16140'],
    ['Ali Rıza Bey Cad. No:', 'Ataevler', '16140'],
    ['Sanayi Cad. No:', 'Fethiye', '16140'],
    ['Bilginler Cad. No:', 'Beşevler', '16110'],
    ['Mithat Paşa Cad. No:', '23 Nisan', '16120'],
    ['Ahmet Cevdet Paşa Cad. No:', 'Altınşehir', '16120'],
    ['Uğur Mumcu Bulv. No:', 'Ertuğrul', '16120'],
    ['Akpınar Cad. No:', 'Odunluk', '16110'],
    ['Lefkoşe Cad. No:', 'Konak', '16110'],
    ['Dumlupınar Cad. No:', 'Üçevler', '16120'],
    ['Prof. Dr. Erdal İnönü Cad. No:', 'Yüzüncüyıl', '16120']
];

echo "Connecting to MySQL server ({$masterHost})...\n";
$masterPdo = new PDO("mysql:host={$masterHost};dbname={$masterDb};charset=utf8mb4", $masterUser, $masterPass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// -------------------------------------------------------------------------------------------------
// Step 1: Total Cleanup of Existing Tenants and Demos
// -------------------------------------------------------------------------------------------------
echo "\n============================================================\n";
echo "STEP 1: Removing all existing tenant databases & demo records\n";
echo "============================================================\n";

$tenantDbs = $masterPdo->query("SHOW DATABASES LIKE 'ki_tenant_%'")->fetchAll(PDO::FETCH_COLUMN);
echo "Found " . count($tenantDbs) . " existing tenant databases. Dropping...\n";
foreach ($tenantDbs as $tdb) {
    $masterPdo->exec("DROP DATABASE IF EXISTS `{$tdb}`");
}
echo "✓ All existing tenant databases dropped.\n";

// Clear master catalog tables
$masterPdo->exec("SET FOREIGN_KEY_CHECKS=0");
$masterPdo->exec("DELETE FROM ea_tenants");
if ($masterPdo->query("SHOW TABLES LIKE 'ea_tenant_wallets'")->rowCount()) {
    $masterPdo->exec("DELETE FROM ea_tenant_wallets");
}
if ($masterPdo->query("SHOW TABLES LIKE 'ea_tenant_migration_log'")->rowCount()) {
    $masterPdo->exec("DELETE FROM ea_tenant_migration_log");
}
if ($masterPdo->query("SHOW TABLES LIKE 'ea_sandbox_slots'")->rowCount()) {
    $masterPdo->exec("DELETE FROM ea_sandbox_slots");
}
if ($masterPdo->query("SHOW TABLES LIKE 'ea_tenant_ai_policies'")->rowCount()) {
    $masterPdo->exec("DELETE FROM ea_tenant_ai_policies");
}
$masterPdo->exec("SET FOREIGN_KEY_CHECKS=1");
echo "✓ Master catalog tables successfully cleared.\n";

// Load base schema template
$schemaFile = '/tmp/ki_tenant_schema.sql';
if (!file_exists($schemaFile)) {
    die("FATAL: {$schemaFile} missing.\n");
}
$baseSchemaSql = file_get_contents($schemaFile);

// -------------------------------------------------------------------------------------------------
// Step 2: Blueprints Configuration Definition
// -------------------------------------------------------------------------------------------------
$blueprintsDir = __DIR__ . '/../seeders/blueprints/';
$bpFiles = glob($blueprintsDir . '*.json');

// Metadata mapping for all 18 blueprints
$blueprintMetadata = [
    'barber' => [
        'company_name' => 'Gentleman Barber Studio',
        'subdomain' => 'barber-bookiapp',
        'category' => 'Kuaför & Saç Tasarım',
        'staff_count' => 5,
        'station_prefix' => 'Koltuk',
        'cover_image' => 'https://images.unsplash.com/photo-1503951914875-452162b0f3f1?auto=format&fit=crop&w=1200&q=80',
        'description' => "Bursa Nilüfer'de modern erkek saç tasarımı, sakal tıraşı, cilt bakımı ve VIP berber deneyimi. RandevuBurada üzerinden 7/24 anında randevu alın.",
        'address' => 'Fatih Sultan Mehmet Bulv. No: 42, İhsaniye, Nilüfer / Bursa',
        'district' => 'Nilüfer',
        'phone' => '+90 224 451 10 20',
        'working_hours' => [
            'monday' => null,
            'tuesday' => ['start' => '09:00', 'end' => '20:00', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
            'wednesday' => ['start' => '09:00', 'end' => '20:00', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
            'thursday' => ['start' => '09:00', 'end' => '20:00', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
            'friday' => ['start' => '09:00', 'end' => '20:00', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
            'saturday' => ['start' => '09:00', 'end' => '20:00', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
            'sunday' => ['start' => '10:00', 'end' => '18:00', 'breaks' => [['start' => '13:30', 'end' => '14:30']]],
        ],
        'ai_persona' => "Gentleman Barber Studio'nun resmi AI asistanı. Müşterilere saç-sakal modelleri hakkında öneriler sunar, berber müsaitliklerini kontrol eder ve randevuları onaylar."
    ],
    'beauty_salon' => [
        'company_name' => 'Luxe Beauty Studio',
        'subdomain' => 'beauty-salon-bookiapp',
        'category' => 'Güzellik Salonu',
        'staff_count' => 6,
        'station_prefix' => 'Kabin',
        'cover_image' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&w=1200&q=80',
        'description' => "Özlüce'de uzman estetisyen kadrosu ile profesyonel cilt bakımı, lazer epilasyon, bölgesel incelme ve kaş tasarımı.",
        'address' => 'Ahmet Taner Kışlalı Bulv. No: 18, Özlüce, Nilüfer / Bursa',
        'district' => 'Nilüfer',
        'phone' => '+90 224 452 30 40',
        'working_hours' => [
            'monday' => ['start' => '09:00', 'end' => '19:30', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
            'tuesday' => ['start' => '09:00', 'end' => '19:30', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
            'wednesday' => ['start' => '09:00', 'end' => '19:30', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
            'thursday' => ['start' => '09:00', 'end' => '19:30', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
            'friday' => ['start' => '09:00', 'end' => '19:30', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
            'saturday' => ['start' => '09:00', 'end' => '19:30', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
            'sunday' => null,
        ],
        'ai_persona' => "Luxe Beauty Studio güzellik danışmanı AI asistanı. Danışanların cilt tipi ve işlem geçmişine göre uygun seans ve bakım paketleri önerir."
    ],
    'nail_studio' => [
        'company_name' => 'Glossy Art Nail Studio',
        'subdomain' => 'nail-studio-bookiapp',
        'category' => 'Tırnak Stüdyosu & Nail Bar',
        'staff_count' => 5,
        'station_prefix' => 'Tırnak Masası',
        'cover_image' => 'https://images.unsplash.com/photo-1632345031435-8727f6897d53?auto=format&fit=crop&w=1200&q=80',
        'description' => "Balat'ta protez tırnak, kalıcı oje, medikal manikür, pedikür ve özel nail art tasarımları.",
        'address' => 'Bey Sok. No: 8/A, Balat, Nilüfer / Bursa',
        'district' => 'Nilüfer',
        'phone' => '+90 224 453 50 60',
        'working_hours' => [
            'monday' => ['start' => '10:00', 'end' => '19:30', 'breaks' => [['start' => '13:30', 'end' => '14:30']]],
            'tuesday' => ['start' => '10:00', 'end' => '19:30', 'breaks' => [['start' => '13:30', 'end' => '14:30']]],
            'wednesday' => ['start' => '10:00', 'end' => '19:30', 'breaks' => [['start' => '13:30', 'end' => '14:30']]],
            'thursday' => ['start' => '10:00', 'end' => '19:30', 'breaks' => [['start' => '13:30', 'end' => '14:30']]],
            'friday' => ['start' => '10:00', 'end' => '19:30', 'breaks' => [['start' => '13:30', 'end' => '14:30']]],
            'saturday' => ['start' => '10:00', 'end' => '19:30', 'breaks' => [['start' => '13:30', 'end' => '14:30']]],
            'sunday' => null,
        ],
        'ai_persona' => "Glossy Art Nail Studio tırnak tasarım asistanı. Nail art modelleri ve tırnak güçlendirme seansları hakkında bilgi verir."
    ],
    'massage_spa' => [
        'company_name' => 'Serenity Spa & Wellness Studio',
        'subdomain' => 'massage-spa-bookiapp',
        'category' => 'Masaj Salonu / SPA & Wellness',
        'staff_count' => 6,
        'station_prefix' => 'Terapi Odası',
        'cover_image' => 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?auto=format&fit=crop&w=1200&q=80',
        'description' => "Çekirge'de geleneksel Türk hamamı, aromaterapi masajı, derin doku masajı ve sauna wellness ritüelleri.",
        'address' => 'Çekirge Cad. No: 104, Osmangazi / Bursa',
        'district' => 'Osmangazi',
        'phone' => '+90 224 233 70 80',
        'working_hours' => [
            'monday' => ['start' => '10:00', 'end' => '22:00', 'breaks' => [['start' => '14:00', 'end' => '15:00']]],
            'tuesday' => ['start' => '10:00', 'end' => '22:00', 'breaks' => [['start' => '14:00', 'end' => '15:00']]],
            'wednesday' => ['start' => '10:00', 'end' => '22:00', 'breaks' => [['start' => '14:00', 'end' => '15:00']]],
            'thursday' => ['start' => '10:00', 'end' => '22:00', 'breaks' => [['start' => '14:00', 'end' => '15:00']]],
            'friday' => ['start' => '10:00', 'end' => '22:00', 'breaks' => [['start' => '14:00', 'end' => '15:00']]],
            'saturday' => ['start' => '10:00', 'end' => '22:00', 'breaks' => [['start' => '14:00', 'end' => '15:00']]],
            'sunday' => ['start' => '10:00', 'end' => '22:00', 'breaks' => [['start' => '14:00', 'end' => '15:00']]],
        ],
        'ai_persona' => "Serenity Spa & Wellness AI resepsiyonu. Terapist ve oda müsaitliklerini koordine eder, spa paketlerini tanıtır."
    ],
    'restaurant' => [
        'company_name' => 'Gusto Gurme Restorant & Lounge',
        'subdomain' => 'restaurant-bookiapp',
        'category' => 'Restoran, Kafe & Bistro',
        'staff_count' => 10,
        'station_prefix' => 'Masa',
        'cover_image' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1200&q=80',
        'description' => "Fethiye Sanayi Cad.'de şefin özel gurme tadım menüsü, taş fırın lezzetleri, VIP loca ve masa rezervasyonu.",
        'address' => 'Sanayi Cad. No: 215, Fethiye, Nilüfer / Bursa',
        'district' => 'Nilüfer',
        'phone' => '+90 224 454 90 00',
        'working_hours' => [
            'monday' => ['start' => '11:30', 'end' => '23:30', 'breaks' => [['start' => '16:00', 'end' => '17:00']]],
            'tuesday' => ['start' => '11:30', 'end' => '23:30', 'breaks' => [['start' => '16:00', 'end' => '17:00']]],
            'wednesday' => ['start' => '11:30', 'end' => '23:30', 'breaks' => [['start' => '16:00', 'end' => '17:00']]],
            'thursday' => ['start' => '11:30', 'end' => '23:30', 'breaks' => [['start' => '16:00', 'end' => '17:00']]],
            'friday' => ['start' => '11:30', 'end' => '23:30', 'breaks' => [['start' => '16:00', 'end' => '17:00']]],
            'saturday' => ['start' => '11:30', 'end' => '23:30', 'breaks' => [['start' => '16:00', 'end' => '17:00']]],
            'sunday' => ['start' => '11:30', 'end' => '23:30', 'breaks' => [['start' => '16:00', 'end' => '17:00']]],
        ],
        'ai_persona' => "Gusto Gurme Restorant maître d' AI asistanı. Masa rezervasyonu alır, özel gün ve diyet tercihlerini (alerjen, vejetaryen) not eder."
    ],
    'hotel' => [
        'company_name' => 'Grand Bursa Butik Hotel & Suites',
        'subdomain' => 'hotel-bookiapp',
        'category' => 'Otel & Butik Konaklama / Resort',
        'staff_count' => 12,
        'station_prefix' => 'Oda',
        'cover_image' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1200&q=80',
        'description' => "Kükürtlü'de termal spa ayrıcalıklı Deluxe süitler, panoramik şehir manzaralı executive odalar ve 7/24 oda servisi.",
        'address' => 'Kükürtlü Mah. Oulu Cad. No: 12, Osmangazi / Bursa',
        'district' => 'Osmangazi',
        'phone' => '+90 224 234 11 22',
        'working_hours' => [
            'monday' => ['start' => '00:00', 'end' => '23:59', 'breaks' => []],
            'tuesday' => ['start' => '00:00', 'end' => '23:59', 'breaks' => []],
            'wednesday' => ['start' => '00:00', 'end' => '23:59', 'breaks' => []],
            'thursday' => ['start' => '00:00', 'end' => '23:59', 'breaks' => []],
            'friday' => ['start' => '00:00', 'end' => '23:59', 'breaks' => []],
            'saturday' => ['start' => '00:00', 'end' => '23:59', 'breaks' => []],
            'sunday' => ['start' => '00:00', 'end' => '23:59', 'breaks' => []],
        ],
        'ai_persona' => "Grand Bursa Hotel 7/24 Dijital Concierge AI asistanı. Oda tipleri, transfer, giriş-çıkış ve otel olanakları hakkında yardımcı olur."
    ],
    'doctor_clinic' => [
        'company_name' => 'Vita Medikal Klinik Studio',
        'subdomain' => 'doctor-clinic-bookiapp',
        'category' => 'Doktor Özel Klinik & Poliklinik',
        'staff_count' => 7,
        'station_prefix' => 'Muayene Odası',
        'cover_image' => 'https://images.unsplash.com/photo-1629909613654-28e377c37b09?auto=format&fit=crop&w=1200&q=80',
        'description' => "Odunluk'ta uzman hekim kadrosu, modern teşhis cihazları, dahiliye, kardiyoloji ve check-up muayeneleri.",
        'address' => 'Akpınar Cad. No: 5, Odunluk, Nilüfer / Bursa',
        'district' => 'Nilüfer',
        'phone' => '+90 224 455 33 44',
        'working_hours' => [
            'monday' => ['start' => '08:30', 'end' => '18:00', 'breaks' => [['start' => '12:30', 'end' => '13:30']]],
            'tuesday' => ['start' => '08:30', 'end' => '18:00', 'breaks' => [['start' => '12:30', 'end' => '13:30']]],
            'wednesday' => ['start' => '08:30', 'end' => '18:00', 'breaks' => [['start' => '12:30', 'end' => '13:30']]],
            'thursday' => ['start' => '08:30', 'end' => '18:00', 'breaks' => [['start' => '12:30', 'end' => '13:30']]],
            'friday' => ['start' => '08:30', 'end' => '18:00', 'breaks' => [['start' => '12:30', 'end' => '13:30']]],
            'saturday' => ['start' => '09:00', 'end' => '14:00', 'breaks' => []],
            'sunday' => null,
        ],
        'ai_persona' => "Vita Medikal Klinik hasta kabul ve randevu AI asistanı. Hekim uzmanlık alanları ve poliklinik saatleri hakkında danışmanlık sağlar."
    ],
    'dentist' => [
        'company_name' => 'DentArt Diş Sağlığı & Dental Studio',
        'subdomain' => 'dentist-bookiapp',
        'category' => 'Diş Hekimi & Ağız Diş Sağlığı',
        'staff_count' => 6,
        'station_prefix' => 'Dental Ünit',
        'cover_image' => 'https://images.unsplash.com/photo-1629909615184-74f495363b67?auto=format&fit=crop&w=1200&q=80',
        'description' => "Üçevler'de estetik gülüş tasarımı, zirkonyum kaplama, implantoloji, diş beyazlatma ve şeffaf plak ortodonti.",
        'address' => 'Dumlupınar Cad. No: 28, Üçevler, Nilüfer / Bursa',
        'district' => 'Nilüfer',
        'phone' => '+90 224 456 55 66',
        'working_hours' => [
            'monday' => ['start' => '09:00', 'end' => '19:00', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
            'tuesday' => ['start' => '09:00', 'end' => '19:00', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
            'wednesday' => ['start' => '09:00', 'end' => '19:00', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
            'thursday' => ['start' => '09:00', 'end' => '19:00', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
            'friday' => ['start' => '09:00', 'end' => '19:00', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
            'saturday' => ['start' => '09:00', 'end' => '17:00', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
            'sunday' => null,
        ],
        'ai_persona' => "DentArt Dental Studio hasta koordinatörü AI asistanı. Diş tedavisi süreçleri ve hekim randevu takvimi hakkında bilgilendirme yapar."
    ],
    'psychology_dietitian_clinic' => [
        'company_name' => 'Denge Beslenme & Psikoloji Danışmanlık Studio',
        'subdomain' => 'psychology-dietitian-clinic-bookiapp',
        'category' => 'Diyetisyen / Psikolog & Danışmanlık',
        'staff_count' => 4,
        'station_prefix' => 'Görüşme Odası',
        'cover_image' => 'https://images.unsplash.com/photo-1573497620053-ea5300f94f21?auto=format&fit=crop&w=1200&q=80',
        'description' => "Konak Mah.'de klinik psikoloji, bilişsel davranışçı terapi, sporcu beslenmesi ve metabolik kilo yönetimi danışmanlığı.",
        'address' => 'Lefkoşe Cad. No: 44, Konak, Nilüfer / Bursa',
        'district' => 'Nilüfer',
        'phone' => '+90 224 457 77 88',
        'working_hours' => [
            'monday' => ['start' => '09:30', 'end' => '18:30', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
            'tuesday' => ['start' => '09:30', 'end' => '18:30', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
            'wednesday' => ['start' => '09:30', 'end' => '18:30', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
            'thursday' => ['start' => '09:30', 'end' => '18:30', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
            'friday' => ['start' => '09:30', 'end' => '18:30', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
            'saturday' => ['start' => '10:00', 'end' => '16:00', 'breaks' => []],
            'sunday' => null,
        ],
        'ai_persona' => "Denge Danışmanlık AI asistanı. Yüz yüze ve online terapi & diyetisyen seanslarını gizlilik esaslarına uygun organize eder."
    ],
    'gym' => [
        'company_name' => 'IronCore Fitness & GYM Studio',
        'subdomain' => 'gym-bookiapp',
        'category' => 'Spor Salonu / Fitness & GYM',
        'staff_count' => 7,
        'station_prefix' => 'Antrenman Alanı',
        'cover_image' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=1200&q=80',
        'description' => "Beşevler'de serbest ağırlık, kardiyo, CrossFit WOD parkuru, grup dersleri ve üyelik programları.",
        'address' => 'Bilginler Cad. No: 62, Beşevler, Nilüfer / Bursa',
        'district' => 'Nilüfer',
        'phone' => '+90 224 458 99 00',
        'working_hours' => [
            'monday' => ['start' => '07:00', 'end' => '22:30', 'breaks' => []],
            'tuesday' => ['start' => '07:00', 'end' => '22:30', 'breaks' => []],
            'wednesday' => ['start' => '07:00', 'end' => '22:30', 'breaks' => []],
            'thursday' => ['start' => '07:00', 'end' => '22:30', 'breaks' => []],
            'friday' => ['start' => '07:00', 'end' => '22:30', 'breaks' => []],
            'saturday' => ['start' => '09:00', 'end' => '20:30', 'breaks' => []],
            'sunday' => ['start' => '09:00', 'end' => '20:30', 'breaks' => []],
        ],
        'ai_persona' => "IronCore GYM üye destek AI asistanı. Üyelik paketleri, antrenör seansları ve tesis kuralları hakkında bilgi sunar."
    ],
    'pilates_studio' => [
        'company_name' => 'CoreFlex Reformer Pilates Studio',
        'subdomain' => 'pilates-studio-bookiapp',
        'category' => 'Pilates & Reformer Stüdyosu',
        'staff_count' => 5,
        'station_prefix' => 'Reformer İstasyonu',
        'cover_image' => 'https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=1200&q=80',
        'description' => "Ertuğrul Mah.'de aletli reformer pilates, tower cadillac, postür analizi ve klinik omurga sağlığı seansları.",
        'address' => 'Uğur Mumcu Bulv. No: 33, Ertuğrul, Nilüfer / Bursa',
        'district' => 'Nilüfer',
        'phone' => '+90 224 459 12 34',
        'working_hours' => [
            'monday' => ['start' => '08:00', 'end' => '21:00', 'breaks' => [['start' => '13:30', 'end' => '14:30']]],
            'tuesday' => ['start' => '08:00', 'end' => '21:00', 'breaks' => [['start' => '13:30', 'end' => '14:30']]],
            'wednesday' => ['start' => '08:00', 'end' => '21:00', 'breaks' => [['start' => '13:30', 'end' => '14:30']]],
            'thursday' => ['start' => '08:00', 'end' => '21:00', 'breaks' => [['start' => '13:30', 'end' => '14:30']]],
            'friday' => ['start' => '08:00', 'end' => '21:00', 'breaks' => [['start' => '13:30', 'end' => '14:30']]],
            'saturday' => ['start' => '08:00', 'end' => '19:00', 'breaks' => [['start' => '13:30', 'end' => '14:30']]],
            'sunday' => ['start' => '10:00', 'end' => '16:00', 'breaks' => []],
        ],
        'ai_persona' => "CoreFlex Pilates stüdyo danışmanı AI asistanı. Birebir ve düet reformer derslerini eğitmen programına göre planlar."
    ],
    'pt_training' => [
        'company_name' => 'ProFit Birebir Antrenman & PT Studio',
        'subdomain' => 'pt-training-bookiapp',
        'category' => 'PT Antrenman & Kişisel Koçluk',
        'staff_count' => 4,
        'station_prefix' => 'PT Alanı',
        'cover_image' => 'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?auto=format&fit=crop&w=1200&q=80',
        'description' => "Yüzüncüyıl'da kişiye özel fonksiyonel antrenman, kuvvet ve hipertrofi programı, vücut analizi ve beslenme takibi.",
        'address' => 'Prof. Dr. Erdal İnönü Cad. No: 19, Yüzüncüyıl, Nilüfer / Bursa',
        'district' => 'Nilüfer',
        'phone' => '+90 224 460 23 45',
        'working_hours' => [
            'monday' => ['start' => '07:30', 'end' => '21:30', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
            'tuesday' => ['start' => '07:30', 'end' => '21:30', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
            'wednesday' => ['start' => '07:30', 'end' => '21:30', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
            'thursday' => ['start' => '07:30', 'end' => '21:30', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
            'friday' => ['start' => '07:30', 'end' => '21:30', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
            'saturday' => ['start' => '08:00', 'end' => '18:00', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
            'sunday' => null,
        ],
        'ai_persona' => "ProFit PT Studio antrenman koçu AI asistanı. Danışanların hedeflerine uygun seans takvimini yönetir."
    ],
    'sports_court' => [
        'company_name' => 'Arena Padel & Spor Sahası Studio',
        'subdomain' => 'sports-court-bookiapp',
        'category' => 'Kort & Halı Saha / Padel / Tenis',
        'staff_count' => 4,
        'station_prefix' => 'Saha / Kort',
        'cover_image' => 'https://images.unsplash.com/photo-1554068865-24cecd4e34b8?auto=format&fit=crop&w=1200&q=80',
        'description' => "Ataevler'de panoramik cam padel kortları, kapalı halı saha, tenis kortu kiralama ve video maç kaydı.",
        'address' => 'Sanayi Cad. No: 88, Ataevler, Nilüfer / Bursa',
        'district' => 'Nilüfer',
        'phone' => '+90 224 461 34 56',
        'working_hours' => [
            'monday' => ['start' => '08:00', 'end' => '23:59', 'breaks' => []],
            'tuesday' => ['start' => '08:00', 'end' => '23:59', 'breaks' => []],
            'wednesday' => ['start' => '08:00', 'end' => '23:59', 'breaks' => []],
            'thursday' => ['start' => '08:00', 'end' => '23:59', 'breaks' => []],
            'friday' => ['start' => '08:00', 'end' => '23:59', 'breaks' => []],
            'saturday' => ['start' => '08:00', 'end' => '23:59', 'breaks' => []],
            'sunday' => ['start' => '08:00', 'end' => '23:59', 'breaks' => []],
        ],
        'ai_persona' => "Arena Sports Court saha kiralama AI asistanı. Boş saatleri sorgular, maç rezervasyonlarını organize eder."
    ],
    'car_wash' => [
        'company_name' => 'AquaShine Oto Yıkama & Detailing Studio',
        'subdomain' => 'car-wash-bookiapp',
        'category' => 'Araç Yıkama & Detailing / Auto Spa',
        'staff_count' => 5,
        'station_prefix' => 'Yıkama Peronu',
        'cover_image' => 'https://images.unsplash.com/photo-1520340356584-f9917d1eea6f?auto=format&fit=crop&w=1200&q=80',
        'description' => "İzmir Yolu üzerinde fırçasız köpüklü iç-dış yıkama, antibakteriyel buharlı detaylı temizlik ve hızlı cila.",
        'address' => 'İzmir Yolu Cad. No: 140, Nilüfer / Bursa',
        'district' => 'Nilüfer',
        'phone' => '+90 224 462 45 67',
        'working_hours' => [
            'monday' => ['start' => '08:30', 'end' => '20:00', 'breaks' => [['start' => '12:30', 'end' => '13:30']]],
            'tuesday' => ['start' => '08:30', 'end' => '20:00', 'breaks' => [['start' => '12:30', 'end' => '13:30']]],
            'wednesday' => ['start' => '08:30', 'end' => '20:00', 'breaks' => [['start' => '12:30', 'end' => '13:30']]],
            'thursday' => ['start' => '08:30', 'end' => '20:00', 'breaks' => [['start' => '12:30', 'end' => '13:30']]],
            'friday' => ['start' => '08:30', 'end' => '20:00', 'breaks' => [['start' => '12:30', 'end' => '13:30']]],
            'saturday' => ['start' => '08:30', 'end' => '20:00', 'breaks' => [['start' => '12:30', 'end' => '13:30']]],
            'sunday' => ['start' => '09:00', 'end' => '18:00', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
        ],
        'ai_persona' => "AquaShine Oto Yıkama AI görevlisi. Araç segmentine göre yıkama peronunu ve süresini planlar."
    ],
    'auto_service_detailing' => [
        'company_name' => 'Apex Detailing & Oto Servis Studio',
        'subdomain' => 'auto-service-detailing-bookiapp',
        'category' => 'Oto Servis / Detailing & Ekspertiz',
        'staff_count' => 6,
        'station_prefix' => 'Servis Lifti',
        'cover_image' => 'https://images.unsplash.com/photo-1619642751034-765dfdf7c58e?auto=format&fit=crop&w=1200&q=80',
        'description' => "Nilüfer Ticaret Merkezi'nde PPF şeffaf kaplama, seramik kaplama, boyasız göçük düzeltme ve 101 nokta ekspertiz.",
        'address' => 'Nilüfer Ticaret Merkezi 64. Sok. No: 14, Nilüfer / Bursa',
        'district' => 'Nilüfer',
        'phone' => '+90 224 463 56 78',
        'working_hours' => [
            'monday' => ['start' => '08:30', 'end' => '19:00', 'breaks' => [['start' => '12:30', 'end' => '13:30']]],
            'tuesday' => ['start' => '08:30', 'end' => '19:00', 'breaks' => [['start' => '12:30', 'end' => '13:30']]],
            'wednesday' => ['start' => '08:30', 'end' => '19:00', 'breaks' => [['start' => '12:30', 'end' => '13:30']]],
            'thursday' => ['start' => '08:30', 'end' => '19:00', 'breaks' => [['start' => '12:30', 'end' => '13:30']]],
            'friday' => ['start' => '08:30', 'end' => '19:00', 'breaks' => [['start' => '12:30', 'end' => '13:30']]],
            'saturday' => ['start' => '08:30', 'end' => '17:00', 'breaks' => [['start' => '12:30', 'end' => '13:30']]],
            'sunday' => null,
        ],
        'ai_persona' => "Apex Detailing & Servis servis danışmanı AI asistanı. Araç modeli ve bakım ihtiyacına göre iş emri randevusu oluşturur."
    ],
    'experience_escape_room' => [
        'company_name' => 'Enigma Kaçış Oyunu & VR Deneyim Studio',
        'subdomain' => 'experience-escape-room-bookiapp',
        'category' => 'Kaçış Oyunu / VR & Atölye Deneyimleri',
        'staff_count' => 4,
        'station_prefix' => 'Oyun Odası',
        'cover_image' => 'https://images.unsplash.com/photo-1511512578047-dfb367046420?auto=format&fit=crop&w=1200&q=80',
        'description' => "Görükle'de adrenalin dolu tematik kaçış odaları, çok oyunculu VR simülasyonları ve seramik atölye deneyimleri.",
        'address' => 'Görükle Mah. Atatürk Cad. No: 76, Nilüfer / Bursa',
        'district' => 'Nilüfer',
        'phone' => '+90 224 464 67 89',
        'working_hours' => [
            'monday' => ['start' => '12:00', 'end' => '23:59', 'breaks' => []],
            'tuesday' => ['start' => '12:00', 'end' => '23:59', 'breaks' => []],
            'wednesday' => ['start' => '12:00', 'end' => '23:59', 'breaks' => []],
            'thursday' => ['start' => '12:00', 'end' => '23:59', 'breaks' => []],
            'friday' => ['start' => '12:00', 'end' => '23:59', 'breaks' => []],
            'saturday' => ['start' => '11:00', 'end' => '23:59', 'breaks' => []],
            'sunday' => ['start' => '11:00', 'end' => '23:59', 'breaks' => []],
        ],
        'ai_persona' => "Enigma Deneyim Stüdyosu Game Master AI asistanı. Grup büyüklüğüne göre oda müsaitliğini ayarlar, kuralları iletir."
    ],
    'law_firm' => [
        'company_name' => 'Adalet Hukuk & Danışmanlık Legal Studio',
        'subdomain' => 'law-firm-bookiapp',
        'category' => 'Hukuk Bürosu & Avukatlık',
        'staff_count' => 4,
        'station_prefix' => 'Toplantı Odası',
        'cover_image' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1200&q=80',
        'description' => "Bursa Adliyesi karşısında şirketler hukuku, sözleşme denetimi, iş hukuku davaları ve arabuluculuk danışmanlığı.",
        'address' => 'Kıbrıs Şehitleri Cad. Adliye Karşısı No: 22, Osmangazi / Bursa',
        'district' => 'Osmangazi',
        'phone' => '+90 224 225 78 90',
        'working_hours' => [
            'monday' => ['start' => '09:00', 'end' => '18:00', 'breaks' => [['start' => '12:30', 'end' => '13:30']]],
            'tuesday' => ['start' => '09:00', 'end' => '18:00', 'breaks' => [['start' => '12:30', 'end' => '13:30']]],
            'wednesday' => ['start' => '09:00', 'end' => '18:00', 'breaks' => [['start' => '12:30', 'end' => '13:30']]],
            'thursday' => ['start' => '09:00', 'end' => '18:00', 'breaks' => [['start' => '12:30', 'end' => '13:30']]],
            'friday' => ['start' => '09:00', 'end' => '18:00', 'breaks' => [['start' => '12:30', 'end' => '13:30']]],
            'saturday' => null,
            'sunday' => null,
        ],
        'ai_persona' => "Adalet Hukuk Bürosu müvekkil koordinatörü AI asistanı. Danışmanlık randevularını ve toplantı saatlerini yönetir."
    ],
    'consulting_agency' => [
        'company_name' => 'Nova Danışmanlık & Strateji Studio',
        'subdomain' => 'consulting-agency-bookiapp',
        'category' => 'Danışmanlık & Ajans',
        'staff_count' => 4,
        'station_prefix' => 'Strateji Odası',
        'cover_image' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=1200&q=80',
        'description' => "Eker Meydan'da kurumsal yönetim danışmanlığı, dijital büyüme stratejisi, İK süreç analizi ve marka yönetimi.",
        'address' => 'Odunluk Mah. Akademi Cad. Eker Meydan No: 10, Nilüfer / Bursa',
        'district' => 'Nilüfer',
        'phone' => '+90 224 465 89 01',
        'working_hours' => [
            'monday' => ['start' => '09:00', 'end' => '18:30', 'breaks' => [['start' => '12:30', 'end' => '13:30']]],
            'tuesday' => ['start' => '09:00', 'end' => '18:30', 'breaks' => [['start' => '12:30', 'end' => '13:30']]],
            'wednesday' => ['start' => '09:00', 'end' => '18:30', 'breaks' => [['start' => '12:30', 'end' => '13:30']]],
            'thursday' => ['start' => '09:00', 'end' => '18:30', 'breaks' => [['start' => '12:30', 'end' => '13:30']]],
            'friday' => ['start' => '09:00', 'end' => '18:30', 'breaks' => [['start' => '12:30', 'end' => '13:30']]],
            'saturday' => null,
            'sunday' => null,
        ],
        'ai_persona' => "Nova Danışmanlık strateji asistanı AI. Şirket keşif seanslarını ve çalıştay saatlerini planlar."
    ]
];

echo "\n============================================================\n";
echo "STEP 2: Provisioning 18 Blueprint Demo Tenants\n";
echo "============================================================\n";

$tenantIndex = 0;
foreach ($blueprintMetadata as $code => $meta) {
    $tenantIndex++;
    $t0 = microtime(true);
    
    $subdomain = $meta['subdomain'];
    $customDomain = "{$subdomain}.kibusiness.co";
    $dbName = "ki_tenant_{$subdomain}";
    $companyName = $meta['company_name'];
    $category = $meta['category'];
    $address = $meta['address'];
    $phone = $meta['phone'];
    $coverImage = $meta['cover_image'];
    $shortDesc = $meta['description'];
    $staffCount = $meta['staff_count'];
    $stationPrefix = $meta['station_prefix'];
    $logoUrl = "/assets/img/logos/{$code}.svg";
    
    // Load json blueprint
    $jsonPath = $blueprintsDir . $code . '.json';
    $bpData = file_exists($jsonPath) ? json_decode(file_get_contents($jsonPath), true) : [];
    $bType = $bpData['business_type'] ?? $code;
    $family = $bpData['family'] ?? 'universal';
    
    echo sprintf("\n[%02d/18] Provisioning %s (%s)...\n", $tenantIndex, $companyName, $subdomain);

    // 1. Create DB
    $masterPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    $tenantPdo = new PDO("mysql:host={$masterHost};dbname={$dbName};charset=utf8mb4", $masterUser, $masterPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $tenantPdo->exec("SET FOREIGN_KEY_CHECKS=0");
    $tenantPdo->exec($baseSchemaSql);

    // 2. Generate per-tenant encryption keys
    $piiEncKeyB64 = base64_encode(random_bytes(32));
    $piiHashKeyB64 = base64_encode(random_bytes(32));
    $piiEncKeyRaw = base64_decode($piiEncKeyB64);
    $piiHashKeyRaw = base64_decode($piiHashKeyB64);

    // 3. Configure working hours JSON
    $workingHoursJson = json_encode($meta['working_hours'], JSON_UNESCAPED_UNICODE);

    // 4. Register in Master `ea_tenants`
    $stmtMaster = $masterPdo->prepare("
        INSERT INTO ea_tenants (
            subdomain, custom_domain, db_host, db_name, db_username, db_password,
            pii_enc_key, pii_hash_key, deployment_type, status,
            created_at, updated_at, plan, trial_ends_at, license_expires_at,
            marketplace_opt_in, category, city, district, address, phone_number,
            company_name, cover_image_url, short_description, price_range,
            working_hours_json, business_type, onboarding_status, onboarding_completed_at
        ) VALUES (
            :sub, :cdom, 'db', :db, :user, :pass,
            :enc_k, :hash_k, 'cloud', 'active',
            NOW(), NOW(), 'Premium', '2028-01-01 00:00:00', '2028-01-01 00:00:00',
            1, :cat, 'Bursa', :dist, :addr, :phone,
            :cname, :cover, :sdesc, '₺₺',
            :whours, :btype, 'completed', NOW()
        )
    ");
    $stmtMaster->execute([
        'sub' => $subdomain,
        'cdom' => $customDomain,
        'db' => $dbName,
        'user' => $masterUser,
        'pass' => tm_encrypt($masterPass, $tenantMasterKeyRaw),
        'enc_k' => tm_encrypt($piiEncKeyB64, $tenantMasterKeyRaw),
        'hash_k' => tm_encrypt($piiHashKeyB64, $tenantMasterKeyRaw),
        'cat' => $category,
        'dist' => $meta['district'],
        'addr' => $address,
        'phone' => $phone,
        'cname' => $companyName,
        'cover' => $coverImage,
        'sdesc' => $shortDesc,
        'whours' => $workingHoursJson,
        'btype' => $bType
    ]);

    // 5. Tenant settings (`ea_settings`)
    $featuresMap = [
        'appointments' => true,
        'calendar' => true,
        'stations' => true,
        'packages' => true,
        'memberships' => true,
        'adisyon' => true,
        'pos' => true,
        'finance' => true,
        'expenses' => true,
        'inventory' => true,
        'staff_commissions' => true,
        'marketing' => true,
        'reviews' => true,
        'loyalty' => true,
        'client_portal' => true,
        'checkin' => true,
        'invoices' => true,
        'restaurant_floor_plan' => ($code === 'restaurant'),
        'restaurant_reservations' => ($code === 'restaurant'),
        'restaurant_experiences' => ($code === 'restaurant'),
    ];

    $terminology = $bpData['terminology'] ?? [
        'customer' => 'Müşteri',
        'provider' => 'Uzman',
        'appointment' => 'Randevu',
        'service' => 'Hizmet',
        'station' => 'İstasyon'
    ];

    $tenantSettings = [
        'company_name' => $companyName,
        'company_email' => "info@{$subdomain}.kibusiness.co",
        'company_link' => "https://{$customDomain}",
        'company_phone' => $phone,
        'company_address' => $address,
        'company_logo' => $logoUrl,
        'business_type' => $bType,
        'industry_code' => $code,
        'features_enabled_json' => json_encode($featuresMap),
        'industry_custom_terminology' => json_encode($terminology, JSON_UNESCAPED_UNICODE),
        'company_working_plan' => $workingHoursJson,
        'onboarding_completed' => '1',
        'default_timezone' => 'Europe/Istanbul',
        'date_format' => 'DMY',
        'time_format' => 'regular',
        'first_weekday' => 'Monday',
        'require_phone_number' => '1',
        'currency' => 'TRY',
        'currency_symbol' => '₺',
        'currency_code' => 'TRY',
        'future_booking_limit' => '120',
        'book_advance_timeout' => '30',
        'slot_interval' => (string)($bpData['default_settings']['slot_interval'] ?? '15'),
        'marketplace_opt_in' => '1',
        'marketplace_cover_image_url' => $coverImage,
        'randevuburada_active' => '1',
        // BooKi AI & AI Assistant
        'ai_assistant_enabled' => '1',
        'ai_engine_type' => 'booki_hosted',
        'ai_assistant_name' => 'BooKi AI Asistan',
        'ai_assistant_tone' => 'professional',
        'ai_assistant_persona' => $meta['ai_persona'],
        'ai_assistant_channel_scope' => 'all',
        'ai_assistant_permissions' => json_encode(['read_calendar', 'book_appointment', 'reschedule_appointment', 'cancel_appointment']),
        'ai_assistant_tasks' => "Gelen müşteri mesajlarını 7/24 karşılamak.\nMüsait personel ve saatleri sorgulayıp anında randevu kaydetmek.\nRandevu öncesi hatırlatma ve teyit iletileri göndermek.\nİptal ve erteleme taleplerini takvime işlemek.",
        'ai_assistant_prohibitions' => "Yönetici onayı olmadan ekstra indirim tanımlamamak.\nİşletme yetki sınırları dışındaki konularda kesin taahhüt vermemek.\nGizli müşteri kayıtlarını üçüncü taraflarla paylaşmamak.",
        'ai_assistant_sales_rules' => "Tamamlayıcı ek bakım ve sarfiyat ürünlerini hatırlat.\nİlk defa gelen müşterilere hoş geldin avantajından bahset.\nPaket ve seans seçeneklerindeki birim fiyat avantajını vurgula.",
        'ai_assistant_knowledge_base' => "İşletmemiz {$companyName}, Bursa {$meta['district']} lokasyonunda {$shortDesc} Müşterilerimiz RandevuBurada ve BooKi üzerinden güvenle online randevu oluşturabilir.",
        'ai_conversations_total' => '1000',
        'ai_conversations_used' => '18',
        'ai_voice_enabled' => '1',
        'ai_voice_total_minutes' => '120',
        'ai_voice_remaining_minutes' => '114',
    ];

    $stmtSet = $tenantPdo->prepare("INSERT INTO ea_settings (name, value) VALUES (:name, :value) ON DUPLICATE KEY UPDATE value = VALUES(value)");
    foreach ($tenantSettings as $k => $v) {
        $stmtSet->execute(['name' => $k, 'value' => $v]);
    }

    // 6. Tenant AI Policy (`ea_tenant_ai_policies`)
    $stmtAiPol = $tenantPdo->prepare("
        INSERT INTO ea_tenant_ai_policies (
            brand_name, tone, language, greeting_style, allowed_terms, forbidden_terms,
            do_rules, dont_rules, business_rules, cancellation_policy, refund_policy,
            discount_policy, escalation_rules, allowed_actions, approval_required_actions,
            forbidden_actions, created_at, updated_at
        ) VALUES (
            :bname, 'professional', 'tr', 'Saygılı, samimi ve kurumsal',
            '[\"randevu\",\"rezervasyon\",\"seans\",\"uzman\",\"hizmet\"]',
            '[\"garanti\",\"kesin sonuç\",\"bedava\"]',
            '[\"Müşteriye ismiyle hitap et\",\"Randevu saatini net teyit et\"]',
            '[\"Yetkisiz indirim yapma\",\"Rakip firmalarla kıyaslama\"]',
            '[\"Çalışma saatleri dışında acil durum mesajı bırak\"]',
            'Randevuya 2 saat kalana kadar sistem üzerinden ücretsiz iptal/erteleme yapılabilir.',
            'Hizmet başlangıcından önce yapılan iptallerde ödemeler kesintisiz iade edilir.',
            'İlk randevuda %10 tanışma indirimi uygulanır.',
            'Müşteri memnuniyetsizliği durumunda yöneticiye aktarılır.',
            '[\"read_calendar\",\"create_appointment\",\"reschedule_appointment\"]',
            '[\"cancel_appointment\",\"apply_discount\"]',
            '[\"delete_customer\",\"change_system_settings\"]',
            NOW(), NOW()
        )
    ");
    $stmtAiPol->execute(['bname' => $companyName]);

    // 7. Admin User
    $adminEmail = "admin@{$subdomain}.kibusiness.co";
    $adminPassHash = password_hash('BooKiDemo2026!', PASSWORD_DEFAULT);
    $stmtAdmin = $tenantPdo->prepare("
        INSERT INTO ea_users (first_name, last_name, email, email_hash, phone_number, phone_number_hash, address, city, zip_code, id_roles)
        VALUES (:fn, :ln, :email, :email_h, :phone, :phone_h, :addr, 'Bursa', '16120', 1)
    ");
    $stmtAdmin->execute([
        'fn' => sf_encrypt('Yönetici', $piiEncKeyRaw),
        'ln' => sf_encrypt('Admin', $piiEncKeyRaw),
        'email' => sf_encrypt($adminEmail, $piiEncKeyRaw),
        'email_h' => sf_hash($adminEmail, $piiHashKeyRaw),
        'phone' => sf_encrypt($phone, $piiEncKeyRaw),
        'phone_h' => sf_hash($phone, $piiHashKeyRaw),
        'addr' => sf_encrypt($address, $piiEncKeyRaw)
    ]);
    $adminId = $tenantPdo->lastInsertId();
    $tenantPdo->prepare("
        INSERT INTO ea_user_settings (id_users, username, password, notifications, calendar_view)
        VALUES (:uid, 'admin', :pass, 1, 0)
    ")->execute(['uid' => $adminId, 'pass' => $adminPassHash]);

    // 8. Categories & Services
    $catMap = []; // name => id
    if (!empty($bpData['service_categories'])) {
        $stmtCat = $tenantPdo->prepare("INSERT INTO ea_service_categories (name, description) VALUES (:name, :desc)");
        foreach ($bpData['service_categories'] as $c) {
            $stmtCat->execute(['name' => $c['name'], 'desc' => $c['description'] ?? '']);
            $catMap[$c['name']] = (int)$tenantPdo->lastInsertId();
        }
    }
    if (empty($catMap)) {
        $stmtCat = $tenantPdo->prepare("INSERT INTO ea_service_categories (name, description) VALUES (:name, 'Standart Hizmetler')");
        $stmtCat->execute(['name' => "Temel {$meta['category']}"]);
        $catMap["Temel {$meta['category']}"] = (int)$tenantPdo->lastInsertId();
    }

    $firstCatId = reset($catMap);
    $serviceIds = [];
    $servicesData = [];

    $stmtSvc = $tenantPdo->prepare("
        INSERT INTO ea_services (
            name, duration, price, currency, description, color, slot_interval,
            attendants_number, id_service_categories, service_nature, access_type, tax_rate
        ) VALUES (
            :name, :dur, :price, 'TRY', :desc, :color, :interval, :att, :cat, :nature, :access, 20.00
        )
    ");

    $bpServices = $bpData['services'] ?? [];
    // Ensure between 3 and 10 services per blueprint
    if (count($bpServices) < 3) {
        $bpServices[] = ['name' => "Standart {$companyName} Seansı", 'duration' => 45, 'price' => 850.00, 'color' => '#2563eb'];
        $bpServices[] = ['name' => "VIP Kapsamlı {$companyName} Paketi", 'duration' => 90, 'price' => 2200.00, 'color' => '#7c3aed'];
        $bpServices[] = ['name' => "Hızlı Ekspres Seans", 'duration' => 30, 'price' => 500.00, 'color' => '#059669'];
    }

    $colorPalette = ['#2563eb', '#7c3aed', '#059669', '#d97706', '#dc2626', '#0891b2', '#db2777', '#4f46e5'];
    foreach ($bpServices as $idx => $s) {
        $catId = isset($s['category']) && isset($catMap[$s['category']]) ? $catMap[$s['category']] : $firstCatId;
        $color = $s['color'] ?? $colorPalette[$idx % count($colorPalette)];
        $duration = (int)($s['duration'] ?? 45);
        $price = (float)($s['price'] ?? 500.00);
        $att = (int)($s['attendants_number'] ?? 1);
        $nature = $s['service_nature'] ?? 'duration';

        $stmtSvc->execute([
            'name' => $s['name'],
            'dur' => $duration,
            'price' => $price,
            'desc' => $s['description'] ?? "{$companyName} kapsamında sunulan profesyonel hizmet.",
            'color' => $color,
            'interval' => 15,
            'att' => $att,
            'cat' => $catId,
            'nature' => $nature,
            'access' => $nature
        ]);
        $sId = (int)$tenantPdo->lastInsertId();
        $serviceIds[] = $sId;
        $servicesData[] = [
            'id' => $sId,
            'name' => $s['name'],
            'duration' => $duration,
            'price' => $price,
            'color' => $color
        ];
    }

    // 9. Stations / Rooms / Tables / Courts (2 to 6)
    $stationIds = [];
    $bpStations = $bpData['stations'] ?? [];
    if (empty($bpStations)) {
        for ($st = 1; $st <= 4; $st++) {
            $bpStations[] = ['name' => "{$stationPrefix} {$st}", 'notes' => "Standart {$stationPrefix}"];
        }
    }
    $stmtStn = $tenantPdo->prepare("INSERT INTO ea_stations (name, notes, is_active) VALUES (:name, :notes, 1)");
    foreach ($bpStations as $stn) {
        $stmtStn->execute(['name' => $stn['name'], 'notes' => $stn['notes'] ?? '']);
        $stationIds[] = (int)$tenantPdo->lastInsertId();
    }

    // 10. Staff / Providers (1 to 30 depending on sector, e.g. 4 to 12)
    $providerIds = [];
    $stmtProv = $tenantPdo->prepare("
        INSERT INTO ea_users (first_name, last_name, email, email_hash, phone_number, phone_number_hash, address, city, notes, id_roles)
        VALUES (:fn, :ln, :email, :email_h, :phone, :phone_h, :addr, 'Bursa', :notes, 2)
    ");
    $stmtProvSet = $tenantPdo->prepare("
        INSERT INTO ea_user_settings (id_users, username, password, working_plan, notifications, calendar_view)
        VALUES (:uid, :uname, :pass, :wp, 1, 0)
    ");

    for ($stf = 1; $stf <= $staffCount; $stf++) {
        $isFemale = ($stf % 2 === 1);
        $fn = $isFemale ? $firstNamesFemale[($tenantIndex * 5 + $stf) % count($firstNamesFemale)] : $firstNamesMale[($tenantIndex * 5 + $stf) % count($firstNamesMale)];
        $ln = $lastNames[($tenantIndex * 3 + $stf) % count($lastNames)];
        $pEmail = "personel{$stf}@{$subdomain}.kibusiness.co";
        $pPhone = "+90 5" . sprintf("%02d", rand(30, 55)) . " " . sprintf("%03d", rand(100, 999)) . " " . sprintf("%02d", rand(10, 99)) . " " . sprintf("%02d", rand(10, 99));

        $stmtProv->execute([
            'fn' => sf_encrypt($fn, $piiEncKeyRaw),
            'ln' => sf_encrypt($ln, $piiEncKeyRaw),
            'email' => sf_encrypt($pEmail, $piiEncKeyRaw),
            'email_h' => sf_hash($pEmail, $piiHashKeyRaw),
            'phone' => sf_encrypt($pPhone, $piiEncKeyRaw),
            'phone_h' => sf_hash($pPhone, $piiHashKeyRaw),
            'addr' => sf_encrypt($address, $piiEncKeyRaw),
            'notes' => sf_encrypt("{$meta['category']} Uzmanı ({$fn} {$ln})", $piiEncKeyRaw)
        ]);
        $pId = (int)$tenantPdo->lastInsertId();
        $providerIds[] = $pId;

        $stmtProvSet->execute([
            'uid' => $pId,
            'uname' => "staff_{$stf}_{$code}",
            'pass' => password_hash('BooKiStaff2026!', PASSWORD_DEFAULT),
            'wp' => $workingHoursJson
        ]);

        // Map provider to all services
        foreach ($serviceIds as $sId) {
            $tenantPdo->exec("INSERT IGNORE INTO ea_services_providers (id_services, id_users) VALUES ({$sId}, {$pId})");
        }
        // Map provider to all stations
        foreach ($stationIds as $stId) {
            $tenantPdo->exec("INSERT IGNORE INTO ea_stations_providers (id_stations, id_users) VALUES ({$stId}, {$pId})");
        }
    }

    // Map stations to services
    foreach ($stationIds as $stId) {
        foreach ($serviceIds as $sId) {
            $tenantPdo->exec("INSERT IGNORE INTO ea_stations_services (id_stations, id_services) VALUES ({$stId}, {$sId})");
        }
    }

    // 11. Customer Data (40-50 realistic customers with Bursa addresses)
    $customerCount = rand(42, 48);
    $customerIds = [];
    $customersList = [];
    $stmtCust = $tenantPdo->prepare("
        INSERT INTO ea_users (first_name, last_name, email, email_hash, phone_number, phone_number_hash, address, city, zip_code, notes, id_roles)
        VALUES (:fn, :ln, :email, :email_h, :phone, :phone_h, :addr, 'Bursa', :zip, :notes, 3)
    ");

    for ($c = 1; $c <= $customerCount; $c++) {
        $isFemale = ($c % 2 === 0);
        $cFn = $isFemale ? $firstNamesFemale[($tenantIndex * 7 + $c) % count($firstNamesFemale)] : $firstNamesMale[($tenantIndex * 7 + $c) % count($firstNamesMale)];
        $cLn = $lastNames[($tenantIndex * 11 + $c) % count($lastNames)];
        $cEmail = "musteri{$c}@{$subdomain}.kibusiness.co";
        $cPhone = "+90 5" . sprintf("%02d", rand(30, 55)) . " " . sprintf("%03d", rand(100, 999)) . " " . sprintf("%02d", rand(10, 99)) . " " . sprintf("%02d", rand(10, 99));
        
        $districtInfo = $niluferDistricts[($tenantIndex + $c) % count($niluferDistricts)];
        $cAddress = $districtInfo[0] . rand(1, 95) . ", " . $districtInfo[1] . ", Nilüfer / Bursa";
        $cZip = $districtInfo[2];
        $cNotes = "Düzenli müşteri - {$companyName}";

        $stmtCust->execute([
            'fn' => sf_encrypt($cFn, $piiEncKeyRaw),
            'ln' => sf_encrypt($cLn, $piiEncKeyRaw),
            'email' => sf_encrypt($cEmail, $piiEncKeyRaw),
            'email_h' => sf_hash($cEmail, $piiHashKeyRaw),
            'phone' => sf_encrypt($cPhone, $piiEncKeyRaw),
            'phone_h' => sf_hash($cPhone, $piiHashKeyRaw),
            'addr' => sf_encrypt($cAddress, $piiEncKeyRaw),
            'zip' => $cZip,
            'notes' => sf_encrypt($cNotes, $piiEncKeyRaw)
        ]);
        $custId = (int)$tenantPdo->lastInsertId();
        $customerIds[] = $custId;
        $customersList[] = [
            'id' => $custId,
            'name' => "{$cFn} {$cLn}",
            'email' => $cEmail,
            'phone' => $cPhone,
            'address' => $cAddress
        ];
    }

    // 12. Consumable Products & Recipes
    $productIds = [];
    $productNames = [
        'Steril Sarf Seti' => ['sku' => "SRF-{$code}-01", 'cost' => 45.00, 'price' => 120.00, 'unit' => 'set', 'is_c' => 1],
        'Tek Kullanımlık Havlu / Önlük' => ['sku' => "SRF-{$code}-02", 'cost' => 12.00, 'price' => 35.00, 'unit' => 'adet', 'is_c' => 1],
        'Hijyen & Dezenfektan Solüsyonu (100ml)' => ['sku' => "SRF-{$code}-03", 'cost' => 25.00, 'price' => 60.00, 'unit' => 'şişe', 'is_c' => 1],
        'Premium Bakım Kürü / Yağı (50ml)' => ['sku' => "PRD-{$code}-04", 'cost' => 180.00, 'price' => 450.00, 'unit' => 'adet', 'is_c' => 0],
        'Özel Formüllü Ev Devam Ürünü' => ['sku' => "PRD-{$code}-05", 'cost' => 220.00, 'price' => 600.00, 'unit' => 'kutu', 'is_c' => 0],
    ];
    $stmtProd = $tenantPdo->prepare("
        INSERT INTO ea_products (name, sku, cost_price, sale_price, stock_quantity, low_stock_threshold, unit, is_consumable, is_active, created_at)
        VALUES (:name, :sku, :cost, :price, 150, 15, :unit, :is_c, 1, NOW())
    ");
    foreach ($productNames as $pName => $pData) {
        $stmtProd->execute([
            'name' => $pName,
            'sku' => $pData['sku'],
            'cost' => $pData['cost'],
            'price' => $pData['price'],
            'unit' => $pData['unit'],
            'is_c' => $pData['is_c']
        ]);
        $productIds[] = (int)$tenantPdo->lastInsertId();
    }

    // Link service consumables
    $stmtSvcCsm = $tenantPdo->prepare("
        INSERT INTO ea_service_consumables (id_services, id_products, quantity_used, unit, notes, created_at)
        VALUES (:sid, :pid, 1.00, 'adet', 'Standart Seans Sarfiyatı', NOW())
    ");
    foreach ($serviceIds as $sId) {
        $stmtSvcCsm->execute(['sid' => $sId, 'pid' => $productIds[0]]);
        $stmtSvcCsm->execute(['sid' => $sId, 'pid' => $productIds[1]]);
    }

    // 13. Digital Waivers / Onam Formları
    $waiverContent = "<h3>{$companyName} Hizmet & KVKK Onam Formu</h3>
<p>6698 sayılı Kişisel Verilerin Korunması Kanunu (KVKK) uyarınca, <strong>{$companyName}</strong> tarafından sunulan hizmetler kapsamında paylaştığım kişisel verilerimin randevu takibi, yasal muhasebe ve bildirim amacıyla işlenmesine onay veriyorum.</p>
<p>İşlem öncesinde tarafıma bildirilen uygulama adımlarını, hijyen protokollerini ve dikkat edilmesi gereken hususları okudum, anladım ve kabul ediyorum.</p>";

    $stmtWaiver = $tenantPdo->prepare("
        INSERT INTO ea_digital_waivers (title, content_html, is_mandatory, applicable_service_ids, created_at, updated_at)
        VALUES (:title, :content, 1, '[]', NOW(), NOW())
    ");
    $stmtWaiver->execute([
        'title' => "{$companyName} Aydınlatma ve Onam Metni",
        'content' => $waiverContent
    ]);
    $waiverId = (int)$tenantPdo->lastInsertId();

    // 14. Seed > 200 Appointments (Exactly 220 appointments)
    $totalAppointmentsToSeed = 220;
    $appointmentIds = [];

    $stmtAppt = $tenantPdo->prepare("
        INSERT INTO ea_appointments (
            start_datetime, end_datetime, book_datetime, notes, hash, color, status,
            payment_status, payment_method, payment_amount, deposit_amount, deposit_status,
            id_users_provider, id_users_customer, id_services, id_stations
        ) VALUES (
            :start_dt, :end_dt, :book_dt, :notes, :hash, :color, :status,
            :pay_status, :pay_method, :pay_amount, :dep_amount, :dep_status,
            :prov_id, :cust_id, :svc_id, :stn_id
        )
    ");

    $stmtApptCsm = $tenantPdo->prepare("
        INSERT INTO ea_appointment_consumables (id_appointments, id_products, quantity, unit_cost, total_cost, created_at)
        VALUES (:aid, :pid, 1.00, 45.00, 45.00, :dt)
        INSERT INTO ea_appointment_consumables (id_appointments, id_products, quantity_used, unit, unit_cost, total_cost, created_at)
        VALUES (:aid, :pid, 1.00, 'adet', 45.00, 45.00, :dt)
    ");

    $stmtApptPrd = $tenantPdo->prepare("
        INSERT INTO ea_appointment_products (id_appointments, id_products, quantity, price, total_price, created_at)
        VALUES (:aid, :pid, 1.00, :price, :price, :dt)
        INSERT INTO ea_appointment_products (id_appointments, id_products, quantity, unit_price, created_at)
        VALUES (:aid, :pid, 1, :price, :dt)
    ");

    $stmtSignature = $tenantPdo->prepare("
        INSERT INTO ea_waiver_signatures (id_waivers, id_appointments, id_users_customer, signer_full_name, signer_email, signer_phone, signature_data, ip_address, signed_at)
        VALUES (:wid, :aid, :cid, :name, :email, :phone, 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==', :ip, :dt)
    ");

    // Distribute appointments:
    // Past: 01.07.2026 to 29.09.2026 (~150 appointments)
    // Current / Near future: 30.09.2026 to 31.12.2026 (~70 appointments)
    $todayTs = strtotime('2026-09-30 12:00:00');
    $startDateTs = strtotime('2026-07-01 09:00:00');
    $endDateTs   = strtotime('2026-12-15 18:00:00');

    for ($a = 0; $a < $totalAppointmentsToSeed; $a++) {
        $fraction = $a / (float)$totalAppointmentsToSeed;
        $appTs = $startDateTs + (int)($fraction * ($endDateTs - $startDateTs));
        // Add jitter
        $jitter = (($a * 37) % 7) - 3;
        $appTs += ($jitter * 86400);
        if ($appTs < $startDateTs) $appTs = $startDateTs;
        if ($appTs > $endDateTs) $appTs = $endDateTs;

        // Skip Sundays if business closed
        $dow = strtolower(date('l', $appTs));
        if ($meta['working_hours'][$dow] === null) {
            $appTs += 86400; // shift to Monday
        }

        // Slot hour
        $slotHour = 9 + (($a * 3) % 9); // between 09:00 and 17:00
        $slotMin  = (($a * 15) % 4) * 15; // 0, 15, 30, 45
        $appDateStr = date('Y-m-d', $appTs);
        $startDt = "{$appDateStr} " . sprintf("%02d:%02d:00", $slotHour, $slotMin);

        // Pick service, provider, customer, station
        $svc = $servicesData[$a % count($servicesData)];
        $duration = $svc['duration'];
        $price = $svc['price'];
        $endDt = date('Y-m-d H:i:s', strtotime("{$startDt} +{$duration} minutes"));
        $bookDt = date('Y-m-d H:i:s', strtotime("{$startDt} -" . rand(2, 6) . " days"));

        $provId = $providerIds[$a % count($providerIds)];
        $cust = $customersList[$a % count($customersList)];
        $custId = $cust['id'];
        $stnId = $stationIds[$a % count($stationIds)];

        $isPast = (strtotime($startDt) < $todayTs);
        $status = $isPast ? 'Tamamlandı' : ($a % 7 === 0 ? 'Booked' : 'Confirmed');
        $payStatus = $isPast ? 'paid' : ($a % 5 === 0 ? 'pending' : 'paid');
        $payMethod = ($a % 3 === 0) ? 'credit_card' : (($a % 3 === 1) ? 'cash' : 'virtual_pos');

        $hasDeposit = ($a % 4 === 0);
        $depAmount = $hasDeposit ? round($price * 0.20, 2) : 0.00;
        $depStatus = $hasDeposit ? ($isPast ? 'paid' : 'pending') : 'none';

        $notes = "{$companyName} - {$svc['name']} (" . ($isPast ? "Başarıyla Tamamlandı" : "Onaylandı") . ")";
        $hash = md5(uniqid("appt_{$a}_", true));

        $stmtAppt->execute([
            'start_dt' => $startDt,
            'end_dt' => $endDt,
            'book_dt' => $bookDt,
            'notes' => $notes,
            'hash' => $hash,
            'color' => $svc['color'],
            'status' => $status,
            'pay_status' => $payStatus,
            'pay_method' => $payMethod,
            'pay_amount' => $price,
            'dep_amount' => $depAmount,
            'dep_status' => $depStatus,
            'prov_id' => $provId,
            'cust_id' => $custId,
            'svc_id' => $svc['id'],
            'stn_id' => $stnId
        ]);
        $apptId = (int)$tenantPdo->lastInsertId();
        $appointmentIds[] = $apptId;

        // Add consumables on past appointments
        if ($isPast && $a % 2 === 0) {
            $stmtApptCsm->execute(['aid' => $apptId, 'pid' => $productIds[0], 'dt' => $startDt]);
        }
        // Add retail product sales occasionally
        if ($isPast && $a % 6 === 0) {
            $stmtApptPrd->execute(['aid' => $apptId, 'pid' => $productIds[3], 'price' => 450.00, 'dt' => $startDt]);
        }
        // Add digital waiver signature on first 35 appointments
        if ($a < 35) {
            $stmtSignature->execute([
                'wid' => $waiverId,
                'aid' => $apptId,
                'cid' => $custId,
                'name' => $cust['name'],
                'email' => $cust['email'],
                'phone' => $cust['phone'],
                'ip' => '176.240.' . rand(10, 250) . '.' . rand(2, 254),
                'dt' => $bookDt
            ]);
        }
    }

    // 15. Vertical-Specific Specialized Records
    if (in_array($code, ['beauty_salon', 'barber', 'nail_studio', 'massage_spa'])) {
        $stmtBeauty = $tenantPdo->prepare("
            INSERT INTO ea_customer_beauty_profiles (id_users_customer, color_formula, hair_type, skin_type, nail_notes, private_notes, created_at, updated_at)
            VALUES (:cid, :formula, :hair, :skin, :nail, :notes, NOW(), NOW())
            ON DUPLICATE KEY UPDATE updated_at = NOW()
        ");
        for ($bpIdx = 0; $bpIdx < min(15, count($customerIds)); $bpIdx++) {
            $stmtBeauty->execute([
                'cid' => $customerIds[$bpIdx],
                'formula' => '7.1 Küllü Kumral + %6 Oksidan (1:1)',
                'hair' => 'Dalgalı, kalın telli',
                'skin' => 'Karma / Hassas',
                'nail' => 'Badem form, güçlendirici baz uygulandı',
                'notes' => 'Müşteri sıcak tonları tercih ediyor, amonyaksız boya kullanılmalı.'
            ]);
        }
    } elseif (in_array($code, ['doctor_clinic', 'dentist', 'psychology_dietitian_clinic'])) {
        $stmtSoap = $tenantPdo->prepare("
            INSERT INTO ea_clinical_records (id_users_customer, id_appointments, id_users_provider, record_type, subjective, objective, assessment, plan, is_confidential, created_at, updated_at)
            VALUES (:cid, :aid, :pid, 'soap_note', :subj, :obj, :ass, :plan, 1, NOW(), NOW())
        ");
        for ($crIdx = 0; $crIdx < min(15, count($appointmentIds)); $crIdx++) {
            $stmtSoap->execute([
                'cid' => $customerIds[$crIdx % count($customerIds)],
                'aid' => $appointmentIds[$crIdx],
                'pid' => $providerIds[$crIdx % count($providerIds)],
                'subj' => 'Hasta genel kontrol ve rutin şikayetler ile başvurdu.',
                'obj' => 'Vital bulgular stabil: TA 120/80 mmHg, Nabız 72/dk.',
                'ass' => 'Rutin takip ve periyodik kontrol önerildi.',
                'plan' => 'Gerekli tetkikler istendi, 3 hafta sonra kontrol randevusu planlandı.'
            ]);
        }
    } elseif (in_array($code, ['car_wash', 'auto_service_detailing'])) {
        $stmtVeh = $tenantPdo->prepare("
            INSERT INTO ea_customer_vehicles (id_users_customer, plate_number, vin, brand, model, year, color, current_km, fuel_type, notes, created_at, updated_at)
            VALUES (:cid, :plate, :vin, :brand, :model, 2023, 'Füme', 42000, 'Benzin', 'Düzenli detailing ve bakım geçmişi mevcut.', NOW(), NOW())
        ");
        $carBrands = ['BMW', 'Mercedes-Benz', 'Audi', 'Volkswagen', 'Volvo', 'Porsche'];
        $carModels = ['320i', 'C200', 'A4', 'Tiguan', 'XC60', 'Macan'];
        for ($vIdx = 0; $vIdx < min(15, count($customerIds)); $vIdx++) {
            $plate = "16 BKI " . (100 + $vIdx);
            $vin = "WBA" . strtoupper(bin2hex(random_bytes(7)));
            $stmtVeh->execute([
                'cid' => $customerIds[$vIdx],
                'plate' => $plate,
                'vin' => $vin,
                'brand' => $carBrands[$vIdx % count($carBrands)],
                'model' => $carModels[$vIdx % count($carModels)]
            ]);
        }
    } elseif ($code === 'restaurant') {
        $stmtTable = $tenantPdo->prepare("
            INSERT INTO ea_restaurant_tables (table_number, table_label, capacity_min, capacity_max, section, is_active, created_at, updated_at)
            VALUES (:num, :lbl, 2, 6, :sec, 1, NOW(), NOW())
            INSERT INTO ea_restaurant_tables (table_number, name, section, capacity, shape, status)
            VALUES (:num, :name, :sec, 4, 'rectangle', 'available')
        ");
        $sections = ['İç Salon', 'Teras', 'Bahçe', 'VIP Loca'];
        for ($t = 1; $t <= 8; $t++) {
            $sec = $sections[($t - 1) % count($sections)];
            $stmtTable->execute(['num' => "M-{$t}", 'lbl' => "Masa {$t} ({$sec})", 'sec' => $sec]);
            $stmtTable->execute(['num' => "M-{$t}", 'name' => "Masa {$t} ({$sec})", 'sec' => $sec]);
        }
    } elseif ($code === 'hotel') {
        $stmtRoom = $tenantPdo->prepare("
            INSERT INTO ea_hospitality_room_types (code, name, max_adults, max_children, base_price, description, is_active, created_at)
            VALUES (:code, :name, 2, 1, :price, :desc, 1, NOW())
            INSERT INTO ea_hospitality_room_types (code, name, base_capacity_adults, base_capacity_children, max_capacity, base_price_per_night, description, is_active, created_at)
            VALUES (:code, :name, 2, 1, 3, :price, :desc, 1, NOW())
        ");
        $roomTypes = [
            ['code' => 'DLX', 'name' => 'Deluxe Thermal Suite', 'price' => 4500.00, 'desc' => 'Özel termal banyolu süit'],
            ['code' => 'SUP', 'name' => 'Superior City View Room', 'price' => 3200.00, 'desc' => 'Bursa manzaralı oda'],
            ['code' => 'EXE', 'name' => 'Executive Suite Penthouse', 'price' => 7500.00, 'desc' => 'Teraslı lüks çatı süiti']
        ];
        foreach ($roomTypes as $rt) {
            $stmtRoom->execute($rt);
        }
    } elseif (in_array($code, ['gym', 'pilates_studio', 'pt_training'])) {
        $stmtPlan = $tenantPdo->prepare("
            INSERT INTO ea_membership_plans (name, id_services, billing_period, price, sessions_per_period, is_active, created_at, category)
            VALUES (:name, :sid, 'monthly', :price, :sessions, 1, NOW(), 'Genel')
            INSERT INTO ea_membership_plans (name, id_services, billing_period, price, sessions_per_period, is_active, created_at)
            VALUES (:name, :sid, 'monthly', :price, :sessions, 1, NOW())
        ");
        $stmtPlan->execute(['name' => 'Aylık Sınırsız Üyelik', 'sid' => $serviceIds[0], 'price' => 3800.00, 'sessions' => null]);
        $planId = (int)$tenantPdo->lastInsertId();

        $stmtMem = $tenantPdo->prepare("
            INSERT INTO ea_customer_memberships (id_users_customer, id_membership_plans, status, current_period_start, current_period_end, sessions_used_this_period, auto_renew, qr_code_token, created_at)
            VALUES (:cid, :mpid, 'active', '2026-09-01 00:00:00', '2026-12-31 23:59:59', 8, 1, :qr, NOW())
        ");
        for ($mIdx = 0; $mIdx < 8; $mIdx++) {
            $stmtMem->execute([
                'cid' => $customerIds[$mIdx],
                'mpid' => $planId,
                'qr' => 'QR-' . strtoupper(bin2hex(random_bytes(6)))
            ]);
        }
    }

    $tenantPdo->exec("SET FOREIGN_KEY_CHECKS=1");

    $elapsed = round(microtime(true) - $t0, 2);
    echo sprintf("  ✓ Seeded: %s | %d Staff | %d Customers | %d Appointments | Products & AI Enabled (%ss)\n",
        $subdomain, $staffCount, count($customerIds), count($appointmentIds), $elapsed);
}

echo "\n============================================================\n";
echo "🎉 ALL 18 BLUEPRINT DEMO TENANTS PROVISIONED SUCCESSFULLY!\n";
echo "All subdomains: {blueprint}-bookiapp.kibusiness.co\n";
echo "All registered in master ea_tenants table with Premium plan.\n";
echo "============================================================\n";
