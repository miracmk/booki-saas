import { useRoute, Link } from "wouter";
import { SECTORS } from "@/data/sectorsData";
import { SEOHead } from "@/components/SEOHead";
import { Navbar } from "@/components/Navbar";
import { Footer } from "@/components/Footer";
import { StickyContactBar } from "@/components/StickyContactBar";
import { SalesFunnelSection } from "@/components/SalesFunnelSection";
import NotFound from "./NotFound";
import {
  Sparkles,
  MoveRight,
  ArrowUpRight,
  CheckCircle2,
  HelpCircle,
  ChevronDown,
  ShieldCheck,
  Utensils,
  Stethoscope,
  UsersRound,
  StickyNote,
  BarChart3,
  BellRing,
  Globe2,
  Clock3,
  Building2,
  Zap,
  Phone,
  MessageCircle
} from "lucide-react";
import { useState } from "react";

const ICON_MAP: Record<string, any> = {
  Utensils,
  Stethoscope,
  UsersRound,
  StickyNote,
  BarChart3,
  BellRing,
  Globe2,
  Clock3,
  Building2,
  Zap,
  Sparkles,
  ShieldCheck,
  MessageCircle
};

export default function SectorPage() {
  const [, params] = useRoute("/sektorler/:slug");
  const slug = params?.slug || "";
  const sector = SECTORS[slug];
  const [openFaq, setOpenFaq] = useState<number | null>(0);

  if (!sector) {
    return <NotFound />;
  }

  const whatsappUrl = "https://wa.me/905062505562?text=" + encodeURIComponent(`Merhaba BooKi ekibi, ${sector.title} için bilgi ve canlı demo almak istiyorum.`);

  // Schema.org for this sector
  const schemaData = {
    "@context": "https://schema.org",
    "@graph": [
      {
        "@type": "WebPage",
        "@id": `https://booki.kibusiness.co/sektorler/${sector.slug}#webpage`,
        "url": `https://booki.kibusiness.co/sektorler/${sector.slug}`,
        "name": sector.metaTitle,
        "description": sector.metaDescription,
        "isPartOf": { "@id": "https://booki.kibusiness.co/#website" },
        "breadcrumb": {
          "@type": "BreadcrumbList",
          "itemListElement": [
            { "@type": "ListItem", "position": 1, "name": "Ana Sayfa", "item": "https://booki.kibusiness.co/" },
            { "@type": "ListItem", "position": 2, "name": "Sektörler", "item": "https://booki.kibusiness.co/#sektorler" },
            { "@type": "ListItem", "position": 3, "name": sector.title, "item": `https://booki.kibusiness.co/sektorler/${sector.slug}` }
          ]
        }
      },
      {
        "@type": "Service",
        "@id": `https://booki.kibusiness.co/sektorler/${sector.slug}#service`,
        "name": sector.title,
        "serviceType": "Online Rezervasyon ve Randevu Yazılımı",
        "provider": { "@id": "https://booki.kibusiness.co/#organization" },
        "description": sector.metaDescription,
        "offers": {
          "@type": "Offer",
          "price": "1999",
          "priceCurrency": "TRY",
          "priceSpecification": {
            "@type": "UnitPriceSpecification",
            "price": "1999",
            "priceCurrency": "TRY",
            "unitText": "MONTH"
          }
        }
      },
      {
        "@type": "FAQPage",
        "@id": `https://booki.kibusiness.co/sektorler/${sector.slug}#faq`,
        "mainEntity": sector.faqs.map((f) => ({
          "@type": "Question",
          "name": f.question,
          "acceptedAnswer": {
            "@type": "Answer",
            "text": f.answer
          }
        }))
      }
    ]
  };

  return (
    <div className="site-shell sector-landing-page">
      <SEOHead
        title={sector.metaTitle}
        description={sector.metaDescription}
        canonicalPath={`/sektorler/${sector.slug}`}
        keywords={sector.keywords}
        schemaJson={schemaData}
      />

      <Navbar />

      <main id="main-content">
        {/* Sector Hero */}
        <section className="sector-hero">
          <div className="sector-hero-container">
            <div className="sector-hero-badge">
              <span className="dot" /> {sector.heroBadge}
            </div>
            <h1>
              {sector.heroHeadline} <br />
              <em>{sector.heroSubheadline}</em>
            </h1>
            <p className="sector-hero-description">{sector.heroDescription}</p>

            <div className="sector-hero-ctas">
              <a href="#demo-section" className="button button-primary">
                15 Dk. Canlı Demo İste <MoveRight size={16} />
              </a>
              <a href={whatsappUrl} target="_blank" rel="noopener noreferrer" className="button button-whatsapp">
                <MessageCircle size={17} /> WhatsApp ile Konuş
              </a>
            </div>

            <div className="sector-trust-bar">
              <span><CheckCircle2 size={15} /> %0 Rezervasyon Komisyonu</span>
              <span><CheckCircle2 size={15} /> Kurulumda Güvenli Ödeme</span>
              <span><CheckCircle2 size={15} /> 15 Dk. Hazır Sistem</span>
            </div>
          </div>
        </section>

        {/* Stats Grid */}
        <section className="sector-stats-section">
          <div className="sector-stats-grid">
            {sector.stats.map((stat, i) => (
              <div key={i} className="stat-box">
                <strong>{stat.value}</strong>
                <span>{stat.label}</span>
              </div>
            ))}
          </div>
        </section>

        {/* Pain vs Solution Matrix */}
        <section className="sector-problems-section">
          <div className="section-container">
            <div className="section-header-center">
              <span className="badge-sub">GELENEKSEL SORUNLAR VS BOOKI</span>
              <h2>Neden <em>{sector.title}</em> için BooKi?</h2>
              <p>Eski yöntemler zamanınızı ve gelirinizi eritir. BooKi ile işiniz tıkır tıkır işlesin.</p>
            </div>

            <div className="problems-grid">
              {sector.painPoints.map((item, i) => (
                <div key={i} className="problem-card">
                  <div className="problem-header">
                    <span className="bad-pill">Eski Yöntem</span>
                    <p>{item.problem}</p>
                  </div>
                  <div className="solution-body">
                    <span className="good-pill">BooKi Çözümü</span>
                    <p>{item.solution}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </section>

        {/* Key Features Bento */}
        <section className="sector-features-section">
          <div className="section-container">
            <div className="section-header-center">
              <span className="badge-sub">ÖNE ÇIKAN YETENEKLER</span>
              <h2>İşletmenize Özel <em>Güçlü Fonksiyonlar</em></h2>
            </div>

            <div className="sector-features-grid">
              {sector.keyFeatures.map((feat, i) => {
                const IconComp = ICON_MAP[feat.icon] || Sparkles;
                return (
                  <div key={i} className="sector-feature-card">
                    <div className="feat-icon-wrap">
                      <IconComp size={22} />
                    </div>
                    <h3>{feat.title}</h3>
                    <p>{feat.desc}</p>
                  </div>
                );
              })}
            </div>
          </div>
        </section>

        {/* 3-Step Setup Flow */}
        <section className="sector-flow-section">
          <div className="section-container">
            <div className="section-header-center">
              <span className="badge-sub">3 ADIMDA CANLIYA GEÇİŞ</span>
              <h2>15 Dakikada <em>Sisteminiz Hazır</em></h2>
            </div>

            <div className="flow-steps-row">
              {sector.workflowSteps.map((step, i) => (
                <div key={i} className="flow-col">
                  <span className="step-tag">{step.step}</span>
                  <h3>{step.title}</h3>
                  <p>{step.desc}</p>
                </div>
              ))}
            </div>
          </div>
        </section>

        {/* Sector FAQ */}
        <section className="sector-faq-section">
          <div className="section-container">
            <div className="section-header-center">
              <span className="badge-sub">SIKÇA SORULAN SORULAR</span>
              <h2>Merak Edilenler</h2>
            </div>

            <div className="faq-accordion-wrap">
              {sector.faqs.map((faq, i) => (
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
        <SalesFunnelSection
          defaultSector={sector.title}
          title={`${sector.title} İçin Özel Akışınızı Başlatalım`}
          subtitle="Görüşelim, 15 dakikada işletmenize özel kuralım. Ödemeyi kurulumda güvenle yapın."
        />
      </main>

      <StickyContactBar />
      <Footer />
    </div>
  );
}
