import { useState, useMemo } from "react";
import { SEOHead } from "@/components/SEOHead";
import { Navbar } from "@/components/Navbar";
import { Footer } from "@/components/Footer";
import { StickyContactBar } from "@/components/StickyContactBar";
import { SalesFunnelSection } from "@/components/SalesFunnelSection";
import {
  Check,
  Building2,
  Sparkles,
  Zap,
  Crown,
  ShieldCheck,
  MoveRight,
  ChevronDown,
  X,
  Bot,
  Layers,
  CheckCircle2,
  HelpCircle
} from "lucide-react";
import {
  MAIN_SECTORS,
  SECTOR_MATRIX,
  SUBSECTOR_NAMES,
  PLAN_PRICES,
  type SubsectorData
} from "@/data/sectorPricingMatrix";

export default function PricingPage() {
  const [isAnnual, setIsAnnual] = useState(false);
  const [openFaq, setOpenFaq] = useState<number | null>(0);

  // Sector and subsector selection
  const mainSectorList = useMemo(() => Object.keys(MAIN_SECTORS), []);
  const [selectedMainSector, setSelectedMainSector] = useState<string>("Güzellik / Kişisel Bakım");

  const filteredSubsectors = useMemo(() => {
    return SUBSECTOR_NAMES.filter(
      (name) => SECTOR_MATRIX[name]?.main_sector === selectedMainSector
    );
  }, [selectedMainSector]);

  const [selectedSubsector, setSelectedSubsector] = useState<string>("Kuaför");

  // Handle main sector change
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

  const featureRows = [
    { name: "POS & Adisyon Sistemi", all: true },
    { name: "e-Fatura & Mali Entegrasyon", all: true },
    { name: "Stok & Reçete / Depo Yönetimi", all: true },
    { name: "Gelişmiş Gelir / Ciro / Kâr Raporları", all: true },
    { name: "Müşteri CRM & Sadakat Kartı", all: true },
    { name: "WhatsApp & SMS Randevu Bildirimleri", all: true },
    { name: "Google & Apple Çift Yönlü Takvim Senkronizasyonu", all: true },
    { name: "Akıllı Bekleme Listesi (Waitlist)", all: true },
    { name: "Online Web & Mobil Rezervasyon Sayfası", all: true },
    { name: "Çoklu Şube ve Personel İzin Yönetimi", all: true },
    {
      name: "7/24 Çok Kanallı AI Asistan (WhatsApp, IG, Web)",
      all: false,
      free: false,
      basic: true,
      pro: true,
      premium: true,
      custom: true,
      note: "Yalnızca Ücretsiz pakette hariçtir"
    },
  ];

  const faqs = [
    {
      question: "Tüm özellikler gerçekten her pakette açık mı?",
      answer: "Evet! BooKi felsefesinde 'özellik kilitleme' yoktur. POS, Adisyon, Raporlar, e-Fatura, Stok, Çift Yönlü Takvim ve diğer tüm kurumsal modüller Ücretsiz paket dahil HER pakette aktiftir. Tek istisna, yüksek LLM sunucu maliyeti gerektiren 7/24 AI Asistan modülünün Ücretsiz pakette yer almamasıdır; Başlangıç ve üstü tüm paketlerde AI Asistan tamamen açıktır."
    },
    {
      question: "Sektörüme ve işletme tipime göre baremler nasıl belirleniyor?",
      answer: "Sistemimizde 9 ana sektör ve 168 alt işletme tipi için özel kapasite baremleri tanımlıdır. Örneğin bir kuaför için koltuk sayısı, bir halı saha için saha adedi, bir butik otel için oda adedi geçerlidir. Yukarıdaki seçiciden işletmenizi seçerek her paketin size sunduğu net kaynak, personel ve randevu sınırını görebilirsiniz."
    },
    {
      question: "Rezervasyon veya ciro başına komisyon ödüyor muyum?",
      answer: "Kesinlikle %0 komisyon! Randevu veya adisyon hacminiz ne olursa olsun BooKi cironuzdan veya rezervasyonlarınızdan komisyon talep etmez."
    },
    {
      question: "Yıllık ödeme avantajı nedir?",
      answer: "Yıllık faturalandırmayı seçtiğinizde net %20 indirim uygulanır (12 ay yerine yalnızca 10 ay ücreti ödersiniz, 2 ay ücretsiz kazanırsınız)."
    },
    {
      question: "İstediğim zaman paket değiştirebilir veya iptal edebilir miyim?",
      answer: "Evet, taahhüt yoktur. İşletmeniz büyüdükçe dilediğiniz an bir üst pakete geçebilir veya üyeliğinizi dilediğiniz an iptal edebilirsiniz."
    },
  ];

  return (
    <div className="site-shell pricing-page">
      <SEOHead
        title="Paketler & Şeffaf Fiyatlandırma — %0 Komisyon | BooKi"
        description="9 ana sektör ve 168 alt işletme tipine özel paketler. Tüm modüller açık, %0 komisyon. Ücretsiz 0 ₺, Başlangıç 1.250 ₺, Pro 2.450 ₺, Premium 4.750 ₺."
        canonicalPath="/fiyatlar"
        keywords={[
          "randevu sistemi fiyatları",
          "rezervasyon programı fiyat",
          "kuaför randevu programı fiyatı",
          "restoran masa yönetim programı fiyat",
          "komisyonsuz randevu paketleri",
          "sektörel randevu baremleri"
        ]}
      />

      <Navbar />

      <main id="main-content">
        {/* Hero Section */}
        <section className="sector-hero pricing-hero-mod" style={{ paddingBottom: "2rem" }}>
          <div className="sector-hero-container">
            <div className="sector-hero-badge">
              <span className="dot" /> %0 KOMİSYON · 9 SEKTÖR · 168 İŞLETME TİPİ
            </div>
            <h1>
              İşletmeniz Büyüdükçe <br />
              <em>BooKi de Sizinle Büyür.</em>
            </h1>
            <p className="sector-hero-description" style={{ maxWidth: "760px", margin: "0 auto 1.5rem auto" }}>
              Gizli modül ücretleri, eklenti maliyetleri ve ciro komisyonları yok. 
              Tüm kurumsal özellikler her pakette açık; tek istisna Ücretsiz planda AI Asistan hariçtir.
            </p>

            {/* Annual Billing Toggle */}
            <div className="billing-toggle-container" style={{ marginBottom: "2.5rem" }}>
              <div className="billing-toggle" role="group" aria-label="Faturalandırma dönemi">
                <button
                  type="button"
                  className={!isAnnual ? "active" : ""}
                  onClick={() => setIsAnnual(false)}
                >
                  Aylık Ödeme <small>Taahhütsüz</small>
                </button>
                <button
                  type="button"
                  className={isAnnual ? "active" : ""}
                  onClick={() => setIsAnnual(true)}
                >
                  Yıllık Ödeme <span>%20 İNDİRİM (2 AY ÜCRETSİZ)</span>
                </button>
              </div>
            </div>

            {/* Sector & Subsector Dynamic Selector Box */}
            <div
              style={{
                background: "rgba(255, 255, 255, 0.95)",
                backdropFilter: "blur(12px)",
                border: "2px solid rgba(46, 153, 149, 0.3)",
                borderRadius: "16px",
                padding: "1.5rem 2rem",
                maxWidth: "840px",
                margin: "0 auto",
                boxShadow: "0 12px 36px rgba(10, 23, 36, 0.08)",
                textAlign: "left"
              }}
            >
              <div style={{ display: "flex", alignItems: "center", gap: "0.5rem", marginBottom: "1rem" }}>
                <Layers size={20} color="var(--teal, #2e9999)" />
                <strong style={{ fontSize: "1.05rem", color: "#0a1724" }}>
                  İşletmenizin Sektörünü Seçin &amp; Dinamik Baremleri İnceleyin:
                </strong>
              </div>

              <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(280px, 1fr))", gap: "1rem" }}>
                <div>
                  <label style={{ display: "block", fontSize: "0.85rem", fontWeight: 600, color: "#475569", marginBottom: "0.4rem" }}>
                    Ana Sektör:
                  </label>
                  <select
                    value={selectedMainSector}
                    onChange={(e) => handleMainSectorChange(e.target.value)}
                    style={{
                      width: "100%",
                      padding: "0.75rem 1rem",
                      borderRadius: "10px",
                      border: "1px solid #cbd5e1",
                      background: "#f8fafc",
                      fontSize: "0.95rem",
                      fontWeight: 600,
                      color: "#0f172a",
                      cursor: "pointer",
                      outline: "none"
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
                  <label style={{ display: "block", fontSize: "0.85rem", fontWeight: 600, color: "#475569", marginBottom: "0.4rem" }}>
                    Alt İşletme Tipi ({filteredSubsectors.length} tip mevcut):
                  </label>
                  <select
                    value={selectedSubsector}
                    onChange={(e) => setSelectedSubsector(e.target.value)}
                    style={{
                      width: "100%",
                      padding: "0.75rem 1rem",
                      borderRadius: "10px",
                      border: "1px solid #cbd5e1",
                      background: "#f8fafc",
                      fontSize: "0.95rem",
                      fontWeight: 600,
                      color: "#0f172a",
                      cursor: "pointer",
                      outline: "none"
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

              <div
                style={{
                  marginTop: "1rem",
                  padding: "0.75rem 1rem",
                  borderRadius: "8px",
                  background: "rgba(46, 153, 149, 0.08)",
                  display: "flex",
                  alignItems: "center",
                  justifyContent: "space-between",
                  flexWrap: "wrap",
                  gap: "0.5rem"
                }}
              >
                <span style={{ fontSize: "0.88rem", color: "#0f766e" }}>
                  Seçili İşletme: <strong>{selectedSubsector}</strong> ({selectedMainSector})
                </span>
                <span style={{ fontSize: "0.85rem", color: "#64748b" }}>
                  Ölçü Birimi: <strong>{resourceLabel}</strong>
                </span>
              </div>
            </div>

            {/* Universal Rule Alert Banner */}
            <div
              style={{
                marginTop: "1.5rem",
                display: "inline-flex",
                alignItems: "center",
                gap: "0.6rem",
                background: "linear-gradient(90deg, rgba(16, 185, 129, 0.15) 0%, rgba(46, 153, 149, 0.15) 100%)",
                border: "1px solid rgba(16, 185, 129, 0.4)",
                padding: "0.65rem 1.25rem",
                borderRadius: "999px",
                color: "#065f46",
                fontSize: "0.92rem",
                fontWeight: 600
              }}
            >
              <Sparkles size={16} color="#059669" />
              <span>
                <strong>Tüm Özellikler Açık:</strong> POS, Adisyon, Raporlar, e-Fatura, Stok ve tüm modüller her pakette aktif! Tek istisna: <u>Ücretsiz pakette AI Asistan hariçtir.</u>
              </span>
            </div>
          </div>
        </section>

        {/* 5-Plan Pricing Cards Grid */}
        <section className="pricing-grid-section" style={{ paddingTop: "1rem" }}>
          <div className="section-container">
            <div
              style={{
                display: "grid",
                gridTemplateColumns: "repeat(auto-fit, minmax(230px, 1fr))",
                gap: "1.5rem",
                alignItems: "stretch"
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
                      padding: "1.75rem 1.25rem",
                      display: "flex",
                      flexDirection: "column",
                      position: "relative",
                      boxShadow: isPro
                        ? "0 16px 40px rgba(46, 153, 149, 0.16)"
                        : "0 4px 12px rgba(0,0,0,0.03)",
                      transform: isPro ? "scale(1.03)" : "none",
                      transition: "all 0.25s ease"
                    }}
                  >
                    {isPro && (
                      <div
                        style={{
                          position: "absolute",
                          top: "-12px",
                          left: "50%",
                          transform: "translateX(-50%)",
                          background: "var(--teal, #2e9999)",
                          color: "#fff",
                          fontSize: "0.72rem",
                          fontWeight: 800,
                          letterSpacing: "0.05em",
                          padding: "0.25rem 0.75rem",
                          borderRadius: "999px",
                          display: "inline-flex",
                          alignItems: "center",
                          gap: "0.3rem",
                          boxShadow: "0 4px 12px rgba(46, 153, 149, 0.3)"
                        }}
                      >
                        <Crown size={12} /> EN POPÜLER
                      </div>
                    )}

                    <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", marginBottom: "0.75rem" }}>
                      <span
                        style={{
                          width: "38px",
                          height: "38px",
                          borderRadius: "10px",
                          background: isPro ? "rgba(46, 153, 149, 0.12)" : "#f1f5f9",
                          color: isPro ? "var(--teal, #2e9999)" : "#475569",
                          display: "inline-flex",
                          alignItems: "center",
                          justifyContent: "center"
                        }}
                      >
                        <IconComp size={20} />
                      </span>
                      <span style={{ fontSize: "0.75rem", fontWeight: 700, color: "#64748b", textTransform: "uppercase" }}>
                        {plan.badge}
                      </span>
                    </div>

                    <h3 style={{ fontSize: "1.3rem", fontWeight: 800, color: "#0a1724", marginBottom: "0.25rem" }}>
                      {plan.name}
                    </h3>
                    <p style={{ fontSize: "0.82rem", color: "#64748b", minHeight: "2.4rem", marginBottom: "1rem" }}>
                      {plan.role}
                    </p>

                    {/* Price Tag */}
                    <div style={{ marginBottom: "1rem" }}>
                      <div style={{ display: "flex", alignItems: "baseline", gap: "0.3rem" }}>
                        <strong style={{ fontSize: "2rem", fontWeight: 900, color: "#0a1724" }}>
                          {isAnnual ? plan.annual : plan.monthly}
                        </strong>
                        {plan.key !== "custom" && (
                          <span style={{ fontSize: "0.85rem", color: "#64748b" }}>/ ay</span>
                        )}
                      </div>
                      {isAnnual && plan.key !== "custom" && plan.key !== "free" && (
                        <div style={{ fontSize: "0.75rem", color: "#059669", fontWeight: 700, marginTop: "0.2rem" }}>
                          Yıllık faturada %20 avantaj
                        </div>
                      )}
                    </div>

                    {/* Dynamic Sector Limits Box */}
                    <div
                      style={{
                        background: isPro ? "rgba(46, 153, 149, 0.06)" : "#f8fafc",
                        border: "1px solid #e2e8f0",
                        borderRadius: "10px",
                        padding: "0.75rem",
                        marginBottom: "1.25rem"
                      }}
                    >
                      <div style={{ fontSize: "0.75rem", fontWeight: 700, color: "#475569", textTransform: "uppercase", marginBottom: "0.5rem" }}>
                        {selectedSubsector} Kapasiteleri:
                      </div>
                      <div style={{ display: "grid", gap: "0.4rem", fontSize: "0.82rem" }}>
                        <div style={{ display: "flex", justifyContent: "space-between" }}>
                          <span style={{ color: "#64748b" }}>{resourceLabel}:</span>
                          <strong style={{ color: "#0a1724" }}>{plan.limits.resource_limit}</strong>
                        </div>
                        <div style={{ display: "flex", justifyContent: "space-between" }}>
                          <span style={{ color: "#64748b" }}>Personel / Uzman:</span>
                          <strong style={{ color: "#0a1724" }}>{plan.limits.staff_limit}</strong>
                        </div>
                        <div style={{ display: "flex", justifyContent: "space-between" }}>
                          <span style={{ color: "#64748b" }}>Aylık Randevu:</span>
                          <strong style={{ color: "#0a1724" }}>{plan.limits.appointment_limit}</strong>
                        </div>
                      </div>
                    </div>

                    <a
                      href="#demo-section"
                      className={isPro ? "button button-primary" : "button button-outline"}
                      style={{
                        width: "100%",
                        justifyContent: "center",
                        padding: "0.75rem",
                        fontSize: "0.9rem",
                        marginBottom: "1.25rem"
                      }}
                    >
                      {plan.cta} <MoveRight size={14} />
                    </a>

                    {/* Features list */}
                    <ul style={{ listStyle: "none", padding: 0, margin: 0, display: "flex", flexDirection: "column", gap: "0.55rem", fontSize: "0.82rem" }}>
                      <li style={{ display: "flex", alignItems: "center", gap: "0.5rem" }}>
                        {plan.aiIncluded ? (
                          <>
                            <Bot size={15} color="#059669" />
                            <strong style={{ color: "#059669" }}>7/24 AI Asistan Dahil</strong>
                          </>
                        ) : (
                          <>
                            <X size={15} color="#ef4444" />
                            <span style={{ color: "#94a3b8", textDecoration: "line-through" }}>AI Asistan (Ücretsiz Hariç)</span>
                          </>
                        )}
                      </li>
                      <li style={{ display: "flex", alignItems: "center", gap: "0.5rem", color: "#334155" }}>
                        <Check size={14} color="#059669" />
                        <span>POS &amp; Adisyon Sistemi</span>
                      </li>
                      <li style={{ display: "flex", alignItems: "center", gap: "0.5rem", color: "#334155" }}>
                        <Check size={14} color="#059669" />
                        <span>e-Fatura &amp; Mali Raporlar</span>
                      </li>
                      <li style={{ display: "flex", alignItems: "center", gap: "0.5rem", color: "#334155" }}>
                        <Check size={14} color="#059669" />
                        <span>Stok &amp; Depo Yönetimi</span>
                      </li>
                      <li style={{ display: "flex", alignItems: "center", gap: "0.5rem", color: "#334155" }}>
                        <Check size={14} color="#059669" />
                        <span>WhatsApp / SMS Bildirimleri</span>
                      </li>
                      <li style={{ display: "flex", alignItems: "center", gap: "0.5rem", color: "#334155" }}>
                        <Check size={14} color="#059669" />
                        <span>%0 Rezervasyon Komisyonu</span>
                      </li>
                    </ul>
                  </article>
                );
              })}
            </div>

            <p className="pricing-trust" style={{ marginTop: "2.5rem" }}>
              <ShieldCheck size={18} />
              Kredi kartı gerekmez. <strong>Kurulum sırasında güvenli ödeme.</strong> İstediğiniz an iptal edin.
            </p>
          </div>
        </section>

        {/* Full Feature Comparison Table */}
        <section className="pricing-features-table-section">
          <div className="section-container">
            <div className="section-header-center">
              <span className="badge-sub">DETAYLI LİSTE</span>
              <h2>Paket Özellikleri <em>Karşılaştırma Tablosu</em></h2>
              <p style={{ maxWidth: "680px", margin: "0.5rem auto 0 auto", color: "#64748b" }}>
                Tüm kurumsal modüller her pakette etkindir. Tek ayrışan özellik Ücretsiz paketteki AI Asistan kısıtıdır.
              </p>
            </div>

            <div className="comparison-table-wrap">
              <table className="comparison-table">
                <thead>
                  <tr>
                    <th>Özellik / Yetenek</th>
                    <th>Ücretsiz</th>
                    <th>Başlangıç</th>
                    <th className="comparison-highlight">Orta (Pro)</th>
                    <th>Premium</th>
                    <th>Özel</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <th scope="row">Aylık Fiyat</th>
                    <td>0 ₺</td>
                    <td>1.250 ₺</td>
                    <td className="comparison-highlight">2.450 ₺</td>
                    <td>4.750 ₺</td>
                    <td>Teklif</td>
                  </tr>
                  <tr>
                    <th scope="row">Yıllık Fiyat (Aylık Karşılığı)</th>
                    <td>0 ₺</td>
                    <td>1.000 ₺ / ay</td>
                    <td className="comparison-highlight">1.950 ₺ / ay</td>
                    <td>3.800 ₺ / ay</td>
                    <td>Teklif</td>
                  </tr>
                  <tr>
                    <th scope="row">Seçili Sektör: {selectedSubsector} ({resourceLabel})</th>
                    <td>{currentData.tiers.free.resource_limit}</td>
                    <td>{currentData.tiers.basic.resource_limit}</td>
                    <td className="comparison-highlight">{currentData.tiers.pro.resource_limit}</td>
                    <td>{currentData.tiers.premium.resource_limit}</td>
                    <td>{currentData.tiers.custom.resource_limit}</td>
                  </tr>
                  <tr>
                    <th scope="row">Personel / Uzman Limiti</th>
                    <td>{currentData.tiers.free.staff_limit}</td>
                    <td>{currentData.tiers.basic.staff_limit}</td>
                    <td className="comparison-highlight">{currentData.tiers.pro.staff_limit}</td>
                    <td>{currentData.tiers.premium.staff_limit}</td>
                    <td>{currentData.tiers.custom.staff_limit}</td>
                  </tr>
                  <tr>
                    <th scope="row">Aylık Randevu Kotası</th>
                    <td>{currentData.tiers.free.appointment_limit}</td>
                    <td>{currentData.tiers.basic.appointment_limit}</td>
                    <td className="comparison-highlight">{currentData.tiers.pro.appointment_limit}</td>
                    <td>{currentData.tiers.premium.appointment_limit}</td>
                    <td>{currentData.tiers.custom.appointment_limit}</td>
                  </tr>
                  <tr>
                    <th scope="row">Rezervasyon Komisyonu</th>
                    <td>%0</td>
                    <td>%0</td>
                    <td className="comparison-highlight">%0</td>
                    <td>%0</td>
                    <td>%0</td>
                  </tr>
                  {featureRows.map((row, idx) => (
                    <tr key={idx}>
                      <th scope="row">{row.name}</th>
                      <td>
                        {row.all ? (
                          <Check size={16} color="#059669" />
                        ) : row.free ? (
                          <Check size={16} color="#059669" />
                        ) : (
                          <X size={16} color="#ef4444" />
                        )}
                      </td>
                      <td><Check size={16} color="#059669" /></td>
                      <td className="comparison-highlight"><Check size={16} color="#059669" /></td>
                      <td><Check size={16} color="#059669" /></td>
                      <td><Check size={16} color="#059669" /></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </section>

        {/* Pricing FAQs */}
        <section className="sector-faq-section">
          <div className="section-container">
            <div className="section-header-center">
              <span className="badge-sub">SIKÇA SORULAN SORULAR</span>
              <h2>Ödeme ve Paketler Hakkında Merak Edilenler</h2>
            </div>

            <div className="faq-accordion-wrap">
              {faqs.map((faq, i) => (
                <div key={i} className={`faq-row ${openFaq === i ? "active" : ""}`}>
                  <button type="button" onClick={() => setOpenFaq(openFaq === i ? null : i)}>
                    <span>{faq.question}</span>
                    <ChevronDown size={18} />
                  </button>
                  {openFaq === i && (
                    <div className="faq-desc-content">
                      <p>{faq.answer}</p>
                    </div>
                  )}
                </div>
              ))}
            </div>
          </div>
        </section>

        {/* Sales Funnel Lead Section */}
        <div id="demo-section">
          <SalesFunnelSection
            title="Doğru Paketi Seçin, 15 Dakikada Yayına Geçelim"
            subtitle="Görüşelim, canlı demo yapalım. Ödemeyi kurulumda güvenle yapın."
          />
        </div>
      </main>

      <StickyContactBar />
      <Footer />
    </div>
  );
}

