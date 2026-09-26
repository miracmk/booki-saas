import json

with open("/tmp/businesses_161.json", "r", encoding="utf-8") as f:
    businesses = json.load(f)

print(f"Loaded {len(businesses)} businesses.")

php_content = """<?php
/**
 * BooKi - 161 Demo Businesses Automated Seeder.
 *
 * Fully provisions all 161 demo tenants across 9 categories.
 * Each business includes:
 * - BooKi Logo & Branding
 * - Dedicated Database
 * - 3-5 Staff Providers with working plans
 * - 2-5 Stations / Cabins / Rooms / Lifts / Courts
 * - 4-8 Services with categorized pricing and durations
 * - Exactly 40 Random Customers with Bursa Nilüfer addresses, valid phones, emails, and tailored scenario notes
 * - Reservations / Appointments visible between 01.09.2026 and 01.01.2027
 * - Enterprise vertical data (Vehicles, SOAP Clinical Records, Waivers, Tickets, Memberships, Packages, Orders, Gift Cards)
 * - Master Database registration in ea_tenants
 */

ini_set('memory_limit', '1024M');
set_time_limit(0);

$masterHost = '127.0.0.1';
$masterPort = 3307;
$masterUser = 'ki_booki_dev_master';
$masterPass = 'booki_dev_pass_7f3k9q2m';
$masterDb   = 'ki_booki_dev_master';

$tenantMasterKeyB64 = getenv('TENANT_MASTER_KEY') ?: 'l5KoEF+jN5kfpJYkVO7bnj/RhpS56iswh7pleHGOpcw=';
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

// Turkish Names Pool
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
    'Başak', 'Burcu', 'Özge', 'Cansu', 'Eylül', 'Defne', 'Aylin', 'Selin', 'Dilara', 'Deniz',
    'Miray', 'Beren', 'Nehir', 'Melike', 'Buse', 'Aleyna', 'Rüya', 'Işıl', 'Gülşah', 'Nihan'
];

$lastNames = [
    'Yılmaz', 'Kaya', 'Demir', 'Çelik', 'Şahin', 'Yıldız', 'Yıldırım', 'Öztürk', 'Aydın', 'Özdemir',
    'Arslan', 'Doğan', 'Kılıç', 'Aslan', 'Çetin', 'Kara', 'Koç', 'Kurt', 'Özkan', 'Şimşek',
    'Polat', 'Korkmaz', 'Acar', 'Bulut', 'Yüksel', 'Yavuz', 'Bilgin', 'Gül', 'Avcı', 'Güler',
    'Aksoy', 'Eren', 'Güneş', 'Bozkurt', 'Coşkun', 'Keskin', 'Dağ', 'Taş', 'Koçak', 'Şen',
    'Uçar', 'Ateş', 'Tekin', 'Aktaş', 'Erdoğan', 'Kalkan', 'Güngör', 'Albayrak', 'Işık', 'Varol',
    'Yalçın', 'Sarı', 'Duran', 'Büyük', 'Küçük', 'Gündoğdu', 'Çakır', 'Duman', 'Sezer', 'Kahraman',
    'Özer', 'Erdem', 'Karataş', 'Şeker', 'Özmen', 'Turan', 'Akın', 'Taşkın', 'Özbek', 'Gündüz',
    'Bayrak', 'Demirci', 'Gök', 'Ergül', 'Kaplan', 'Yavuzer', 'Gökçe', 'Toprak', 'Sancar', 'Akyol',
    'Alkan', 'Altun', 'Başaran', 'Bayraktar', 'Ceylan', 'Çiftçi', 'Dalgıç', 'Engin', 'Gediz', 'Kalyoncu',
    'Karakaya', 'Karaman', 'Kayalar', 'Kocatürk', 'Malkoç', 'Okan', 'Pala', 'Sancaktar', 'Sevim', 'Soylu'
];

$niluferDistricts = [
    ['Özlüce Mah. Ahmet Taner Kışlalı Bulv. No:', '16120'],
    ['İhsaniye Mah. Fatih Sultan Mehmet Bulv. No:', '16130'],
    ['Görükle Mah. Atatürk Cad. No:', '16285'],
    ['Balat Mah. Bey Sok. No:', '16140'],
    ['Ataevler Mah. Ali Rıza Bey Cad. No:', '16140'],
    ['Fethiye Mah. Sanayi Cad. No:', '16140'],
    ['Beşevler Mah. Bilginler Cad. No:', '16110'],
    ['23 Nisan Mah. Mithat Paşa Cad. No:', '16120'],
    ['Altınşehir Mah. Ahmet Cevdet Paşa Cad. No:', '16120'],
    ['Ertuğrul Mah. Uğur Mumcu Bulv. No:', '16120'],
    ['Barış Mah. İkbal Sok. No:', '16140'],
    ['Odunluk Mah. Akpınar Cad. No:', '16110'],
    ['Çamlıca Mah. Eğitimciler Cad. No:', '16110'],
    ['Konak Mah. Lefkoşe Cad. No:', '16110'],
    ['Üçevler Mah. Dumlupınar Cad. No:', '16120'],
    ['Yüzüncüyıl Mah. Prof. Dr. Erdal İnönü Cad. No:', '16120']
];

// Scenario Notes Generator Pool by Category
function get_scenario_notes(string $category, string $bType, int $idx): string {
    $c4_health = [
        "L4-L5 disk hernisi (bel fıtığı), sol bacağa vuran radiküler ağrı; traksiyon ve manuel terapi takibi.",
        "Tip 2 Diyabet ve insülin direnci (HOMA-IR 3.8); eliminasyon diyeti ve glikoz regülasyonu.",
        "Kronik servikal gerilim baş ağrısı ve migren; tetik nokta kuru iğneleme protokolü.",
        "Bruksizm (şiddetli gece diş sıkma); masseter botoksu ve oklüzal gece plağı kontrolü.",
        "Sağ alt 6 numara diş kırığı; zirkonyum kaplama ve endodontik kanal tedavisi konsültasyonu.",
        "Skolyoz Cobb açısı 14 derece; 3 boyutlu Schroth egzersiz seansı planlandı.",
        "Alerjik rinit ve polen hassasiyeti; immünoterapi ve antihistaminik tedavi takibi.",
        "İş kaynaklı tükenmişlik ve yaygın anksiyete; Bilişsel Davranışçı Terapi (BDT) 6. seansı.",
        "Hipertansiyon Evre 1 ve çarpıntı şikayeti; Holter takibi ve kardiyoloji konsültasyonu.",
        "Diz menisküs Evre 2 yırtık; PRP enjeksiyonu ve kuadriseps kuvvetlendirme programı.",
        "Akne vulgaris kistik tip; topikal retinoid ve medikal cilt temizleme protokolü.",
        "Panik atak ve agorafobi semptomları; nefes biyogeribildirim ve EMDR terapisi.",
        "Plak tipi sedef (psoriasis) diz ve dirsek lezyonları; fototerapi değerlendirmesi.",
        "Artikülasyon r ve k harfleri fonolojik bozukluk; haftalık dil ve konuşma terapisi.",
        "Duyu bütünleme ve hiperaktivite; ince motor ve vestibüler uyarım seansı.",
        "Golden Retriever 3 yaş; kalça displazisi kontrolü ve yıllık karma aşı takvimi.",
        "Miyopi -4.25 astigmat -1.50; Wavefront lazer cerrahisi uygunluk kornea topografisi.",
        "PCOS kaynaklı metabolik yavaşlama; düşük karbonhidratlı anti-inflamatuar beslenme programı.",
        "Fibromiyalji yaygın kas ağrısı; medikal masaj ve osteopatik kranyosakral terapi.",
        "Karpal tünel sendromu sağ el bileği; gece ateli ve el ergoterapisi egzersizleri."
    ];

    $c5_auto = [
        "16 BKI 204 - BMW 320i Sedan (2022) - Tam kaput ve ön çamurluk Stek DynoShield PPF kaplama.",
        "16 BUR 91 - Mercedes C200d Sedan (2021) - 60.000 km periyodik bakım, fren balataları ve şanzıman yağı.",
        "16 NLF 45 - Volkswagen Tiguan SUV (2023) - 3 aşamalı pasta cila + Gyeon 5 yıllık seramik kaplama.",
        "16 TED 88 - Audi A4 Sedan (2020) - 101 nokta DVI ekspertiz raporu ve dyno motor güç testi.",
        "16 YLM 72 - Volvo XC60 SUV (2022) - Orijinal cam filmi ve detaylı iç ozon sterilizasyonu.",
        "16 KYA 34 - Renault Megane (2019) - 4 mevsim Michelin lastik değişimi ve 3D rot-balans ayarı.",
        "16 CLK 12 - Porsche Macan SUV (2023) - Full şeffaf TPU PPF kaplama ve deri koruma kalkanı.",
        "16 DFR 55 - Ford Focus Hatchback (2021) - Ağır bakım, triger seti değişimi ve antifriz yenileme.",
        "16 SKN 83 - Honda Civic Sedan (2022) - Klima gazı dolumu ve ozon klima kanalı dezenfeksiyonu.",
        "16 HKN 06 - Tesla Model Y EV (2023) - Batarya sağlık testi (SoH %98) ve yüksek voltaj kontrolü.",
        "16 BRK 44 - Peugeot 3008 SUV (2022) - Boyasız göçük düzeltme (PDR) ve seramik cila tazeleme.",
        "16 ZNP 99 - Hyundai Tucson SUV (2023) - Detaylı koltuk leke çıkarma, tavan yıkama ve motor koruma.",
        "16 EMR 27 - Cupra Formentor (2023) - Stage 1 yazılım güncellemesi ve spor egzoz montaj kontrolü.",
        "16 CAN 63 - Toyota Corolla Sedan (2021) - Ön takım kontrolü, amortisör takozları ve rotil değişimi.",
        "16 MLK 18 - Fiat Egea Sedan (2020) - Periyodik yağ filtre bakımı ve fren disk tornalama."
    ];

    $c3_sports = [
        "Lomber disk herniasyonu başlangıcı; bel bölgesini koruyan modifiye klinik reformer pilates.",
        "Aylık sınırsız fitness üyeliği; hedef: 3 ayda 6 kg yağ kaybı ve kardiyovasküler dayanıklılık.",
        "Tenis kortu düzenli çarşamba akşamı 20:00 kiralama; kordaj tansiyon ayarı 24 kg.",
        "CrossFit WOD grubu katılımcısı; omuz sıkışması nedeniyle kettlebell clean modifikasyonu.",
        "Aletli pilates 10 derslik paket (4. ders); skolyoz dengeleme ve core stabilizasyon.",
        "Halı saha kaptanı; haftalık periyodik 7v7 maç rezervasyonu ve maç video kaydı.",
        "Birebir PT seansı; hipertrofi programı, bench press ve squat form analizi.",
        "Padel kortu 4 kişilik maç rezervasyonu; raket kiralama ve yeni top talebi.",
        "Boks özel ders; sol kroşe kombinasyonu ve gölge boksu kondisyon antrenmanı.",
        "Vinyasa yoga seansı; esneklik geliştirme ve nefes meditasyon odaklı çalışma."
    ];

    $c2_food = [
        "Ciddi yer fıstığı ve kabuklu deniz ürünleri alerjisi; mutfak şefine özel bilgilendirme yapıldı.",
        "Evlilik yıldönümü kutlaması; teras cam kenarı romantik masa, pasta ve mum servisi.",
        "Şirket yönetim kurulu akşam yemeği; 6 kişilik VIP loca, sessiz servis protokolü.",
        "Glutensiz ve laktozsuz menü tercihi; şefin tadım tabağı özel hazırlanacak.",
        "Doğum günü sürprizi rezervasyonu; 8 kişilik bahçe masası ve fotoğraf köşesi talebi.",
        "Taş fırın Napoliten pizza ve organik şarap tadımı; fırın manzaralı masa tercihi.",
        "Omakase sushi tadım menüsü; taze somon ve ton balığı sashimi tercihi.",
        "Geleneksel serpme köy kahvaltısı; 4 yetişkin 2 çocuk, mama sandalyesi talebi."
    ];

    $c1_beauty = [
        "Hassas ve kılcal damarlı cilt; organik aloe vera içerikli yatıştırıcı bakım kürü.",
        "Tüm vücut buz başlıklı lazer epilasyon 8 seanslık paket (4. seans randevusu).",
        "Bebek sarısı sombre ve Olaplex saç koruma; amonyaksız vegan boya tercihi.",
        "Medikal manikür ve kalıcı oje; tırnak kırılmalarına karşı güçlendirici keratin tabaka.",
        "Klasik İsveç masajı; lavanta aromaterapi yağı tercihi, omuz ve kürek kemiği kulunç açma.",
        "Microblading kıl tekniği kaş tasarımı; doğal açık kahve pigment tonu onaylandı.",
        "İpek kirpik 3D Russian Volume uygulaması; C kıvrım 11mm ipek kirpik tercihi.",
        "Gelin saçı ve porselen makyaj provası; düğün günü erken saat hazırlık paketi."
    ];

    $c6_exp = [
        "Piramit Laneti Kaçış Odası; 5 kişilik arkadaş grubu, zor seviye ipucu istemiyor.",
        "VR 4 kişilik çok oyunculu uzay simülatörü; feragatname dijital onaylandı.",
        "Seramik Çömlek Atölyesi; 2 kişilik çift tornalama seansı, sırlama dahil.",
        "Tuval üzerine akrilik workshop; soyut manzara çalışması ve şarap ikramı.",
        "Paintball senaryo maçı; 10 kişilik iki takım, 200'er boya topu paketi.",
        "Go-Kart 10 dakikalık grand prix sıralama turları; kask ve tulum teslim edildi."
    ];

    $c7_hotel = [
        "Jakuzili balayı bungalovu; şömine odun takviyesi ve late check-out 14:00 talebi.",
        "Uludağ eteklerinde göl manzaralı dağ evi; organik serpme köy kahvaltısı dahil.",
        "Executive süit oda; iş seyahati için sessiz üst kat ve ütü masası talebi.",
        "Lüks kubbe glamping dome çadır; doğa içinde yıldız gözlem terası konaklaması."
    ];

    $c8_edu = [
        "IELTS Akademik Hazırlık; hedef skor 7.5, haftada 2 gün 90 dk birebir ders.",
        "Klasik Piyano başlangıç; Czerny etütleri ve solfej eğitimi (10 seanslık paket).",
        "Python ile Yapay Zeka ve Veri Analitiği; proje mentorluk seansı.",
        "B Sınıfı manuel vites direksiyon eğitimi; akan trafik ve paralel park çalışması."
    ];

    $c9_prof = [
        "Marka patent tescili ve şirket birleşmesi hukuki danışmanlık görüşmesi.",
        "2026 yılı KDV iadesi raporlaması ve tam tasdik mali müşavirlik denetimi.",
        "Özlüce 450 m² modern villa projesi; ruhsat projesi ve 3D iç mimari render teslimi.",
        "Kurumsal şirket profil çekimi ve LinkedIn profesyonel portre oturumu."
    ];

    if ($category === "Sağlık / Uzmanlık") return $c4_health[$idx % count($c4_health)];
    if ($category === "Otomotiv") return $c5_auto[$idx % count($c5_auto)];
    if ($category === "Spor / Fitness") return $c3_sports[$idx % count($c3_sports)];
    if ($category === "Restoran / Yeme-İçme") return $c2_food[$idx % count($c2_food)];
    if ($category === "Güzellik / Kişisel Bakım") return $c1_beauty[$idx % count($c1_beauty)];
    if ($category === "Deneyim / Eğlence / Aktivite") return $c6_exp[$idx % count($c6_exp)];
    if ($category === "Konaklama") return $c7_hotel[$idx % count($c7_hotel)];
    if ($category === "Eğitim / Kurs / Birebir Ders") return $c8_edu[$idx % count($c8_edu)];
    return $c9_prof[$idx % count($c9_prof)];
}

// Master Database connection
$masterPdo = new PDO("mysql:host={$masterHost};port={$masterPort};dbname={$masterDb};charset=utf8mb4", $masterUser, $masterPass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// Prepare template SQL
$templateSql = file_get_contents('/tmp/ki_tenant_template.sql');

$allBusinesses = json_decode(file_get_contents('/tmp/businesses_161.json'), true);

// Worker mode arguments: php seed_161_demo_tenants.php --worker=0 --total-workers=4
$options = getopt('', ['worker:', 'total-workers:', 'concurrency:']);
$workerId = isset($options['worker']) ? (int)$options['worker'] : null;
$totalWorkers = isset($options['total-workers']) ? (int)$options['total-workers'] : 1;

if ($workerId === null) {
    $numForks = isset($options['concurrency']) ? (int)$options['concurrency'] : 6;
    echo "========================================================\\n";
    echo "🚀 BooKi 161 Demo Businesses Automated Seeder\\n";
    echo "Total businesses: " . count($allBusinesses) . "\\n";
    echo "Forking {$numForks} parallel workers for maximum speed...\\n";
    echo "========================================================\\n";

    $pids = [];
    for ($w = 0; $w < $numForks; $w++) {
        $pid = pcntl_fork();
        if ($pid == -1) {
            die("Could not fork worker $w\\n");
        } else if ($pid) {
            $pids[$w] = $pid;
        } else {
            run_worker($w, $numForks, $allBusinesses, $masterHost, $masterPort, $masterUser, $masterPass, $masterDb, $tenantMasterKeyRaw, $templateSql, $firstNamesMale, $firstNamesFemale, $lastNames, $niluferDistricts);
            exit(0);
        }
    }

    foreach ($pids as $w => $pid) {
        pcntl_waitpid($pid, $status);
        echo "✓ Worker {$w} (PID {$pid}) completed.\\n";
    }

    echo "========================================================\\n";
    echo "🎉 ALL 161 DEMO BUSINESSES SUCCESSFULLY SEEDED!\\n";
    echo "All tenants registered in master ea_tenants.\\n";
    echo "Reservations visible between 01.09.2026 and 01.01.2027.\\n";
    echo "========================================================\\n";
    exit(0);
} else {
    run_worker($workerId, $totalWorkers, $allBusinesses, $masterHost, $masterPort, $masterUser, $masterPass, $masterDb, $tenantMasterKeyRaw, $templateSql, $firstNamesMale, $firstNamesFemale, $lastNames, $niluferDistricts);
    exit(0);
}

function run_worker($workerId, $totalWorkers, $allBusinesses, $masterHost, $masterPort, $masterUser, $masterPass, $masterDb, $tenantMasterKeyRaw, $templateSql, $firstNamesMale, $firstNamesFemale, $lastNames, $niluferDistricts) {
    $masterPdo = new PDO("mysql:host={$masterHost};port={$masterPort};dbname={$masterDb};charset=utf8mb4", $masterUser, $masterPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    foreach ($allBusinesses as $index => $b) {
        if ($index % $totalWorkers !== $workerId) {
            continue;
        }

        $bName = $b['company_name'];
        $bType = $b['business_type'];
        $cat   = $b['category'];
        $sub   = $b['subdomain'];
        $dbName = $b['db_name'];
        $desc  = $b['description'];
        $stPrefix = $b['station_prefix'];
        $staffTitle = $b['staff_title'];
        $codePrefix = $b['code_prefix'];

        $t0 = microtime(true);

        // 1. Create DB and load template schema & base data
        $masterPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $tenantPdo = new PDO("mysql:host={$masterHost};port={$masterPort};dbname={$dbName};charset=utf8mb4", $masterUser, $masterPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $tenantPdo->exec("SET FOREIGN_KEY_CHECKS=0");
        $tenantPdo->exec($templateSql);

        // 2. Generate per-tenant keys
        $piiEncKeyB64 = base64_encode(random_bytes(32));
        $piiHashKeyB64 = base64_encode(random_bytes(32));
        $piiEncKeyRaw = base64_decode($piiEncKeyB64);
        $piiHashKeyRaw = base64_decode($piiHashKeyB64);

        // 3. Configure ea_settings
        $dist = $niluferDistricts[$index % count($niluferDistricts)];
        $address = $dist[0] . rand(1, 99) . ", Nilüfer / Bursa";
        $phone = "+90 224 451 " . sprintf("%04d", rand(1000, 9999));
        $email = "info@{$sub}.kibusiness.co";

        $settings = [
            'company_name' => $bName,
            'company_email' => $email,
            'company_link' => 'https://devbookiapp.kibusiness.co',
            'company_phone' => $phone,
            'company_address' => $address,
            'company_logo' => 'assets/img/logo.png',
            'business_type' => $bType,
            'industry_code' => $codePrefix,
            'onboarding_completed' => '1',
            'default_timezone' => 'Europe/Istanbul',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'first_weekday' => 'monday',
            'require_phone_number' => '1',
            'currency' => 'TRY',
            'currency_symbol' => '₺',
            'future_booking_limit' => '120',
            'book_advance_timeout' => '60',
            'slot_interval' => '15'
        ];

        $stmtDel = $tenantPdo->prepare("DELETE FROM ea_settings WHERE name = :name");
        $stmtSet = $tenantPdo->prepare("INSERT INTO ea_settings (name, value) VALUES (:name, :value)");
        foreach ($settings as $k => $v) {
            $stmtDel->execute(['name' => $k]);
            $stmtSet->execute(['name' => $k, 'value' => $v]);
        }

        // 4. Admin User
        $adminEmail = "admin@{$sub}.kibusiness.co";
        $adminPassHash = password_hash('BooKiDemo2026!', PASSWORD_DEFAULT);
        $tenantPdo->exec("DELETE FROM ea_users WHERE id_roles = 1");
        $stmtAdmin = $tenantPdo->prepare("
            INSERT INTO ea_users (first_name, last_name, email, email_hash, phone_number, phone_number_hash, address, city, zip_code, id_roles)
            VALUES ('BooKi', 'Yönetici', :email, :email_h, :phone, :phone_h, :addr, 'Bursa', '16120', 1)
        ");
        $stmtAdmin->execute([
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

        // 5. Providers / Staff (3-4 specialists)
        $numStaff = ($index % 2 === 0) ? 3 : 4;
        $providerIds = [];
        $workingPlanJson = json_encode([
            'monday' => ['start' => '09:00', 'end' => '19:00', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
            'tuesday' => ['start' => '09:00', 'end' => '19:00', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
            'wednesday' => ['start' => '09:00', 'end' => '19:00', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
            'thursday' => ['start' => '09:00', 'end' => '19:00', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
            'friday' => ['start' => '09:00', 'end' => '19:00', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
            'saturday' => ['start' => '09:00', 'end' => '18:00', 'breaks' => [['start' => '13:00', 'end' => '14:00']]],
            'sunday' => null
        ]);

        for ($stf = 1; $stf <= $numStaff; $stf++) {
            $isFemale = ($stf % 2 === 1);
            $fn = $isFemale ? $firstNamesFemale[($index * 3 + $stf) % count($firstNamesFemale)] : $firstNamesMale[($index * 3 + $stf) % count($firstNamesMale)];
            $ln = $lastNames[($index * 2 + $stf) % count($lastNames)];
            $pEmail = "staff{$stf}@{$sub}.kibusiness.co";
            $pPhone = "+90 5" . sprintf("%02d", rand(30, 55)) . " " . sprintf("%03d", rand(100, 999)) . " " . sprintf("%02d", rand(10, 99)) . " " . sprintf("%02d", rand(10, 99));

            $stmtProv = $tenantPdo->prepare("
                INSERT INTO ea_users (first_name, last_name, email, email_hash, phone_number, phone_number_hash, address, city, notes, id_roles)
                VALUES (:fn, :ln, :email, :email_h, :phone, :phone_h, :addr, 'Bursa', :notes, 2)
            ");
            $stmtProv->execute([
                'fn' => $fn,
                'ln' => $ln,
                'email' => sf_encrypt($pEmail, $piiEncKeyRaw),
                'email_h' => sf_hash($pEmail, $piiHashKeyRaw),
                'phone' => sf_encrypt($pPhone, $piiEncKeyRaw),
                'phone_h' => sf_hash($pPhone, $piiHashKeyRaw),
                'addr' => sf_encrypt($address, $piiEncKeyRaw),
                'notes' => sf_encrypt("{$staffTitle} ({$fn} {$ln})", $piiEncKeyRaw)
            ]);
            $pId = $tenantPdo->lastInsertId();
            $providerIds[] = $pId;

            $tenantPdo->prepare("
                INSERT INTO ea_user_settings (id_users, username, password, working_plan, notifications, calendar_view)
                VALUES (:uid, :uname, :pass, :wp, 1, 0)
            ")->execute([
                'uid' => $pId,
                'uname' => "staff_{$stf}_{$sub}",
                'pass' => $adminPassHash,
                'wp' => $workingPlanJson
            ]);
        }

        // 6. Stations (3-5 stations)
        $numStations = 4;
        $stationIds = [];
        for ($stn = 1; $stn <= $numStations; $stn++) {
            $stationName = "{$stPrefix} {$stn}";
            $tenantPdo->prepare("INSERT INTO ea_stations (name, notes, is_active) VALUES (:name, :notes, 1)")
                ->execute(['name' => $stationName, 'notes' => "{$bType} {$stPrefix}"]);
            $stationIds[] = $tenantPdo->lastInsertId();
        }

        // 7. Categories & Services
        $tenantPdo->prepare("INSERT INTO ea_service_categories (name, description) VALUES (:n1, 'Standart & Temel Hizmetler')")
            ->execute(['n1' => "{$bType} Hizmetleri"]);
        $cat1Id = $tenantPdo->lastInsertId();

        $tenantPdo->prepare("INSERT INTO ea_service_categories (name, description) VALUES (:n2, 'Özel Seans & Paket Hizmetler')")
            ->execute(['n2' => "Özel Paketler & VIP"]);
        $cat2Id = $tenantPdo->lastInsertId();

        $servicesConfig = [
            ['name' => "Standart {$bType} Seansı", 'duration' => 45, 'price' => 750, 'cat' => $cat1Id, 'att' => 1],
            ['name' => "Kapsamlı {$bType} Hizmeti", 'duration' => 60, 'price' => 1500, 'cat' => $cat1Id, 'att' => 1],
            ['name' => "Özel VIP {$bType} Paketi", 'duration' => 90, 'price' => 2800, 'cat' => $cat2Id, 'att' => 1],
            ['name' => "10 Seanslık {$bType} Paketi", 'duration' => 60, 'price' => 12000, 'cat' => $cat2Id, 'att' => 1],
        ];

        if ($cat === "Spor / Fitness") {
            $servicesConfig[0]['att'] = 12;
            $servicesConfig[0]['name'] = "Grup Reformer / Mat Dersi (12 Kişilik)";
            $servicesConfig[1]['name'] = "Birebir Özel Antrenman (PT)";
            $servicesConfig[2]['name'] = "Aylık Sınırsız Üyelik Seansı";
            $servicesConfig[3]['name'] = "10 Derslik Paket Seansı";
        } elseif ($cat === "Otomotiv") {
            $servicesConfig[0]['name'] = "Periyodik Bakım & Kontrol";
            $servicesConfig[1]['name'] = "Full Seramik Kaplama & Boya Düzeltme";
            $servicesConfig[1]['price'] = 14500;
            $servicesConfig[1]['duration'] = 180;
            $servicesConfig[2]['name'] = "Şeffaf TPU PPF Kaplama";
            $servicesConfig[2]['price'] = 22000;
            $servicesConfig[2]['duration'] = 240;
            $servicesConfig[3]['name'] = "101 Nokta DVI Ekspertiz Raporu";
            $servicesConfig[3]['price'] = 2500;
        } elseif ($cat === "Restoran / Yeme-İçme") {
            $servicesConfig[0]['name'] = "Öğle / Akşam Masa Rezervasyonu";
            $servicesConfig[0]['duration'] = 90;
            $servicesConfig[1]['name'] = "Şef Tadım Menüsü & Eşleşme";
            $servicesConfig[1]['price'] = 3200;
            $servicesConfig[1]['duration'] = 120;
            $servicesConfig[2]['name'] = "VIP Loca / Özel Etkinlik Masa";
            $servicesConfig[2]['price'] = 5000;
            $servicesConfig[3]['name'] = "Hafta Sonu Gurme Brunch Rezervasyonu";
            $servicesConfig[3]['price'] = 950;
        }

        $serviceIds = [];
        $stmtSvc = $tenantPdo->prepare("
            INSERT INTO ea_services (name, duration, price, currency, description, color, slot_interval, attendants_number, id_service_categories)
            VALUES (:name, :dur, :pr, 'TRY', :desc, '#3b82f6', 15, :att, :cat)
        ");

        foreach ($servicesConfig as $sc) {
            $stmtSvc->execute([
                'name' => $sc['name'],
                'dur' => $sc['duration'],
                'pr' => $sc['price'],
                'desc' => "{$bType} kapsamında profesyonel hizmet.",
                'att' => $sc['att'],
                'cat' => $sc['cat']
            ]);
            $sId = $tenantPdo->lastInsertId();
            $serviceIds[] = $sId;

            foreach ($providerIds as $pId) {
                $tenantPdo->exec("INSERT IGNORE INTO ea_services_providers (id_services, id_users) VALUES ({$sId}, {$pId})");
            }
            foreach ($stationIds as $stId) {
                $tenantPdo->exec("INSERT IGNORE INTO ea_stations_services (id_stations, id_services) VALUES ({$stId}, {$sId})");
            }
        }

        foreach ($providerIds as $pId) {
            foreach ($stationIds as $stId) {
                $tenantPdo->exec("INSERT IGNORE INTO ea_stations_providers (id_stations, id_users) VALUES ({$stId}, {$pId})");
            }
        }

        // 8. Exactly 40 Customers per business
        $customerIds = [];
        $stmtCust = $tenantPdo->prepare("
            INSERT INTO ea_users (first_name, last_name, email, email_hash, phone_number, phone_number_hash, address, city, zip_code, notes, id_roles)
            VALUES (:fn, :ln, :email, :email_h, :phone, :phone_h, :addr, 'Bursa', :zip, :notes, 3)
        ");

        for ($c = 1; $c <= 40; $c++) {
            $isFemale = ($c % 2 === 1);
            $fn = $isFemale ? $firstNamesFemale[($index * 7 + $c) % count($firstNamesFemale)] : $firstNamesMale[($index * 7 + $c) % count($firstNamesMale)];
            $ln = $lastNames[($index * 5 + $c) % count($lastNames)];
            $cDist = $niluferDistricts[($index + $c) % count($niluferDistricts)];
            $cAddr = $cDist[0] . rand(1, 140) . ", Nilüfer / Bursa";
            $cZip = $cDist[1];
            $cPhone = "+90 5" . sprintf("%02d", rand(30, 55)) . " " . sprintf("%03d", rand(100, 999)) . " " . sprintf("%02d", rand(10, 99)) . " " . sprintf("%02d", rand(10, 99));
            $cEmail = strtolower($fn . "." . $ln . $c . "@kibusiness.co");
            $cNotes = get_scenario_notes($cat, $bType, $c);

            $stmtCust->execute([
                'fn' => $fn,
                'ln' => $ln,
                'email' => sf_encrypt($cEmail, $piiEncKeyRaw),
                'email_h' => sf_hash($cEmail, $piiHashKeyRaw),
                'phone' => sf_encrypt($cPhone, $piiEncKeyRaw),
                'phone_h' => sf_hash($cPhone, $piiHashKeyRaw),
                'addr' => sf_encrypt($cAddr, $piiEncKeyRaw),
                'zip' => sf_encrypt($cZip, $piiEncKeyRaw),
                'notes' => sf_encrypt($cNotes, $piiEncKeyRaw)
            ]);
            $customerIds[] = $tenantPdo->lastInsertId();
        }

        // 9. Appointments (01.09.2026 to 01.01.2027)
        $stmtAppt = $tenantPdo->prepare("
            INSERT INTO ea_appointments (
                start_datetime, end_datetime, book_datetime, is_unavailability, notes, status,
                payment_status, payment_method, payment_amount, deposit_amount, deposit_status, deposit_paid_at,
                id_users_provider, id_users_customer, id_services, id_stations
            ) VALUES (
                :start_dt, :end_dt, :book_dt, 0, :notes, :status,
                :pay_st, :pay_m, :pay_amt, :dep_amt, :dep_st, :dep_paid_at,
                :p_id, :c_id, :s_id, :stn_id
            )
        ");

        $startDateTs = strtotime('2026-09-01 09:00:00');
        $endDateTs   = strtotime('2027-01-01 18:00:00');
        $simTodayTs  = strtotime('2026-09-25 12:00:00');

        $numAppointments = 36;
        $appointmentIds = [];
        for ($a = 0; $a < $numAppointments; $a++) {
            $fraction = $a / ($numAppointments - 1);
            $appTs = $startDateTs + (int)($fraction * ($endDateTs - $startDateTs));
            $jitterDays = (($a * 7) % 5) - 2;
            $appTs += ($jitterDays * 86400);
            if ($appTs < $startDateTs) $appTs = $startDateTs;
            if ($appTs > $endDateTs) $appTs = $endDateTs;

            $hour = 9 + (($a * 3) % 9);
            $minute = (($a * 15) % 4) * 15;
            $appDateStr = date('Y-m-d', $appTs);
            $startDt = "{$appDateStr} " . sprintf("%02d:%02d:00", $hour, $minute);

            $svcIdx = $a % count($serviceIds);
            $svcId = $serviceIds[$svcIdx];
            $svcDuration = $servicesConfig[$svcIdx]['duration'];
            $svcPrice = $servicesConfig[$svcIdx]['price'];

            $endDt = date('Y-m-d H:i:s', strtotime("{$startDt} +{$svcDuration} minutes"));
            $bookDt = date('Y-m-d H:i:s', strtotime("{$startDt} -".rand(2, 8)." days"));

            $provId = $providerIds[$a % count($providerIds)];
            $custId = $customerIds[$a % count($customerIds)];
            $stnId  = $stationIds[$a % count($stationIds)];

            $isPast = (strtotime($startDt) < $simTodayTs);
            $isToday = ($appDateStr === '2026-09-25');

            $status = $isPast ? 'completed' : ($isToday ? 'confirmed' : ($a % 5 === 0 ? 'pending' : 'confirmed'));
            $payStatus = $isPast ? 'paid' : ($isToday ? 'paid' : ($a % 4 === 0 ? 'pending' : 'paid'));
            $payMethod = ($a % 2 === 0) ? 'credit_card' : 'cash';

            $hasDeposit = ($a % 3 === 0);
            $depAmount = $hasDeposit ? round($svcPrice * 0.25, 2) : 0.00;
            $depStatus = $hasDeposit ? ($isPast || $isToday || $a % 2 === 0 ? 'paid' : 'pending') : 'none';
            $depPaidAt = ($depStatus === 'paid') ? $bookDt : null;

            $apptNote = "{$bType} Rezervasyonu (" . ($isPast ? "Tamamlandı" : "Kayıtlı") . ")";

            $stmtAppt->execute([
                'start_dt' => $startDt,
                'end_dt' => $endDt,
                'book_dt' => $bookDt,
                'notes' => $apptNote,
                'status' => $status,
                'pay_st' => $payStatus,
                'pay_m' => $payMethod,
                'pay_amt' => $svcPrice,
                'dep_amt' => $depAmount,
                'dep_st' => $depStatus,
                'dep_paid_at' => $depPaidAt,
                'p_id' => $provId,
                'c_id' => $custId,
                's_id' => $svcId,
                'stn_id' => $stnId
            ]);
            $appointmentIds[] = (int)$tenantPdo->lastInsertId();
        }

        // 10. Vertical-Specific Extra Tables
        if ($cat === "Spor / Fitness") {
            $stmtPlan = $tenantPdo->prepare("
                INSERT INTO ea_membership_plans (name, id_services, billing_period, price, sessions_per_period, is_active, created_at, category)
                VALUES (:name, :sid, :period, :price, :sessions, 1, '2026-09-01 00:00:00', :cat)
            ");
            $stmtPlan->execute(['name' => 'Aylık Sınırsız Üyelik', 'sid' => $serviceIds[0], 'period' => 'monthly', 'price' => 3500.00, 'sessions' => null, 'cat' => 'Genel']);
            $plan1 = (int)$tenantPdo->lastInsertId();
            $stmtPlan->execute(['name' => '10 Derslik Reformer Paketi', 'sid' => $serviceIds[min(1, count($serviceIds)-1)], 'period' => 'quarterly', 'price' => 7500.00, 'sessions' => 10, 'cat' => 'Paket']);
            $plan2 = (int)$tenantPdo->lastInsertId();

            $stmtMem = $tenantPdo->prepare("
                INSERT INTO ea_customer_memberships (id_users_customer, id_membership_plans, status, current_period_start, current_period_end, sessions_used_this_period, auto_renew, qr_code_token, created_at)
                VALUES (:cid, :mpid, 'active', '2026-09-01 00:00:00', '2026-12-31 23:59:59', 8, 1, :qr, '2026-09-01 00:00:00')
            ");
            for ($cm = 0; $cm < 5; $cm++) {
                $stmtMem->execute([
                    'cid' => $customerIds[$cm],
                    'mpid' => ($cm % 2 === 0 ? $plan1 : $plan2),
                    'qr' => 'QR-' . strtoupper(bin2hex(random_bytes(8)))
                ]);
            }
        } elseif ($cat === "Otomotiv") {
            $stmtVeh = $tenantPdo->prepare("
                INSERT INTO ea_customer_vehicles (id_users_customer, plate_number, vin, brand, model, year, color, current_km, fuel_type, notes, created_at, updated_at)
                VALUES (:cid, :plate, :vin, :brand, :model, 2022, 'Füme', 45000, 'Benzin', 'Araç kartı ve servis geçmişi kayıtlı.', '2026-09-01 10:00:00', '2026-09-01 10:00:00')
            ");
            $stmtWo = $tenantPdo->prepare("
                INSERT INTO ea_work_orders (work_order_number, id_vehicles, id_appointments, id_users_technician, status, estimated_cost, final_cost, created_at, updated_at)
                VALUES (:wo_num, :vid, :aid, :tech_id, 'completed', 12500.00, 12500.00, '2026-09-10 09:00:00', '2026-09-10 17:00:00')
            ");

            for ($v = 0; $v < 10; $v++) {
                $vPlate = "16 BKI " . (100 + $v);
                $brands = ['BMW', 'Mercedes-Benz', 'Audi', 'Volkswagen', 'Volvo', 'Porsche', 'Tesla', 'Renault'];
                $models = ['320i', 'C200d', 'A4', 'Tiguan', 'XC60', 'Macan', 'Model Y', 'Megane'];
                $vBrand = $brands[$v % count($brands)];
                $vModel = $models[$v % count($models)];
                $vVin = "WBA" . strtoupper(bin2hex(random_bytes(7)));

                $stmtVeh->execute([
                    'cid' => $customerIds[$v],
                    'plate' => $vPlate,
                    'vin' => $vVin,
                    'brand' => $vBrand,
                    'model' => $vModel
                ]);
                $vId = (int)$tenantPdo->lastInsertId();

                $stmtWo->execute([
                    'wo_num' => "WO-2026-" . (1000 + $v),
                    'vid' => $vId,
                    'aid' => $appointmentIds[$v % count($appointmentIds)],
                    'tech_id' => $providerIds[$v % count($providerIds)]
                ]);
            }
        } elseif ($cat === "Sağlık / Uzmanlık") {
            $stmtClin = $tenantPdo->prepare("
                INSERT INTO ea_clinical_records (id_users_customer, id_appointments, id_users_provider, record_type, subjective, objective, assessment, plan, is_confidential, created_at, updated_at)
                VALUES (:cid, :aid, :pid, 'soap_note', 'Hasta şikayeti ve mevcut anamnez alındı.', 'Fiziksel muayene ve klinik testler yapıldı.', 'Klinik değerlendirme ve tanı konuldu.', 'Tedavi ve egzersiz protokolü reçete edildi.', 1, '2026-09-15 11:00:00', '2026-09-15 11:30:00')
            ");
            for ($cr = 0; $cr < 8; $cr++) {
                $stmtClin->execute([
                    'cid' => $customerIds[$cr],
                    'aid' => $appointmentIds[$cr % count($appointmentIds)],
                    'pid' => $providerIds[0]
                ]);
            }
        } elseif ($cat === "Güzellik / Kişisel Bakım") {
            $tenantPdo->prepare("
                INSERT INTO ea_gift_cards (code, initial_amount, current_balance, id_users_customer, recipient_name, recipient_email, status, expires_at, created_at, updated_at)
                VALUES 
                ('BKI-GIFT-1000', 1000.00, 1000.00, :c1, 'Müşteri Hediye 1', 'hediye1@kibusiness.co', 'active', '2027-06-30', '2026-09-01 00:00:00', '2026-09-01 00:00:00'),
                ('BKI-GIFT-2500', 2500.00, 1850.00, :c2, 'Müşteri Hediye 2', 'hediye2@kibusiness.co', 'active', '2027-12-31', '2026-09-01 00:00:00', '2026-09-01 00:00:00')
            ")->execute(['c1' => $customerIds[0], 'c2' => $customerIds[1]]);

            $stmtRev = $tenantPdo->prepare("
                INSERT INTO ea_reviews (appointment_id, id_users_customer, id_users_provider, token, customer_name, rating, comment, status, created_at, submitted_at)
                VALUES (:aid, :cid, :pid, :tok, :cname, 5, 'Hizmet kalitesi ve uzman ilgisinden çok memnun kaldım, herkese tavsiye ederim.', 'published', '2026-09-20 14:00:00', '2026-09-20 15:00:00')
            ");
            for ($r = 0; $r < 4; $r++) {
                $stmtRev->execute([
                    'aid' => $appointmentIds[$r],
                    'cid' => $customerIds[$r],
                    'pid' => $providerIds[0],
                    'tok' => bin2hex(random_bytes(16)),
                    'cname' => 'Değerli Müşteri'
                ]);
            }

            $stmtComm = $tenantPdo->prepare("
                INSERT INTO ea_staff_commissions (id_users_staff, id_appointments, id_services, sale_amount, commission_rate, commission_amount, tip_amount, status, created_at)
                VALUES (:pid, :aid, :sid, 1500.00, 15.00, 225.00, 50.00, 'pending', '2026-09-20 16:00:00')
            ");
            for ($cm = 0; $cm < 3; $cm++) {
                $stmtComm->execute([
                    'pid' => $providerIds[$cm % count($providerIds)],
                    'aid' => $appointmentIds[$cm],
                    'sid' => $serviceIds[0]
                ]);
            }
        } elseif ($cat === "Deneyim / Eğlence / Aktivite") {
            $tenantPdo->exec("
                INSERT INTO ea_digital_waivers (title, content_html, is_mandatory, applicable_service_ids, created_at, updated_at)
                VALUES ('Aktivite Güvenlik ve Katılım Feragatnamesi', '<p>Katılımcı oyun kurallarını ve güvenlik talimatlarını kabul eder.</p>', 1, '[]', '2026-09-01 00:00:00', '2026-09-01 00:00:00')
            ");
        }

        $tenantPdo->exec("SET FOREIGN_KEY_CHECKS=1");

        // 11. Register / Update Tenant in Master DB
        $stmtMaster = $masterPdo->prepare("
            INSERT INTO ea_tenants (
                subdomain, db_host, db_name, db_username, db_password,
                pii_enc_key, pii_hash_key, deployment_type, status,
                created_at, updated_at, plan, trial_ends_at, license_expires_at,
                marketplace_opt_in, category, city, cover_image_url, short_description
            ) VALUES (
                :sub, '127.0.0.1:3307', :db, :user, :pass,
                :enc_k, :hash_k, 'cloud', 'active',
                NOW(), NOW(), 'elite', '2028-01-01 00:00:00', '2028-01-01 00:00:00',
                1, :cat, 'Bursa', '/assets/img/logo.png', :s_desc
            ) ON DUPLICATE KEY UPDATE
                db_host = '127.0.0.1:3307',
                db_name = VALUES(db_name),
                db_username = VALUES(db_username),
                db_password = VALUES(db_password),
                pii_enc_key = VALUES(pii_enc_key),
                pii_hash_key = VALUES(pii_hash_key),
                status = 'active',
                category = VALUES(category),
                city = 'Bursa',
                cover_image_url = '/assets/img/logo.png',
                short_description = VALUES(short_description),
                marketplace_opt_in = 1,
                updated_at = NOW()
        ");

        $stmtMaster->execute([
            'sub' => $sub,
            'db' => $dbName,
            'user' => $masterUser,
            'pass' => tm_encrypt($masterPass, $tenantMasterKeyRaw),
            'enc_k' => tm_encrypt($piiEncKeyB64, $tenantMasterKeyRaw),
            'hash_k' => tm_encrypt($piiHashKeyB64, $tenantMasterKeyRaw),
            'cat' => $cat,
            's_desc' => "BooKi {$bName} — Bursa Nilüfer'de {$desc}. 40 kayıtlı müşteri ve aktif randevu takvimi."
        ]);

        $elapsed = round(microtime(true) - $t0, 2);
        echo "[Worker {$workerId}] ✓ [{$index}/161] Seeded: {$bName} ({$sub}) -> {$dbName} ({$elapsed}s)\\n";
    }
}
"""

with open("application/scripts/seed_161_demo_tenants.php", "w", encoding="utf-8") as f:
    f.write(php_content)

print("File application/scripts/seed_161_demo_tenants.php written successfully.")

