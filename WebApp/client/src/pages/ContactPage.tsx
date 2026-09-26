import { SEOHead } from "@/components/SEOHead";
import { Navbar } from "@/components/Navbar";
import { Footer } from "@/components/Footer";
import { StickyContactBar } from "@/components/StickyContactBar";
import { SalesFunnelSection } from "@/components/SalesFunnelSection";
import {
  Phone,
  MessageCircle,
  Mail,
  Instagram,
  Code2,
  MapPin,
  Clock3,
  ShieldCheck,
  CheckCircle2,
  Sparkles,
  ArrowUpRight
} from "lucide-react";

export default function ContactPage() {
  const whatsappNumber = "905062505562";
  const whatsappUrl = `https://wa.me/${whatsappNumber}?text=${encodeURIComponent("Merhaba BooKi ekibi, randevu/rezervasyon sistemi hakkında görüşmek istiyorum.")}`;

  const schemaData = {
    "@context": "https://schema.org",
    "@graph": [
      {
        "@type": "ContactPage",
        "@id": "https://booki.kibusiness.co/iletisim#webpage",
        "url": "https://booki.kibusiness.co/iletisim",
        "name": "İletişim & Canlı Demo Talebi | BooKi",
        "description": "BooKi iletişim kanalları: WhatsApp canlı destek (0506 250 55 62), telefon hattı, demo randevusu ve kurumsal destek.",
        "address": {
          "@type": "PostalAddress",
          "streetAddress": "Çekirge Mh. Süleyman Sk. No 29",
          "addressLocality": "Osmangazi",
          "addressRegion": "Bursa",
          "addressCountry": "TR"
        },
        "isPartOf": { "@id": "https://booki.kibusiness.co/#website" },
        "breadcrumb": {
          "@type": "BreadcrumbList",
          "itemListElement": [
            { "@type": "ListItem", "position": 1, "name": "Ana Sayfa", "item": "https://booki.kibusiness.co/" },
            { "@type": "ListItem", "position": 2, "name": "İletişim", "item": "https://booki.kibusiness.co/iletisim" }
          ]
        }
      }
    ]
  };

  return (
    <div className="site-shell contact-page">
      <SEOHead
        title="İletişim & Canlı Demo — WhatsApp & Telefon Hattı | BooKi"
        description="BooKi ile hemen iletişime geçin. 0506 250 55 62 veya WhatsApp üzerinden 15 dakikalık canlı demo randevusu alın."
        canonicalPath="/iletisim"
        keywords={[
          "booki iletişim",
          "randevu sistemi demo",
          "rezervasyon programı destek",
          "kibusiness iletişim"
        ]}
        schemaJson={schemaData}
      />

      <Navbar />

      <main id="main-content">
        <section className="sector-hero contact-hero-mod">
          <div className="sector-hero-container">
            <div className="sector-hero-badge">
              <span className="dot" /> DOĞRUDAN VE ŞEFFAF İLETİŞİM
            </div>
            <h1>
              İşletmenizin ritmini <br />
              <em>birlikte sadeleştirelim.</em>
            </h1>
            <p className="sector-hero-description">
              Sorularınız, demo talepleriniz veya sistem kurulumu için dilediğiniz kanaldan bize ulaşabilirsiniz.
            </p>

            <div className="sector-trust-bar">
              <span><CheckCircle2 size={15} /> Anında WhatsApp Yanıtı (0506 250 55 62)</span>
              <span><CheckCircle2 size={15} /> 15 Dk. Canlı Demo</span>
              <span><CheckCircle2 size={15} /> Kurulumda Güvenli Ödeme</span>
            </div>
          </div>
        </section>

        {/* Contact Info Cards */}
        <section className="contact-cards-section">
          <div className="section-container">
            <div className="contact-cards-grid">
              {/* WhatsApp Card */}
              <div className="contact-info-card whatsapp-card">
                <div className="card-icon-header">
                  <MessageCircle size={24} />
                  <span className="live-tag">CANLI DESTEK</span>
                </div>
                <h3>WhatsApp Hattı</h3>
                <p>Mesaj gönderin, ortalama 2 dakika içinde uzman temsilcimiz size dönüş yapsın.</p>
                <a href={whatsappUrl} target="_blank" rel="noopener noreferrer" className="button button-whatsapp">
                  WhatsApp'tan Yazın (0506 250 55 62) <ArrowUpRight size={15} />
                </a>
              </div>

              {/* Phone Card */}
              <div className="contact-info-card phone-card">
                <div className="card-icon-header">
                  <Phone size={24} />
                  <span className="hours-tag">09:00 - 19:00</span>
                </div>
                <h3>Telefon Hattı</h3>
                <p>Doğrudan arayarak satış ve kurulum danışmanımızla görüşün.</p>
                <a href="tel:+905062505562" className="button button-dark">
                  0506 250 55 62 <ArrowUpRight size={15} />
                </a>
              </div>

              {/* Ecosystem & Instagram Card */}
              <div className="contact-info-card email-card">
                <div className="card-icon-header">
                  <Instagram size={24} />
                  <span className="hours-tag">SOSYAL & EKOSİSTEM</span>
                </div>
                <h3>Instagram & Yazılım</h3>
                <p>Bizi Instagram'da takip edin veya özel yazılım çözümlerimizi inceleyin.</p>
                <div style={{ display: "grid", gap: "8px" }}>
                  <a href="https://instagram.com/booki.reservation" target="_blank" rel="noopener noreferrer" className="button button-outline" style={{ justifyContent: "space-between" }}>
                    <span>@booki.reservation</span> <ArrowUpRight size={14} />
                  </a>
                  <a href="https://software.kibusiness.co" target="_blank" rel="noopener noreferrer" className="button button-outline" style={{ justifyContent: "space-between" }}>
                    <span>software.kibusiness.co</span> <ArrowUpRight size={14} />
                  </a>
                </div>
              </div>

              {/* Address & Office Card */}
              <div className="contact-info-card address-card">
                <div className="card-icon-header">
                  <MapPin size={24} />
                  <span className="hours-tag">MERKEZ OFİS</span>
                </div>
                <h3>Adres &amp; İletişim</h3>
                <p style={{ marginBottom: "0.5rem" }}>
                  <strong>Adres:</strong> Çekirge Mh. Süleyman Sk. No 29 Osmangazi / Bursa
                </p>
                <p style={{ fontSize: "0.85rem", color: "var(--muted)", marginBottom: "1rem" }}>
                  Pazartesi - Cuma: 09:00 - 18:00
                </p>
                <div style={{ display: "grid", gap: "8px", marginTop: "auto" }}>
                  <a href="mailto:hello@kibusiness.co" className="button button-outline" style={{ justifyContent: "space-between" }}>
                    <span>hello@kibusiness.co</span> <Mail size={14} />
                  </a>
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* Sales Funnel Lead Section */}
        <SalesFunnelSection
          title="Demo Randevunuzu Hemen Oluşturun"
          subtitle="Formu doldurun, temsilcimiz seçtiğiniz saatte sizi arasın."
        />
      </main>

      <StickyContactBar />
      <Footer />
    </div>
  );
}
