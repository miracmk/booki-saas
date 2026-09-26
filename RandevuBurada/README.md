# 🏪 RandevuBurada — Tüketici Hizmet & Randevu Pazaryeri

**RandevuBurada** (`https://randevuburada.kibusiness.co`), [Ki Software](https://software.kibusiness.co) tarafından geliştirilen ve **BooKi SaaS** ekosistemine bağlı çalışan, tüketicilerin Türkiye genelinde kuaför, berber, klinik, güzellik ve bakım işletmelerini keşfetmesini ve 7/24 anında online randevu almasını sağlayan merkezi pazaryeri platformudur.

---

## 🚀 Ekosistem İçindeki Konumu ve Rolü

- **Üretici Firma:** [Ki Software](https://software.kibusiness.co)
- **Ana SaaS Motoru:** BooKi SaaS (`bookiapp.kibusiness.co`, `{tenant}-bookiapp.kibusiness.co`)
- **Tüketici Pazaryeri:** [RandevuBurada](https://randevuburada.kibusiness.co)

RandevuBurada, BooKi SaaS'ı kullanan tüm kiracı işletmelerin hizmetlerini tek bir tüketici vitrininde toplar. Aynı zamanda Google Places Crawler aracılığıyla Türkiye'deki binlerce işletmeyi zenginleştirilmiş veriyle (adres, telefon, koordinat, fotoğraf, çalışma saatleri) kataloglar ve profil sahiplenme (`/sahiplen/:token`) akışı ile işletmeleri BooKi SaaS kiracısına dönüştürür.

---

## 📁 Bileşenler ve Dizin Düzeni

- `docs/` — PSEO mimarisi, Google Places zenginleştirme botu, arama ve sahiplenme sözleşmeleri.
- `assets/` — RandevuBurada kurumsal logoları, favicon'ları ve pazaryeri rozetleri.

### İlgili Çekirdek Kodlar (Backend):
- **Pazaryeri Denetleyicisi:** `Backend/application/controllers/Marketplace.php`
- **Sahiplenme & PSEO Modeli:** `Backend/application/models/Leads_model.php`
- **Google Places Zenginleştirme:** `Backend/application/libraries/Google_places_crawler.php`
- **Pazaryeri Görünümleri:**
  - `Backend/application/views/pages/marketplace_index.php` (Ana Arama Sayfası)
  - `Backend/application/views/pages/marketplace_business.php` (Kiracı İşletme Sayfası)
  - `Backend/application/views/pages/marketplace_isletme.php` (Zenginleştirilmiş Lead Sayfası)
- **Domain Yönlendirmesi:** `Backend/application/config/routes.php` (`randevuburada.kibusiness.co` ana host eşleşmesi)

---

## 🔍 Temel Özellikler

1. **Programatik SEO (pSEO):**
   - Şehir / İlçe / Kategori hiyerarşik URL yapılandırması (örn. `/kategori/kuafor/istanbul/kadikoy`).
   - Dinamik JSON-LD `LocalBusiness` ve `BreadcrumbList` yapısal veri üretimi.
   - Otomatik `sitemap.xml` ve `robots.txt` çıktısı.

2. **Google Places Entegrasyonu & Görsel Proxy:**
   - İşletme fotoğraflarını Google API anahtarını sızdırmadan proxy'leyen `/api/places/photo` mekanizması.

3. **İşletme Sahiplenme Akışı (Claiming Funnel):**
   - Lead işletmeler için benzersiz `claim_token` üretimi.
   - Doğrulama sonrası anında BooKi SaaS kiracısı (`{subdomain}-bookiapp.kibusiness.co`) oluşturma.
