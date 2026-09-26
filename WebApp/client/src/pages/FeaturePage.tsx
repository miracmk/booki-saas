import { useRoute, Link } from "wouter";
import { FEATURES } from "@/data/featuresData";
import { SEOHead } from "@/components/SEOHead";
import { Navbar } from "@/components/Navbar";
import { Footer } from "@/components/Footer";
import { StickyContactBar } from "@/components/StickyContactBar";
import { SalesFunnelSection } from "@/components/SalesFunnelSection";
import NotFound from "./NotFound";
import {
  MessageCircle,
  Check,
  Globe2,
  Sparkles,
  Clock3,
  Zap,
  ShieldCheck,
  BarChart3,
  UsersRound,
  CalendarDays,
  StickyNote,
  Crown,
  ChevronDown,
  MoveRight,
  CheckCircle2
} from "lucide-react";
import { useState } from "react";

const ICON_MAP: Record<string, any> = {
  MessageCircle,
  Check,
  Globe2,
  Sparkles,
  Clock3,
  Zap,
  ShieldCheck,
  BarChart3,
  UsersRound,
  CalendarDays,
  StickyNote,
  Crown
};

export default function FeaturePage() {
  const [, params] = useRoute("/ozellikler/:slug");
  const slug = params?.slug || "";
  const feat = FEATURES[slug];
  const [openFaq, setOpenFaq] = useState<number | null>(0);

  if (!feat) {
    return <NotFound />;
  }

  const whatsappUrl = "https://wa.me/905062505562?text=" + encodeURIComponent(`Merhaba BooKi ekibi, ${feat.title} özelliği hakkında bilgi ve canlı demo almak istiyorum.`);

  const schemaData = {
    "@context": "https://schema.org",
    "@graph": [
      {
        "@type": "WebPage",
        "@id": `https://booki.kibusiness.co/ozellikler/${feat.slug}#webpage`,
        "url": `https://booki.kibusiness.co/ozellikler/${feat.slug}`,
        "name": feat.metaTitle,
        "description": feat.metaDescription,
        "isPartOf": { "@id": "https://booki.kibusiness.co/#website" },
        "breadcrumb": {
          "@type": "BreadcrumbList",
          "itemListElement": [
            { "@type": "ListItem", "position": 1, "name": "Ana Sayfa", "item": "https://booki.kibusiness.co/" },
            { "@type": "ListItem", "position": 2, "name": "Özellikler", "item": "https://booki.kibusiness.co/#ozellikler" },
            { "@type": "ListItem", "position": 3, "name": feat.title, "item": `https://booki.kibusiness.co/ozellikler/${feat.slug}` }
          ]
        }
      },
      {
        "@type": "FAQPage",
        "@id": `https://booki.kibusiness.co/ozellikler/${feat.slug}#faq`,
        "mainEntity": feat.faqs.map((f) => ({
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
    <div className="site-shell feature-landing-page">
      <SEOHead
        title={feat.metaTitle}
        description={feat.metaDescription}
        canonicalPath={`/ozellikler/${feat.slug}`}
        keywords={feat.keywords}
        schemaJson={schemaData}
      />

      <Navbar />

      <main id="main-content">
        {/* Feature Hero */}
        <section className="sector-hero feature-hero-mod">
          <div className="sector-hero-container">
            <div className="sector-hero-badge">
              <span className="dot" /> {feat.heroBadge}
            </div>
            <h1>
              {feat.heroHeadline} <br />
              <em>{feat.heroSubheadline}</em>
            </h1>
            <p className="sector-hero-description">{feat.heroDescription}</p>

            <div className="sector-hero-ctas">
              <a href="#demo-section" className="button button-primary">
                Canlı Özellik Demosu İste <MoveRight size={16} />
              </a>
              <a href={whatsappUrl} target="_blank" rel="noopener noreferrer" className="button button-whatsapp">
                <MessageCircle size={17} /> WhatsApp ile Konuş
              </a>
            </div>

            <div className="sector-trust-bar">
              <span><CheckCircle2 size={15} /> %0 Komisyon Garantisi</span>
              <span><CheckCircle2 size={15} /> Kurulumda Güvenli Ödeme</span>
              <span><CheckCircle2 size={15} /> 14 Gün Ücretsiz Deneme</span>
            </div>
          </div>
        </section>

        {/* Benefits Grid */}
        <section className="sector-features-section">
          <div className="section-container">
            <div className="section-header-center">
              <span className="badge-sub">TEMEL FAYDALAR</span>
              <h2>İşletmenize Kazandırdıkları</h2>
            </div>

            <div className="sector-features-grid">
              {feat.benefits.map((b, i) => {
                const IconComp = ICON_MAP[b.icon] || Sparkles;
                return (
                  <div key={i} className="sector-feature-card">
                    <div className="feat-icon-wrap">
                      <IconComp size={22} />
                    </div>
                    <h3>{b.title}</h3>
                    <p>{b.desc}</p>
                  </div>
                );
              })}
            </div>
          </div>
        </section>

        {/* Deep Dive Breakdown */}
        <section className="feature-deepdive-section">
          <div className="section-container">
            <div className="deepdive-grid">
              {feat.deepDive.map((d, i) => (
                <div key={i} className="deepdive-card">
                  <span className="deepdive-tag">0{i + 1} / AVANTAJ</span>
                  <h3>{d.title}</h3>
                  <p>{d.desc}</p>
                </div>
              ))}
            </div>
          </div>
        </section>

        {/* Feature FAQs */}
        <section className="sector-faq-section">
          <div className="section-container">
            <div className="section-header-center">
              <span className="badge-sub">SIKÇA SORULAN SORULAR</span>
              <h2>Merak Edilenler</h2>
            </div>

            <div className="faq-accordion-wrap">
              {feat.faqs.map((faq, i) => (
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
          title={`${feat.title} İle İşletmenizi Büyütün`}
          subtitle="Görüşelim, 15 dakikada işletmenize özel kuralım. Ödemeyi kurulumda güvenle yapın."
        />
      </main>

      <StickyContactBar />
      <Footer />
    </div>
  );
}
