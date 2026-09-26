import { useState } from "react";
import { COMPETITORS, CompetitorComparison } from "@/data/competitorsData";
import { SEOHead } from "@/components/SEOHead";
import { Navbar } from "@/components/Navbar";
import { Footer } from "@/components/Footer";
import { StickyContactBar } from "@/components/StickyContactBar";
import { SalesFunnelSection } from "@/components/SalesFunnelSection";
import {
  Check,
  X,
  Search,
  Sparkles,
  ShieldCheck,
  MoveRight,
  MessageCircle,
  Phone,
  Scale,
  Crown,
  CheckCircle2,
  AlertTriangle
} from "lucide-react";

export default function ComparisonPage() {
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedCategory, setSelectedCategory] = useState<string>("Tümü");

  const categories = ["Tümü", "Yerli Yazılımlar", "Global Randevu Sistemleri", "Restoran / Masa Rezervasyon"];

  const filteredCompetitors = COMPETITORS.filter((comp) => {
    const matchesSearch = comp.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      comp.sectorsCovered.toLowerCase().includes(searchTerm.toLowerCase()) ||
      comp.cons.some(c => c.toLowerCase().includes(searchTerm.toLowerCase()));
    const matchesCategory = selectedCategory === "Tümü" || comp.category === selectedCategory;
    return matchesSearch && matchesCategory;
  });

  const schemaData = {
    "@context": "https://schema.org",
    "@graph": [
      {
        "@type": "WebPage",
        "@id": "https://booki.kibusiness.co/karsilastirma#webpage",
        "url": "https://booki.kibusiness.co/karsilastirma",
        "name": "BooKi vs 20+ Randevu ve Rezervasyon Yazılımı Karşılaştırması (2026)",
        "description": "BooKi ile SalonAppy, KolayRandevu, Fresha, Restorano, Calendly ve 20+ popüler randevu programının komisyon, özellik ve fiyat karşılaştırması.",
        "isPartOf": { "@id": "https://booki.kibusiness.co/#website" },
        "breadcrumb": {
          "@type": "BreadcrumbList",
          "itemListElement": [
            { "@type": "ListItem", "position": 1, "name": "Ana Sayfa", "item": "https://booki.kibusiness.co/" },
            { "@type": "ListItem", "position": 2, "name": "Karşılaştırma", "item": "https://booki.kibusiness.co/karsilastirma" }
          ]
        }
      },
      {
        "@type": "Table",
        "about": "Online Randevu ve Rezervasyon Sistemleri Karşılaştırma Matrisi"
      }
    ]
  };

  return (
    <div className="site-shell comparison-page">
      <SEOHead
        title="BooKi vs 20+ Randevu ve Rezervasyon Yazılımı Karşılaştırması | 2026 Analizi"
        description="SalonAppy, KolayRandevu, Fresha, Restorano ve 20+ randevu yazılımını karşılaştırın. Neden BooKi'nin %0 komisyonu ve çok sektörlü altyapısı tercih ediliyor?"
        canonicalPath="/karsilastirma"
        keywords={[
          "salonappy alternatif",
          "kolayrandevu alternatif",
          "fresha alternatif türkiye",
          "en iyi randevu programı",
          "randevu yazılımı karşılaştırma",
          "komisyonsuz randevu sistemleri",
          "restoran rezervasyon yazılımları"
        ]}
        schemaJson={schemaData}
      />

      <Navbar />

      <main id="main-content">
        {/* Comparison Hero */}
        <section className="sector-hero comparison-hero">
          <div className="sector-hero-container">
            <div className="sector-hero-badge">
              <Scale size={14} /> ŞEFFAF VE TARAFSIZ 2026 ANALİZİ
            </div>
            <h1>
              BooKi vs 20+ Randevu ve <br />
              <em>Rezervasyon Programı</em>
            </h1>
            <p className="sector-hero-description">
              Yüksek komisyonlar, gizli eklenti ücretleri ve tek sektöre sıkışmış eski yazılımlarla vedalaşın. Türkiye'deki ve dünyadaki 20 popüler randevu yazılımını detaylıca inceledik.
            </p>

            <div className="sector-trust-bar">
              <span><CheckCircle2 size={15} /> %0 Rezervasyon Komisyonu</span>
              <span><CheckCircle2 size={15} /> Kurulumda Güvenli Ödeme</span>
              <span><CheckCircle2 size={15} /> Restoran + Salon + Klinik Tek Yerde</span>
            </div>
          </div>
        </section>

        {/* Master Comparison Table Summary */}
        <section className="comparison-master-section">
          <div className="section-container">
            <div className="section-header-center">
              <span className="badge-sub">ÖZET KARŞILAŞTIRMA</span>
              <h2>Genel Standartlar <em>Matrisi</em></h2>
            </div>

            <div className="master-table-card">
              <table className="comparison-master-table">
                <thead>
                  <tr>
                    <th>Kriter / Özellik</th>
                    <th className="booki-col"><Crown size={15} /> BooKi</th>
                    <th>SalonAppy</th>
                    <th>KolayRandevu</th>
                    <th>Fresha</th>
                    <th>Restorano / Rezervem</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <th scope="row">Rezervasyon Komisyonu</th>
                    <td className="booki-col highlight-cell"><strong>%0 (Net)</strong></td>
                    <td>%0 (Pahalı Eklentiler)</td>
                    <td>%10 - %20 Arası</td>
                    <td>Yeni Müşteride %20</td>
                    <td>Kapak / Rez. Başı</td>
                  </tr>
                  <tr>
                    <th scope="row">Sektör Kapsamı</th>
                    <td className="booki-col highlight-cell"><strong>Tüm Sektörler (Restoran, Salon, Klinik)</strong></td>
                    <td>Yalnızca Güzellik/Kuaför</td>
                    <td>Güzellik/Kuaför</td>
                    <td>Güzellik/Spa</td>
                    <td>Yalnızca Restoran</td>
                  </tr>
                  <tr>
                    <th scope="row">WhatsApp Onay & Teyit</th>
                    <td className="booki-col highlight-cell"><Check size={18} /> Dahil</td>
                    <td>Ekstra Ücretli (SalonWP)</td>
                    <td>Sadece SMS</td>
                    <td>Pahalı SMS</td>
                    <td>Sadece SMS</td>
                  </tr>
                  <tr>
                    <th scope="row">No-Show Önleme & Waitlist</th>
                    <td className="booki-col highlight-cell"><Check size={18} /> Gelişmiş</td>
                    <td>Sınırlı</td>
                    <td>Yok</td>
                    <td>Var</td>
                    <td>Sınırlı</td>
                  </tr>
                  <tr>
                    <th scope="row">Arayüz Standartları</th>
                    <td className="booki-col highlight-cell"><strong>2026 Bento UI</strong></td>
                    <td>Eski PHP/Bootstrap</td>
                    <td>Pazaryeri Odaklı</td>
                    <td>Modern</td>
                    <td>Geleneksel</td>
                  </tr>
                  <tr>
                    <th scope="row">Ödeme Modeli</th>
                    <td className="booki-col highlight-cell"><strong>Kurulumda Güvenli Ödeme</strong></td>
                    <td>Kredi Kartı Peşin</td>
                    <td>Komisyondan Kesinti</td>
                    <td>Otomatik Kart Çekimi</td>
                    <td>Yüksek Peşinat</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        {/* 20 Competitors Deep Directory */}
        <section className="twenty-competitors-section">
          <div className="section-container">
            <div className="section-header-center">
              <span className="badge-sub">DETAYLI RAKİP DİZİNİ</span>
              <h2>20 Yazılımın <em>Artıları & Eksileri</em></h2>
              <p>Aradığınız yazılımı filtreleyin veya listeden inceleyin.</p>
            </div>

            {/* Filter Bar */}
            <div className="competitor-filters">
              <div className="search-input-wrap">
                <Search size={17} />
                <input
                  type="text"
                  placeholder="Yazılım adı, sektör veya özellik ara (örn: SalonAppy, Komisyon, Restoran)..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                />
              </div>

              <div className="category-pills">
                {categories.map((cat) => (
                  <button
                    key={cat}
                    type="button"
                    className={`cat-pill ${selectedCategory === cat ? "active" : ""}`}
                    onClick={() => setSelectedCategory(cat)}
                  >
                    {cat}
                  </button>
                ))}
              </div>
            </div>

            {/* Competitor Cards Grid */}
            <div className="competitor-cards-grid">
              {filteredCompetitors.map((comp) => (
                <article key={comp.name} className="competitor-card">
                  <div className="comp-card-top">
                    <div>
                      <span className="comp-category-badge">{comp.category}</span>
                      <h3>{comp.name}</h3>
                    </div>
                    <div className="comp-commission-tag">
                      <small>Komisyon / Model</small>
                      <b>{comp.commissionRate}</b>
                    </div>
                  </div>

                  <div className="comp-details-list">
                    <div>
                      <span>Sektörler:</span> <b>{comp.sectorsCovered}</b>
                    </div>
                    <div>
                      <span>WhatsApp Desteği:</span> <b>{comp.whatsappSupport}</b>
                    </div>
                  </div>

                  <div className="pros-cons-grid">
                    <div className="pros-col">
                      <b><Check size={14} /> Artıları</b>
                      <ul>
                        {comp.pros.map((pro, i) => (
                          <li key={i}>{pro}</li>
                        ))}
                      </ul>
                    </div>

                    <div className="cons-col">
                      <b><AlertTriangle size={14} /> Eksileri</b>
                      <ul>
                        {comp.cons.map((con, i) => (
                          <li key={i}>{con}</li>
                        ))}
                      </ul>
                    </div>
                  </div>

                  <div className="booki-edge-box">
                    <span className="edge-title"><Crown size={14} /> BooKi Avantajı</span>
                    <p>{comp.bookiAdvantage}</p>
                  </div>
                </article>
              ))}
            </div>
          </div>
        </section>

        {/* Sales Funnel Lead Section */}
        <SalesFunnelSection
          title="Rakiplerle Vakit Kaybetmeyin, BooKi'yi 15 Dakikada Deneyin"
          subtitle="Görüşelim, canlı demo yapalım. Sisteminizi kurup onayınızı aldığımızda ödemenizi yapın."
        />
      </main>

      <StickyContactBar />
      <Footer />
    </div>
  );
}
