export interface FeatureInfo {
  slug: string;
  title: string;
  metaTitle: string;
  metaDescription: string;
  keywords: string[];
  heroBadge: string;
  heroHeadline: string;
  heroSubheadline: string;
  heroDescription: string;
  benefits: { title: string; desc: string; icon: string }[];
  deepDive: { title: string; desc: string }[];
  faqs: { question: string; answer: string }[];
}

export const FEATURES: Record<string, FeatureInfo> = {
  "whatsapp-randevu-onayi": {
    slug: "whatsapp-randevu-onayi",
    title: "WhatsApp Onaylı Randevu & Hatırlatma Sistemi",
    metaTitle: "WhatsApp Onaylı Randevu Sistemi — Otomatik Hatırlatma & Teyit | BooKi",
    metaDescription: "Müşterilerinize otomatik WhatsApp randevu teyidi, konum bilgisi ve hatırlatma mesajları gönderin. Randevuya gelmeme oranını azaltın.",
    keywords: [
      "whatsapp onaylı randevu sistemi",
      "otomatik randevu hatırlatma whatsapp",
      "whatsapp randevu teyidi",
      "no show önleme whatsapp",
      "randevu onay mesajı programı"
    ],
    heroBadge: "WHATSAPP ENTEGRASYONU",
    heroHeadline: "Müşterinizin en çok baktığı ekranda olun.",
    heroSubheadline: "WhatsApp ile otomatik teyit, daha az no-show.",
    heroDescription: "Randevuları telefonla tek tek teyit etmeyin. Randevu anında, 24 saat önce ve 2 saat önce otomatik WhatsApp mesajı gönderin. Müşteri tek dokunuşla onaylasın veya iptal etsin.",
    benefits: [
      { title: "Anında Otomatik Teyit Mesajı", desc: "Randevu oluşturulur oluşturulmaz tarih, saat, uzman ve konum bilgisi WhatsApp'tan iletilir.", icon: "MessageCircle" },
      { title: "Tek Dokunuşla Onay / İptal", desc: "Müşteri mesaja gelen butona basarak onay verir; gelmeyecekse vaktinde iptal edip yerini başkasına açar.", icon: "Check" },
      { title: "Konum ve Yol Tarifi Paylaşımı", desc: "İşletmenizin adresi ve Google Haritalar konum bağlantısı mesaja otomatik eklenir.", icon: "Globe2" },
      { title: "Randevu Sonrası Yorum & Anket", desc: "Hizmetten sonra müşteriye memnuniyet anketi bağlantısı gönderin ve yorum toplamayı kolaylaştırın.", icon: "Sparkles" }
    ],
    deepDive: [
      { title: "No-Show Maliyetini Azaltın", desc: "Gelmeyen müşteriler boş kalan saat ve kaybedilen gelir demektir. Otomatik WhatsApp hatırlatması, randevuyu unutan müşterilerin sayısını düşürmeye yardımcı olur." },
      { title: "Ekibinizi Telefon Trafiğinden Kurtarın", desc: "Günde onlarca kişiyi arayıp 'Yarın gelecek misiniz?' diye sormak yerine, ekibiniz misafirleri karşılamaya odaklansın." }
    ],
    faqs: [
      { question: "WhatsApp mesajları için ek hat veya numara almam gerekir mi?", answer: "Hayır. Mesajlar kendi işletme numaranız veya BooKi bulut altyapısı üzerinden gönderilebilir." },
      { question: "Müşteri mesajı yanıtlayabilir mi?", answer: "Evet. Gelen yanıtları panelinizden görebilir ve müşteriyle doğrudan iletişime geçebilirsiniz." },
      { question: "Hangi paketlerde geçerlidir?", answer: "WhatsApp otomasyonu Professional ve Enterprise paketlerine dahildir. Starter paketinde standart e-posta bildirimleri bulunur." }
    ]
  },

  "no-show-onleme": {
    slug: "no-show-onleme",
    title: "No-Show Önleme & Akıllı Bekleme Listesi",
    metaTitle: "No-Show Önleme Yazılımı — Boş Kalan Saatleri Doldurun | BooKi",
    metaDescription: "Teyit vermeyen randevuları belirleyin, boşalan saatleri bekleme listesindeki müşterilere hemen önerin.",
    keywords: [
      "no show önleme randevu",
      "akıllı bekleme listesi waitlist",
      "gelmeyen müşteri randevu iptali",
      "randevu teyit sistemi",
      "boş masa doldurma yazılımı"
    ],
    heroBadge: "NO-SHOW ÇÖZÜMÜ",
    heroHeadline: "Boşalan saatler hızla yeniden dolsun.",
    heroSubheadline: "No-show'u azaltın, gelmeyenlerin yerini yedek listeyle doldurun.",
    heroDescription: "Müşteri son anda gelemediğinde masanız veya koltuğunuz boş kalmasın. Akıllı bekleme listesi (Waitlist) sıradaki müşteriye hemen SMS veya WhatsApp bildirimi gönderir.",
    benefits: [
      { title: "Akıllı Bekleme Listesi (Waitlist)", desc: "Dolu günlerde yer arayan müşterileri yedek listeye alın. İptal olunca ilk sıradakine otomatik teklif gitsin.", icon: "Clock3" },
      { title: "Teyitsiz Randevuyu Otomatik Serbest Bırakma", desc: "Belirlediğiniz süre içinde WhatsApp'tan onay vermeyen randevuları serbest bırakıp yeni müşterilere açın.", icon: "Zap" },
      { title: "Müşteri Güvenilirlik Etiketi", desc: "Randevusuna düzenli gelen veya sık iptal eden müşterileri panelde renkli etiketlerle görün.", icon: "ShieldCheck" },
      { title: "Kapora ve Ön Ödeme Desteği", desc: "Yoğun günlerde randevuyu güvenceye almak için kapora veya ön ödeme akışını kullanın.", icon: "BarChart3" }
    ],
    deepDive: [
      { title: "Kaybolan Geliri Geri Kazanın", desc: "Boş kalan her randevu, o saatin gelirinden vazgeçmek demektir. Bekleme listesi, iptal edilen saatleri yeniden satışa çevirir. Kazancınız; hizmet bedelinize ve iptal sayınıza göre değişir." },
      { title: "Müşteri Sadakatini Artırın", desc: "'Yer açılınca haber vereceğiz' dediğiniz müşteri, bildirimi zamanında aldığında size güvenir." }
    ],
    faqs: [
      { question: "Bekleme listesindeki müşteriye nasıl haber gidiyor?", answer: "İptal gerçekleştiği anda sistem sıradaki müşteriye WhatsApp/SMS ile 'Yer açıldı, randevunuzu onaylamak ister misiniz?' mesajı iletir." },
      { question: "Müşteri onaylamazsa ne olur?", answer: "Belirlenen süre içinde yanıt vermezse sıra otomatik olarak bir sonraki adaya geçer." }
    ]
  },

  "ekip-ve-kaynak-takvimi": {
    slug: "ekip-ve-kaynak-takvimi",
    title: "Akıllı Ekip, Oda ve Ekipman Takvimi",
    metaTitle: "Ekip & Kaynak Takvimi — Çakışmasız Personel ve Oda Planlaması | BooKi",
    metaDescription: "Personel, oda, masa, cihaz ve ekipman uygunluğunu tek ekranda yönetin. Kaynak çakışmalarını oluşmadan engelleyin.",
    keywords: [
      "ekip takvimi randevu",
      "kaynak yönetimi yazılımı",
      "personel randevu programı",
      "oda ve cihaz takvimi",
      "çakışmasız randevu takvimi"
    ],
    heroBadge: "KAYNAK PLANLAMA",
    heroHeadline: "Personel, oda ve cihazlar aynı ritimde.",
    heroSubheadline: "Karmaşık operasyonu tek bakışta anlaşılır hale getirin.",
    heroDescription: "Yalnızca kişiye değil; aynı anda odaya, cihaza veya masaya bağlı çalışan işletmeler için çok kaynaklı rezervasyon altyapısı. Çakışma riskini en aza indirin.",
    benefits: [
      { title: "Çok Kaynaklı Eşleşme (Multi-Resource)", desc: "Uzman, oda ve cihaz aynı anda kontrol edilir. Biri meşgulse o saat için randevu açılmaz.", icon: "UsersRound" },
      { title: "Sürükle ve Bırak", desc: "Randevunun saatini veya uzmanını değiştirmek için kartı takvimde istediğiniz yere sürükleyin.", icon: "CalendarDays" },
      { title: "Mola ve İzin Takibi", desc: "Personelin molaları, haftalık izinleri ve tatil günleri takvimde otomatik olarak kapanır.", icon: "Clock3" },
      { title: "Renk Kodlu Görünüm", desc: "Hizmet türüne, uzmana veya ödeme durumuna göre renklendirilmiş net bir takvim.", icon: "Sparkles" }
    ],
    deepDive: [
      { title: "Büyük Ekipler İçin Hızlı Filtreleme", desc: "İster 15 kişilik bir kuaför ister 5 şubeli bir klinik olun; tek tıkla istediğiniz personelin veya şubenin takvimine odaklanın." },
      { title: "Google Takvim Senkronizasyonu", desc: "Personeliniz randevularını kendi telefon takviminden takip edebilir. (Kurulum sırasında Google Takvim bağlantısı yapılandırılır.)" }
    ],
    faqs: [
      { question: "Aynı anda kaç personel takvimi görüntülenebilir?", answer: "Personel sayısı paketinizin limitine bağlıdır (Starter 2, Professional 10, Enterprise sınırsız). Takvimler yan yana günlük veya haftalık görüntülenebilir." },
      { question: "Personelin prim hesabı takvime göre çıkar mı?", answer: "Raporlama modülü her personelin işlem adedini ve cirosunu hesaplar. Bu veriyi prim hesabınızda kullanabilirsiniz." }
    ]
  },

  "musteri-yonetimi-crm": {
    slug: "musteri-yonetimi-crm",
    title: "Müşteri Kartı, Ziyaret Geçmişi & CRM Notları",
    metaTitle: "Müşteri Yönetimi CRM — Ziyaret Geçmişi & Sadakat Takibi | BooKi",
    metaDescription: "Müşterilerinizin geçmiş ziyaretlerini, tercihlerini, alerjilerini ve harcamalarını tek bir müşteri kartında toplayın.",
    keywords: [
      "müşteri takip programı",
      "randevu müşteri crm",
      "ziyaret geçmişi takip yazılımı",
      "müşteri sadakat programı",
      "güzellik ve restoran crm"
    ],
    heroBadge: "MÜŞTERİ CRM",
    heroHeadline: "Her müşteri özel hissetsin.",
    heroSubheadline: "Kişisel hizmet, sadık müşteriler.",
    heroDescription: "Müşteriniz kapıdan girdiğinde veya aradığında en son aldığı hizmeti, sevdiği masayı ve alerjilerini tek tıkla görün. Her ziyarette kişisel bir deneyim sunun.",
    benefits: [
      { title: "Detaylı Müşteri Kartı", desc: "Ad, telefon, doğum günü, notlar ve geçmiş randevular tek sayfada.", icon: "StickyNote" },
      { title: "Harcama ve Ziyaret İstatistikleri", desc: "Müşterinin toplam harcamasını, ziyaret sıklığını ve favori hizmetlerini izleyin.", icon: "BarChart3" },
      { title: "Doğum Günü Mesajı", desc: "Doğum gününde müşterinize otomatik kutlama mesajı ve isteğe bağlı indirim gönderin.", icon: "Sparkles" },
      { title: "Pasif Müşteri Listesi", desc: "Belirli süredir randevu almayan müşterileri filtreleyin ve özel tekliflerle geri kazanın.", icon: "UsersRound" }
    ],
    deepDive: [
      { title: "Müşteri Kaybını Azaltın", desc: "Müşteriler çoğu zaman unuttukları için gelmeyi bırakır. BooKi CRM ile düzenli hatırlatmalar göndererek bağı canlı tutun." },
      { title: "Excel ve Defter Karmaşasından Çıkın", desc: "Defterde numara aramak yerine, arama çubuğuna adı yazın ve müşterinin profilini hemen açın." }
    ],
    faqs: [
      { question: "Eski müşteri listemi Excel'den BooKi'ye aktarabilir miyim?", answer: "Evet. Toplu aktarım aracıyla mevcut Excel listenizi sisteme yükleyebilirsiniz." },
      { question: "Müşteri verileri başka işletmelerle paylaşılır mı?", answer: "Hayır. Her işletmenin müşteri veritabanı ayrı tutulur ve yalnızca o işletmeye aittir." }
    ]
  },

  "komisyonsuz-rezervasyon": {
    slug: "komisyonsuz-rezervasyon",
    title: "%0 Komisyonlu Masa & Randevu Yazılımı",
    metaTitle: "%0 Komisyonsuz Randevu ve Rezervasyon Sistemi — Şeffaf Yazılım | BooKi",
    metaDescription: "Rezervasyon başına komisyon ödemeyin. BooKi'de randevu ve masa rezervasyonlarından %0 komisyon alınır; yalnızca sabit aylık paket ücreti ödersiniz.",
    keywords: [
      "komisyonsuz rezervasyon yazılımı",
      "komisyonsuz randevu sistemi",
      "ücretsiz rezervasyon yazılımı",
      "komisyonsuz masa yönetim sistemi",
      "pazaryeri komisyonsuz randevu"
    ],
    heroBadge: "%0 KOMİSYON GARANTİSİ",
    heroHeadline: "Kazancınız sizde kalsın, aracıya pay vermeyin.",
    heroSubheadline: "Sıfır komisyon, sabit ve şeffaf aylık paketler.",
    heroDescription: "Pazaryeri modeliyle çalışan platformların çoğu, getirdikleri müşteriden komisyon alır. BooKi ise sabit paket fiyatıyla çalışır; rezervasyon sayınız artsa da ek komisyon ödemezsiniz.",
    benefits: [
      { title: "Rezervasyon Başına ₺0 Kesinti", desc: "Ayda 1.000 rezervasyon da alsanız paket bedeliniz değişmez; komisyon çıkmaz.", icon: "Check" },
      { title: "Kendi Markanızla Büyüyün", desc: "Müşterileriniz bir pazaryerinin değil, doğrudan sizin rezervasyon sayfanızdan randevu alır.", icon: "Crown" },
      { title: "Müşteri Verisi Size Aittir", desc: "Pazaryerlerinde müşteri, benzer işletmelerin listelendiği sayfada karar verir. BooKi'de müşteri kaydı yalnızca size aittir.", icon: "ShieldCheck" },
      { title: "Kurulumda Güvenli Ödeme", desc: "Kredi kartı girmeden başlayın; sistem kurulup onayınızı verdikten sonra ödeme yapın.", icon: "Zap" }
    ],
    deepDive: [
      { title: "Komisyon Kalemi Ortadan Kalkar", desc: "Komisyonlu sistemlerde gider, rezervasyon sayısı ve sepet tutarıyla birlikte artar. BooKi'de gideriniz sabit paket ücretidir. Kendi işletmeniz için karşılaştırma yapmak istiyorsanız bize WhatsApp'tan yazın." },
      { title: "Müşteri Sadakati Kendi Markanıza Kalır", desc: "Müşteriniz rezervasyon yaparken sizin logonuzu ve tasarımınızı görür; bağı doğrudan sizinle kurar." }
    ],
    faqs: [
      { question: "Gerçekten hiçbir gizli komisyon yok mu?", answer: "Evet. BooKi, rezervasyon sayınızdan veya cironuzdan yüzde bazlı kesinti yapmaz. Yalnızca seçtiğiniz paketin sabit ücretini ödersiniz." },
      { question: "İstediğim zaman ayrılabilir miyim?", answer: "Evet. Tüm paketler taahhütsüzdür; üyeliğinizi dilediğiniz zaman sonlandırabilirsiniz." }
    ]
  }
};
