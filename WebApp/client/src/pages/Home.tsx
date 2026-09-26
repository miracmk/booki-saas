import { useEffect, useState, useMemo } from "react";
import { Link } from "wouter";
import { Navbar } from "@/components/Navbar";
import { Footer } from "@/components/Footer";
import { StickyContactBar } from "@/components/StickyContactBar";
import { SEOHead } from "@/components/SEOHead";
import { SalesFunnelSection } from "@/components/SalesFunnelSection";
import { SECTORS } from "@/data/sectorsData";
import {
  MAIN_SECTORS,
  SECTOR_MATRIX,
  SUBSECTOR_NAMES,
  PLAN_PRICES,
  type SubsectorData
} from "@/data/sectorPricingMatrix";
import {
  ArrowUpRight,
  BarChart3,
  Building2,
  Crown,
  CalendarDays,
  Check,
  ChevronDown,
  Clock3,
  Command,
  MessageCircle,
  MoveRight,
  Plus,
  Phone,
  StickyNote,
  Utensils,
  Stethoscope,
  ShieldCheck,
  Sparkles,
  UsersRound,
  X,
  Zap,
  CheckCircle2,
  Bot,
  Layers,
  Receipt,
  Package,
  QrCode,
  FileSpreadsheet,
  Globe2,
  Send
} from "lucide-react";

const benefits = [
  { icon: CalendarDays, title: "Tek takvim, tam kontrol", text: "Ekibinizi, hizmetlerinizi ve masa/oda uygunluklarını tek bir canlı akışta yönetin." },
  { icon: MessageCircle, title: "WhatsApp ile sıfır no-show", text: "Otomatik teyit ve hatırlatmalarla müşterileriniz randevusuna zamanında gelsin." },
  { icon: BarChart3, title: "%0 komisyon, net ciro", text: "Pazaryerlerine komisyon ödemeyin; doluluk ve gelir verileri karar anında önünüzde." },
];

const pillars = [
  {
    icon: CalendarDays,
    title: "1. Akıllı Takvim & Rezervasyon",
    desc: "Çift yönlü Google/Apple takvim senkronizasyonu, QR masa rezervasyonu, akıllı bekleme listesi (waitlist) ve no-show önleme.",
    tone: "teal"
  },
  {
    icon: Bot,
    title: "2. Çok Kanallı AI Asistan",
    desc: "7/24 WhatsApp Cloud API, Instagram DM ve Web üzerinden doğal dille konuşan, boşlukları önerip randevu oluşturan yapay zeka.",
    tone: "peach"
  },
  {
    icon: Receipt,
    title: "3. Finans, Adisyon & e-Belge",
    desc: "Masa/hizmet bazlı adisyon, POS entegrasyonu, GİB onaylı e-Fatura / e-Arşiv ve otomatik gün sonu Z raporlama.",
    tone: "blue"
  },
  {
    icon: Package,
    title: "4. Stok, Reçete & Depo",
    desc: "Hizmet ve menü reçeteleriyle otomatik hammadde düşüşü, kritik stok uyarıları, sayım ve çoklu depo yönetimi.",
    tone: "amber"
  },
  {
    icon: StickyNote,
    title: "5. Müşteri CRM & Sadakat",
    desc: "360° müşteri profili, seans notları, alerji/tercih kartları, puan sadakat programı ve KVKK onaylı pazarlama.",
    tone: "lilac"
  },
  {
    icon: Building2,
    title: "6. Çoklu Şube & Ekip Yönetimi",
    desc: "Tek merkezden sınırsız şube, personel vardiya ve prim hesaplama, rol bazlı yetkilendirme ve konsolide patron raporları.",
    tone: "ice"
  }
];

const faqItems = [
  ["Tüm özellikler gerçekten her pakette açık mı?", "Evet! BooKi felsefesinde özellik kilitleme yoktur. POS, Adisyon, Raporlar, e-Fatura, Stok, Çift Yönlü Takvim ve tüm 24 kurumsal modül Ücretsiz paket dahil HER pakette aktiftir. Tek istisna, yüksek LLM altyapısı gerektiren 7/24 AI Asistan modülünün Ücretsiz pakette yer almamasıdır; Başlangıç ve üstü tüm paketlerde AI Asistan tamamen açıktır."],
  ["Sektörüme ve işletme tipime göre baremler nasıl belirleniyor?", "9 ana sektör ve 168 alt işletme tipinin her biri için özel kaynak baremleri (koltuk, oda, saha, masa, araç lifti) belirlenmiştir. Sayfamızdaki seçiciden işletmenizi seçerek tüm paket kapasitelerini anında inceleyebilirsiniz."],
  ["Rezervasyon başına komisyon alıyor musunuz?", "Hayır. Tüm BooKi paketlerinde rezervasyon komisyonu %0'dır. Kazancınız tamamen sizde kalır."],
  ["WhatsApp ile randevu ve onay nasıl çalışır?", "Müşterileriniz ister WhatsApp'tan yazarak AI asistan ile anında randevu alsın, ister webden alsın; otomatik WhatsApp onay ve hatırlatma mesajları ile no-show oranı %40 düşer."],
  ["Kurulum için teknik bilgi gerekir mi?", "Hayır. Uzman ekibimiz 15 dakika içinde sisteminizi hazır hale getirir, menü ve uzmanlarınızı tanımlar ve güvenle yayına alır."],
  ["Paketimi daha sonra değiştirebilir miyim?", "Evet. Hiçbir taahhüt bulunmaz. İşletmeniz büyüdükçe dilediğiniz an bir üst pakete geçebilir veya dilediğiniz an üyeliğinizi iptal edebilirsiniz."]
];

