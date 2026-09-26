export interface CompetitorComparison {
  name: string;
  category: "Yerli Yazılımlar" | "Global Randevu Sistemleri" | "Restoran / Masa Rezervasyon";
  commissionRate: string;
  sectorsCovered: string;
  whatsappSupport: string;
  pricingModel: string;
  pros: string[];
  cons: string[];
  bookiAdvantage: string;
}

export const COMPETITORS: CompetitorComparison[] = [
  {
    name: "SalonAppy",
    category: "Yerli Yazılımlar",
    commissionRate: "%0 (Fakat pahalı eklentiler)",
    sectorsCovered: "Sadece Kuaför & Güzellik",
    whatsappSupport: "Ekstra ücretli (SalonWP modülü)",
    pricingModel: "Aylık paket + Her ek modüle ayrı ücret",
    pros: ["Kuaför sektöründe eski bilinirlik", "Geniş dil desteği"],
    cons: ["Restoran ve klinik desteği sıfır", "Eski PHP altyapı ve arayüz", "WhatsApp, SMS, Fatura için ayrı ayrı ücret talep eder"],
    bookiAdvantage: "Çok sektörlü esneklik (Restoran, klinik, salon tek yerde), modern Bento UI ve her şey dahil şeffaf fiyatlandırma."
  },
  {
    name: "KolayRandevu",
    category: "Yerli Yazılımlar",
    commissionRate: "Rezervasyon başı yüksek komisyon / paket",
    sectorsCovered: "Güzellik salonu, berber",
    whatsappSupport: "Sınırlı SMS",
    pricingModel: "Pazaryeri komisyonu + listeleme",
    pros: ["Tüketici tarafında arama dizini"],
    cons: ["Müşteriyi işletmeye değil pazaryerine bağlar", "Rakiplerinizi aynı sayfada listeler", "Yüksek işlem kesintileri"],
    bookiAdvantage: "İşletmenin kendi bağımsız markasıyla çalışır, müşteri verisi işletmede kalır, %0 komisyon."
  },
  {
    name: "Fresha (Shedul)",
    category: "Global Randevu Sistemleri",
    commissionRate: "Yeni müşterilerden %20 komisyon + %2.19 kart komisyonu",
    sectorsCovered: "Güzellik, spa, masaj",
    whatsappSupport: "Pahalı SMS kredileri",
    pricingModel: "Ücretsiz görünür ama yüksek işlem komisyonu",
    pros: ["Global pazar bilinirliği", "Çoklu para birimi"],
    cons: ["Pazaryerinden gelen her yeni müşteriden %20 keser", "Kendi getirdiğiniz müşteriden bile ödeme komisyonu alır", "Türkçe destek zayıf"],
    bookiAdvantage: "Sabit ve şeffaf Türk Lirası fiyatı, %0 komisyon, doğrudan Türkçe WhatsApp ve telefon desteği."
  },
  {
    name: "Restorano",
    category: "Restoran / Masa Rezervasyon",
    commissionRate: "Kapak / Rezervasyon başı ücret",
    sectorsCovered: "Sadece Restoran & Kafe",
    whatsappSupport: "Sınırlı",
    pricingModel: "Masa başı / Rezervasyon başı",
    pros: ["Restoran krokisi"],
    cons: ["Klinik, güzellik ve uzman randevusu desteği yok", "Yüksek kurulum maliyeti", "Sınırlı entegrasyon"],
    bookiAdvantage: "Restoran masa yönetimini güzellik, klinik ve diğer tüm işletme kaynaklarıyla aynı modern platformda sunar."
  },
  {
    name: "Rezervem",
    category: "Restoran / Masa Rezervasyon",
    commissionRate: "Aylık yüksek sabit + kurulum",
    sectorsCovered: "Lüks restoranlar",
    whatsappSupport: "SMS odaklı",
    pricingModel: "Yüksek giriş maliyeti",
    pros: ["Fine-dining odaklı"],
    cons: ["KOBİ ve küçük kafeler için aşırı pahalı", "Karmaşık yönetim paneli", "Güzellik/sağlık desteği yok"],
    bookiAdvantage: "Ekonomik, 15 dakikada kurulan, KOBİ dostu modern bulut masa yönetim sistemi."
  },
  {
    name: "Rezervasyonline",
    category: "Yerli Yazılımlar",
    commissionRate: "Paket bazlı",
    sectorsCovered: "Genel randevu",
    whatsappSupport: "Yetersiz",
    pricingModel: "Sabit paket + SMS kontörü",
    pros: ["Yerli yazılım"],
    cons: ["Eski nesil kullanıcı deneyimi", "Mobil optimizasyon eksiklikleri", "Yapay zeka (GEO) uyumu yok"],
    bookiAdvantage: "2026 UI/UX standartları, akıllı bekleme listesi ve kusursuz mobil deneyim."
  },
  {
    name: "Calendly",
    category: "Global Randevu Sistemleri",
    commissionRate: "%0",
    sectorsCovered: "Ofis toplantıları & danışmanlık",
    whatsappSupport: "Yok (Zapier gerekir)",
    pricingModel: "Kullanıcı başı USD",
    pros: ["B2B toplantılarında hızlı"],
    cons: ["Masa, oda, cihaz ve kuaför koltuğu yönetimi yapamaz", "Restoran ve salon operasyonuna tamamen uyumsuz", "Döviz bazlı pahalı"],
    bookiAdvantage: "Fiziksel mekanlar, odalar, masalar ve uzmanlar için özel optimize edilmiş çok kaynaklı randevu motoru."
  },
  {
    name: "SimplyBook.me",
    category: "Global Randevu Sistemleri",
    commissionRate: "%0",
    sectorsCovered: "Genel hizmetler",
    whatsappSupport: "Özel eklentiyle (karmaşık)",
    pricingModel: "Modül başı ek ücretler",
    pros: ["Çok sayıda eklenti"],
    cons: ["Her temel özellik için ayrı eklenti satın alma zorunluluğu", "Karmaşık arayüz", "Türkiye'de yerel destek yok"],
    bookiAdvantage: "Her şey dahil paketler, sıfır gizli eklenti ücreti ve kurulumda ödeme garantisi."
  },
  {
    name: "Treatwell",
    category: "Global Randevu Sistemleri",
    commissionRate: "Yeni müşteriden %25-35 komisyon",
    sectorsCovered: "Güzellik ve saç",
    whatsappSupport: "Sadece e-posta / SMS",
    pricingModel: "Yüksek komisyonlu pazaryeri",
    pros: ["Avrupa'da yaygın"],
    cons: ["Aşırı yüksek komisyon", "Müşteri bilgilerini kendine saklar", "Türkiye pazarına uzak"],
    bookiAdvantage: "%0 komisyon garantisi ve işletmenin kendi müşteri portföyünü koruma güvencesi."
  },
  {
    name: "Phorest",
    category: "Global Randevu Sistemleri",
    commissionRate: "%0",
    sectorsCovered: "Salon ve spa zincirleri",
    whatsappSupport: "Sınırlı",
    pricingModel: "Çok yüksek aylık lisans + donanım",
    pros: ["Gelişmiş pazarlama araçları"],
    cons: ["Küçük ve orta ölçekli işletmeler için bütçe dışı", "Uzun süreli sözleşme zorunluluğu", "Yerel dil/fatura desteği yok"],
    bookiAdvantage: "Taahhütsüz aylık planlar, 14 gün ücretsiz deneme ve uygun KOBİ fiyatları."
  },
  {
    name: "Vagaro",
    category: "Global Randevu Sistemleri",
    commissionRate: "Abonelik + işlem kesintisi",
    sectorsCovered: "Fitness, salon, spa",
    whatsappSupport: "Yok",
    pricingModel: "Dolar bazlı + eklenti ücreti",
    pros: ["Kapsamlı özellikler"],
    cons: ["Türkiye ödeme ve fatura sistemleriyle entegre değil", "Döviz kuru riski", "İngilizce destek"],
    bookiAdvantage: "Türk Lirası ile sabit fiyat, yerli mevzuat ve KVKK tam uyumu."
  },
  {
    name: "Mindbody",
    category: "Global Randevu Sistemleri",
    commissionRate: "Yüksek paket ücretleri + ödeme komisyonu",
    sectorsCovered: "Büyük spor salonları & zincir spalar",
    whatsappSupport: "Yok",
    pricingModel: "Çok pahalı kurumsal paketler ($300-$800/ay)",
    pros: ["Büyük zincirler için kurumsal altyapı"],
    cons: ["Aşırı pahalı", "Öğrenmesi ve kurulumu haftalar sürer", "Ağır ve karmaşık yazılım"],
    bookiAdvantage: "15 dakikada kurulan, hafif, hızlı ve ekonomik modern bulut çözümü."
  },
  {
    name: "Timely",
    category: "Global Randevu Sistemleri",
    commissionRate: "%0",
    sectorsCovered: "Güzellik ve kuaför",
    whatsappSupport: "Yalnızca SMS",
    pricingModel: "Kullanıcı başı döviz",
    pros: ["Şık tasarım"],
    cons: ["Dolar kuruyla sürekli artan maliyet", "Restoran masa desteği yok", "Türkiye desteği yok"],
    bookiAdvantage: "Sabit TL fiyatları, hem masa hem randevu desteği, 7/24 WhatsApp destek."
  },
  {
    name: "Acuity Scheduling",
    category: "Global Randevu Sistemleri",
    commissionRate: "%0",
    sectorsCovered: "Bireysel danışmanlar",
    whatsappSupport: "Yok",
    pricingModel: "Aylık USD",
    pros: ["Squarespace entegrasyonu"],
    cons: ["Masa yönetimi ve salon koltuk takibi yok", "Türkçe arayüz ve SMS entegrasyonu zayıf"],
    bookiAdvantage: "Türkiye'ye özel WhatsApp onay akışı ve fiziksel işletmelere tam uyum."
  },
  {
    name: "Booksy",
    category: "Global Randevu Sistemleri",
    commissionRate: "Aylık ücret + yeni müşteri komisyonu",
    sectorsCovered: "Berber, kuaför",
    whatsappSupport: "Uygulama içi bildirim",
    pricingModel: "Pazaryeri modeli",
    pros: ["Mobil uygulama bilinirliği"],
    cons: ["Müşteriyi kendi uygulamasına çekmeye çalışır", "Restoran/klinik desteği yok"],
    bookiAdvantage: "Müşteriyi uygulama indirmeye zorlamayan pürüzsüz web randevu akışı."
  },
  {
    name: "Resy (American Express)",
    category: "Restoran / Masa Rezervasyon",
    commissionRate: "Aylık $249-$899 + komisyon",
    sectorsCovered: "Restoranlar",
    whatsappSupport: "Yok",
    pricingModel: "Aşırı pahalı USD",
    pros: ["Global lüks restoran ağı"],
    cons: ["Türkiye'deki işletmeler için gereksiz pahalı", "Sadece restoran", "Yerel destek yok"],
    bookiAdvantage: "Her ölçekteki kafe ve restorana uygun, ekonomik ve Türkçe çözüm."
  },
  {
    name: "OpenTable",
    category: "Restoran / Masa Rezervasyon",
    commissionRate: "Kapak başı $1 - $2.50 komisyon",
    sectorsCovered: "Restoranlar",
    whatsappSupport: "Yok",
    pricingModel: "Aylık $249 + rezervasyon başı komisyon",
    pros: ["Global turist trafiği"],
    cons: ["Aylık binlerce dolar komisyon faturası çıkarır", "Yalnızca restoran"],
    bookiAdvantage: "%0 rezervasyon komisyonu ile yıllık yüz binlerce lira tasarruf."
  },
  {
    name: "Quandoo",
    category: "Restoran / Masa Rezervasyon",
    commissionRate: "Rezervasyon başı yüksek komisyon",
    sectorsCovered: "Restoranlar",
    whatsappSupport: "Yok",
    pricingModel: "Komisyonlu model",
    pros: ["Pazaryeri görünürlüğü"],
    cons: ["Sürekli komisyon maliyeti", "İşletme kendi müşterisi için bile komisyon öder"],
    bookiAdvantage: "Doğrudan işletmenize ait rezervasyon linki ve sıfır komisyon."
  },
  {
    name: "Setmore",
    category: "Global Randevu Sistemleri",
    commissionRate: "%0",
    sectorsCovered: "Genel randevu",
    whatsappSupport: "Sınırlı",
    pricingModel: "Kullanıcı başı USD",
    pros: ["Temel ücretsiz plan"],
    cons: ["Gelişmiş özellikler kilitli", "WhatsApp onay akışı Türkiye'de çalışmıyor", "Restoran desteği yok"],
    bookiAdvantage: "Tam fonksiyonel WhatsApp otomasyonu ve yerel sektörel uyumluluk."
  },
  {
    name: "Appointlet",
    category: "Global Randevu Sistemleri",
    commissionRate: "%0",
    sectorsCovered: "Bireysel toplantılar",
    whatsappSupport: "Yok",
    pricingModel: "USD bazlı",
    pros: ["Basit arayüz"],
    cons: ["Sadece takvim toplantısı yapar; salon, restoran veya klinik yönetemez", "Türkçe destek yok"],
    bookiAdvantage: "Tüm sektörlere özel şablonlar, oda/ekipman tahsisi ve uçtan uca CRM."
  }
];
