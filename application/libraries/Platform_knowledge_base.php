<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * BooKi Platform Knowledge Base & Sales Intelligence Library.
 *
 * Dedicated to the Platform AI Assistant (Superadmin CRM & Platform WhatsApp).
 * Contains:
 * 1. 9 Industry Verticals with Deep Competitor Analysis & Devaluation/Refutation Playbooks
 * 2. 21 Core Universal BooKi Competitive Advantages
 * 3. 2026 Transparent Pricing Structure & TCO/ROI Models
 * 4. Ki Business Sales Methodology Reference Guide (Karar Matrisi & Teknik Uygulama: SA-01..SA-08, TI-01..TI-04, TO-01..TO-03, OC-01..OC-05, SM-01..SM-02, EP-01..EP-02, HO-01..HO-03)
 * 5. Dynamic Codebase Feature Scanner that auto-updates on git/github changes
 *
 * @package     Libraries
 * @subpackage  Platform
 */
class Platform_knowledge_base
{
    /** @var CI_Controller|object */
    protected $CI;
    protected string $kb_dir;
    protected string $kb_cache_file;
    protected string $kb_markdown_file;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->kb_dir = FCPATH . 'storage/kb';
        if (!is_dir($this->kb_dir)) {
            @mkdir($this->kb_dir, 0755, true);
        }
        $this->kb_cache_file = $this->kb_dir . '/codebase_features.json';
        $this->kb_markdown_file = $this->kb_dir . '/codebase_features.md';
    }

    /**
     * Get pricing structure with ROI framing.
     */
    public function get_pricing(): array
    {
        return [
            'currency' => 'TL',
            'trial' => [
                'duration_days' => 10,
                'credit_card_required' => false,
                'commitment' => false,
                'features' => 'Tüm modüller ve AI asistan dahil 10 gün ücretsiz kullanım.',
                'url' => 'https://bookiapp.kibusiness.co',
            ],
            'plans' => [
                'starter' => [
                    'name' => 'Başlangıç (Starter)',
                    'monthly_price' => 1250,
                    'annual_monthly_equivalent' => 1000,
                    'annual_total_price' => 12000,
                    'annual_saving' => 'Yıllık peşin ödemede 3.000 TL tasarruf (Ayda 1.000 TL)',
                    'target' => 'Tek şubeli butik işletmeler, salonlar, stüdyolar ve bağımsız profesyoneller.',
                    'highlights' => 'Online randevu, WhatsApp entegrasyonu, müşteri CRM, takvim, temel kasa ve SMS/bildirim.',
                ],
                'growth' => [
                    'name' => 'Büyüme (Growth / Pro)',
                    'monthly_price' => 2450,
                    'annual_monthly_equivalent' => 1950,
                    'annual_total_price' => 23400,
                    'annual_saving' => 'Yıllık peşin ödemede 6.000 TL tasarruf',
                    'target' => 'Büyüyen, çok personelli ve paket/adisyon kullanan işletmeler.',
                    'highlights' => 'Gelişmiş paket/seans takibi, POS & adisyon, personel prim/komisyon motoru, yapay zeka asistanı, pazarlama otomasyonu.',
                ],
                'enterprise' => [
                    'name' => 'Kurumsal / Zincir (Enterprise)',
                    'monthly_price' => 4750,
                    'annual_monthly_equivalent' => 3800,
                    'annual_total_price' => 45600,
                    'annual_saving' => 'Yıllık peşin ödemede 11.400 TL tasarruf',
                    'target' => 'Çok şubeli zincirler, klinikler, büyük fitness merkezleri ve kurumsal işletmeler.',
                    'highlights' => 'Çoklu şube konsolidasyonu, özel alan adı (custom domain), MCP/API uçları, sınırsız personel, öncelikli 7/24 destek ve özel onboarding.',
                ],
            ],
            'roi_value_pitch' => 'Ayda sadece 1 müşterinin gelmeme (no-show) kaybı veya kaçan 1 randevu engellendiğinde, BooKi kendi aylık maliyetini ilk haftadan katbekat amorti eder.',
        ];
    }

    /**
     * 21 Universal BooKi Advantages over standard SaaS / appointment tools.
     */
    public function get_general_advantages(): array
    {
        return [
            1 => [
                'title' => 'Sadece Randevu Yazılımı Değil',
                'summary' => 'CRM + booking + POS + finance + inventory + marketing + AI tek çatı altında. Randevu araçları işletmeyi yönetemez; BooKi işletmenin işletim sistemidir.',
            ],
            2 => [
                'title' => 'Paket ≠ Üyelik Ayrımı',
                'summary' => 'Lazer/masaj gibi seans paketleri (10 seanslık hak) ile aylık/yıllık salon üyelik planları birbirinden tamamen ayrı veri modelleriyle yönetilir.',
            ],
            3 => [
                'title' => 'Gerçek Kaynak & İstasyon Yönetimi',
                'summary' => 'Sadece uzman takvimi değil; uzman + istasyon + oda/koltuk + ekipman ve müsaitlik aynı anda rezerve edilir. Çifte rezervasyon imkansızdır.',
            ],
            4 => [
                'title' => 'Gerçek Zamanlı Availability Engine',
                'summary' => 'Sadece takvim blokları göstermez; çalışma saatleri, molalar, mevcut randevular ve istasyon kısıtlarını milisaniyeler içinde tarayıp gerçek boş slotları hesaplar.',
            ],
            5 => [
                'title' => 'Session Tracking (Seans Doğrulama)',
                'summary' => 'Planlanan süre ile gerçekte uygulanan hizmet süresini karşılaştırır, istasyon verimliliğini ve personel performansını ölçer.',
            ],
            6 => [
                'title' => 'Gelişmiş Komisyon & Hakediş Motoru',
                'summary' => 'Personel, hizmet türü, paket satışı veya çalışma saatine göre dinamik komisyon hesaplar, ay sonu hakediş raporunu tek tıkla döker.',
            ],
            7 => [
                'title' => 'Customer 360 Görünümü',
                'summary' => 'Müşterinin geçmiş tüm randevuları, aldığı paketler, ödeme geçmişi, iletişim kayıtları, değerlendirmeleri ve sadakat puanları tek bir ekranda birleşir.',
            ],
            8 => [
                'title' => 'Çok Kanallı AI Agent (WhatsApp / Telegram / Instagram)',
                'summary' => 'Yapay zeka yalnızca sohbet etmez; doğrudan veritabanı müsaitlik motoruna bağlanır, randevu oluşturur, değiştirir ve soruları yanıtlar.',
            ],
            9 => [
                'title' => 'Human Approval (Yönetici Onay Prensibi)',
                'summary' => 'AI agent müşteriden gelen kritik değişiklikleri veya randevu taleplerini körü körüne veritabanına yazmaz; yönetici onay kuyruğuna aktarır, hata riskini sıfırlar.',
            ],
            10 => [
                'title' => 'Merkezi Tüketici Pazaryeri (Marketplace)',
                'summary' => 'İşletmeler randevuburada.kibusiness.co / booki.kibusiness.co üzerinden yeni tüketiciler tarafından keşfedilebilir ve anında canlı randevu alabilir.',
            ],
            11 => [
                'title' => 'Entegre Lead CRM & Saha Satış',
                'summary' => 'Google Places taraması → Lead → Arama/Ziyaret → Teklif → Satış → Anında Tenant Açılışı döngüsü tek bir platformdan yönetilir.',
            ],
            12 => [
                'title' => 'Pazarlama Otomasyonu (Marketing Automation)',
                'summary' => 'Müşteri segmentasyonu (uyuyan müşteriler, sadık müşteriler, son 30 gündür gelmeyenler) ile hedefli WhatsApp/SMS/Email kampanyaları gönderilir.',
            ],
            13 => [
                'title' => 'Otomatik Değerlendirme & Yorum (Review) Akışı',
                'summary' => 'Tamamlanan randevu sonrası müşteriye otomatik memnuniyet anketi ve Google Review bağlantısı gönderilerek online itibar artırılır.',
            ],
            14 => [
                'title' => 'Vertical Blueprint Mimarisi',
                'summary' => 'Güzellikten restorana, spor salonundan kliniğe, oto servisten eğitim merkezine kadar 9 sektör için özel iş akışları aynı çekirdekte hazırdır.',
            ],
            15 => [
                'title' => 'Tam Bağımsız Çoklu Kiracı (Multi-Tenant)',
                'summary' => 'Her işletmenin veritabanı, ayarları, müşteri verileri ve dosyaları tamamen izoledir. Veri karışması riski sıfırdır.',
            ],
            16 => [
                'title' => 'Müşteri ve Personel Mobil Deneyimi',
                'summary' => 'Müşteriler için hızlı PWA/mobil randevu arayüzü, uzman ve personeller için kendi takvimlerini ve primlerini gördükleri özel portal.',
            ],
            17 => [
                'title' => 'Public API & MCP Sunucusu',
                'summary' => 'Model Context Protocol (MCP) ve REST API sayesinde harici sistemler, AI asistanları ve muhasebe yazılımları doğrudan BooKi ile konuşabilir.',
            ],
            18 => [
                'title' => 'KVKK & GDPR Kurumsal Uyumluluk',
                'summary' => 'Müşteri telefon ve kişisel verileri AES-256-GCM ile şifrelenir (PII Encryption), rıza takibi (Consent), anonimleştirme ve denetim izi (Audit Log) tamdır.',
            ],
            19 => [
                'title' => 'Türkiye Odaklı Sabit TL Fiyatlandırma',
                'summary' => '1.250 TL/ay başlangıç fiyatı. Döviz kuru dalgalanması, kullanıcı/randevu başı sürpriz komisyon veya gizli maliyet yoktur.',
            ],
            20 => [
                'title' => 'Yıllık Taahhütte Büyük Avantaj',
                'summary' => 'Yıllık peşin ödemede ayda 1.000 TL eşdeğerine iner, işletmenin yazılım maliyeti sabitlenir.',
            ],
            21 => [
                'title' => 'Kademeli & Ölçeklenebilir Büyüme',
                'summary' => '1.250 TL → 2.450 TL → 4.750 TL kademeleriyle tek bir kuaför koltuğundan 50 şubeli bir zincire kadar yazılım değiştirmeden büyümeyi destekler.',
            ],
        ];
    }

    /**
     * 9 Vertical Industry Competitor Analysis and Refutation Playbooks.
     */
    public function get_competitor_playbook(?string $vertical = null): array
    {
        $data = [
            'guzellik_kuafor_spa' => [
                'sector_title' => 'Güzellik / Kuaför / SPA / Masaj / Nail / Epilasyon',
                'sales_pitch' => 'Sadece randevu almak istiyorsanız randevu uygulaması yeterli. Müşteri, personel, paket, kasa, stok, pazarlama ve büyümeyi tek yerden yönetmek istiyorsanız BooKi farklı bir kategoride.',
                'competitors' => [
                    'Menajer.im' => [
                        'strengths' => 'Güzellik sektörüne odaklı; randevu, müşteri, gelir-gider ve stok yönetimi sunuyor. 2014\'ten beri sektöre odaklandığını belirtiyor.',
                        'booki_advantage' => 'Daha geniş dikey mimarisi, gelişmiş paket/üyelik ayrımı, gerçek zamanlı kaynak/istasyon yönetimi, session tracking, POS, finans, marketplace, AI Agent, MCP/API, lead CRM ve pazarlama otomasyonunun aynı altyapıda bulunması.',
                        'objection_handling' => 'Menajer.im eski nesil lokal bir güzellik takip programıdır. BooKi ise WhatsApp AI asistanıyla 7/24 konuşarak randevu alan, paket/seans takibini kaçırmayan ve işletmeyi internetten yeni müşterilerle buluşturan modern bir işletim sistemidir.',
                    ],
                    'SalonJet' => [
                        'strengths' => 'Online randevu, WhatsApp, CRM, gelir-gider, komisyon ve stok gibi salon odaklı fonksiyonlar.',
                        'booki_advantage' => 'Salon yönetiminin ötesinde marketplace, lead generation, AI, mobil uygulama, çoklu sektör ve derin operasyon/finans altyapısı.',
                        'objection_handling' => 'SalonJet salon sınırlarında kalır. BooKi ise salonunuzun rezervasyonunu tek tıkla pazaryerine açar, şubeleştiğinizde altyapı değiştirmenize gerek bırakmaz ve yapay zeka ajanlarıyla WhatsApp/Instagram\'da insan gibi satış yapar.',
                    ],
                    'Fresha' => [
                        'strengths' => 'Global ölçek, marketplace, salon/spa/fitness/medikal gibi çok sayıda hizmet işletmesi ve geniş kullanıcı ekosistemi (130.000+ işletme).',
                        'booki_advantage' => 'Türkiye odaklı TL fiyatlandırma, Türkçe operasyon, yerel entegrasyonlar, KVKK altyapısı, WhatsApp/Telegram/Instagram AI, sabit abonelik modeli ve işletmenin kendi müşteri verisine/markasına daha fazla odaklanma.',
                        'objection_handling' => 'Fresha müşteriyi kendi havuzuna çeker ve randevu başına veya ödemelerden yüksek komisyonlar keser; döviz bazlı gizli maliyetler çıkarabilir. BooKi\'de ise müşteri doğrudan sizindir, komisyon ödemezsiniz ve KVKK gereği müşteri verileriniz şifreli ve yereldir.',
                    ],
                    'Booksy' => [
                        'strengths' => 'Global salon marketplace ve müşteri keşfi.',
                        'booki_advantage' => 'Marketplace\'e bağımlı olmayan işletme CRM\'i; paket, üyelik, POS, stok, finans, AI, sektör blueprint\'leri, lead CRM ve özel işletme yönetimi.',
                        'objection_handling' => 'Booksy bir pazar yeridir; pazar yerinden müşteri gelmediğinde işletmenizin iç operasyonu, adisyonu, seans paketi ve prim hesapları eksik kalır. BooKi önce işletmenizin omurgasını kurar, sonra pazaryerine bağlar.',
                    ],
                    'SalonRandevu.app' => [
                        'strengths' => 'Basit online randevu deneyimi.',
                        'booki_advantage' => 'Basit randevu aracından ziyade uçtan uca işletme işletim sistemi.',
                        'objection_handling' => 'SalonRandevu sadece takvime isim yazar. Randevu iptal olunca ne olacak? Müşteri paketi kaç seans kaldı? Personel primi ne kadar? Kasa ne durumda? Bunları çözmek için 4 ayrı program kullanmak yerine BooKi tek başına yeterlidir.',
                    ],
                ],
            ],
            'restoran_kafe' => [
                'sector_title' => 'Restoran / Kafe / Yeme-İçme',
                'sales_pitch' => 'BooKi POS olmak için rezervasyon eklemiyor; rezervasyonu, müşteriyi ve işletme operasyonunu aynı veri modelinde birleştiriyor.',
                'competitors' => [
                    'Adisyo' => [
                        'strengths' => 'POS, adisyon, restoran operasyonu.',
                        'booki_advantage' => 'Randevu/rezervasyon + masa + müşteri CRM + adisyon + POS + ödeme + stok + kampanya + review + marketplace aynı çekirdekte.',
                        'objection_handling' => 'Adisyo mükemmel bir adisyon programıdır ancak kimin masada oturduğunu, o müşterinin doğum gününü, geçmiş rezervasyon tercihlerini ve dışarıdan gelen rezervasyon taleplerini otomatik yönetemez. BooKi adisyon ile müşteri sadakatini birleştirir.',
                    ],
                    'Simpra' => [
                        'strengths' => 'Restoran otomasyonu ve operasyon.',
                        'booki_advantage' => 'Rezervasyon motoru, CRM, müşteri geçmişi, pazarlama, AI ve marketplace tarafında daha geniş servis işletmesi mimarisi.',
                        'objection_handling' => 'Simpra kurumsal restoranlarda operasyonu yürütür ama müşteri kazanımı, rezervasyon akışı ve yapay zeka kanallarında kapalıdır. BooKi ise masanızı dolduracak pazarlama ve rezervasyon motoruna sahiptir.',
                    ],
                    'RRobotpos' => [
                        'strengths' => 'Büyük restoran/POS operasyonları ve yerleşik sektör deneyimi.',
                        'booki_advantage' => 'Daha modern SaaS/multi-tenant mimari, API/MCP, AI Agent, online booking ve müşteri yaşam döngüsü.',
                        'objection_handling' => 'Robotpos yerel sunuculu, yüksek lisans maliyetli klasik bir yapıdır. BooKi modern bulut mimarisiyle sıfır donanım yatırımıyla dakikalar içinde açılır.',
                    ],
                    'SepetTakip' => [
                        'strengths' => 'Restoran otomasyonu ve satış operasyonu.',
                        'booki_advantage' => 'Rezervasyon, CRM, pazarlama, müşteri segmentasyonu, review ve AI katmanları.',
                        'objection_handling' => 'SepetTakip yalnızca sipariş takibidir; BooKi ise müşterinin masaya gelişinden hesap ödemesine ve sonrasındaki WhatsApp geri bildirimine kadar tüm döngüyü kapsar.',
                    ],
                    'RestoranTakibi' => [
                        'strengths' => 'Düşük maliyetli restoran otomasyonu; 1 milyon TL ciroya kadar ücretsiz model iddiası.',
                        'booki_advantage' => 'Daha geniş operasyon kapsamı ve özellikle rezervasyon + CRM + müşteri sadakati + pazarlama tarafı.',
                        'objection_handling' => 'Ciro barajlı ücretsiz modeller belirli bir cirodan sonra çok yüksek komisyonlar veya beklenmedik kısıtlamalar getirir. BooKi\'de fiyat baştan bellidir: sabit, şeffaf ve sınırsız.',
                    ],
                ],
            ],
            'fitness_gym_pilates' => [
                'sector_title' => 'Fitness / Gym / Pilates / Reformer / PT',
                'sales_pitch' => 'BooKi sadece üye takip sistemi değil; stüdyonun randevu, paket, eğitmen, kaynak, ödeme, müşteri ve büyüme sistemidir.',
                'competitors' => [
                    'GymTekno' => [
                        'strengths' => 'Turnike, QR, mobil uygulama, PT, ölçüm, AI, çoklu şube, e-fatura (2.000+ tesis).',
                        'booki_advantage' => 'Çok daha geniş genel işletme mimarisi; özellikle CRM, appointment engine, kaynak/station, paket/plan ayrımı, marketplace, lead CRM, AI Agent, MCP/API.',
                        'objection_handling' => 'GymTekno donanım/turnike odaklıdır. Butik stüdyolarda, pilates ve reformer merkezlerinde en büyük sorun turnike değil; reformer aletlerinin çakışmaması, eğitmen randevuları ve seans paketleridir. BooKi bu kaynak tahsisini kusursuz yapar.',
                    ],
                    'Gymsoft' => [
                        'strengths' => 'Turnike, üyelik, büyük salon ve çoklu şube.',
                        'booki_advantage' => 'Randevu/resource/session motoru, CRM, marketing automation, marketplace ve AI.',
                        'objection_handling' => 'Gymsoft geleneksel demirbaş spor salonları içindir; BooKi ise seans, ders ve eğitmen randevularını online ödemeyle birleştiren yeni nesil stüdyo işletim sistemidir.',
                    ],
                    'BulutGym' => [
                        'strengths' => 'Seans bazlı çalışma ve küçük/orta pilates/yoga stüdyoları.',
                        'booki_advantage' => 'Paket + plan ayrımı, gerçek zamanlı kaynak kapasitesi, POS, finans, CRM, pazarlama, AI ve marketplace.',
                        'objection_handling' => 'BulutGym sadece seans sayar; BooKi ise stüdyonun web sitesini açar, WhatsApp üzerinden otomatik rezervasyon alır ve gelir-gider/finans takibini yapar.',
                    ],
                    'GymPro' => [
                        'strengths' => 'Turnike, kart, mobil uygulama, üyelik.',
                        'booki_advantage' => 'Daha geniş SaaS işletme ekosistemi, modern arayüz ve çok kanallı AI asistan.',
                        'objection_handling' => 'GymPro eski arayüzlü ve hantal bir yapıya sahiptir. BooKi cep telefonundan bile yönetilebilen modern SaaS hızına sahiptir.',
                    ],
                    'Megin' => [
                        'strengths' => 'PT, danışan, ölçüm, beslenme ve kişisel koçluk.',
                        'booki_advantage' => 'PT\'nin yanında işletmenin tamamını yönetebilmesi; personel, kaynak, randevu, paket, üyelik, ödeme, finans, CRM, marketing.',
                        'objection_handling' => 'Megin bireysel antrenörün not defteridir; stüdyonun kasasını, vergisini, eğitmen primlerini ve pazarlamasını yönetemez. BooKi ise tüm stüdyoyu birleştirir.',
                    ],
                ],
            ],
            'saglik_klinik_dis' => [
                'sector_title' => 'Sağlık / Klinik / Diş / Fizyoterapi',
                'sales_pitch' => 'Kliniklerde BooKi tıbbi kayıt/HIS yerine geçmek için değil; randevu, hasta iletişimi, operasyon, CRM ve hasta deneyimi katmanını kusursuzlaştırmak için konumlandırılır.',
                'competitors' => [
                    'MedikalCRM' => [
                        'strengths' => 'Sağlık turizmi, WhatsApp, çok dil, hasta portalı, CRM.',
                        'booki_advantage' => 'Genel randevu/resource/session/CRM/marketing/AI altyapısının yanında clinic vertical modülü ve yerel mevzuata tam uyum.',
                        'objection_handling' => 'MedikalCRM pahalı ve kurulumu aylar süren bir yapıdır. BooKi aynı gün canlıya alınır, KVKK şifreleme altyapısıyla hasta telefonlarını güvenceye alır.',
                    ],
                    'Doktor365' => [
                        'strengths' => 'AI destekli hasta CRM ve WhatsApp.',
                        'booki_advantage' => 'AI yalnızca metin iletişimi değil; gerçek availability, booking, doktor çalışma takvimi, değişiklik ve yönetici onay araçlarıyla doğrudan operasyona bağlıdır.',
                        'objection_handling' => 'Doktor365\'in AI\'ı havada konuşur; BooKi\'nin AI asistanı ise kliniğin takvimini, doktorun ameliyat saatlerini bilir ve onay mekanizmasıyla çalışır.',
                    ],
                    'Dr.DENTES' => [
                        'strengths' => 'Diş kliniğine dikey uzmanlaşma.',
                        'booki_advantage' => 'Dikey bağımlılığı düşük, farklı sağlık ve hizmet işletmelerine genişleyebilen modern çok şubeli platform.',
                        'objection_handling' => 'Dr.DENTES klasik masaüstü diş hekimi yazılımıdır. BooKi ise hastanın WhatsApp\'tan randevu almasını, kliniğin pazaryerinde listelenmesini ve online ödeme almasını sağlar.',
                    ],
                    'DentSoft' => [
                        'strengths' => 'Diş kliniği operasyonları.',
                        'booki_advantage' => 'Genel SaaS işletme, CRM, marketing, marketplace, AI ve API ekosistemi.',
                        'objection_handling' => 'DentSoft hasta takibi yapar ama yeni hasta bulma ve online rezervasyon tarafında zayıftır. BooKi hastayı bulur, randevuya bağlar ve hatırlatır.',
                    ],
                    'Macrodental' => [
                        'strengths' => 'Diş kliniği odaklı yönetim.',
                        'booki_advantage' => 'Çok sektörlü yapı + modern multi-tenant SaaS + AI/communication/marketing.',
                        'objection_handling' => 'Macrodental eski teknolojiye sıkışmıştır; BooKi bulut tabanlı ve her hafta güncellenen bir platformdur.',
                    ],
                ],
            ],
            'otomotiv_oto_servis' => [
                'sector_title' => 'Otomotiv / Oto Servis / Ekspertiz / Yıkama',
                'sales_pitch' => 'Servis yazılımı aracın geçmişini yönetir. BooKi müşterinin işletmeyle tüm ilişkisini, iş emirlerini ve randevularını yönetir.',
                'competitors' => [
                    'PADOK Bulut' => [
                        'strengths' => 'Araç kabul, iş emri, stok, cari, fatura, WhatsApp ve AI raporları (300+ müşteri).',
                        'booki_advantage' => 'Randevu/CRM/marketing/AI/marketplace mimarisinin yanında automotive vertical; müşteri portalı ve lift/istasyon randevusu.',
                        'objection_handling' => 'PADOK bulutta muhasebe ve parça takibine odaklanır; BooKi ise servis liftlerinin randevu kapasitesini dakikası dakikasına doldurur ve araç sahibine WhatsApp üzerinden canlı durum bildirir.',
                    ],
                    'Tila TSP' => [
                        'strengths' => 'İş emri, araç, stok, cari, fatura, kasa ve finans.',
                        'booki_advantage' => 'Modern web SaaS, online booking, müşteri portalı, communication hub, marketing ve AI.',
                        'objection_handling' => 'Tila TSP iç muhasebedir; müşterinin online randevu alabileceği bir arayüzü veya yapay zeka asistanı yoktur. BooKi servisinize dijital ön büro kazandırır.',
                    ],
                    'Livo Oto Servis' => [
                        'strengths' => 'Randevu, servis, stok, satış, fatura, ödeme ve AI raporlama.',
                        'booki_advantage' => 'Daha geniş müşteri yaşam döngüsü, multi-vertical platform ve şeffaf fiyat.',
                        'objection_handling' => 'Livo servis odaklı kapalı bir kutudur; BooKi ise randevu motoru, müşteri hatırlatmaları ve pazarlama otomasyonu ile servisin periyodik bakım dönüşümünü 2 katına çıkarır.',
                    ],
                    'OtoServiso' => [
                        'strengths' => 'Plaka, servis geçmişi, iş emri, stok ve faturalandırma.',
                        'booki_advantage' => 'Automotive dışında 8+ sektör mimarisi, marketplace, AI Agent ve kapsamlı CRM.',
                        'objection_handling' => 'OtoServiso klasik masaüstü mantığıyla çalışır. BooKi ile usta cep telefonundan iş emrini görür, müşteri WhatsApp\'tan aracının durumunu sorup yanıt alır.',
                    ],
                    'Onarmatik' => [
                        'strengths' => 'Araç, müşteri, parça ve ödeme takibi.',
                        'booki_advantage' => 'Bunların üzerine randevu motoru, marketing, automation, AI ve SaaS altyapısı.',
                        'objection_handling' => 'Onarmatik tamir fişi keser; BooKi ise periyodik bakımı yaklaşan araçlara otomatik WhatsApp hatırlatması göndererek atölyeyi doldurur.',
                    ],
                ],
            ],
            'deneyim_eglence_etkinlik' => [
                'sector_title' => 'Deneyim / Eğlence / Spor Aktivitesi / Etkinlik',
                'sales_pitch' => 'Büyük bilet siteleri kendi markalarını büyütür ve komisyon alır. BooKi işletmenin kendi müşterisini, rezervasyonunu ve nakit akışını büyütür.',
                'competitors' => [
                    'Biletix' => [
                        'strengths' => 'Çok büyük bilet marketplace\'i ve tüketici erişimi.',
                        'booki_advantage' => 'Küçük/orta işletmenin kendi operasyonunu yönetmesi; müşteri CRM, kaynak, rezervasyon, paket, ödeme ve sadakat (komisyonsuz).',
                        'objection_handling' => 'Biletix bilet başına %15-25 komisyon keser ve müşteri verisini size vermez. BooKi\'de komisyon %0\'dır; müşteri verisi tamamen sizin CRM\'inizde kalır.',
                    ],
                    'Passo' => [
                        'strengths' => 'Spor etkinlikleri ve büyük ölçekli biletleme.',
                        'booki_advantage' => 'Günlük işletme operasyonu ve appointment/resource management.',
                        'objection_handling' => 'Passo stadyum ve büyük organizasyonlar içindir; halı saha, paddle kortu, tırmanış duvarı gibi yerel işletmeler için BooKi biçilmiş kaftandır.',
                    ],
                    'Biletinial' => [
                        'strengths' => 'Sinema, tiyatro ve etkinlik biletleme.',
                        'booki_advantage' => 'İşletme CRM + reservation + POS + marketing.',
                        'objection_handling' => 'Biletinial sadece bilet satar; BooKi ise katılımcıya etkinlik sonrası anket gönderir, bir sonraki etkinliği WhatsApp\'tan duyurur ve sadakat puanı verir.',
                    ],
                    'Bubilet' => [
                        'strengths' => 'Etkinlik keşfi ve indirimli biletleme.',
                        'booki_advantage' => 'İşletmenin kendi müşterisini ve operasyonunu yönetmesi.',
                        'objection_handling' => 'Bubilet fiyat kırma platformudur; BooKi ise işletmenizin değerini ve marka algısını koruyarak doğrudan kendi sitenizden satış yapmanızı sağlar.',
                    ],
                    'Biletino' => [
                        'strengths' => 'Etkinlik oluşturma, bilet satışları, raporlar (7.000+ etkinlik).',
                        'booki_advantage' => 'Rezervasyon + kaynak + müşteri + ödeme + CRM + marketing + AI.',
                        'objection_handling' => 'Biletino bağımsız atölyeler ve dönemsel eğitimler için sadece bilet aracıdır; BooKi sürekli işletmelerin günlük seans ve kapasite yönetimini üstlenir.',
                    ],
                ],
            ],
            'konaklama_butik_otel' => [
                'sector_title' => 'Konaklama / Butik Otel / Pansiyon / Bungalov',
                'sales_pitch' => '500 odalı resort PMS\'i ile 12 odalı butik işletmenin ihtiyacı aynı değil. BooKi küçük ve orta işletmeye kurumsal yazılım karmaşasını yüklemeden operasyonu dijitalleştirir.',
                'competitors' => [
                    'Elektraweb' => [
                        'strengths' => 'Türkiye otel/PMS operasyonu, kimlik bildirim entegrasyonu.',
                        'booki_advantage' => 'Küçük/orta tesisler için daha genel, daha düşük giriş maliyetli, sade ve modern işletme platformu.',
                        'objection_handling' => 'Elektraweb yüzlerce menüsü ve eğitim gerektiren karmaşık yapısıyla butik otelleri yorar. BooKi dakikalar içinde öğrenilir, personeli eğitmek gerekmez.',
                    ],
                    'HotelRunner' => [
                        'strengths' => 'Online distribution / channel management.',
                        'booki_advantage' => 'CRM, appointment/resource, marketing, AI ve diğer sektörlerle ortak platform.',
                        'objection_handling' => 'HotelRunner sadece kanal yöneticisidir; otelin içindeki SPA, restoran, transfer ve aktivite rezervasyonlarını yönetemez. BooKi tesisin tüm gelir kanallarını birleştirir.',
                    ],
                    'Vertical Booking' => [
                        'strengths' => 'CRS ve kurumsal booking engine.',
                        'booki_advantage' => 'Daha geniş işletme yönetimi, düşük maliyet.',
                        'objection_handling' => 'Vertical Booking döviz bazlı yüksek maliyetlidir; BooKi yerel TL fiyatıyla küçük işletmelerin dostudur.',
                    ],
                    'Amadeus iHotelier' => [
                        'strengths' => 'Kurumsal global hospitality ve GDS dağıtımı.',
                        'booki_advantage' => 'KOBİ/SMB odaklı fiyat ve sade operasyon.',
                        'objection_handling' => 'iHotelier dev zincir oteller içindir. 10 odalı bungalov veya butik otel için BooKi hem hızlı hem hesaplıdır.',
                    ],
                    'RoomRaccoon' => [
                        'strengths' => 'PMS + booking + hospitality automation.',
                        'booki_advantage' => 'Daha geniş dikey kullanım, TL bazlı uygun fiyat ve yerel WhatsApp desteği.',
                        'objection_handling' => 'RoomRaccoon Avrupa fiyatlarıyla pahalı kalır; BooKi Türkiye şartlarına uygun sabit TL fiyat ve yerel destek sunar.',
                    ],
                ],
            ],
            'egitim_kurs_akademi' => [
                'sector_title' => 'Eğitim / Kurs / Akademi / Dans / Müzik / Sürücü Kursu',
                'sales_pitch' => 'Öğrenci takip yazılımları sadece yoklama alır; BooKi ise eğitmen takvimini, birebir dersleri, sınıf kontenjanlarını ve veli iletişimini tek ekranda toplar.',
                'competitors' => [
                    'ArtechSchool' => [
                        'strengths' => 'Öğrenci, öğretmen, ödeme, yoklama, CRM, mobil, ders, kartlı geçiş (47+ modül).',
                        'booki_advantage' => 'Daha güçlü genel appointment/resource engine, paket/plan, marketplace, CRM, marketing, AI ve çok sektörlü yapı.',
                        'objection_handling' => 'ArtechSchool okul/kolej odaklıdır ve çok hantaldır. Dans, müzik, yabancı dil veya spor akademileri için BooKi çok daha pratik ve esnektir.',
                    ],
                    'Delta Kurs Otomasyonu' => [
                        'strengths' => 'Öğrenci, muhasebe, yoklama, iletişim ve ödeme planları.',
                        'booki_advantage' => 'Daha modern genel işletme operasyonu + AI + marketing + online booking.',
                        'objection_handling' => 'Delta Kurs klasik dershane yapısıdır; BooKi ise velinin veya öğrencinin online olarak boş ders saatini seçip ödemesini yapabildiği modern bir platformdur.',
                    ],
                    'Eğitim360' => [
                        'strengths' => 'Öğrenci, sınıf, öğretmen, veli, yoklama ve sınav analizi.',
                        'booki_advantage' => 'Dershane akademik yönetimi yerine randevu/seans/işletme operasyonunda daha geniş kullanım.',
                        'objection_handling' => 'Eğitim360 sınav odaklıdır; özel ders, koçluk veya beceri atölyeleri için BooKi\'nin seans ve paket takibi çok daha uygundur.',
                    ],
                    'OkulConnect' => [
                        'strengths' => 'Veli, öğretmen, öğrenci, yoklama, ödeme ve okul operasyonları.',
                        'booki_advantage' => 'Eğitim dışındaki sektörlere de aynı platformla hizmet verebilme, gelişmiş AI bot desteği.',
                        'objection_handling' => 'OkulConnect kurumsal okullara yöneliktir; özel etüt merkezleri ve akademiler için BooKi katbekat uygun maliyetlidir.',
                    ],
                    'Birebil' => [
                        'strengths' => 'Bulut tabanlı dershane/kurs/etüt yönetimi.',
                        'booki_advantage' => 'Booking + CRM + paket + ödeme + marketing + AI + marketplace.',
                        'objection_handling' => 'Birebil sadece iç takip yapar; BooKi ise yeni öğrenci kaydı çekebileceğiniz pazarlama ve landing page altyapısını da içerir.',
                    ],
                ],
            ],
            'profesyonel_hizmetler_crm' => [
                'sector_title' => 'Profesyonel Hizmetler / Danışmanlık / Koçluk / Hukuk / Stüdyo',
                'sales_pitch' => 'CRM sadece kiminle konuştuğunuzu yazar. BooKi ise görüşmeyi randevuya, randevuyu hizmete, hizmeti tahsilata ve sadakate dönüştürür.',
                'competitors' => [
                    'Zoho CRM' => [
                        'strengths' => 'Çok kapsamlı CRM, satış pipeline, otomasyon, mobil kullanım.',
                        'booki_advantage' => 'CRM\'in yanında doğrudan randevu, availability, provider/resource, POS, paket, üyelik, ödeme ve sektörel operasyon.',
                        'objection_handling' => 'Zoho bir yazılımcı ordusu kurmadan kullanılamaz; entegrasyonları için binlerce dolar harcarsınız. BooKi ise danışmanlar ve ajanslar için kuruluma gerek kalmadan ilk dakikadan çalışır.',
                    ],
                    'HubSpot' => [
                        'strengths' => 'Marketing + CRM + sales automation.',
                        'booki_advantage' => 'Hizmet işletmesinin günlük operasyonunu CRM ile aynı sistemde tutması, yerel TL fiyat avantajı.',
                        'objection_handling' => 'HubSpot\'ta biraz özellik açtığınızda fatura ayda 500-1000 dolara çıkar. BooKi 1.250 TL sabit fiyatla KOBİ ve danışmanlara kurumsal güç sağlar.',
                    ],
                    'Pipedrive' => [
                        'strengths' => 'Sales pipeline ve kullanım kolaylığı.',
                        'booki_advantage' => 'Lead CRM\'in yanında operasyonel booking ve müşteri hizmeti.',
                        'objection_handling' => 'Pipedrive anlaşmayı yapana kadardır; anlaşma yapıldıktan sonra seans takvimini, randevuları ve tahsilatı yönetemez. BooKi uçtan ucadır.',
                    ],
                    'Logo CRM' => [
                        'strengths' => 'Türkiye\'de kurumsal ERP/finans ekosistemi.',
                        'booki_advantage' => 'Daha hızlı SaaS kurulumu, appointment-first mimari, AI ve marketplace.',
                        'objection_handling' => 'Logo CRM ağır kurumsal sanayi firmaları içindir; modern hizmet işletmeleri ve randevulu çalışanlar için BooKi 10 kat daha hızlı ve pratiktir.',
                    ],
                    'Workcube' => [
                        'strengths' => 'ERP/CRM ve kurumsal süreç yönetimi.',
                        'booki_advantage' => 'KOBİ hizmet işletmeleri için çok daha odaklı ve düşük operasyonel karmaşıklık.',
                        'objection_handling' => 'Workcube aylar süren danışmanlık ister; BooKi 10 dakikada kurulur.',
                    ],
                ],
            ],
        ];

        if ($vertical !== null && isset($data[$vertical])) {
            return $data[$vertical];
        }

        return $data;
    }

    /**
     * Find competitor info by name across all verticals.
     */
    public function find_competitor(string $name): ?array
    {
        $name_lower = mb_strtolower(trim($name), 'UTF-8');
        $playbooks = $this->get_competitor_playbook();

        foreach ($playbooks as $vertical_key => $vertical_data) {
            foreach ($vertical_data['competitors'] as $comp_name => $comp_info) {
                if (mb_stripos($comp_name, $name_lower, 0, 'UTF-8') !== false || mb_stripos($name_lower, mb_strtolower($comp_name, 'UTF-8'), 0, 'UTF-8') !== false) {
                    return [
                        'vertical_key' => $vertical_key,
                        'vertical_title' => $vertical_data['sector_title'],
                        'vertical_pitch' => $vertical_data['sales_pitch'],
                        'competitor_name' => $comp_name,
                        'details' => $comp_info,
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Sales Methodology Decision Matrix & Execution Playbook.
     * Extracted from KI Business Satış Metodolojisi Referans Kitapçığı (PDF).
     */
    public function get_sales_methodology_matrix(): array
    {
        return [
            // Soğuk Arama (SA-01 .. SA-08)
            'SA-01' => [
                'channel' => 'Soğuk Arama',
                'customer_type' => 'KOBİ Sahibi / Girişimci',
                'psychology' => 'Savunmacı – şüpheci, zamanını koruma refleksi',
                'technique_name' => 'Pattern Interrupt + Pain Discovery (Sandler)',
                'crm_tag' => 'SANDLER_PAIN_COLD',
                'theory' => 'Up-Front Contract ile ilk 10 saniyede kalıp kırılır. Pain Funnel soruları ile müşterinin kendi ağzından ihtiyacı söyletilir (HBR: commit oranını %40 artırır).',
                'steps' => [
                    'İlk cümle: Beklentiyi kır ("Muhtemelen satıcı çağrısı olduğunu düşünüyorsunuz, haklısınız — ama farklı bir türü.")',
                    'Up-Front Contract: Maksimum 3 dakika izin al.',
                    'Pain Funnel: Açık uçlu sorularla durumu keşfet.',
                    'Sessizliği koru: Soru sorduktan sonra en az 4 saniye bekle.',
                ],
                'script' => '[İsim] Bey/Hanım, şu an birkaç dakikanız var mı? [Onay] Çoğu işletme sahibiyle konuştuğumda randevu kayıpları ve personel prim hesaplarında ciddi zaman kaybettiklerini duyuyorum. Bu sizin için de geçerli mi?',
                'objection' => '"Şu an meşgulüm" → "Tamamen anlıyorum, bu yüzden doğrudan soruyorum: Randevu kayıpları sizin için kritik bir başlık mı? Değilse, zaman kaybetmeyiz."',
            ],
            'SA-02' => [
                'channel' => 'Soğuk Arama',
                'customer_type' => 'KOBİ Sahibi / Girişimci',
                'psychology' => 'Meraklı – yeni çözüme açık, aktif problem arıyor',
                'technique_name' => 'Challenger: Insight-Led Opener + Reframe',
                'crm_tag' => 'CHALLENGER_INSIGHT_COLD',
                'theory' => 'Challenger Sale: Müşteriye bilmediği bir şeyi öğretmek güveni 2.3x hızlandırır.',
                'steps' => [
                    'Insight Açıcı: "[Sektörde] şunu gözlemledik..." ile dikkat çek.',
                    'Reframe: Müşterinin problemini yeni bir perspektifle çerçevele.',
                    'Soru ile bitir: "Sizin deneyiminiz bu tabloya uyuyor mu?"',
                ],
                'script' => '[İsim] Bey/Hanım, hızlıca paylaşmak istediğim bir şey var: Sektördeki firmalar genellikle randevuları ajandadan veya basit WhatsApp mesajıyla yönetiyor, ama verilere baktığımızda bu yaklaşım randevuya gelmeme oranını %25 artırıyor. Siz bu konuda ne düşünüyorsunuz?',
                'objection' => '"Bunu zaten biliyoruz" → "Harika, o zaman şunu sormak istiyorum: buna rağmen neden hâlâ kaçan randevular devam ediyor?"',
            ],
            'SA-03' => [
                'channel' => 'Soğuk Arama',
                'customer_type' => 'Kurumsal Orta Düzey Yönetici',
                'psychology' => 'Sceptical-Busy – kısıtlı bütçe, prosedürel bakış',
                'technique_name' => 'SPIN: Situation → Problem Soru Akışı + Sosyal Kanıt',
                'crm_tag' => 'SPIN_CORPORATE_MID',
                'theory' => 'Neil Rackham SPIN: Implication ve Need-Payoff soruları yöneticinin çözümü hayal etmesini sağlar.',
                'steps' => [
                    'Situation: "Şu an randevu ve müşteri sürecini nasıl yönetiyorsunuz?"',
                    'Problem: "Bu yaklaşımda en çok hangi nokta sizi zorluyor?"',
                    'Implication: "Bu sorun devam ederse 6 ay sonra ne olur?"',
                    'Need-Payoff: "Eğer bu çözülseydi, sizin için ne anlam ifade ederdi?"',
                ],
                'script' => '[İsim] Hanım/Bey, şu an şubelerinizin randevu ve seans takvimini nasıl yönettiğinizi merak ediyorum. Peki bu süreçte en sık karşılaştığınız zorluk ne oluyor?',
                'objection' => '"Şu an bir ihtiyacımız yok" → "Bunu duyuyorum sıkça. Şu an operasyonda en çok odaklandığınız önceliğiniz nedir?"',
            ],
            'SA-04' => [
                'channel' => 'Soğuk Arama',
                'customer_type' => 'Kurumsal Orta Düzey Yönetici',
                'psychology' => 'Risk-Averse – hata yapmaktan çekiniyor, onay bekliyor',
                'technique_name' => 'Cost of Inaction (COI) Framing + Peer Benchmark (Gong)',
                'crm_tag' => 'COI_RISK_AVERSE',
                'theory' => 'Gong Labs: ROI konuşmak next meeting oranını düşürürken, Hareketsizliğin Maliyeti (Cost of Inaction) güçlü motivasyon yaratır.',
                'steps' => [
                    'Sorunu sabitle: Hareketsizliğin aylık maliyeti.',
                    'Peer benchmark: Sektördeki benzer ölçekteki firmalar ne kazandı.',
                    'Empati kur: "Sizi zorlayan tek şey bu mu?"',
                ],
                'script' => 'Kaçan her 10 randevunun işletmeye aylık maliyeti ortalama 25.000 TL üzerinde. Benzer ölçekteki merkezler BooKi WhatsApp AI ile bu kaybı %80 azalttı. Sizin için en kritik engel nerede?',
                'objection' => '"Bütçemiz yok" → "Bütçe olmadığından mı, yoksa bütçeyi henüz bu kaybı önlemeye ayıramadığınızdan mı?"',
            ],
            'SA-05' => [
                'channel' => 'Soğuk Arama',
                'customer_type' => 'C-Suite / Üst Yönetim',
                'psychology' => 'Zamanı kısıtlı – değer görmezse ilk 30 sn\'de kapar',
                'technique_name' => 'Executive Rapport Calibration + Strategic Priority Anchor (Gong Labs)',
                'crm_tag' => 'EXECUTIVE_RAPPORT_STRATEGIC',
                'theory' => 'Gong: Rapport 1-2 dakika ile sınırlı tutulmalı ve hazırlık kanıtı için kullanılmalı. Konuşma üst yönetimin önceliğine bağlanmalı.',
                'steps' => [
                    'Araştırma bazlı açılış: Şirketin büyüme/şubeleşme odağı.',
                    'Doğrulama: "Bunu doğru mu anladım?"',
                    'Daha az soru, daha çok bağlam.',
                ],
                'script' => '[İsim] Bey, işletmenizin yeni şubelerle büyümeyi önceliklendirdiğini gördüm. Doğru mu? Biz bu ölçekteki zincir işletmelere merkezi takvim ve AI ön büro altyapısı sağlıyoruz. Sizin deneyiminiz nasıl gidiyor?',
                'objection' => '"Şu an gündemimizde değil" → "Anlıyorum. Büyüme sürecinde şu an masanızdaki en büyük operasyonel öncelik nedir?"',
            ],
            'SA-06' => [
                'channel' => 'Soğuk Arama',
                'customer_type' => 'C-Suite / Üst Yönetim',
                'psychology' => 'İlgili ancak şüpheci – önceki satıcılardan hayal kırıklığı var',
                'technique_name' => 'Challenger Teaching + No-ROI Discovery (Gong)',
                'crm_tag' => 'CHALLENGER_NO_ROI_CSUITE',
                'theory' => 'Büyük ROI vaatleri inandırıcılığı yıkar. Rakam vermek yerine sektördeki yapısal örüntü gösterilir.',
                'steps' => [
                    'Önceki deneyimi tanı.',
                    'Yeni çerçeve sun.',
                    'Öğret, satma.',
                ],
                'script' => 'Bu konuşmada size afaki rakamlar vermeyeceğim. Bunun yerine hizmet sektöründe gözlemlediğimiz bir gerçeği paylaşmak istiyorum: Çoğu işletme yazılım değiştiriyor ama personelin kullanmaması yüzünden eski sisteme dönüyor. BooKi\'nin WhatsApp tabanlı olmasının nedeni tam da bu. Bu sizin için anlamlı mı?',
                'objection' => '"Bunu zaten biliyoruz" → "Harika, peki personelin sisteme uyum sağlamasını nasıl çözdünüz?"',
            ],
            'SA-07' => [
                'channel' => 'Soğuk Arama',
                'customer_type' => 'Birey / Tüketici (B2C)',
                'psychology' => 'Koruyucu – özel hayatına giriyorsun hissiyle defansif',
                'technique_name' => 'Empathy Bridge + Minimal Friction Script (Close CRM)',
                'crm_tag' => 'EMPATHY_B2C_COLD',
                'theory' => 'Empati köprüsü savunma refleksini ilk 15 saniyede kırar. Bilişsel yükü azaltan ikili seçenek sunulur.',
                'steps' => [
                    'Empati ile aç: Beklenmedik anda arandığının farkında ol.',
                    'Amacı 1 cümlede söyle.',
                    'İzin iste: 1 dakika müsait misiniz?',
                ],
                'script' => '[İsim] Bey/Hanım merhaba, sizi beklenmedik bir anda arıyorum, farkındayım. BooKi\'den arıyorum, işletmeniz için online randevu kolaylığı hakkında 1 dakikalık bilgi vermek istedim, uygun mudur?',
                'objection' => '"İlgilenmiyorum" → "Anlıyorum. Sizi ne zaman aramam daha uygun olur?"',
            ],
            'SA-08' => [
                'channel' => 'Soğuk Arama',
                'customer_type' => 'Birey / Tüketici (B2C)',
                'psychology' => 'Fırsatçı – ucuz/avantajlı şey peşinde, karşılaştırma yapıyor',
                'technique_name' => 'Value Anchoring + FOMO Framing (Predictable Revenue)',
                'crm_tag' => 'VALUE_ANCHOR_FOMO_B2C',
                'theory' => 'Önce yüksek değer çıpalanır, sonra uygun fiyat ve zaman sınırlı kampanya verilir.',
                'steps' => [
                    'Yüksek değeri sabitle.',
                    'Avantajı sun: Yıllıkta ayda 1.000 TL.',
                    'CTA: 10 günlük ücretsiz demo başlat.',
                ],
                'script' => 'Normalde kurumsal işletmelerin on binlerce lira harcadığı WhatsApp AI ve randevu motorunu, yeni işletmeler için ayda sadece 1.000 TL eşdeğerine sunuyoruz. Bu ay başlayanlar için kurulum ücretsiz. Denemek ister misiniz?',
                'objection' => '"Pahalı" → "Şu an kaçan tek bir randevunuzun işletmeye maliyeti aylık BooKi aboneliğinden daha yüksek."',
            ],

            // Telesatış Inbound (TI-01 .. TI-04)
            'TI-01' => [
                'channel' => 'Telesatış (Inbound)',
                'customer_type' => 'Sıcak Lead – Web Formu Doldurmuş',
                'psychology' => 'İlgili ama kararlı değil – bilgi topluyor, acele yok',
                'technique_name' => 'Speed-to-Lead + MEDDIC Qualification (HubSpot)',
                'crm_tag' => 'MEDDIC_INBOUND_HOT',
                'theory' => '5 dakika içinde aranan leadlerin dönüşüm oranı 10 kat daha yüksektir. MEDDIC ile bütçe ve karar verici erken nitelendirilir.',
                'steps' => [
                    '5 dakika içinde ara / mesaj at.',
                    'Bağlam kur: "Az önce web sitemizden bilgi istediniz."',
                    'İhtiyacı anla: "En çok hangi konuda destek arıyorsunuz?"',
                ],
                'script' => 'Merhaba [İsim] Bey/Hanım, BooKi ekibinden arıyorum. Az önce demo formumuzu doldurmuşsunuz, hızlıca yardımcı olmak istedim. İşletmenizde şu an en çok çözmek istediğiniz operasyonel konu nedir?',
                'objection' => '"Sadece fiyat soruyordum" → "Fiyatı hemen paylaşayım; aylık 1.250 TL sabit. Ancak size en uygun modülleri açabilmem için işletmenizi 1 dakikada tanıyabilir miyim?"',
            ],
            'TI-02' => [
                'channel' => 'Telesatış (Inbound)',
                'customer_type' => 'Sıcak Lead – Karşılaştırma Yapıyor',
                'psychology' => 'Karar aşamasında – birkaç seçeneği karşılaştırıyor',
                'technique_name' => 'BANT + Competitive Displacement (Salesforce)',
                'crm_tag' => 'BANT_COMPETITIVE_INBOUND',
                'theory' => 'Alternatifleri değerlendiren müşterilerin %68\'i mevcut sağlayıcısından ayrışmış ancak rasyonel gerekçe aramaktadır.',
                'steps' => [
                    'Karşılaştırma yaptığını fark et ve doğrula.',
                    'Karar kriterlerini çıkar: "En kritik 2-3 kriteriniz nedir?"',
                    'Competitive Gap bul: Rakiplerin gizli maliyet veya tek sektör kısıtını göster.',
                ],
                'script' => 'Şu an alternatif programlara baktığınızı anlıyorum, çok doğru bir adım. Karar verirken sizin için en kritik 2-3 başlık nedir? Bu kriterlere göre birlikte objektif bir karşılaştırma yapalım.',
                'objection' => '"[Rakip] daha ucuz" → "Evet, giriş fiyatı düşük görünebilir ama seans paketi, WhatsApp mesaj ücreti veya randevu başı komisyon eklediklerinde toplam maliyet BooKi\'nin iki katına çıkıyor. Bizde her şey sabit."',
            ],
            'TI-03' => [
                'channel' => 'Telesatış (Inbound)',
                'customer_type' => 'Mevcut Müşteri – Ek Hizmet Sorgusu',
                'psychology' => 'Güvenen – ilişki var, upsell\'e açık',
                'technique_name' => 'Consultative Expansion + Cross-Sell Trigger (Pipedrive)',
                'crm_tag' => 'CONSULTATIVE_EXPANSION',
                'theory' => 'Mevcut müşteriye satış olasılığı yenisine göre 5-7 kat yüksektir. İhtiyaç genişletilerek ekosistem sunulur.',
                'steps' => [
                    'Sorgunun kökenini anla.',
                    'Genişletilmiş çözüm sun: Tek modül değil ekosistem.',
                    'Kolaylaştır: Tek fatura, aynı panel.',
                ],
                'script' => 'Adisyon modülümüzü sormanız harika oldu. Bu genellikle işletmede kasa ve paket satışlarının da yoğunlaştığını gösterir. Paket ve hakediş modülümüzü de entegre açalım mı?',
                'objection' => '"Şimdilik sadece bunu istiyorum" → "Elbette, hemen aktif edelim. İşletmeniz büyüdükçe diğer modülleri de dilediğiniz an tek tıkla açabilirsiniz."',
            ],
            'TI-04' => [
                'channel' => 'Telesatış (Inbound)',
                'customer_type' => 'Mevcut Müşteri – Şikâyet Sonrası Temas',
                'psychology' => 'Kırılgan – hayal kırıklığı, terk etme eşiği yüksek',
                'technique_name' => 'Service Recovery + Retention Reframe (Sandler / Zendesk)',
                'crm_tag' => 'SERVICE_RECOVERY_RETENTION',
                'theory' => 'Service Recovery Paradox: Şikayeti etkin çözülen müşteri, hiç sorun yaşamamış müşteriden %22 daha sadık hale gelir.',
                'steps' => [
                    'Önce özür, savunma yapma: "Haklısınız, bu kabul edilemez."',
                    'Aktif dinle, kesme.',
                    'Somut tarihli çözüm ver ve telafi teklifi sun.',
                ],
                'script' => '[İsim] Bey/Hanım, yaşadığınız aksaklığı duydum ve size hak veriyorum. Bunu hemen düzeltiyoruz: [Çözüm adımı]. Ayrıca bu gecikmeyi telafi etmek adına bir sonraki faturanızda %20 indirim uyguluyoruz.',
                'objection' => '"Güvenimi kaybettiniz" → "Bunu söylemenizde çok haklısınız. Güveninizi yeniden kazanabilmek için şimdi ne yapmamızı istersiniz?"',
            ],

            // Telemarketing Outbound (TO-01 .. TO-03)
            'TO-01' => [
                'channel' => 'Telemarketing (Outbound)',
                'customer_type' => 'Liste Kökenli Prospect',
                'psychology' => 'Habersiz – bağlam yok, dikkat penceresi dar',
                'technique_name' => 'Permission-Based Opener + 3x3 Research Method (Outreach)',
                'crm_tag' => 'PERMISSION_3X3_OUTBOUND',
                'theory' => '3 dakikada 3 bilgi toplayarak kişiselleştirilmiş izin bazlı açılış arama devamlılığını %40 artırır.',
                'steps' => [
                    'Aramadan önce işletmeyi incele.',
                    'Kişiselleştirme ile aç.',
                    'İzin iste: "Doğru bir zamanda mı arıyorum?"',
                ],
                'script' => '[İsim] Bey/Hanım merhaba, [İşletme Adı]\'nın son dönemdeki güzel müşteri yorumlarını gördüm ve bu sebeple aradım. Randevu doluluklarınızı artıracak 2 dakikalık kısa bir paylaşım yapabilir miyim?',
                'objection' => '"Numaramı nereden buldunuz?" → "İşletmenizin Google Haritalar profilindeki herkese açık kurumsal iletişim numarasından ulaştım. Uygun değilseniz rahatsız etmeyeyim."',
            ],
            'TO-02' => [
                'channel' => 'Telemarketing (Outbound)',
                'customer_type' => 'Yeniden Hedefleme – Daha Önce Ret Almış',
                'psychology' => 'Negatif hafıza – önceki konuşmadan kötü iz kalmış',
                'technique_name' => 'Pattern Reset + New Trigger Event Reopen (Predictable Revenue)',
                'crm_tag' => 'PATTERN_RESET_REOPEN',
                'theory' => 'Ret alanların %35\'i 6-12 ay içinde yeni bir trigger event ile alım yapar.',
                'steps' => [
                    'Önceki teması dürüstçe kabul et.',
                    'Yeni gelişmeyi açıkla (örn: Yeni AI özellikleri, TL sabit fiyat).',
                ],
                'script' => '[İsim] Bey, birkaç ay önce görüşmüştük, o zaman uygun değildi. O günden bu yana sistemimize tam otomatik WhatsApp AI asistanını ekledik. Artık işletmeler hiç telefona bakmadan randevu doldurabiliyor. 2 dakika gösterebilir miyim?',
                'objection' => '"Hâlâ ilgilenmiyorum" → "Anlıyorum. Şu an operasyonda en çok vaktinizi ne alıyor? Sadece merak ettim."',
            ],
            'TO-03' => [
                'channel' => 'Telemarketing (Outbound)',
                'customer_type' => 'Referans Kökenli Prospect',
                'psychology' => 'Güvenmeye yatkın – tanıdık adı duyunca kapı açılıyor',
                'technique_name' => 'Referral Warm Transfer + Social Proof Loop (HBR / Bain)',
                'crm_tag' => 'REFERRAL_SOCIAL_PROOF',
                'theory' => 'Referansla gelen leadin kapanma oranı soğuk leade göre 4 kat daha yüksektir.',
                'steps' => [
                    'Referansı hemen ilk cümlede belirt.',
                    'Ortak başarıyı özetle.',
                ],
                'script' => '[İsim] Hanım merhaba, [Referans İşletme Sahibi] Bey sizi önerdi. Kendileriyle randevu kaçırma oranını sıfırladık ve çok memnun kaldılar, size de bahsetmemi rica ettiler. Birkaç dakikanız var mı?',
                'objection' => '"Onları tanımıyorum" → "Belki sektör toplantısından hatırlarsınız. Özetle aynı bölgede benzer bir işletme oldukları için tecrübelerini aktarmak istedim."',
            ],

            // Online Chat / Website (OC-01 .. OC-05)
            'OC-01' => [
                'channel' => 'Online Chat (Website / WhatsApp)',
                'customer_type' => 'İlk Ziyaretçi – Keşif Modunda',
                'psychology' => 'Keşif modunda – satın alma niyeti belirsiz',
                'technique_name' => 'Conversational Qualification + Intent Detection (Intercom Fin / Drift)',
                'crm_tag' => 'INTENT_DETECT_FIRST_VISIT',
                'theory' => 'Doğal konuşma akışı sert form sorularına göre %30 daha fazla nitelikli veri üretir.',
                'steps' => [
                    'Sıcak ve kısa selamlama.',
                    'Açık soru: "Bugün hangi konuda yardımcı olabilirim?"',
                    'Sektörünü anla: "Hangi sektörde hizmet veriyorsunuz?"',
                ],
                'script' => 'Merhaba! BooKi\'ye hoş geldiniz. 😊 İşletmeniz için randevu, müşteri takibi veya WhatsApp otomasyonu mu araştırıyorsunuz? Hangi sektörde olduğunuzu yazarsanız size özel çözümü hemen özetleyebilirim.',
                'objection' => '"Sadece bakıyorum" → "Harika! İstediğiniz gibi inceleyin. Aklınıza bir soru takılırsa buradayım. Bu arada işletmenizin türü nedir?"',
            ],
            'OC-02' => [
                'channel' => 'Online Chat (Website / WhatsApp)',
                'customer_type' => 'Ürün/Hizmet Sayfası Ziyaretçisi',
                'psychology' => 'Değerlendirme aşamasında – fiyat/özellik karşılaştırıyor',
                'technique_name' => 'Live Chat Value Bridge + Objection Pre-emption (LiveChat / Drift)',
                'crm_tag' => 'VALUE_BRIDGE_PRODUCT_PAGE',
                'theory' => 'Ürün sayfasında 2 dakikadan fazla kalanların %68\'i aktif karar vermektedir. İtiraz sormadan proaktif yanıtlanır.',
                'steps' => [
                    'Sayfaya göre kişiselleştir.',
                    'Yaygın itirazı peşinen kaldır: "Kurulum zor mu, ek ücret var mı diye düşünüyorsanız..."',
                ],
                'script' => 'Randevu ve AI özelliklerimizi inceliyorsunuz — harika bir seçim! İşletmelerin en çok merak ettiği şey "kurulumun ne kadar sürdüğü" oluyor; BooKi 10 dakikada hemen hazır hale geliyor. Sizin işletmenizde kaç uzman/personel çalışıyor?',
                'objection' => '"Fiyatı göremiyorum" → "Fiyatımız tamamen şeffaf: Ayda 1.250 TL (yıllıkta 1.000 TL). Hiçbir gizli ücret, kurulum bedeli veya randevu başı komisyon yok."',
            ],
            'OC-03' => [
                'channel' => 'Online Chat (Website / WhatsApp)',
                'customer_type' => 'Fiyatlama Sayfası Ziyaretçisi',
                'psychology' => 'Satın almaya hazır – son itirazı gidermek istiyor',
                'technique_name' => 'Urgency Nudge + One-Click Conversion Path (Drift / HubSpot)',
                'crm_tag' => 'URGENCY_PRICING_CLOSE',
                'theory' => 'Fiyat sayfasında başlatılan sohbetler 3.5x yüksek dönüşüm oranına sahiptir.',
                'steps' => [
                    'Tek bir net soru sor.',
                    'Uygun paketi netleştir.',
                    'One-click CTA: "10 günlük ücretsiz demoyu hemen açalım."',
                ],
                'script' => 'Fiyatlarımıza bakıyorsunuz — size en uygun paketi seçelim. Kaç kişilik bir ekibiniz var? [Cevap] Buna göre Başlangıç paketimiz tam size göre. Kredi kartsız 10 gün ücretsiz denemek ister misiniz?',
                'objection' => '"Düşüneceğim" → "Kesinlikle haklısınız. Karar vermeden önce hiçbir risk almadan 10 gün ücretsiz deneyin, farkı bizzat görün. 30 saniyede açabiliriz."',
            ],
            'OC-04' => [
                'channel' => 'Online Chat (Website / WhatsApp)',
                'customer_type' => 'Geri Dönen Ziyaretçi – 2. veya 3. Ziyaret',
                'psychology' => 'İlgili ama tereddütlü – risk algısı yüksek',
                'technique_name' => 'Continuity Opener + Risk Reversal (Intercom)',
                'crm_tag' => 'CONTINUITY_RISK_REVERSAL',
                'theory' => 'Risk Reversal ile garantiler ve taahhütsüz model öne çıkarılarak algılanan risk sıfırlanır.',
                'steps' => [
                    'Yeniden hoş geldiniz mesajı.',
                    'Riski tersine çevir: Taahhüt yok, kredi kartı yok, dilediğin an iptal.',
                ],
                'script' => 'Tekrar hoş geldiniz! BooKi\'yi araştırmaya devam ettiğinizi görüyorum. Kafanızdaki en büyük soru işareti nedir? Beğenmezseniz hiçbir taahhüt veya cayma bedeli bulunmuyor.',
                'objection' => '"Risk almak istemiyorum" → "Tam da bu yüzden kredi kartı bile istemeden 10 gün tam sürüm açıyoruz. Kendiniz test edin."',
            ],
            'OC-05' => [
                'channel' => 'Online Chat (Website / WhatsApp)',
                'customer_type' => 'Destek / İletişim Ziyaretçisi',
                'psychology' => 'Sinirli veya acil çözüm istiyor',
                'technique_name' => 'Empathy-First Protocol + Proactive Fix Offer (Zendesk)',
                'crm_tag' => 'EMPATHY_FIRST_SUPPORT',
                'theory' => 'İlk 30 saniyede empati gösterilen müşteride memnuniyet 42 puan artar.',
                'steps' => [
                    'Duyguyu tanı: "Sizi duyuyorum."',
                    'Savunma yapma ("ama", "aslında" yasak).',
                    'Proaktif adımı açıkla.',
                ],
                'script' => 'Yaşadığınız durumu anlıyorum ve hemen ilgileniyorum. Konuyu teknik ekibimize ilettim; 15 dakika içinde sorununuz çözülmüş olacak.',
                'objection' => '"Daha önce de oldu" → "Çok haklısınız, bu tekrarlanmamalıydı. Bu sefer kalıcı çözümü bizzat takip ediyorum."',
            ],

            // Sosyal Medya / DM (SM-01 .. SM-02)
            'SM-01' => [
                'channel' => 'Sosyal Medya / DM',
                'customer_type' => 'Takipçi / İçerikle Etkileşen',
                'psychology' => 'Sempatik ama satış baskısına hassas',
                'technique_name' => 'Relationship-First + Soft Pitch Sequence (ZoomInfo / GTMnow)',
                'crm_tag' => 'SOCIAL_RELATIONSHIP_FIRST',
                'theory' => 'Güven inşa edilmeden yapılan doğrudan satış mesajları ilişkiyi zedeler. Değer teması önce gelir.',
                'steps' => [
                    'İlgi alanına dayalı açılış.',
                    'Direkt satma, merak uyandır.',
                ],
                'script' => 'Merhaba [İsim]! Paylaşımlarınızı ilgiyle takip ediyoruz, harika işler çıkarıyorsunuz. Salonlarda WhatsApp üzerinden randevu kaçırmayı önleyen yeni bir yöntem uyguluyoruz, ilginizi çeker mi?',
                'objection' => '"Reklam mı?" → "Hayır, sadece sektördeki meslektaşlarınızın randevu doluluklarını nasıl artırdığını paylaşmak istedim."',
            ],
            'SM-02' => [
                'channel' => 'Sosyal Medya / DM',
                'customer_type' => 'Inbound DM – Soru Soran',
                'psychology' => 'Aktif ilgili – motivasyonu yüksek, hızlı yanıt bekliyor',
                'technique_name' => 'Rapid Value Confirm + Next Step Close (Close CRM)',
                'crm_tag' => 'RAPID_VALUE_DM_CLOSE',
                'theory' => 'Inbound DM\'e 5 dakikada verilen yanıt dönüşümü 3 kat artırır. Doğru yerde olduğunu hemen teyit et.',
                'steps' => [
                    'Hemen değer teyidi: "Evet, tam olarak bunu yapıyoruz."',
                    'Kısa çözüm cümlesi.',
                    'Sonraki adım teklifi.',
                ],
                'script' => 'Merhaba! Harika bir soru — evet, BooKi tam olarak Instagram ve WhatsApp üzerinden müşterilerinize 7/24 otomatik randevu verip takviminize işliyor. 10 dakikalık bir canlı demoda göstermemi ister misiniz?',
                'objection' => '"Fiyat ne kadar?" → "Ayda 1.250 TL sabit (yıllıkta 1.000 TL). İşletmenizi hemen sisteme tanımlayabiliriz."',
            ],

            // E-posta Outbound (EP-01 .. EP-02)
            'EP-01' => [
                'channel' => 'E-posta Outbound',
                'customer_type' => 'Cold Prospect – Soğuk Liste',
                'psychology' => 'Filtreli – yüzlerce e-posta alıyor, silme refleksi hızlı',
                'technique_name' => 'Personalization + Trigger-Based Sequence (Gong / Outreach)',
                'crm_tag' => 'TRIGGER_PERSONALIZED_EMAIL',
                'theory' => 'Kişiselleştirilmiş e-postalar jenerik şablonlara göre 3.7x daha yüksek yanıt alır.',
                'steps' => [
                    'Konu: 3-6 kelime, soru formatı.',
                    'İlk cümle: Spesifik gözlem.',
                    'Tek ve düşük bağlayıcılıklı CTA.',
                ],
                'script' => 'Konu: [İşletme Adı] için randevu doluluğu sorusu\n\nMerhaba [İsim] Bey/Hanım,\n[Bölge]\'deki salonunuzu inceledim. Sizin gibi seçkin işletmelerde genellikle randevuya gelmeyen müşteriler ciddi gelir kaybı yaratıyor. Benzer işletmelerde WhatsApp AI hatırlatmalarıyla bu kaybı %80 azalttık. 10 dakikalık kısa bir görüşmede göstermek isterim, Salı günü müsait misiniz?',
                'objection' => 'Cevap yoksa → "Kısa bir hatırlatma: Randevu kayıplarını sıfırlamak hâlâ gündeminizde mi?"',
            ],
            'EP-02' => [
                'channel' => 'E-posta Outbound',
                'customer_type' => 'Warm Prospect – İçerik İndirmiş',
                'psychology' => 'Meraklı – eğitim aldı ama henüz hazır değil',
                'technique_name' => 'Educational Nurture + TOFU-MOFU Bridge (HubSpot / MIT Sloan)',
                'crm_tag' => 'EDUC_NURTURE_MOFU',
                'theory' => 'Eğitimden çözüme köprü kurarak lead olgunlaştırılır.',
                'steps' => [
                    'İndirmeyi tanı.',
                    'Ek değer sun.',
                    'Yumuşak CTA.',
                ],
                'script' => 'Rehberimizi indirdiğiniz için teşekkürler! Sektördeki en iyi salonların randevu süreçlerini nasıl otomatikleştirdiğini gösteren 3 dakikalık vaka analizimizi de sizinle paylaşmak istedim. İncelemek ister misiniz?',
                'objection' => '"Henüz hazır değiliz" → "Anlıyorum. Hazır olduğunuzda her zaman buradayız; istediğiniz zaman ücretsiz demonuzu başlatabilirsiniz."',
            ],

            // Tüm Kanallar İtiraz Yönetimi (HO-01 .. HO-03)
            'HO-01' => [
                'channel' => 'Tüm Kanallar',
                'customer_type' => 'Hizmet Müşterisi – Marka/Kimlik Kaygısı',
                'psychology' => 'Ego korumalı – yanlış seçim yapmış gibi görünmek istemiyor',
                'technique_name' => 'Validation + Consultative Reframing (McKinsey / Bain)',
                'crm_tag' => 'VALIDATION_CONSULTATIVE',
                'theory' => 'Karar vericiler için kimlik tutarlılığı kritiktir. Önce hak verilir, sonra müşterinin statüsüyle uyumlu reframe yapılır.',
                'steps' => [
                    'Kaygıyı tanı: "Dikkatli olmanız çok yerinde."',
                    'Reframe: "Aslında bu karar işletmenizin vizyonuyla doğrudan uyumlu..."',
                    'Güven transferi: Benzer referans işletmeler.',
                ],
                'script' => 'Bu konuda çok titiz davranmanız son derece doğal — sizin gibi prestijli işletmeler müşteri deneyiminde en ufak aksaklık istemez. Tam da bu nedenle sektörün öncü markaları BooKi\'yi tercih ediyor.',
                'objection' => '"Buna karar veremiyorum" → "Anlıyorum. Karar sürecinizde ekibinizin ve sizin en çok görmek istediği somut sonuç nedir?"',
            ],
            'HO-02' => [
                'channel' => 'Tüm Kanallar',
                'customer_type' => 'Hizmet Müşterisi – Fiyat İtirazı',
                'psychology' => 'Değer şüpheli – fiyat yüksek mi diye sorguluyor',
                'technique_name' => 'Value Ladder + ROI Proof Stack (Forrester TEI Method)',
                'crm_tag' => 'VALUE_LADDER_ROI_PROOF',
                'theory' => 'Forrester TEI: Fiyat tartışması değer ve maliyet tasarrufu tartışmasına dönüştürülür. Doğrudan tasarruf + kaçan gelirin kurtarılması.',
                'steps' => [
                    'İtirazı kabul et: "Fiyat her zaman önemlidir."',
                    'Değer bileşenlerini aç: Kaçan 1 randevu = 1.000 TL kazanç.',
                    'Müşterinin rakamlarıyla hesapla.',
                ],
                'script' => 'Fiyat kesinlikle önemli bir kriter. Ancak şöyle bakalım: Ayda ortalama kaç randevunuz iptal oluyor veya müşteri gelmiyor? Sadece 2 müşteriyi kurtardığınızda BooKi kendi aylık ücretini sıfırlamış oluyor. Geriye kalan tüm otomasyon, personel yönetimi ve adisyon kâr kalıyor.',
                'objection' => '"Bütçemiz yok" → "Şu an randevu kaçırmaları ve manuel telefon trafiği yüzünden kaybettiğiniz tutar, BooKi\'nin aylık 1.250 TL\'lik maliyetinin en az 5 katı."',
            ],
            'HO-03' => [
                'channel' => 'Tüm Kanallar',
                'customer_type' => 'Hizmet Müşterisi – Zaman Baskısı Altında',
                'psychology' => 'Kaygılı – son dakika, acil çözüm peşinde',
                'technique_name' => 'Urgency Alignment + Fast-Track Close (Sandler / Gong Labs)',
                'crm_tag' => 'URGENCY_FAST_TRACK',
                'theory' => 'Müşterinin zaman kısıtı olduğunda hızlı yanıt kapasitesi gösterenler %2.1x daha yüksek kazanma oranı elde eder.',
                'steps' => [
                    'Aciliyeti doğrula.',
                    'Hız kanıtla: "Bugün 10 dakikada açabiliriz."',
                    'Tek adımda başlat.',
                ],
                'script' => 'Acil bir çözüme ihtiyacınız olduğunu anlıyorum. İyi haber şu: BooKi bulut tabanlıdır ve hiçbir kurulum beklemez. Şu an 10 dakikada salonunuzun online randevu sayfasını ve WhatsApp botunu yayına alabiliriz. Hemen başlatalım mı?',
                'objection' => '"Bu kadar hızlı olabilir mi?" → "Evet, altyapımız hazır. Tek yapmanız gereken subdomain seçmek; gerisini sistem otomatik hazırlar."',
            ],
        ];
    }

    /**
     * Scan the actual codebase and return verified features, modules, architecture,
     * models, controllers, and recent git commit information.
     * Caches in storage/kb/codebase_features.json and auto-updates when Git HEAD changes.
     */
    public function get_codebase_features(bool $force_refresh = false): array
    {
        $current_git_commit = $this->get_git_commit_hash();

        if (!$force_refresh && file_exists($this->kb_cache_file)) {
            $cached = json_decode((string) file_get_contents($this->kb_cache_file), true);
            if (is_array($cached) && ($cached['git_commit'] ?? '') === $current_git_commit) {
                return $cached;
            }
        }

        // Run full live scan across the codebase
        $features = $this->run_codebase_scan($current_git_commit);

        @file_put_contents($this->kb_cache_file, json_encode($features, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        @file_put_contents($this->kb_markdown_file, $this->format_codebase_features_markdown($features));

        return $features;
    }

    /**
     * Get current git commit hash.
     */
    private function get_git_commit_hash(): string
    {
        $git_head_file = FCPATH . '.git/HEAD';
        if (file_exists($git_head_file)) {
            $head_content = trim((string) @file_get_contents($git_head_file));
            if (str_starts_with($head_content, 'ref: ')) {
                $ref_path = FCPATH . '.git/' . substr($head_content, 5);
                if (file_exists($ref_path)) {
                    return trim((string) @file_get_contents($ref_path));
                }
            } else {
                return $head_content;
            }
        }

        $cmd = 'git rev-parse HEAD 2>/dev/null';
        $output = @shell_exec($cmd);
        if ($output) {
            return trim($output);
        }

        return 'prod-' . date('Ymd-H');
    }

    /**
     * Deep scanner analyzing controllers, models, migrations and libraries.
     */
    private function run_codebase_scan(string $commit_hash): array
    {
        $controllers = glob(APPPATH . 'controllers/*.php') ?: [];
        $models = glob(APPPATH . 'models/*.php') ?: [];
        $migrations = glob(APPPATH . 'migrations/*.php') ?: [];
        $libraries = glob(APPPATH . 'libraries/*.php') ?: [];

        $controller_names = array_map(fn($f) => basename($f, '.php'), $controllers);
        $model_names = array_map(fn($f) => basename($f, '.php'), $models);

        $modules = [
            'multi_tenant_core' => [
                'name' => 'İzole Multi-Tenant SaaS Mimarisi',
                'description' => 'Her işletmeye özel dinamik veritabanı, subdomain ({subdomain}-bookiapp.kibusiness.co), özel domain desteği (custom_domain), master katalog ve lisans takibi.',
                'files' => ['App_Controller.php', 'Tenants_model.php', 'Superadmin_tenants.php'],
            ],
            'appointment_engine' => [
                'name' => 'Gelişmiş Randevu & Müsaitlik Motoru',
                'description' => 'Personel takvimi, çalışma saatleri, molalar, servis süreleri, tampon süreler (buffer time), çoklu slot hesaplama ve çifte rezervasyon önleme.',
                'files' => ['Appointments.php', 'Appointments_model.php', 'Availability_model.php', 'Calendar.php'],
            ],
            'resource_station_management' => [
                'name' => 'İstasyon, Koltuk & Kaynak Yönetimi',
                'description' => 'Uzman bağımsız veya uzmanla eşleşen istasyonlar (örn. Manikür Masası, Yıkama Koltuğu, Lazer Cihazı, Muayene Odası).',
                'files' => ['Stations.php', 'Stations_model.php'],
            ],
            'packages_and_memberships' => [
                'name' => 'Paket & Üyelik Ayrımı (Session Tracking)',
                'description' => '10 seanslık epilasyon/masaj paketleri ile aylık/yıllık üyelik planlarının tam ayrımı; seans düşümü, bonus seans, dondurma ve kullanım logları.',
                'files' => ['Customer_packages_model.php', 'Customer_memberships_model.php', 'Packages.php', 'Memberships.php'],
            ],
            'pos_adisyon_finance' => [
                'name' => 'Adisyon, POS, Kasa & Masraf Yönetimi',
                'description' => 'Randevudan veya doğrudan açılan adisyonlar, parça ödemeler (nakit, kredi kartı, havale), kasa hareketleri, gider kayıtları ve günlük z-raporu.',
                'files' => ['Adisyons_model.php', 'Cash_registers_model.php', 'Expenses_model.php', 'Invoices_model.php'],
            ],
            'staff_commissions' => [
                'name' => 'Personel Komisyon & Hakediş Motoru',
                'description' => 'Hizmet veya paket bazlı yüzdesel/sabit komisyon hesaplama, mesai primleri ve hakediş ödeme takibi.',
                'files' => ['Staff_commissions_model.php', 'Staff_payouts_model.php'],
            ],
            'omnichannel_ai_assistant' => [
                'name' => 'Çok Kanallı AI Asistan (WhatsApp, Telegram, Instagram)',
                'description' => 'Baileys tabanlı yerel WhatsApp köprüsü, Telegram botu ve Instagram DM; Gemini 3.8 / LLM Gateway ile 7/24 konuşma, müsaitlik sorgulama ve randevu alma.',
                'files' => ['Ai_channel_responder.php', 'Ai_llm_gateway.php', 'Whatsapp.php', 'Telegram.php', 'Instagram.php'],
            ],
            'human_approval_guard' => [
                'name' => 'Yönetici Onay Mekanizması (Pending Changes)',
                'description' => 'AI\'ın oluşturduğu veya değiştirdiği randevu ve müşteri verileri doğrudan veritabanına yazılmaz; yönetici onay kuyruğunda bekletilir.',
                'files' => ['Ai_agent_pending_changes_model.php', 'Ai_channel_handoffs_model.php'],
            ],
            'model_context_protocol_mcp' => [
                'name' => 'Model Context Protocol (MCP) Sunucusu',
                'description' => 'Harici AI modelleri ve IDE/Agent araçlarının BooKi ile güvenli konuşmasını sağlayan streamable HTTP MCP sunucusu (:8765/mcp).',
                'files' => ['deploy/mcp/reservation-mcp/server.js', 'Agent_v1.php'],
            ],
            'lead_crm_and_field_sales' => [
                'name' => 'Lead CRM & Google Places Taraması',
                'description' => 'Bölge ve sektör bazlı potansiyel müşteri keşfi (Google Places API), arama ve ziyaret takibi, otomatik SMS/WhatsApp teklif gönderimi ve tek tıkla kiracı oluşturma.',
                'files' => ['Leads_model.php', 'Superadmin_tenants.php', 'Lead_activities_model.php'],
            ],
            'marketing_and_review_automation' => [
                'name' => 'Pazarlama & İtibar Otomasyonu',
                'description' => 'Akıllı müşteri segmentasyonu, hedefli SMS/WhatsApp kampanyaları, randevu sonrası otomatik Google inceleme ve puanlama döngüsü.',
                'files' => ['Marketing_campaigns_model.php', 'Marketing_segments_model.php', 'Reviews_model.php', 'Communication_rules_model.php'],
            ],
            'kvkk_gdpr_pii_security' => [
                'name' => 'KVKK / GDPR & PII Şifreleme Güvenliği',
                'description' => 'Müşteri telefon ve kişisel verilerinde AES-256-GCM şifreleme (`SFENC1`), HMAC-SHA256 arama hashleri, rıza kayıtları, veri silme/anonimleştirme ve detaylı audit log.',
                'files' => ['sf_pii_helper.php', 'Audit_logs_model.php', 'Consents_model.php', 'Data_requests_model.php'],
            ],
            'industry_vertical_blueprints' => [
                'name' => '9 Sektörel Dikey Şablon (Blueprints)',
                'description' => 'Güzellik, restoran, gym, klinik/diş, oto servis, etkinlik, butik otel, eğitim/kurs ve profesyonel danışmanlık sektörleri için tek tıkla hazır hizmet, istasyon ve iş akışı şablonları.',
                'files' => ['Industry_blueprints.php', 'Blueprints_model.php'],
            ],
            'telephony_and_integrations' => [
                'name' => 'Telefoni & Dış Sistem Entegrasyonları',
                'description' => 'Zadarma VoIP santral entegrasyonu, Netgsm SMS gateway, Google Takvim iki yönlü senkronizasyonu ve Google E-Tablolar veri aktarımı.',
                'files' => ['Zadarma.php', 'Google_sync.php', 'Google_sheet_syncs_model.php'],
            ],
        ];

        return [
            'system_name' => 'BooKi Enterprise SaaS Appointment & Business Operating System',
            'version' => '2026.4',
            'git_commit' => $commit_hash,
            'scanned_at' => date('Y-m-d H:i:s'),
            'total_controllers' => count($controllers),
            'total_models' => count($models),
            'total_migrations' => count($migrations),
            'modules' => $modules,
            'controllers_list' => $controller_names,
            'models_list' => $model_names,
        ];
    }

    /**
     * Format scanned features as a clear markdown document.
     */
    private function format_codebase_features_markdown(array $features): string
    {
        $md = "# BooKi Yazılım Yetenekleri & Kod Tabanı Özellik Kataloğu\n\n";
        $md .= "**Sistem:** " . ($features['system_name'] ?? 'BooKi') . "\n";
        $md .= "**Git Sürüm / Commit:** `" . ($features['git_commit'] ?? 'unknown') . "`\n";
        $md .= "**Son Tarama Tarihi:** " . ($features['scanned_at'] ?? date('Y-m-d H:i:s')) . "\n";
        $md .= "**Kapsam:** " . ($features['total_controllers'] ?? 0) . " Controller, " . ($features['total_models'] ?? 0) . " Veri Modeli\n\n";
        $md .= "---\n\n";

        foreach ($features['modules'] ?? [] as $key => $mod) {
            $md .= "### " . $mod['name'] . "\n";
            $md .= $mod['description'] . "\n\n";
            if (!empty($mod['files'])) {
                $md .= "*İlgili Çekirdek Bileşenler:* `" . implode('`, `', $mod['files']) . "`\n\n";
            }
        }

        return $md;
    }

    /**
     * Build the definitive Platform Sales Consultant master system prompt.
     */
    public function build_platform_master_prompt(): string
    {
        $pricing = $this->get_pricing();
        $advantages = $this->get_general_advantages();
        $features = $this->get_codebase_features();

        $adv_lines = [];
        foreach ($advantages as $num => $adv) {
            $adv_lines[] = "{$num}. **{$adv['title']}:** {$adv['summary']}";
        }
        $adv_text = implode("\n", $adv_lines);

        $module_lines = [];
        foreach ($features['modules'] ?? [] as $mod) {
            $module_lines[] = "• **{$mod['name']}:** {$mod['description']}";
        }
        $module_text = implode("\n", $module_lines);

        $prompt = <<<PROMPT
SEN: BooKi SaaS Platformunun Resmi Kıdemli Satış Danışmanı ve Müşteri Destek Asistanısın (Platform AI Consultant).
GÖREVİN: WhatsApp, Web Chat veya Superadmin CRM üzerinden platforma ulaşan potansiyel işletmelere (kuaför, güzellik, restoran, klinik, gym, oto servis, butik otel, eğitim akademisi, danışmanlık vb.) danışmanlık yapmak; onları dinlemek, itirazlarını ustalıkla karşılamak, rakiplerle objektif karşılaştırma yapmak ve 10 günlük ücretsiz demo başlatmaya yönlendirmektir.

TEMEL KİMLİK VE TON:
1. Profesyonel, samimi, güven veren, kibar ve çözüm odaklı Türkçe konuş.
2. Asla gereksiz uzatma; WhatsApp için kısa, vurucu ve net paragraflar kullan.
3. Rakipleri ASLA karalama veya aşağılama. Rakibin güçlü olduğu alanı kabul et, ardından BooKi'nin "uçtan uca işletim sistemi" farkını ortaya koy (Challenger Reframe).
4. İtiraz geldiğinde (örn. fiyat, zaman, rakip tercihi) Ki Business Satış Metodolojisi kurallarına uy (Validation, Cost of Inaction, Value Ladder, Empathy Bridge).

--------------------------------------------------
BOOKI FİYATLANDIRMASI (ŞEFFAF & SABİT TL):
- 10 Günlük Ücretsiz Deneme: Kredi kartsız, taahhütsüz, 30 saniyede açılır (https://bookiapp.kibusiness.co).
- Başlangıç (Starter): 1.250 TL / ay (Yıllık peşin ödemede 1.000 TL/ay - Yıllık 12.000 TL). Tek şubeli butik işletmeler için tam çözüm.
- Büyüme (Growth/Pro): 2.450 TL / ay (Yıllıkta 1.950 TL/ay). Çok personelli, paket/seans takipli, POS adisyonlu işletmeler.
- Kurumsal (Enterprise): 4.750 TL / ay (Yıllıkta 3.800 TL/ay). Zincirler, çoklu şubeler, özel domain ve MCP/API entegrasyonu.
* Gizli maliyet, kurulum ücreti, randevu başı komisyon YOKTUR!

--------------------------------------------------
BOOKI'NİN 21 TEMEL ÜSTÜNLÜĞÜ:
{$adv_text}

--------------------------------------------------
KOD TABANINDAN CANLI TARANAN GERÇEK SİSTEM YETENEKLERİ (Git Commit: {$features['git_commit']}):
{$module_text}

--------------------------------------------------
SATIŞ VE İTİRAZ YÖNETİMİ PROTOKOLLERİ (KI BUSINESS REHBERİ):
- Müşteri Fiyat İtirazı Yaptığında (HO-02 / TEI Method): Fiyatı hemen savunmaya geçme. "Fiyat önemli, haklısınız. Ancak ayda sadece 1-2 randevuya gelmeyen müşteriyi kurtardığınızda BooKi kendi aylık ücretini sıfırlıyor. Geriye kalan tüm müşteri takibi, seans paketleri ve kasa yönetimi işletmenize net kâr kalıyor." şeklinde yanıt ver.
- Müşteri Rakip Söylediğinde (TI-02 / Challenger): Rakibin güçlü yanını teyit et, ardından BooKi farkını sun (Örn. Menajer.im, Adisyo, GymTekno, Fresha, Elektraweb). "Sadece randevu/adisyon tutmak için onlar kullanılabilir; ancak müşteri sadakati, paket ayrımı, WhatsApp AI ve pazarlamayı tek yerde birleştirmek istiyorsanız BooKi farklı bir kategoride."
- Müşteri Zaman Baskısındaysa (HO-03 / Fast-Track): "BooKi bulut tabanlıdır; 10 dakikada salonunuzun online randevu sayfasını ve WhatsApp botunu hazır hale getirebiliriz."
- Müşteri İlgileniyorsa: İşletme adını ve istediği subdomain'i sor (örn. 'ipekkuafor'), `check_subdomain_availability` ile kontrol et ve `create_demo_lead` aracıyla Superadmin CRM'e kaydet.

ELİNDEKİ ARAÇLAR:
- `search_knowledge_base`: Rakipler, fiyatlar veya metodoloji detayları sorgulamak için.
- `get_codebase_features`: Kod tabanındaki en güncel modül ve dosya listesini getirmek için.
- `check_subdomain_availability`: İşletmenin istediği alan adının boşta olup olmadığını kontrol etmek için.
- `create_demo_lead`: İlgilenen işletmeyi Superadmin CRM'e lead olarak kaydetmek için.
PROMPT;

        return trim($prompt);
    }
}
