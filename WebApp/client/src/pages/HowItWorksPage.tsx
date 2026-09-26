import { SEOHead } from "@/components/SEOHead";
import { Navbar } from "@/components/Navbar";
import { Footer } from "@/components/Footer";
import { StickyContactBar } from "@/components/StickyContactBar";
import { SalesFunnelSection } from "@/components/SalesFunnelSection";
import {
  Phone,
  CalendarDays,
  ShieldCheck,
  Sparkles,
  CheckCircle2,
  MoveRight,
  MessageCircle,
  Clock3,
  UsersRound,
  Zap,
  HelpCircle,
  ChevronDown
} from "lucide-react";
import { useState } from "react";

export default function HowItWorksPage() {
  const [openFaq, setOpenFaq] = useState<number | null>(0);

  const steps = [
    {
      step: "01",
      title: "İletişim & Demo Talebi",
      tagline: "2 Dakikalık Hızlı Başvuru",
      desc: "WhatsApp hattımızdan bize yazın, telefonla arayın veya web sitemizdeki demo formunu doldurun. İşletmenizin sektörünü, personel veya masa sayısını dinleyelim.",
      icon: Phone,
      color: "teal",
      highlights: ["Kredi kartı gerekmez", "Ön ödeme yok", "Anında yanıt"]
    },
    {
      step: "02",
      title: "Canlı Görüşme & İşletmeye Özel Demo",
      tagline: "15 Dakikalık Ekran Paylaşımı",
      desc: "Müşteri temsilcimiz ekran paylaşımıyla işletmenize özel tasarlanmış canlı rezervasyon sayfasını ve yönetim panelini size göstersin. Aklınızdaki tüm soruları canlı yanıtlayalım.",
      icon: CalendarDays,
      color: "peach",
      highlights: ["İşletmenize özel akış", "Masa/Uzman simülasyonu", "Soru-cevap"]
    },
    {
      step: "03",
      title: "Kurulum & Güvenli Ödeme",
      tagline: "Sisteminiz Hazır Olduğunda Ödeyin",
      desc: "Menünüz, hizmetleriniz, uzmanlarınız ve çalışma saatleriniz sisteme eksiksiz girilir. Sisteminiz canlıya alınıp onayınız tamamlandığında güvenli ödeme yöntemleriyle paket ücretinizi ödersiniz.",
      icon: ShieldCheck,
      color: "amber",
      highlights: ["Kurulumda ödeme güvencesi", "%0 Gizli masraf", "Anahtar teslim"]
    },
    {
      step: "04",
      title: "Canlıya Geçiş & 7/24 Sürekli Destek",
      tagline: "İşletmeniz Kesintisiz Akışta",
      desc: "Sosyal medya ve Google profillerinize rezervasyon linkiniz eklenir. Müşterileriniz 7/24 online randevu almaya başlar; WhatsApp destek hattımız her an yanınızda kalır.",
      icon: Sparkles,
      color: "blue",
      highlights: ["WhatsApp canlı destek", "Ücretsiz personel eğitimi", "%0 Komisyon"]
    }
  ];

  const faqs = [
    { question: "Kurulum gerçekten 15 dakikada tamamlanıyor mu?", answer: "Evet. Ekibimiz hizmet listenizi ve çalışma saatlerinizi hızlıca sisteme entegre eder, linklerinizi yayına hazırlar." },
    { question: "Görüşme sonrasında hemen karar vermek zorunda mıyım?", answer: "Hayır. Görüşme sonrasında düşünmek isterseniz aday müşteri havuzumuzda kalırsınız; hazır olduğunuzda süreci hemen başlatabiliriz." },
    { question: "Eski müşteri listemizi aktarıyor musunuz?", answer: "Evet, Excel veya önceki yazılımınızdaki müşteri kayıtlarınızı ücretsiz olarak BooKi'ye taşıyoruz." }
  ];

  const schemaData = {
    "@context": "https://schema.org",
    "@graph": [
      {
        "@type": "WebPage",
        "@id": "https://booki.kibusiness.co/nasil-calisir#webpage",
        "url": "https://booki.kibusiness.co/nasil-calisir",
        "name": "Nasıl Çalışır? — 4 Adımda Kurulum ve Canlıya Geçiş | BooKi",
        "description": "BooKi online randevu ve masa rezervasyon sistemi nasıl kurulur? Demo, görüşme, kurulumda güvenli ödeme ve 7/24 destek adımları.",
        "isPartOf": { "@id": "https://booki.kibusiness.co/#website" },
        "breadcrumb": {
          "@type": "BreadcrumbList",
          "itemListElement": [
            { "@type": "ListItem", "position": 1, "name": "Ana Sayfa", "item": "https://booki.kibusiness.co/" },
            { "@type": "ListItem", "position": 2, "name": "Nasıl Çalışır?", "item": "https://booki.kibusiness.co/nasil-calisir" }
          ]
        }
      }
    ]
  };

  return (
    <div className="site-shell how-it-works-page">
      <SEOHead
        title="Nasıl Çalışır? — 4 Adımda Kurulum & Canlıya Geçiş | BooKi"
        description="Demo talebi, 15 dakikalık canlı görüşme, kurulumda güvenli ödeme ve kesintisiz 7/24 destek. BooKi ile işletmenizi büyütme rehberi."
        canonicalPath="/nasil-calisir"
        keywords={[
          "randevu sistemi kurulumu",
          "rezervasyon programı nasıl kurulur",
          "kolay randevu kurulum süreci",
          "online randevu entegrasyonu"
        ]}
        schemaJson={schemaData}
      />

      <Navbar />

      <main id="main-content">
        <section className="sector-hero how-it-works-hero-mod">
          <div className="sector-hero-container">
            <div className="sector-hero-badge">
              <span className="dot" /> 4 ADIMLI ŞEFFAF SÜREÇ
            </div>
            <h1>
              İyi bir iş akışı, <br />
              <em>4 basit adımda başlar.</em>
            </h1>
            <p className="sector-hero-description">
              Teknolojiyle aranıza karmaşık işler koymuyoruz. Sizi dinliyor, sisteminizi 15 dakikada kuruyor ve ödemeyi kurulumda güvenle alıyoruz.
            </p>

            <div className="sector-trust-bar">
              <span><CheckCircle2 size={15} /> Kredi Kartı Şartı Yok</span>
              <span><CheckCircle2 size={15} /> Kurulumda Güvenli Ödeme</span>
              <span><CheckCircle2 size={15} /> 15 Dk. Anahtar Teslim</span>
            </div>
          </div>
        </section>

        {/* Detailed 4 Steps Grid */}
        <section className="how-steps-detail-section">
          <div className="section-container">
            <div className="how-steps-timeline">
              {steps.map((item) => {
                const IconComp = item.icon;
                return (
                  <div key={item.step} className="timeline-item">
                    <div className="timeline-badge-col">
                      <span className={`timeline-icon-box ${item.color}`}>
                        <IconComp size={24} />
                      </span>
                      <span className="timeline-step-number">{item.step}</span>
                    </div>

                    <div className="timeline-card-content">
                      <span className="timeline-tagline">{item.tagline}</span>
                      <h3>{item.title}</h3>
                      <p>{item.desc}</p>

                      <div className="timeline-highlights">
                        {item.highlights.map((h, i) => (
                          <span key={i}>
                            <CheckCircle2 size={14} /> {h}
                          </span>
                        ))}
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        </section>

        {/* FAQ Section */}
        <section className="sector-faq-section">
          <div className="section-container">
            <div className="section-header-center">
              <span className="badge-sub">SIKÇA SORULAN SORULAR</span>
              <h2>Süreç Hakkında Merak Edilenler</h2>
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
        <SalesFunnelSection
          title="İlk Adımı Şimdi Atın, Birlikte Kuralım"
          subtitle="WhatsApp'tan yazın veya formu doldurun. 15 dakikada sisteminizi canlıya alalım."
        />
      </main>

      <StickyContactBar />
      <Footer />
    </div>
  );
}