function Avatar({ initials, className = "" }: { initials: string; className?: string }) {
  return <span className={`avatar ${className}`}>{initials}</span>;
}

function ProductPreview() {
  return (
    <div className="product-preview real-product-preview">
      <div className="preview-glow" />
      <div className="real-screen-frame">
        <img
          src="/manus-storage/real-dashboard_c6d8a973.webp"
          alt="BooKi gerçek dashboard ekranı"
          width={1280}
          height={720}
          fetchPriority="high"
        />
      </div>
      <div className="floating-card floating-top">
        <span className="floating-icon"><Zap size={14} /></span>
        <span><b>Tüm randevularınız</b><small>Tek ekranda, net akış</small></span>
      </div>
      <div className="floating-card floating-bottom">
        <span className="floating-icon peach"><Check size={14} /></span>
        <span><b>Takvim senkronize</b><small>Ekip ve uygunluklar hazır</small></span>
      </div>
    </div>
  );
}

export default function Home() {
  const [openFaq, setOpenFaq] = useState(0);
  const [isAnnual, setIsAnnual] = useState(false);
  const [activeSectorTab, setActiveSectorTab] = useState("restoran-masa-rezervasyon");
  const [showComparison, setShowComparison] = useState(false);

  // Sector and subsector selection for dynamic pricing calculation on home page
  const mainSectorList = useMemo(() => Object.keys(MAIN_SECTORS), []);
  const [selectedMainSector, setSelectedMainSector] = useState<string>("Güzellik / Kişisel Bakım");

  const filteredSubsectors = useMemo(() => {
    return SUBSECTOR_NAMES.filter(
      (name) => SECTOR_MATRIX[name]?.main_sector === selectedMainSector
    );
  }, [selectedMainSector]);

  const [selectedSubsector, setSelectedSubsector] = useState<string>("Kuaför");

  const handleMainSectorChange = (sector: string) => {
    setSelectedMainSector(sector);
    const subList = SUBSECTOR_NAMES.filter(
      (name) => SECTOR_MATRIX[name]?.main_sector === sector
    );
    if (subList.length > 0) {
      setSelectedSubsector(subList[0]);
    }
  };

  const currentData: SubsectorData = SECTOR_MATRIX[selectedSubsector] || SECTOR_MATRIX["Kuaför"];
  const resourceLabel = currentData?.resource_type_label || "Kaynak / Koltuk / Masa";

  const whatsappNumber = "905062505562";
  const whatsappUrl = `https://wa.me/${whatsappNumber}?text=${encodeURIComponent("Merhaba BooKi ekibi, işletmem için 7/24 AI destekli online randevu ve masa rezervasyon sistemi hakkında canlı demo almak istiyorum.")}`;

  useEffect(() => {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("visible");
        }
      });
    }, { threshold: 0.1 });
    document.querySelectorAll("[data-reveal]").forEach((element) => observer.observe(element));
    return () => observer.disconnect();
  }, []);

  const currentSectorData = SECTORS[activeSectorTab] || SECTORS["restoran-masa-rezervasyon"];

  const plans = [
    {
      key: "free",
      name: "Ücretsiz",
      badge: "Sonsuza Kadar",
      role: "Denemek veya yeni başlayanlar için",
      monthly: PLAN_PRICES.free.monthly,
      annual: PLAN_PRICES.free.annual,
      description: "Tüm temel ve kurumsal özellikler açık. Sadece AI Asistan hariç.",
      limits: currentData.tiers.free,
      cta: "Ücretsiz Başla",
      icon: Zap,
      popular: false,
      aiIncluded: false,
    },
    {
      key: "basic",
      name: "Başlangıç",
      badge: "KOBİ Giriş",
      role: "Butik işletmeler ve uzman stüdyoları",
      monthly: PLAN_PRICES.basic.monthly,
      annual: PLAN_PRICES.basic.annual,
      description: "7/24 AI Asistan dahil, tüm modüller ve tam entegrasyon.",
      limits: currentData.tiers.basic,
      cta: "Başlangıç Seç",
      icon: Layers,
      popular: false,
      aiIncluded: true,
    },
    {
      key: "pro",
      name: "Orta (Pro)",
      badge: "EN POPÜLER",
      role: "Büyüyen salon, restoran ve klinikler",
      monthly: PLAN_PRICES.pro.monthly,
      annual: PLAN_PRICES.pro.annual,
      description: "Geniş kapasite, gelişmiş AI optimizasyonu ve tam operasyon.",
      limits: currentData.tiers.pro,
      cta: "Pro ile Büyü",
      icon: Sparkles,
      popular: true,
      aiIncluded: true,
    },
    {
      key: "premium",
      name: "Premium",
      badge: "Yoğun İşletme",
      role: "Yüksek hacimli ve çok şubeli işletmeler",
      monthly: PLAN_PRICES.premium.monthly,
      annual: PLAN_PRICES.premium.annual,
      description: "Sektörel tavan kapasiteler ve öncelikli destek kanalları.",
      limits: currentData.tiers.premium,
      cta: "Premium Tercih Et",
      icon: Crown,
      popular: false,
      aiIncluded: true,
    },
    {
      key: "custom",
      name: "Özel (Enterprise)",
      badge: "Zincir & Franchise",
      role: "Kurumsal zincirler ve limitsiz talepler",
      monthly: PLAN_PRICES.custom.monthly,
      annual: PLAN_PRICES.custom.annual,
      description: "Özel SLA, sınırsız şube, özel sunucu ve özel API entegrasyonu.",
      limits: currentData.tiers.custom,
      cta: "Teklif Alın",
      icon: Building2,
      popular: false,
      aiIncluded: true,
    },
  ];

  return (
    <div id="top" className="site-shell">
      <SEOHead
        title="BooKi — 9 Sektör, 168 İşletme Tipine Özel %0 Komisyonlu Randevu & Rezervasyon Sistemi"
        description="Restoran, güzellik salonu, klinik, spa, spor ve tüm işletmeler için %0 komisyonlu online randevu, masa yönetimi ve 7/24 AI Asistanı. 14 gün ücretsiz deneyin."
        canonicalPath="/"
      />

      <a className="skip-link" href="#main-content">Ana içeriğe geç</a>

      <Navbar />

      <main id="main-content" tabIndex={-1}>
        {/* Hero Section */}
        <section className="hero">
          <div className="hero-grid">
            <div className="hero-copy" data-reveal>
              <div className="eyebrow">
                <i /> %0 KOMİSYON · 9 ANA SEKTÖR · 168 İŞLETME TİPİ
              </div>
              <h1>
                İşletmeniz<br />
                <span>akışa geçsin.</span>
              </h1>
              <p>
                BooKi; restoran, güzellik salonu, klinik, spor ve tüm sektörler için randevu, masa, 
                POS, adisyon ve <strong>7/24 Çok Kanallı AI Asistanını</strong> tek platformda buluşturur. 
                Komisyon yok, tüm özellikler her pakette açık!
              </p>
              
              <div className="hero-buttons">
                <a
                  className="button button-whatsapp"
                  href={whatsappUrl}
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  <MessageCircle size={17} /> WhatsApp ile Canlı Demo İste
                </a>
                <Link className="button button-outline" href="/fiyatlar">
                  Fiyatları &amp; Paketleri İncele <MoveRight size={15} />
                </Link>
              </div>

              <div className="hero-meta">
                <div className="avatar-stack">
                  <Avatar initials="E" className="avatar-teal" />
                  <Avatar initials="S" className="avatar-coral" />
                  <Avatar initials="N" className="avatar-blue" />
                  <b>+2k</b>
                </div>
                <span>
                  <strong>Daha sakin, daha karlı günler</strong><br />
                  %0 komisyon · Kurulumda ödeme güvencesi
                </span>
              </div>
            </div>
            <div className="hero-product" data-reveal>
              <ProductPreview />
            </div>
          </div>
          
          <div className="hero-ticker">
            <span>9 Ana Sektör ve 168 İşletme Tipine Özel Çözümler:</span>
            <div>
              <Link href="/sektorler/restoran-masa-rezervasyon"><b><Utensils size={14} /> RESTORAN &amp; YEME-İÇME</b></Link>
              <Link href="/sektorler/guzellik-salonu-randevu"><b><Sparkles size={14} /> GÜZELLİK &amp; KİŞİSEL BAKIM</b></Link>
              <Link href="/sektorler/klinik-doktor-randevu"><b><Stethoscope size={14} /> SAĞLIK &amp; KLİNİK</b></Link>
              <Link href="/fiyatlar"><b><Zap size={14} /> SPOR &amp; FITNESS</b></Link>
              <Link href="/fiyatlar"><b><Building2 size={14} /> KONAKLAMA &amp; OTEL</b></Link>
              <Link href="/fiyatlar"><b><UsersRound size={14} /> TÜM 168 TİP</b></Link>
            </div>
          </div>
        </section>

        {/* 9 Sektör Gezgini Kartları */}
        <section className="section" id="sektorler" style={{ background: "#f8fafc", padding: "4rem 1.5rem" }}>
          <div className="section-container">
            <div className="section-header-center">
              <span className="badge-sub">SEKTÖREL MİMARİ</span>
              <h2>9 Ana Sektör &amp; <em>168 İşletme Tipine Özel Altyapı</em></h2>
              <p style={{ maxWidth: "720px", margin: "0.5rem auto 2.5rem auto", color: "#64748b" }}>
                Her işletmenin dinamiği farklıdır. BooKi; kuaförden halı sahaya, butik otelden oto ekspertize kadar her sektörün kendi kapasite birimiyle çalışır.
              </p>
            </div>

            <div
              style={{
                display: "grid",
                gridTemplateColumns: "repeat(auto-fit, minmax(260px, 1fr))",
                gap: "1.25rem"
              }}
            >
              {mainSectorList.map((sectorName) => {
                const info = MAIN_SECTORS[sectorName];
                const subCount = SUBSECTOR_NAMES.filter(
                  (n) => SECTOR_MATRIX[n]?.main_sector === sectorName
                ).length;

                return (
                  <div
                    key={sectorName}
                    style={{
                      background: "#ffffff",
                      border: "1px solid #e2e8f0",
                      borderRadius: "14px",
                      padding: "1.5rem",
                      boxShadow: "0 4px 12px rgba(0,0,0,0.02)",
                      display: "flex",
                      flexDirection: "column",
                      justifyContent: "space-between"
                    }}
                  >
                    <div>
                      <div style={{ fontSize: "2rem", marginBottom: "0.5rem" }}>{info.icon}</div>
                      <h3 style={{ fontSize: "1.1rem", fontWeight: 700, color: "#0a1724", marginBottom: "0.3rem" }}>
                        {sectorName}
                      </h3>
                      <p style={{ fontSize: "0.85rem", color: "#64748b", marginBottom: "1rem" }}>
                        <strong>{subCount} farklı işletme tipi</strong> için optimize edilmiş kapasite baremleri ve rezervasyon akışı.
                      </p>
                    </div>

                    <Link
                      href="/fiyatlar"
                      style={{
                        display: "inline-flex",
                        alignItems: "center",
                        gap: "0.4rem",
                        fontSize: "0.85rem",
                        fontWeight: 600,
                        color: "var(--teal, #2e9999)",
                        textDecoration: "none"
                      }}
                    >
                      Baremleri &amp; Paketleri Gör <ArrowUpRight size={14} />
                    </Link>
                  </div>
                );
              })}
            </div>
          </div>
        </section>

        {/* 7/24 Çok Kanallı AI Asistan Vitrini */}
        <section
          className="section"
          id="yapay-zeka"
          style={{
            background: "linear-gradient(160deg, #0a1724 0%, #0d233a 100%)",
            color: "#ffffff",
            padding: "5rem 1.5rem"
          }}
        >
          <div className="section-container">
            <div
              style={{
                display: "grid",
                gridTemplateColumns: "repeat(auto-fit, minmax(320px, 1fr))",
                gap: "3.5rem",
                alignItems: "center"
              }}
            >
              <div>
                <div
                  style={{
                    display: "inline-flex",
                    alignItems: "center",
                    gap: "0.4rem",
                    background: "rgba(16, 185, 129, 0.2)",
                    color: "#6ee7b7",
                    padding: "0.35rem 0.85rem",
                    borderRadius: "999px",
                    fontSize: "0.8rem",
                    fontWeight: 700,
                    marginBottom: "1rem"
                  }}
                >
                  <Bot size={15} /> 7/24 ÇOK KANALLI AI ASİSTANI
                </div>

                <h2 style={{ fontSize: "clamp(2rem, 3.5vw, 2.7rem)", color: "#fff", lineHeight: 1.2, marginBottom: "1rem" }}>
                  Müşterileriniz WhatsApp'tan Yazsın, <br />
                  <em style={{ color: "#e8d5b0", fontStyle: "normal" }}>Yapay Zeka Randevuyu Kapatsın.</em>
                </h2>

                <p style={{ color: "rgba(255,255,255,0.85)", fontSize: "1.02rem", lineHeight: 1.65, marginBottom: "1.5rem" }}>
                  Gece yarısı veya en yoğun anınızda bile tek bir randevu kaçırmayın. BooKi AI Asistanı; 
                  müşterinizin doğal dilini anlar, takvimdeki boş saatleri önerir, uzmanı seçtirir, randevuyu teyit eder ve panele anında işler.
                </p>

                <div style={{ display: "flex", flexDirection: "column", gap: "0.9rem", marginBottom: "2rem" }}>
                  <div style={{ display: "flex", gap: "0.6rem", alignItems: "flex-start" }}>
                    <CheckCircle2 size={18} color="#10b981" style={{ flexShrink: 0, marginTop: "2px" }} />
                    <span style={{ fontSize: "0.92rem", color: "rgba(255,255,255,0.9)" }}>
                      <strong>Çok Kanallı Entegrasyon:</strong> WhatsApp Cloud API, Instagram DM, Web ve Google Haritalar'da eşzamanlı çalışır.
                    </span>
                  </div>
                  <div style={{ display: "flex", gap: "0.6rem", alignItems: "flex-start" }}>
                    <CheckCircle2 size={18} color="#10b981" style={{ flexShrink: 0, marginTop: "2px" }} />
                    <span style={{ fontSize: "0.92rem", color: "rgba(255,255,255,0.9)" }}>
                      <strong>Doğal Dil Anlayışı:</strong> "Yarın akşam 7'ye 4 kişilik masa var mı?" veya "Cumartesi Ahmet Bey'e saç kesimi" sorularını hatasız çözümler.
                    </span>
                  </div>
                  <div style={{ display: "flex", gap: "0.6rem", alignItems: "flex-start" }}>
                    <CheckCircle2 size={18} color="#10b981" style={{ flexShrink: 0, marginTop: "2px" }} />
                    <span style={{ fontSize: "0.92rem", color: "rgba(255,255,255,0.9)" }}>
                      <strong>No-Show Katili:</strong> Randevudan 24 saat ve 2 saat önce otomatik teyit isteyip iptalleri waitlist'e devreder.
                    </span>
                  </div>
                </div>

                <a
                  href={whatsappUrl}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="button button-whatsapp"
                  style={{ display: "inline-flex", alignItems: "center", gap: "0.5rem" }}
                >
                  <MessageCircle size={17} /> AI Canlı Demosunu WhatsApp'ta Deneyin
                </a>
              </div>

              {/* Simulated WhatsApp Chat UI Card */}
              <div
                style={{
                  background: "#075e54",
                  borderRadius: "20px",
                  overflow: "hidden",
                  boxShadow: "0 24px 60px rgba(0,0,0,0.5)",
                  border: "1px solid rgba(255,255,255,0.15)",
                  maxWidth: "420px",
                  margin: "0 auto"
                }}
              >
                {/* Chat Header */}
                <div
                  style={{
                    background: "#128c7e",
                    padding: "0.9rem 1.2rem",
                    display: "flex",
                    alignItems: "center",
                    gap: "0.75rem",
                    color: "#fff"
                  }}
                >
                  <div
                    style={{
                      width: "38px",
                      height: "38px",
                      borderRadius: "50%",
                      background: "#25d366",
                      display: "flex",
                      alignItems: "center",
                      justifyContent: "center",
                      fontWeight: 800,
                      fontSize: "1rem"
                    }}
                  >
                    <Bot size={22} color="#fff" />
                  </div>
                  <div>
                    <strong style={{ display: "block", fontSize: "0.95rem" }}>BooKi AI Asistanı</strong>
                    <span style={{ fontSize: "0.75rem", color: "#dcf8c6", display: "inline-flex", alignItems: "center", gap: "4px" }}>
                      <span style={{ width: "7px", height: "7px", borderRadius: "50%", background: "#4ade80" }} /> Çevrimiçi · 7/24
                    </span>
                  </div>
                </div>

                {/* Chat Body */}
                <div
                  style={{
                    background: "#e5ddd5",
                    padding: "1.2rem",
                    display: "flex",
                    flexDirection: "column",
                    gap: "0.85rem",
                    fontSize: "0.85rem",
                    minHeight: "360px"
                  }}
                >
                  {/* Incoming Client */}
                  <div
                    style={{
                      alignSelf: "flex-end",
                      background: "#e7fed8",
                      color: "#111827",
                      padding: "0.6rem 0.9rem",
                      borderRadius: "12px 0 12px 12px",
                      maxWidth: "80%",
                      boxShadow: "0 1px 2px rgba(0,0,0,0.1)"
                    }}
                  >
                    Merhaba, cumartesi günü saat 14:00 civarı saç kesimi ve sakal için boşluğunuz var mı?
                    <div style={{ fontSize: "0.68rem", color: "#6b7280", textAlign: "right", marginTop: "2px" }}>14:32</div>
                  </div>

                  {/* AI Response */}
                  <div
                    style={{
                      alignSelf: "flex-start",
                      background: "#ffffff",
                      color: "#111827",
                      padding: "0.65rem 0.9rem",
                      borderRadius: "0 12px 12px 12px",
                      maxWidth: "85%",
                      boxShadow: "0 1px 2px rgba(0,0,0,0.1)"
                    }}
                  >
                    Merhaba Sinan Bey! 👋 Cumartesi günü için Baş Berber <strong>Ahmet Bey</strong> saat 14:00'te ve <strong>Mehmet Bey</strong> saat 14:30'da müsait.
                    <br /><br />
                    Hangisine randevunuzu onaylayalım?
                    <div style={{ fontSize: "0.68rem", color: "#6b7280", textAlign: "right", marginTop: "2px" }}>14:32 · AI Bot</div>
                  </div>

                  {/* Client response */}
                  <div
                    style={{
                      alignSelf: "flex-end",
                      background: "#e7fed8",
                      color: "#111827",
                      padding: "0.6rem 0.9rem",
                      borderRadius: "12px 0 12px 12px",
                      maxWidth: "80%",
                      boxShadow: "0 1px 2px rgba(0,0,0,0.1)"
                    }}
                  >
                    Ahmet Bey saat 14:00 harika olur.
                    <div style={{ fontSize: "0.68rem", color: "#6b7280", textAlign: "right", marginTop: "2px" }}>14:33</div>
                  </div>

                  {/* AI Confirmation */}
                  <div
                    style={{
                      alignSelf: "flex-start",
                      background: "#ffffff",
                      color: "#111827",
                      padding: "0.65rem 0.9rem",
                      borderRadius: "0 12px 12px 12px",
                      maxWidth: "85%",
                      boxShadow: "0 1px 2px rgba(0,0,0,0.1)"
                    }}
                  >
                    ✅ <strong>Randevunuz Oluşturuldu!</strong>
                    <br />
                    📅 <strong>Tarih:</strong> 28 Eylül Cumartesi, 14:00
                    <br />
                    ✂️ <strong>Hizmet:</strong> Saç &amp; Sakal Bakımı (Ahmet Usta)
                    <br /><br />
                    Randevu takviminize eklendi. Gelmeden 2 saat önce hatırlatma göndereceğim. Görüşmek üzere! 💈
                    <div style={{ fontSize: "0.68rem", color: "#6b7280", textAlign: "right", marginTop: "2px" }}>14:33 · AI Bot</div>
                  </div>
                </div>

                {/* Input Footer */}
                <div
                  style={{
                    background: "#f0f2f5",
                    padding: "0.6rem 1rem",
                    display: "flex",
                    alignItems: "center",
                    gap: "0.5rem"
                  }}
                >
                  <div
                    style={{
                      flex: 1,
                      background: "#fff",
                      borderRadius: "20px",
                      padding: "0.45rem 0.9rem",
                      fontSize: "0.82rem",
                      color: "#94a3b8"
                    }}
                  >
                    Mesaj yazın...
                  </div>
                  <Send size={16} color="#075e54" />
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* 24 Modül & 6 Temel Sütun */}
        <section className="section" id="ozellikler" style={{ padding: "4.5rem 1.5rem" }}>
          <div className="section-container">
            <div className="section-header-center">
              <span className="badge-sub">KOMPLE SİSTEM</span>
              <h2>Tek Bir Platform. <em>24 Entegre Modül.</em></h2>
              <p style={{ maxWidth: "700px", margin: "0.5rem auto 2.5rem auto", color: "#64748b" }}>
                Ayrı ayrı adisyon yazılımı, randevu takip programı, muhasebe eklentisi veya SMS paneli aramayın. BooKi tüm operasyonunuzu birleştirir.
              </p>
            </div>

            <div
              style={{
                display: "grid",
                gridTemplateColumns: "repeat(auto-fit, minmax(320px, 1fr))",
                gap: "1.5rem"
              }}
            >
              {pillars.map((pil, idx) => {
                const IconComp = pil.icon;
                return (
                  <div
                    key={idx}
                    style={{
                      background: "#ffffff",
                      border: "1px solid #e2e8f0",
                      borderRadius: "16px",
                      padding: "1.75rem",
                      boxShadow: "0 6px 16px rgba(0,0,0,0.03)"
                    }}
                  >
                    <div
                      style={{
                        width: "42px",
                        height: "42px",
                        borderRadius: "10px",
                        background: "rgba(46, 153, 149, 0.12)",
                        color: "var(--teal, #2e9999)",
                        display: "flex",
                        alignItems: "center",
                        justifyContent: "center",
                        marginBottom: "1rem"
                      }}
                    >
                      <IconComp size={22} />
                    </div>
                    <h3 style={{ fontSize: "1.15rem", fontWeight: 800, color: "#0a1724", marginBottom: "0.5rem" }}>
                      {pil.title}
                    </h3>
                    <p style={{ fontSize: "0.9rem", color: "#64748b", lineHeight: 1.6 }}>
                      {pil.desc}
                    </p>
                  </div>
                );
              })}
            </div>
          </div>
        </section>

        {/* Fiyatlar & Dinamik Barem Simülatörü */}
        <section className="pricing section" id="fiyatlar">
          <div className="pricing-heading" data-reveal>
            <div className="section-label"><span /> ŞEFFAF FİYATLANDIRMA</div>
            <h2>İşletmeniz Büyüdükçe<br /><em>BooKi de Sizinle Büyür.</em></h2>
            <p>Tüm kurumsal modüller açık. Komisyon yok. Sadece kendi sektörünüzün kapasite baremlerini seçin.</p>

            <div className="billing-toggle" role="group" aria-label="Faturalandırma dönemi">
              <button
                type="button"
                className={!isAnnual ? "active" : ""}
                onClick={() => setIsAnnual(false)}
              >
                Aylık ödeme <small>Taahhütsüz</small>
              </button>
              <button
                type="button"
                className={isAnnual ? "active" : ""}
                onClick={() => setIsAnnual(true)}
              >
                Yıllık ödeme <span>%20 İNDİRİM (2 AY ÜCRETSİZ)</span>
              </button>
            </div>

            {/* In-page Sector Picker */}
            <div
              style={{
                marginTop: "2rem",
                background: "#ffffff",
                border: "2px solid rgba(46, 153, 149, 0.3)",
                borderRadius: "14px",
                padding: "1.25rem 1.75rem",
                maxWidth: "780px",
                margin: "2rem auto 1rem auto",
                boxShadow: "0 8px 24px rgba(0,0,0,0.04)",
                textAlign: "left"
              }}
            >
              <div style={{ display: "flex", alignItems: "center", gap: "0.4rem", marginBottom: "0.75rem" }}>
                <Layers size={18} color="var(--teal, #2e9999)" />
                <strong style={{ fontSize: "0.95rem", color: "#0a1724" }}>
                  Sektörünüzü Seçin, Paket Baremlerini Canlı İzleyin:
                </strong>
              </div>

              <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: "1rem" }}>
                <div>
                  <label style={{ display: "block", fontSize: "0.8rem", fontWeight: 600, color: "#64748b", marginBottom: "0.3rem" }}>
                    Ana Sektör:
                  </label>
                  <select
                    value={selectedMainSector}
                    onChange={(e) => handleMainSectorChange(e.target.value)}
                    style={{
                      width: "100%",
                      padding: "0.6rem 0.8rem",
                      borderRadius: "8px",
                      border: "1px solid #cbd5e1",
                      background: "#f8fafc",
                      fontSize: "0.9rem",
                      fontWeight: 600,
                      color: "#0f172a"
                    }}
                  >
                    {mainSectorList.map((m) => (
                      <option key={m} value={m}>
                        {MAIN_SECTORS[m]?.icon} {m}
                      </option>
                    ))}
                  </select>
                </div>

                <div>
                  <label style={{ display: "block", fontSize: "0.8rem", fontWeight: 600, color: "#64748b", marginBottom: "0.3rem" }}>
                    Alt İşletme:
                  </label>
                  <select
                    value={selectedSubsector}
                    onChange={(e) => setSelectedSubsector(e.target.value)}
                    style={{
                      width: "100%",
                      padding: "0.6rem 0.8rem",
                      borderRadius: "8px",
                      border: "1px solid #cbd5e1",
                      background: "#f8fafc",
                      fontSize: "0.9rem",
                      fontWeight: 600,
                      color: "#0f172a"
                    }}
                  >
                    {filteredSubsectors.map((s) => (
                      <option key={s} value={s}>
                        {s}
                      </option>
                    ))}
                  </select>
                </div>
              </div>
            </div>

            {/* Rule Banner */}
            <div
              style={{
                display: "inline-flex",
                alignItems: "center",
                gap: "0.5rem",
                background: "rgba(16, 185, 129, 0.12)",
                border: "1px solid rgba(16, 185, 129, 0.3)",
                padding: "0.5rem 1rem",
                borderRadius: "999px",
                color: "#065f46",
                fontSize: "0.85rem",
                fontWeight: 600,
                marginTop: "0.5rem"
              }}
            >
              <Sparkles size={15} color="#059669" />
              <span>
                <strong>Tüm Özellikler Açık:</strong> POS, Adisyon, Raporlar, e-Fatura, Stok dahil! Tek istisna: <u>Ücretsiz planda AI Asistan hariçtir.</u>
              </span>
            </div>
          </div>
          
          <div
            style={{
              display: "grid",
              gridTemplateColumns: "repeat(auto-fit, minmax(220px, 1fr))",
              gap: "1.25rem",
              alignItems: "stretch",
              maxWidth: "1240px",
              margin: "2rem auto 0 auto",
              padding: "0 1rem"
            }}
          >
            {plans.map((plan) => {
              const IconComp = plan.icon;
              const isPro = plan.popular;

              return (
                <article
                  key={plan.key}
                  style={{
                    background: isPro ? "#ffffff" : "#fbfcfe",
                    border: isPro ? "2px solid var(--teal, #2e9999)" : "1px solid #e2e8f0",
                    borderRadius: "16px",
                    padding: "1.5rem 1.1rem",
                    display: "flex",
                    flexDirection: "column",
                    position: "relative",
                    boxShadow: isPro ? "0 16px 36px rgba(46, 153, 149, 0.15)" : "0 4px 12px rgba(0,0,0,0.03)",
                    transform: isPro ? "scale(1.02)" : "none"
                  }}
                >
                  {isPro && (
                    <div
                      style={{
                        position: "absolute",
                        top: "-11px",
                        left: "50%",
                        transform: "translateX(-50%)",
                        background: "var(--teal, #2e9999)",
                        color: "#fff",
                        fontSize: "0.7rem",
                        fontWeight: 800,
                        padding: "0.2rem 0.65rem",
                        borderRadius: "999px",
                        display: "inline-flex",
                        alignItems: "center",
                        gap: "0.25rem"
                      }}
                    >
                      <Crown size={11} /> EN POPÜLER
                    </div>
                  )}

                  <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", marginBottom: "0.5rem" }}>
                    <span
                      style={{
                        width: "34px",
                        height: "34px",
                        borderRadius: "8px",
                        background: isPro ? "rgba(46, 153, 149, 0.12)" : "#f1f5f9",
                        color: isPro ? "var(--teal, #2e9999)" : "#475569",
                        display: "inline-flex",
                        alignItems: "center",
                        justifyContent: "center"
                      }}
                    >
                      <IconComp size={18} />
                    </span>
                    <span style={{ fontSize: "0.72rem", fontWeight: 700, color: "#64748b", textTransform: "uppercase" }}>
                      {plan.badge}
                    </span>
                  </div>

                  <h3 style={{ fontSize: "1.2rem", fontWeight: 800, color: "#0a1724", marginBottom: "0.2rem" }}>
                    {plan.name}
                  </h3>
                  <p style={{ fontSize: "0.78rem", color: "#64748b", minHeight: "2.2rem", marginBottom: "0.8rem" }}>
                    {plan.role}
                  </p>

                  <div style={{ marginBottom: "0.8rem" }}>
                    <div style={{ display: "flex", alignItems: "baseline", gap: "0.25rem" }}>
                      <strong style={{ fontSize: "1.75rem", fontWeight: 900, color: "#0a1724" }}>
                        {isAnnual ? plan.annual : plan.monthly}
                      </strong>
                      {plan.key !== "custom" && (
                        <span style={{ fontSize: "0.8rem", color: "#64748b" }}>/ ay</span>
                      )}
                    </div>
                    {isAnnual && plan.key !== "custom" && plan.key !== "free" && (
                      <div style={{ fontSize: "0.7rem", color: "#059669", fontWeight: 700 }}>
                        %20 tasarruf
                      </div>
                    )}
                  </div>

                  {/* Limits */}
                  <div
                    style={{
                      background: isPro ? "rgba(46, 153, 149, 0.06)" : "#f8fafc",
                      border: "1px solid #e2e8f0",
                      borderRadius: "8px",
                      padding: "0.6rem",
                      marginBottom: "1rem",
                      fontSize: "0.78rem"
                    }}
                  >
                    <div style={{ display: "flex", justifyContent: "space-between", marginBottom: "0.25rem" }}>
                      <span style={{ color: "#64748b" }}>{resourceLabel}:</span>
                      <strong>{plan.limits.resource_limit}</strong>
                    </div>
                    <div style={{ display: "flex", justifyContent: "space-between", marginBottom: "0.25rem" }}>
                      <span style={{ color: "#64748b" }}>Personel:</span>
                      <strong>{plan.limits.staff_limit}</strong>
                    </div>
                    <div style={{ display: "flex", justifyContent: "space-between" }}>
                      <span style={{ color: "#64748b" }}>Randevu:</span>
                      <strong>{plan.limits.appointment_limit}</strong>
                    </div>
                  </div>

                  <a
                    className={isPro ? "button button-primary" : "button button-outline"}
                    href="#demo-section"
                    style={{ width: "100%", justifyContent: "center", padding: "0.65rem", fontSize: "0.85rem", marginBottom: "1rem" }}
                  >
                    {plan.cta} <MoveRight size={13} />
                  </a>

                  <ul style={{ listStyle: "none", padding: 0, margin: 0, display: "flex", flexDirection: "column", gap: "0.45rem", fontSize: "0.78rem" }}>
                    <li style={{ display: "flex", alignItems: "center", gap: "0.4rem" }}>
                      {plan.aiIncluded ? (
                        <>
                          <Bot size={14} color="#059669" />
                          <strong style={{ color: "#059669" }}>7/24 AI Asistan Dahil</strong>
                        </>
                      ) : (
                        <>
                          <X size={14} color="#ef4444" />
                          <span style={{ color: "#94a3b8", textDecoration: "line-through" }}>AI Asistan (Ücretsiz Hariç)</span>
                        </>
                      )}
                    </li>
                    <li style={{ display: "flex", alignItems: "center", gap: "0.4rem", color: "#334155" }}>
                      <Check size={13} color="#059669" />
                      <span>POS &amp; Adisyon</span>
                    </li>
                    <li style={{ display: "flex", alignItems: "center", gap: "0.4rem", color: "#334155" }}>
                      <Check size={13} color="#059669" />
                      <span>e-Fatura &amp; Raporlar</span>
                    </li>
                    <li style={{ display: "flex", alignItems: "center", gap: "0.4rem", color: "#334155" }}>
                      <Check size={13} color="#059669" />
                      <span>Stok &amp; Depo</span>
                    </li>
                    <li style={{ display: "flex", alignItems: "center", gap: "0.4rem", color: "#334155" }}>
                      <Check size={13} color="#059669" />
                      <span>%0 Komisyon</span>
                    </li>
                  </ul>
                </article>
              );
            })}
          </div>
          
          <p className="pricing-trust" style={{ marginTop: "2rem" }}>
            <ShieldCheck size={16} /> Kredi kartı gerekmez. <strong>Kurulum sırasında güvenli ödeme.</strong> İstediğiniz an iptal edin.
          </p>

          <div style={{ textAlign: "center", marginTop: "1.5rem" }}>
            <Link href="/fiyatlar" className="button button-outline" style={{ display: "inline-flex", gap: "0.5rem" }}>
              <BarChart3 size={16} /> Detaylı Karşılaştırma Tablosunu Aç <ArrowUpRight size={15} />
            </Link>
          </div>
        </section>

        {/* 3 Adımda Akış */}
        <section className="flow section" id="akış">
          <div className="flow-grid">
            <div data-reveal>
              <div className="section-label"><span /> KURULUMDAN SONRA</div>
              <h2>Bir sonraki iyi gününüz<br /><em>üç adım uzakta.</em></h2>
              <p>Teknolojiyle aranıza yeni bir iş daha koymuyoruz. Gününüzü hafifleten bir sistem kuruyoruz.</p>
              <Link className="button button-outline" href="/nasil-calisir">Akış rehberini inceleyin <MoveRight size={15} /></Link>
            </div>
            <div className="flow-list" data-reveal>
              <div className="flow-item"><b>01</b><span className="flow-icon"><Command size={17} /></span><div><h3>İşletmenizi tanımlayın</h3><p>Hizmetlerinizi, çalışma saatlerinizi ve ekibinizi birkaç dakikada ekleyin.</p></div></div>
              <div className="flow-item"><b>02</b><span className="flow-icon"><CalendarDays size={17} /></span><div><h3>Rezervasyon sayfanızı açın</h3><p>Müşterilerinizin size ulaşacağı sade, markanıza özel akışı paylaşın.</p></div></div>
              <div className="flow-item"><b>03</b><span className="flow-icon"><Sparkles size={17} /></span><div><h3>Gününüzü yönetmeye başlayın</h3><p>Randevular, bildirimler ve raporlar sakin merkezinizde buluşsun.</p></div></div>
            </div>
          </div>
        </section>

        {/* Testimonial / Proof */}
        <section className="proof section">
          <div className="proof-card" data-reveal>
            <div className="quote-symbol">“</div>
            <blockquote>BooKi, randevuları ve ekip uygunluklarını tek yerde görünür kılar. Böylece günümüz daha sakin, müşterilerimizle geçirdiğimiz zaman daha kaliteli.</blockquote>
            <div className="proof-author">
              <Avatar initials="A" className="avatar-coral" />
              <span><b>BooKi kullanıcı deneyimi</b><small>Randevu odaklı işletmeler için</small></span>
            </div>
            <div className="proof-badge">
              <ShieldCheck size={17} />
              <span>Birlikte daha sakin<br /><b>çalışma günleri</b></span>
            </div>
          </div>
        </section>

        {/* FAQ Section */}
        <section className="faq section" id="sss">
          <div className="faq-grid">
            <div data-reveal>
              <div className="section-label"><span /> MERAK EDİLENLER</div>
              <h2>Online randevu sistemi hakkında<br /><em>sorularınız mı var?</em></h2>
              <p>9 sektör, 168 alt işletme tipi, 7/24 AI Asistanı ve %0 komisyon politikamız hakkında sorularınızı yanıtlayalım.</p>
              <a className="arrow-link" href="mailto:hello@kibusiness.co">Bize yazın <MoveRight size={15} /></a>
            </div>
            <div className="faq-list" data-reveal>
              {faqItems.map(([question, answer], index) => (
                <div className={openFaq === index ? "faq-item open" : "faq-item"} key={question}>
                  <button onClick={() => setOpenFaq(openFaq === index ? -1 : index)}>
                    <span>{question}</span>
                    <ChevronDown size={18} />
                  </button>
                  <div className="faq-answer"><p>{answer}</p></div>
                </div>
              ))}
            </div>
          </div>
        </section>

        {/* Unified Sales Funnel Final Section */}
        <div id="demo-section">
          <SalesFunnelSection
            title="İyi iş, daha iyi bir akışla başlar."
            subtitle="İşletmenizin ritmini birlikte sadeleştirelim. Görüşelim, 15 dakikada kuralım. Ödemeyi kurulumda güvenle yapın."
          />
        </div>
      </main>

      <StickyContactBar />
      <Footer />
    </div>
  );
}

