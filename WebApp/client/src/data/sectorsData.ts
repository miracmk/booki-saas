export interface SectorInfo {
  slug: string;
  title: string;
  metaTitle: string;
  metaDescription: string;
  keywords: string[];
  heroBadge: string;
  heroHeadline: string;
  heroSubheadline: string;
  heroDescription: string;
  stats: { value: string; label: string }[];
  painPoints: { problem: string; solution: string }[];
  keyFeatures: { title: string; desc: string; icon: string }[];
  workflowSteps: { step: string; title: string; desc: string }[];
  faqs: { question: string; answer: string }[];
}

export const SECTORS: Record<string, SectorInfo> = {
  "restoran-masa-rezervasyon": {
    slug: "restoran-masa-rezervasyon",
    title: "Restoran & Kafe Masa Rezervasyon Sistemi",
    metaTitle: "Restoran Masa Rezervasyon Sistemi — Komisyonsuz Masa Yönetimi | BooKi",
    metaDescription: "Restoran ve kafeler için %0 komisyonlu dijital masa yönetim sistemi. WhatsApp onaylı rezervasyon, çift vardiya masa yönetimi, doluluk takibi ve no-show önleme tek ekranda.",
    keywords: [
      "restoran masa rezervasyon sistemi",
      "dijital masa yönetim programı",
      "kafe rezervasyon sistemi",
      "komisyonsuz restoran rezervasyonu",
      "whatsapp restoran rezervasyon",
      "no show önleme restoran",
      "qr menü rezervasyon entegrasyonu",
      "masa takip yazılımı"
    ],
    heroBadge: "RESTORAN & KAFE ÖZEL",
    heroHeadline: "Masanızı doldurun, komisyon ödemeyin.",
    heroSubheadline: "Restoranınızın ritmini hafifletin.",
    heroDescription: "Yüksek komisyonlu platformlara bağımlı kalmadan, kendi markanızla WhatsApp onaylı masa rezervasyonu alın. Çift vardiya doluluk, masa birleştirme ve bekleme listesini tek panelden yönetin.",
    stats: [
      { value: "%0", label: "Rezervasyon Başı Komisyon" },
      { value: "+%35", label: "Ortalama Masa Devir Hızı" },
      { value: "-%45", label: "No-Show (Gelmemezlik) Oranı" },
      { value: "15 Dk", label: "Hızlı Kurulum Süresi" }
    ],
    painPoints: [
      { problem: "Pazaryerlerine ödenen %15-20 rezervasyon komisyonları", solution: "BooKi ile sabit paket ücreti ödeyin, rezervasyon başına %0 komisyonla tasarruf edin." },
      { problem: "Telefonla rezervasyon alırken yaşanan masa çakışmaları", solution: "Canlı salon planı ile boş ve dolu masaları saniye saniye görün, çakışmayı sıfıra indirin." },
      { problem: "Rezervasyon yapıp gelmeyen (No-Show) müşteriler", solution: "Otomatik WhatsApp teyit mesajları ile gelmeyecek müşterileri saatler önce öğrenin." }
    ],
    keyFeatures: [
      { title: "İnteraktif Salon & Masa Krokisi", desc: "İç mekan, teras ve VIP alanları masalarınıza göre kuşbakışı yönetin.", icon: "Utensils" },
      { title: "WhatsApp Onay & İptal Akışı", desc: "Müşteriye otomatik rezervasyon onay linki ve hatırlatma mesajı gitsin.", icon: "MessageCircle" },
      { title: "Akıllı Bekleme Listesi (Waitlist)", desc: "Dolu saatlerde kapıda bekleyen veya yedek rezervasyon isteyen müşterileri sıraya alın.", icon: "Clock3" },
      { title: "Müşteri Tercih Kartı", desc: "Sürekli gelen misafirlerin masa tercihlerini, alerjilerini ve özel notlarını kaydedin.", icon: "UsersRound" }
    ],
    workflowSteps: [
      { step: "01", title: "Salon ve Masalarınızı Tanımlayın", desc: "Masalarınızın kişi kapasitesini ve çalışma saatlerinizi 10 dakikada sisteme girin." },
      { step: "02", title: "Rezervasyon Linkinizi Paylaşın", desc: "Instagram bio'nuza, web sitenize ve Google Haritalar profilinize rezervasyon linkinizi ekleyin." },
      { step: "03", title: "Masanız Dolsun, Bildirim Cebinize Gelsin", desc: "Gelen rezervasyonlar panelinize düşsün, WhatsApp teyitleri otomatik tamamlansın." }
    ],
    faqs: [
      { question: "Masa rezervasyonunda kapora alma seçeneği var mı?", answer: "Evet, yoğun saatler ve özel günler için kapora veya ön ödeme akışını kolayca entegre edebilirsiniz." },
      { question: "Mevcut POS veya QR menümle entegre olabilir mi?", answer: "BooKi bağımsız çalışabildiği gibi QR menünüzün içine rezervasyon butonu olarak entegre edilebilir." },
      { question: "Rezervasyon başına ek ücret veya gizli maliyet var mı?", answer: "Kesinlikle hayır. BooKi'de kaç rezervasyon alırsanız alın komisyon oranı daima %0'dır." }
    ]
  },

  "guzellik-salonu-randevu": {
    slug: "guzellik-salonu-randevu",
    title: "Güzellik Salonu Randevu Programı",
    metaTitle: "Güzellik Salonu Randevu Programı — Uzman Takvimi & Müşteri Takibi | BooKi",
    metaDescription: "Güzellik salonları ve estetik merkezleri için modern randevu programı. Uzman takvimi, oda/cihaz tahsisi, paket seans takibi ve WhatsApp onaylı randevu sistemi.",
    keywords: [
      "güzellik salonu randevu programı",
      "güzellik merkezi müşteri takip sistemi",
      "estetik salonu randevu yazılımı",
      "uzman randevu takvimi",
      "güzellik salonu no show önleme",
      "seans takip programı",
      "kuaför ve güzellik salonu programı"
    ],
    heroBadge: "GÜZELLİK SALONLARI & ESTETİK",
    heroHeadline: "Uzmanlarınız, odalarınız ve seanslarınız tam ritminde.",
    heroSubheadline: "Güzellik merkezinizde karışıklığa son verin.",
    heroDescription: "Personel, lazer cihazı, cilt bakım odası ve uzman uygunluklarını tek ekranda çakışmasız yönetin. Seans hatırlatmalarıyla no-show oranını düşürün.",
    stats: [
      { value: "%0", label: "Randevu Komisyonu" },
      { value: "-%40", label: "No-Show Oranı" },
      { value: "x2", label: "Tekrar Randevu Alma Oranı" },
      { value: "15 Dk", label: "Kurulum Süresi" }
    ],
    painPoints: [
      { problem: "Cilt bakım odası veya lazer cihazı çakışmaları", solution: "BooKi'nin kaynak bazlı mimarisiyle cihaz, oda ve uzmanı aynı anda rezerve edin, çakışmayı önleyin." },
      { problem: "Müşterinin hangi uzmandan randevu aldığını unutması", solution: "Uzman bazlı online randevu sayfasıyla müşteri doğrudan dilediği uzmandan randevu seçsin." },
      { problem: "Randevu teyidi için telefon başında harcanan saatler", solution: "Otomatik WhatsApp onay ve hatırlatma mesajlarıyla teyit işini robota devredin." }
    ],
    keyFeatures: [
      { title: "Uzman & Cihaz Bazlı Takvim", desc: "Personel ve ekipman uygunluklarını tek sütunda veya yan yana karşılaştırarak yönetin.", icon: "Sparkles" },
      { title: "Müşteri Seans & Not Kartı", desc: "Kullanılan cilt tipi, boya numarası veya seans geçmişini güvenle saklayın.", icon: "StickyNote" },
      { title: "WhatsApp Hatırlatma & Onay", desc: "Randevudan 24 saat ve 2 saat önce müşteriye otomatik hatırlatma gitsin.", icon: "BellRing" },
      { title: "Personel Prim & Performans", desc: "Hangi uzman kaç seans yaptı, işletmeye ne kadar ciro getirdi anında raporlayın.", icon: "BarChart3" }
    ],
    workflowSteps: [
      { step: "01", title: "Hizmet ve Uzmanlarınızı Ekleyin", desc: "Cilt bakımı, tırnak, lazer gibi hizmet sürelerini ve uzmanlarınızı belirleyin." },
      { step: "02", title: "Sosyal Medya Linkinizi Paylaşın", desc: "Instagram 'Randevu Al' butonuna BooKi linkinizi bağlayın." },
      { step: "03", title: "Takviminiz Otomatik Dolsun", desc: "Müşteriler 7/24 randevu alsın, ekibinizin takvimi anında senkronize olsun." }
    ],
    faqs: [
      { question: "Birden fazla cihaz ve uzmanı aynı anda bağlayabilir miyim?", answer: "Evet. Örneğin 1 Uzman + 1 Lazer Odası aynı anda rezerve edilerek kaynak çakışması engellenir." },
      { question: "Personellerim sadece kendi takvimini görebilir mi?", answer: "Evet, personele özel yetkilendirme ile herkes sadece kendi randevularını görebilir." },
      { question: "Deneme süresinde tüm özellikler açık mı?", answer: "Evet, 14 günlük ücretsiz denemede tüm Pro özellikler eksiksiz kullanılabilir." }
    ]
  },

  "kuafor-berber-randevu": {
    slug: "kuafor-berber-randevu",
    title: "Kuaför & Berber Randevu Takip Yazılımı",
    metaTitle: "Kuaför ve Berber Randevu Sistemi — Kolay Takvim & WhatsApp Onayı | BooKi",
    metaDescription: "Kuaförler, berberler ve saç tasarım stüdyoları için hızlı online randevu sistemi. Koltuğunuz boş kalmasın, WhatsApp onaylı randevu ile no-show'ları bitirin.",
    keywords: [
      "kuaför randevu sistemi",
      "berber randevu takip yazılımı",
      "erkek kuaförü randevu programı",
      "bayan kuaförü randevu sistemi",
      "kuaför müşteri takip programı",
      "koltuk takip sistemi berber"
    ],
    heroBadge: "KUAFÖR & BERBER",
    heroHeadline: "Koltuklar dolu, müşteri memnun, gün sakin.",
    heroSubheadline: "Kuaför ve berber salonları için en pratik takvim.",
    heroDescription: "Telefon trafiğini bitirin. Müşterileriniz usta ve saat seçerek 10 saniyede randevu alsın. WhatsApp hatırlatmalarıyla unutulan randevular tarihe karışsın.",
    stats: [
      { value: "%0", label: "Komisyon" },
      { value: "10 Sn", label: "Müşteri Randevu Süresi" },
      { value: "+8 Saat", label: "Aylık Kurtarılan Telefon Süresi" },
      { value: "7/24", label: "Kesintisiz Randevu Alma" }
    ],
    painPoints: [
      { problem: "Kesim ve işlem yaparken çalan telefonlara yetişememek", solution: "Müşterileriniz Instagram'dan veya linkinizden 7/24 kendi randevusunu alsın." },
      { problem: "Müşterilerin haber vermeden randevuya gelmemesi", solution: "WhatsApp onay ve hatırlatması ile teyitsiz randevular hemen boşa çıksın." },
      { problem: "Usta ve koltuk hesaplarının karışması", solution: "Her ustanın yaptığı kesim ve ciro anlık olarak panelde listelensin." }
    ],
    keyFeatures: [
      { title: "Usta Bazlı Koltuk Takvimi", desc: "Her ustanın çalışma saatleri, molaları ve randevuları ayrı ayrı görünsün.", icon: "UsersRound" },
      { title: "Hızlı SMS & WhatsApp Teyidi", desc: "Randevu alındığında müşteriye anında WhatsApp teyit mesajı iletilsin.", icon: "MessageCircle" },
      { title: "Mobil Uyumlu Yönetim", desc: "Cep telefonunuzdan tek dokunuşla randevu ekleyin, değiştirin veya iptal edin.", icon: "Zap" },
      { title: "Sadık Müşteri Geçmişi", desc: "Müşterinin en son ne zaman geldiğini, hangi işlemleri yaptırdığını görün.", icon: "StickyNote" }
    ],
    workflowSteps: [
      { step: "01", title: "Ustalarınızı ve Fiyatlarınızı Girin", desc: "Saç kesimi, sakal, boya gibi hizmet sürelerini ve fiyatlarınızı ekleyin." },
      { step: "02", title: "Randevu Linkinizi Dağıtın", desc: "WhatsApp durumunda, Instagram'da ve işletme kartınızda linkinizi paylaşın." },
      { step: "03", title: "Koltuklarınız Tıkır Tıkır Dolsun", desc: "Randevular otomatik düzenlensin, siz sadece sanatınıza odaklanın." }
    ],
    faqs: [
      { question: "Müşterilerimin uygulama indirmesi gerekir mi?", answer: "Hayır! Müşterileriniz hiçbir uygulama indirmeden doğrudan tarayıcı üzerinden 10 saniyede randevu alır." },
      { question: "Tek kişilik berber salonları için uygun paketiniz var mı?", answer: "Evet, Starter paketimiz tek kişilik ve yeni işletmeler için özel olarak uygundur." },
      { question: "Ödemeyi nasıl yapıyoruz?", answer: "Ödemenizi sistem kurulup canlıya alındıktan sonra güvenli yöntemlerle yapabilirsiniz." }
    ]
  },

  "klinik-doktor-randevu": {
    slug: "klinik-doktor-randevu",
    title: "Klinik & Doktor Hasta Randevu Takip Sistemi",
    metaTitle: "Klinik & Doktor Hasta Randevu Sistemi — Güvenli Hasta Takibi | BooKi",
    metaDescription: "Klinikler, diş hekimleri, fizyoterapistler ve özel muayenehaneler için KVKK uyumlu hasta randevu takip yazılımı. WhatsApp onaylı randevu ve hekim takvimi.",
    keywords: [
      "klinik hasta randevu sistemi",
      "doktor randevu takip yazılımı",
      "diş hekimi randevu programı",
      "muayenehane randevu sistemi",
      "fizik tedavi seans takip",
      "sağlık kliniği randevu programı",
      "hasta takip yazılımı"
    ],
    heroBadge: "KLİNİK & SAĞLIK",
    heroHeadline: "Hekim takvimi ve hasta akışı kusursuz düzende.",
    heroSubheadline: "Sağlık işletmeleri ve muayenehaneler için profesyonel randevu.",
    heroDescription: "Diş hekimleri, fizyoterapistler, estetik klinikleri ve uzman doktorlar için KVKK uyumlu hasta randevu yönetimi. WhatsApp onaylı randevu ve hasta notları.",
    stats: [
      { value: "%100", label: "KVKK & Gizlilik Uyumu" },
      { value: "-%50", label: "Hasta No-Show Düşüşü" },
      { value: "+12 Saat", label: "Asistan Zaman Tasarrufu" },
      { value: "%0", label: "Komisyon" }
    ],
    painPoints: [
      { problem: "Hastaların randevu saatini unutması ve muayenehanenin boş kalması", solution: "WhatsApp ve SMS hatırlatma otomasyonu ile hasta teyitleri önceden alınır." },
      { problem: "Farklı hekimlerin ve tedavi odalarının çakışması", solution: "Oda ve hekim bazlı akıllı kaynak planlaması ile çakışmalar engellenir." },
      { problem: "Hasta geçmişi ve tedavi seanslarının takibinin zorluğu", solution: "Güvenli hasta kartında tüm geçmiş randevular ve özel hekim notları arşivlenir." }
    ],
    keyFeatures: [
      { title: "Hekim & Tedavi Odası Yönetimi", desc: "Doktor, asistan ve ameliyathane/tedavi odası uygunluklarını senkronize edin.", icon: "Stethoscope" },
      { title: "Güvenli Hasta Kartı & Notlar", desc: "Hasta anamnez özetini, alerji ve randevu notlarını güvenli altyapıda saklayın.", icon: "ShieldCheck" },
      { title: "WhatsApp Teyit & Randevu İptali", desc: "Hastalar tek tıkla randevusunu onaylasın veya vaktinde iptal etsin.", icon: "MessageCircle" },
      { title: "Detaylı Muayene & Seans Raporu", desc: "Aylık hasta sayısı, doluluk ve hekim bazlı tedavi sürelerini raporlayın.", icon: "BarChart3" }
    ],
    workflowSteps: [
      { step: "01", title: "Hekim ve Tedavi Türlerini Belirleyin", desc: "Muayene, kontrol, operasyon sürelerinizi ve hekim takvimini sisteme girin." },
      { step: "02", title: "Hasta Kabul Linkinizi Aktif Edin", desc: "Web sitenize, WhatsApp hattınıza ve SMS şablonlarınıza ekleyin." },
      { step: "03", title: "Düzenli ve Sakin Bir Muayenehane", desc: "Hastalar beklemeden zamanında gelsin, operasyonunuz düzen kazansın." }
    ],
    faqs: [
      { question: "Hasta verileri KVKK standartlarına uygun mu?", answer: "Evet, tüm veriler yüksek güvenlikli sunucularda şifreli olarak saklanır ve KVKK standartlarına tam uyumludur." },
      { question: "Kontrol randevuları için otomatik hatırlatma gönderebilir miyiz?", answer: "Evet, tedavi sonrası 15 gün veya 1 ay sonrasına kontrol hatırlatması otomatik planlanabilir." },
      { question: "Sekreter veya asistan için ayrı kullanıcı açılabilir mi?", answer: "Evet, rol bazlı yetkilendirme ile asistanlar randevuları düzenleyebilir, hekimler kendi takvimini izleyebilir." }
    ]
  },

  "spa-wellness-rezervasyon": {
    slug: "spa-wellness-rezervasyon",
    title: "Spa, Masaj & Wellness Rezervasyon Sistemi",
    metaTitle: "Spa & Masaj Salonu Rezervasyon Sistemi — Oda ve Terapist Takvimi | BooKi",
    metaDescription: "Spa, masaj salonları, hamam ve termal tesisler için profesyonel kaynak rezervasyon yazılımı. Terapist, masaj odası ve sauna kapasite yönetimi.",
    keywords: [
      "spa rezervasyon sistemi",
      "masaj salonu randevu programı",
      "wellness rezervasyon yazılımı",
      "terapist randevu takip",
      "hamam ve sauna rezervasyon sistemi",
      "masaj takip programı"
    ],
    heroBadge: "SPA & WELLNESS",
    heroHeadline: "Huzurlu misafir deneyimi, kusursuz oda planlaması.",
    heroSubheadline: "Spa ve masaj merkezleri için çok kaynaklı rezervasyon.",
    heroDescription: "Terapist, masaj odası, sauna ve jakuzi kapasitelerini aynı anda yönetin. Çiftler için ortak oda veya bireysel masaj seanslarını kolayca planlayın.",
    stats: [
      { value: "%0", label: "Rezervasyon Komisyonu" },
      { value: "+%30", label: "Oda Kullanım Verimliliği" },
      { value: "-%40", label: "No-Show Oranı" },
      { value: "15 Dk", label: "Kurulum Süresi" }
    ],
    painPoints: [
      { problem: "Masaj odası var ama terapist yok; ya da terapist var oda dolu karmaşası", solution: "BooKi eş zamanlı olarak Terapist + Oda kontrolü yaparak çakışmayı sıfırlar." },
      { problem: "Çift masajı ve paket terapilerde saat ayarlama zorluğu", solution: "Paket seans ve çoklu kaynak rezervasyon akışı ile 1 dakikada doğru planlama." },
      { problem: "Hafta sonu yoğunluğunda rezervasyon teyidi yetişememesi", solution: "Otomatik WhatsApp bildirimleri ile misafirler randevularını kendileri onaylar." }
    ],
    keyFeatures: [
      { title: "Terapist & Oda Çiftli Eşleşmesi", desc: "Masaj odası ve terapistin aynı anda uygun olduğu saatleri otomatik gösterir.", icon: "Sparkles" },
      { title: "Paket & Seans Takibi", desc: "10 seanslık masaj paketi alan misafirlerin kalan seanslarını otomatik takip edin.", icon: "StickyNote" },
      { title: "Çoklu Dil & Turist Desteği", desc: "Yabancı misafirler için çok dilli rezervasyon akışı ve e-posta onayı.", icon: "Globe2" },
      { title: "Kapasite & Doluluk Raporu", desc: "Hangi saatlerde spa merkeziniz en yoğun, hangi terapist daha çok seans yapıyor görün.", icon: "BarChart3" }
    ],
    workflowSteps: [
      { step: "01", title: "Masaj Odalarını ve Terapistleri Tanımlayın", desc: "Aromaterapi, medikal masaj sürelerini ve oda tiplerini girin." },
      { step: "02", title: "Otel veya Web Sitenize Buton Ekleyin", desc: "Misafirler otel odasından veya web sitenizden 7/24 rezervasyon yapsın." },
      { step: "03", title: "Kusursuz Bir Spa Akışı Başlasın", desc: "Misafir geldiğinde odası ve terapisti hazır olsun." }
    ],
    faqs: [
      { question: "Otel içi spa işletmeleri için uygun mu?", answer: "Evet, hem otel misafirleri hem de dışarıdan gelen misafirler için idealdir." },
      { question: "Çift masajı (Couples Massage) için iki terapist atanabilir mi?", answer: "Evet, tek bir odaya iki terapist ve iki misafir atanarak rezervasyon oluşturulabilir." },
      { question: "Fiyatlandırma nasıl yapılıyor?", answer: "Sabit şeffaf paketlerle çalışır, misafir sayınız artsa bile rezervasyon başına komisyon alınmaz." }
    ]
  },

  "uzman-danisman-randevu": {
    slug: "uzman-danisman-randevu",
    title: "Psikolog, Diyetisyen & Uzman Seans Takip Sistemi",
    metaTitle: "Psikolog & Diyetisyen Randevu Takip Programı — Seans Yönetimi | BooKi",
    metaDescription: "Psikologlar, diyetisyenler, koçlar ve danışmanlar için online seans ve randevu yönetim yazılımı. Online / yüz yüze seans takvimi ve otomatik hatırlatma.",
    keywords: [
      "psikolog randevu takip programı",
      "diyetisyen danışan takip sistemi",
      "uzman seans takip yazılımı",
      "online danışmanlık randevu sistemi",
      "koçluk seans randevu programı",
      "terapist randevu programı"
    ],
    heroBadge: "UZMAN & DANIŞMANLIK",
    heroHeadline: "Seanslarınız düzenli, danışanlarınız vaktinde.",
    heroSubheadline: "Bireysel çalışan uzmanlar ve klinikler için akıllı takvim.",
    heroDescription: "Psikolog, diyetisyen, yaşam koçu ve danışmanlar için yüz yüze veya online seans randevu sistemi. Google Takvim senkronizasyonu ve otomatik WhatsApp hatırlatma.",
    stats: [
      { value: "%0", label: "Komisyon" },
      { value: "+%98", label: "Zamanında Seansa Katılım" },
      { value: "10 Dk", label: "Hızlı Kurulum" },
      { value: "14 Gün", label: "Ücretsiz Deneme" }
    ],
    painPoints: [
      { problem: "Danışanların seans saatlerini unutması veya son anda ertelemesi", solution: "Otomatik hatırlatma mesajları ile seans doluluk oranınız %98'e çıksın." },
      { problem: "Kişisel takvim ile iş randevularının birbirine girmesi", solution: "BooKi profesyonel takvimi ile çalışma saatlerinizi net sınırlarla koruyun." },
      { problem: "Paket seansların takibinde yaşanan hesap karışıklıkları", solution: "Danışan kartında kaçıncı seansta olduğunuzu ve kalan seansları tek tıkla görün." }
    ],
    keyFeatures: [
      { title: "Online / Yüz Yüze Seans Seçimi", desc: "Danışan randevu alırken seansın ofiste mi yoksa online mı olacağını seçsin.", icon: "Globe2" },
      { title: "Gizli Danışan Notları", desc: "Seans içi notlarınızı yalnızca sizin görebileceğiniz güvenli alanda tutun.", icon: "StickyNote" },
      { title: "WhatsApp Seans Hatırlatması", desc: "Seans öncesi danışana hatırlatma ve online görüşme linki iletilsin.", icon: "MessageCircle" },
      { title: "Mola & Çalışma Saati Koruması", desc: "Seanslar arası 15 dakika dinlenme payı ve çalışma sınırlarınızı otomatik ayarlayın.", icon: "Clock3" }
    ],
    workflowSteps: [
      { step: "01", title: "Seans Türlerinizi Oluşturun", desc: "İlk görüşme, terapi seansı, diyet kontrolü gibi süre ve ücretleri girin." },
      { step: "02", title: "Randevu Profil Linkinizi Paylaşın", desc: "Instagram bio'nuza, web sitenize veya kartvizitinize linkinizi ekleyin." },
      { step: "03", title: "Danışanlarınız Kolayca Randevu Alsın", desc: "Siz sadece danışanınıza odaklanın, randevu trafiğini BooKi yönetsin." }
    ],
    faqs: [
      { question: "Online görüşmeler için Zoom veya Google Meet linki eklenebilir mi?", answer: "Evet, online seans seçen danışana otomatik onay mesajında görüşme linki iletilebilir." },
      { question: "Tek kişilik çalışan uzmanlar için hangi paket uygun?", answer: "Starter paketimiz tek kişilik danışmanlar ve uzmanlar için eksiksiz bir başlangıç sağlar." },
      { question: "Danışan gizliliği nasıl korunuyor?", answer: "Danışan notları ve bilgileri uçtan uca şifreli altyapıda, sadece hesap sahibinin erişimine açık tutulur." }
    ]
  },

  "studyo-kurs-rezervasyon": {
    slug: "studyo-kurs-rezervasyon",
    title: "Stüdyo, Spor, Pilates & Kurs Rezervasyon Sistemi",
    metaTitle: "Stüdyo & Pilates Rezervasyon Sistemi — Ders & Kapasite Takibi | BooKi",
    metaDescription: "Pilates, yoga stüdyoları, müzik/dans kursları ve spor merkezleri için grup dersi ve bireysel seans rezervasyon sistemi. Eğitmen ve alan yönetimi.",
    keywords: [
      "pilates stüdyosu rezervasyon sistemi",
      "yoga stüdyosu randevu programı",
      "spor salonu ders rezervasyon",
      "dans kursu randevu takip",
      "müzik stüdyosu rezervasyon programı",
      "grup dersi rezervasyon sistemi"
    ],
    heroBadge: "STÜDYO & SPOR & KURS",
    heroHeadline: "Reformer, grup dersleri ve eğitmen takvimi tek merkezde.",
    heroSubheadline: "Stüdyolar ve atölyeler için akıllı kapasite yönetimi.",
    heroDescription: "Pilates, yoga, dans, müzik stüdyoları ve spor merkezleri için grup dersi ve bireysel seans rezervasyon altyapısı. Kontenjan aşımını önleyin, yedek liste oluşturun.",
    stats: [
      { value: "%0", label: "Komisyon" },
      { value: "+%40", label: "Ders Doluluk Oranı" },
      { value: "0", label: "Kontenjan Çakışması" },
      { value: "15 Dk", label: "Kurulum Süresi" }
    ],
    painPoints: [
      { problem: "Grup derslerinde kontenjan aşımı veya reformer cihazı yetersizliği", solution: "Kişi limiti koyun; kontenjan dolduğunda ders otomatik kapansın ve yedek liste açılsın." },
      { problem: "Derse gelmeyen üyeler yüzünden başkalarının yer bulamaması", solution: "WhatsApp iptal kuralı ile gelmeyen üyenin yeri anında yedek listedeki üyeye açılsın." },
      { problem: "Eğitmen ve stüdyo odalarının çakışması", solution: "Oda, eğitmen ve ekipman aynı takvimde senkronize yönetilsin." }
    ],
    keyFeatures: [
      { title: "Kontenjanlı Grup Dersi Yönetimi", desc: "Maksimum katılımcı sayısı belirleyin, dolunca otomatik yedek listeye alsın.", icon: "UsersRound" },
      { title: "Eğitmen & Stüdyo Alanı Tahsisi", desc: "Hangi eğitmen hangi stüdyoda ders veriyor anında görün.", icon: "Sparkles" },
      { title: "Üye Paket & Kredi Takibi", desc: "8 derslik veya aylık üyelik paketlerini danışan kartından takip edin.", icon: "StickyNote" },
      { title: "Otomatik Ders Hatırlatması", desc: "Ders saatinden önce üyelere WhatsApp hatırlatması iletilsin.", icon: "BellRing" }
    ],
    workflowSteps: [
      { step: "01", title: "Ders Programınızı Oluşturun", desc: "Haftalık reformer, mat yoga veya dans saatlerinizi ve kontenjanları belirleyin." },
      { step: "02", title: "Üyelerinizle Linki Paylaşın", desc: "Üyeleriniz mobil cihazlarından diledikleri derse yer ayırtsın." },
      { step: "03", title: "Dolu ve Verimli Sınıflar", desc: "Yoklama ve katılımı tek ekrandan takip edin." }
    ],
    faqs: [
      { question: "Hem bireysel özel ders hem grup dersi açabilir miyim?", answer: "Evet, aynı sistemde 1'e 1 özel dersler ve 10 kişilik grup seansları birlikte yönetilebilir." },
      { question: "Üye randevusunu iptal ederse ne olur?", answer: "Belirlediğiniz saat sınırına göre iptal gerçekleşir ve sırada bekleyen üyeye bildirim gider." },
      { question: "Mobil telefondan yoklama alınabilir mi?", answer: "Evet, tüm paneller mobil cihazlara %100 uyumludur." }
    ]
  },

  "coklu-sube-randevu-yonetimi": {
    slug: "coklu-sube-randevu-yonetimi",
    title: "Çoklu Şube & Zincir İşletme Randevu Yönetimi",
    metaTitle: "Çoklu Şube Randevu Yönetim Sistemi — Zincir İşletmeler | BooKi",
    metaDescription: "Birden fazla şubesi olan restoran zincirleri, güzellik merkezleri ve klinikler için merkezi randevu yönetim yazılımı. Tek panelden tüm şubelerin doluluk ve rapor kontrolü.",
    keywords: [
      "çoklu şube randevu yönetim sistemi",
      "zincir işletme randevu programı",
      "franchise randevu takip yazılımı",
      "merkezi randevu yönetim sistemi",
      "şube bazlı randevu takvimi"
    ],
    heroBadge: "ZİNCİR & ÇOKLU ŞUBE",
    heroHeadline: "Tüm şubeleriniz tek merkezde, tek akışta.",
    heroSubheadline: "Büyüyen zincir işletmeler ve franchise ağları için kurumsal güç.",
    heroDescription: "Farklı şehirlerde veya semtlerdeki tüm şubelerinizi tek bir çatı altında toplayın. Şube bazlı yetkilendirme, merkezi raporlama ve konsolide doluluk analizi.",
    stats: [
      { value: "Sınırsız", label: "Şube ve Personel Desteği" },
      { value: "Tek Panel", label: "Merkezi Yönetim" },
      { value: "%0", label: "Rezervasyon Komisyonu" },
      { value: "Dedicated", label: "Kurumsal Destek" }
    ],
    painPoints: [
      { problem: "Her şubenin ayrı bir defter veya farklı program kullanması", solution: "Tüm şubeleri tek bir merkezi veritabanında toplayın, standart bir operasyon kurun." },
      { problem: "Hangi şubenin ne kadar ciro ve randevu aldığını görmekte zorlanmak", solution: "Merkezi dashboard ile şubeleri birbirleriyle karşılaştırın, anlık performansı izleyin." },
      { problem: "Müşterinin farklı şubelere gittiğinde bilgilerinin kaybolması", solution: "Ortak müşteri havuzu ile misafir hangi şubeye giderse gitsin geçmişi tanınsın." }
    ],
    keyFeatures: [
      { title: "Merkezi Yönetici & Şube Yetkileri", desc: "Merkez tüm şubeleri görsün; şube müdürleri sadece kendi lokasyonunu yönetsin.", icon: "Building2" },
      { title: "Şube Karşılaştırmalı Raporlar", desc: "Doluluk, iptal oranı, seans adetleri ve gelirleri şubeler arası kıyaslayın.", icon: "BarChart3" },
      { title: "Ortak Müşteri Veritabanı", desc: "Müşterilerinizin tüm şubelerdeki geçmiş işlemlerini tek müşteri kartında görün.", icon: "UsersRound" },
      { title: "Franchise & Şube Bazlı Özelleştirme", desc: "Her şubenin çalışma saatleri, fiyatları ve uzman kadrosu bağımsız yönetilebilir.", icon: "Zap" }
    ],
    workflowSteps: [
      { step: "01", title: "Şubelerinizi Tanımlayın", desc: "Kadıköy, Nişantaşı, Ankara gibi tüm lokasyonlarınızı ekleyin." },
      { step: "02", title: "Şube Yöneticilerini Yetkilendirin", desc: "Her şube için ilgili ekip üyelerine kullanıcı hesaplarını açın." },
      { step: "03", title: "Büyüyen Ağınızı Kolayca Yönetin", desc: "Merkezden tüm operasyonu canlı izleyin, veriye dayalı büyüyün." }
    ],
    faqs: [
      { question: "Şube sayısı arttıkça ekstra lisans ücreti öder miyiz?", answer: "Enterprise paketimizde sınırsız şube ve personel desteği ile büyümenizin önüne maliyet engeli koymuyoruz." },
      { question: "Merkezden tüm şubelerin WhatsApp mesaj şablonları kontrol edilebilir mi?", answer: "Evet, kurumsal marka dilinizi tüm şubelerde standart hale getirebilirsiniz." },
      { question: "Mevcut verilerimizi topluca aktarabilir miyiz?", answer: "Evet, Enterprise müşterilerimize özel veri aktarım ve geçiş desteği sağlıyoruz." }
    ]
  }
};
