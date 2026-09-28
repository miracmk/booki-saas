# 🌟 BooKi SaaS — Çok Kiracılı Rezervasyon, Sektörel CRM, AI Copilot & RandevuBurada Pazaryeri Ekosistemi

> **Geliştirici & Üretici Firma:** [Ki Software](https://software.kibusiness.co) (`software.kibusiness.co`)  
> **Ana SaaS Platformu:** [BooKi SaaS](https://bookiapp.kibusiness.co) & [booki.kibusiness.co](https://booki.kibusiness.co)  
> **Tüketici Pazaryeri:** [RandevuBurada](https://randevuburada.kibusiness.co)  
> **Merkezi Mimari Haritası & Proje Beyni:** [PROJECTMAP.md](file:///opt/ki-ecosystem/ki-reservation-src/PROJECTMAP.md)

---

## 🏛️ Mimari Vizyon: Vertical-First, Role-Aware & Permission-Aware SaaS

BooKi SaaS, tek bir jenerik rezervasyon yazılımı yerine sektörün kendi dinamiklerini, terminolojisini ve iş akışlarını birebir yansıtan **Vertical-First** mimari üzerine inşa edilmiştir.

### 📐 Hiyerarşik Yapı
```text
Aile (Family) 
  └── İşletme Tipi (Business Type) 
        └── Sektörel Blueprint (JSON) 
              └── Modül Sistemi (Enabled / Available / Visible / Permission) 
                    └── Dinamik Navigation (12 Grup, Tek Şema) 
                          └── Rol Şablonları (Job Title vs Role Slug) 
                                └── Granular Permissions (10 Aksiyon, 4 Scope, Branch İzolasyonu)
```

### 🏢 9 Standart Sektör Ailesi & 18 Blueprint
1. **`beauty_wellness`:** Güzellik Salonu, Kuaför, Berber, Tırnak Stüdyosu, Masaj & Spa Merkezi
2. **`restaurant_food`:** Restoran, Kafe & Bistro, Şef Masası & Gastronomi, Canlı 2D Masa Krokisi & KDS
3. **`health_clinical`:** Özel Muayenehane, Poliklinik, Diş Kliniği, Psikoloji & Diyetisyen (EHR/SOAP)
4. **`sports_fitness`:** Gym & Fitness Club, Pilates & Yoga Stüdyosu, Birebir PT, Halı Saha & Spor Kortları
5. **`automotive`:** Profesyonel Oto Yıkama & Detailing, Oto Servis, Ekspertiz & Periyodik Bakım
6. **`hospitality`:** Butik Otel, Bungalov & Konaklama
7. **`experience`:** Kaçış Evi & Deneyim Odaları
8. **`education`:** Özel Kurs & Atölye
9. **`professional`:** Hukuk Bürosu, Mali Müşavirlik, Yönetim Danışmanlığı & Ajans

---

## 🚀 Öne Çıkan Yeni Yetenekler & Refactor Özeti

- **🧭 Merkezi Navigation Servisi (`Navigation_service`):**
  - Tüm menü ağacı tek bir şemadan desktop sidebar ve mobil offcanvas olarak dinamik üretilir.
  - 12 Standart Bölüm: *Dashboard, Operasyon, CRM, Katalog, Kaynaklar, Ekip, Satış & Finans, Pazarlama, Vertical Modülleri, Raporlar, AI Asistan, Ayarlar*.
  - Menü öğeleri kullanıcının rolüne, aktif modüllere ve granular yetkilere göre anlık süzülür.

- **📦 Birleşik Ortak Katalog Sistemi (`Catalog`):**
  - Hizmet, Kategori, Ürün, Paket ve Üyelik modellerini sektöre göre dinamik sunan ortak UI katmanı (Örn: Restoran için *Menü/Ekstralar*, Klinik için *Muayene/Tetkik/Tedavi Paketleri*, Fitness için *Ders/Seans/Üyelikler*).

- **🛡️ Gelişmiş Yetki (RBAC + Scope) & Şube İzolasyonu (`Permission_service`):**
  - Unvan (`job_title`) ile sistem yetki rolü (`role_slug`) ayrılmıştır.
  - 10 Granular Aksiyon: `view`, `add`, `edit`, `delete`, `approve`, `export`, `manage`, `refund`, `override`, `execute`.
  - 4 Kapsam Hiyerarşisi: `own < assigned < branch < all`.
  - Çok şubeli (`multi-branch`) mimaride kullanıcılar yalnızca izinli oldukları şubenin verilerini görür.

- **🔒 Backend Düzeyinde Route Güvenliği (`App_Controller`):**
  - Yalnızca frontend menüsünü gizlemek güvenlik sayılmaz; izinsiz doğrudan URL erişimleri `enforce_route_permissions()` tarafından anında **HTTP 403 Forbidden** ile engellenir.

- **🎭 Canlı Demo Rol Değiştirici (`Demo_service`):**
  - Blueprint'lerde tanımlı roller (Owner, Manager, Garson, Kasiyer, Mutfak vb.) arasında tek tıkla canlı geçiş yapılabilir. Menü, yetkiler ve dashboard anında seçilen role bürünür.

- **🤖 Kurumsal AI Yönetişimi, Yetkileri & Eskalasyon (`Ai_governance_service`):**
  - **Policy Intersection:** `Tenant Politikası ∩ Sektör Politikası ∩ Kullanıcı Yetkisi`
  - **5 Kademeli Yetki:** `read`, `suggest`, `propose`, `execute`, `approve` (AI hiçbir zaman kullanıcının yetkisini aşamaz).
  - **Kontrollü Öğrenme Hattı:** `Observed → Suggested → Owner Approval → Business Rule → Active`.
  - **Otomatik Eskalasyon:** Medikal, hukuki, finansal ve kızgın müşteri durumlarında tespitle `ea_ai_escalation_handoffs` kaydı açılır ve insan personeline devredilir.

- **⚙️ Modernize Edilmiş Birleşik Ayarlar Merkezi (`Settings`):**
  - Eski dağınık ayar sayfaları yerine 6 ana bölüm (*İşletme, Rezervasyon, İletişim, Entegrasyonlar, Hukuk, Güvenlik*) ve 25 sekmeden oluşan tek çatı UI mimarisi.
  - Geriye dönük uyumlu rota yönlendirmeleri (`/general_settings`, `/booking_settings` vb.) otomatik olarak doğru sekmeyi açar.
  - %100 çok dilli (`application/language/`) altyapı ile hem demo hem de canlı üretim kiracılarında tutarlı, güvenli ve sezgisel yönetim.

- **📊 Sektörel & Role Duyarlı Dashboard & Empty States:**
  - `industry_dashboard_config`: Owner ile Staff için sektörün KPI'ları ve hızlı butonları ayrışır.
  - `render_empty_state`: Boş tablolarda sektörel terminolojiye ve kullanıcının yetkisine uygun CTA butonları gösterilir.

---

## 🏛️ Monorepo Mimarisi ve Dizin Düzeni

```text
booki-saas/
├── ⚙️ Backend/                        # PHP CodeIgniter 3 SaaS Çekirdeği, REST API & Sidecar'lar
│   ├── application/                   # Controllers, Models, Libraries, Migrations (1-170)
│   │   ├── libraries/                 # Vertical_service, Navigation_service, Permission_service,
│   │   │                              # Ai_governance_service, Demo_service, Blueprint_service
│   │   ├── seeders/blueprints/        # 18 Sektörel JSON Şablonu (Tüm alanlar eksiksiz)
│   │   ├── core/                      # App_Controller (Multi-tenant router + Route Security)
│   │   └── views/                     # backend_header (Dinamik Menü), Catalog, Vertical sayfaları
│   ├── tests/                         # PHPUnit Test Paketi (Unit, Integration, System)
│   ├── deploy/                        # Dockerfile, docker-compose.yml, wa-bridge, booki-mcp
│   └── README.md                      # Backend Mimari Dokümantasyonu
│
├── 🌐 WebApp/                         # React 19 + Vite + Tailwind Resmi Tanıtım & Fiyatlandırma Sitesi
│   ├── client/                        # React Sayfaları, UI Bileşenleri, Tema ve Formlar
│   ├── server/                        # Node.js Express, tRPC, Zoho CRM & SMTP Entegrasyonu
│   └── README.md                      # WebApp Dokümantasyonu
│
├── 📱 MobileApp/                      # Flutter Çoklu Platform Mobil Uygulama (iOS & Android)
│   ├── lib/                           # Riverpod State, Dio API v1 İstemcisi, QR Kod Okuyucu
│   └── README.md                      # Mobil Uygulama Kılavuzu
│
├── 🖥️ DesktopApp/                     # Windows & macOS Masaüstü Uygulaması
│   ├── docs/                          # ESC/POS Termal Fiş Yazıcı & Offline SQLite Senkronizasyonu
│   └── README.md                      # Masaüstü Yol Haritası
│
├── 🏪 RandevuBurada/                  # Tüketici Hizmet & Randevu Pazaryeri (Marketplace)
│   ├── docs/                          # PSEO, Google Places Crawler & Sahiplenme Sözleşmesi
│   └── README.md                      # RandevuBurada Kılavuzu
│
├── 🧠 PROJECTMAP.md                   # Proje Beyni: Tüm API, MCP, ENV ve Mimari Haritası
└── README.md                          # Genel BooKi SaaS Ekosistem Kılavuzu
```

---

## 🌐 Ekosistem Domain Haritası

| Domain | Servis / Rol | Port / Konteyner | Açıklama |
| :--- | :--- | :--- | :--- |
| `booki.kibusiness.co` | **WebApp** | 8091 &rarr; `booki-website` | React 19 vitrin, fiyatlandırma ve demo talepleri |
| `bookiapp.kibusiness.co` | **Backend (SaaS)** | 80 &rarr; `booki-app` | Çok kiracılı SaaS randevu karşılama ve ana platform |
| `admin-bookiapp.kibusiness.co` | **Backend (Admin)** | 80 &rarr; `booki-app` | SaaS süperadmin yönetim, lisans ve kiracı paneli |
| `{tenant}-bookiapp.kibusiness.co` | **Backend (Kiracı)** | 80 &rarr; `booki-app` | Kiracıya özel personel paneli, takvim, POS ve Ayarlar Merkezi |
| `{demoismi}-bookiapp.kibusiness.co` | **Backend (Demo)** | 80 &rarr; `booki-app` | Sektörel interaktif canlı demolar (Kullanıcı: `{demoismi}-{rol}`, Şifre: `{demoismi}.BooKi`) |
| `randevuburada.kibusiness.co` | **RandevuBurada** | 80 &rarr; `booki-app` | Tüketici keşif pazaryeri (Marketplace) & pSEO |
| `127.0.0.1:3039` | **WhatsApp Bridge** | 3000 &rarr; `booki-wa` | Baileys izole WhatsApp Web oturum köprüsü |
| Port `8765` | **MCP Server** | 8765 &rarr; `booki-mcp` | LLM Ajanları için Streamable HTTP MCP sunucusu |

---

## 🧪 Test & Doğrulama Güvencesi

Sistem, kapsamlı PHPUnit test otomasyonu ile korunmaktadır:
- **`VerticalRolePermissionIntegrationTest`:** 9 test senaryosu, 137 assertion (%100 başarıyla geçmektedir).
- **`SettingsCenterIntegrationTest`:** 6 test senaryosu, 38 assertion (%100 başarıyla geçmektedir).
- **Genel Test Paketi:** 466 test, 7074 assertion, 0 hata ile çalışır.
- Test Edilen 5 Ana Arketip:
  - **Restoran:** Owner, General Manager, Waiter, Cashier, Kitchen KDS
  - **Klinik:** Owner, Clinic Manager, Doctor, Nurse, Reception, Cashier
  - **Güzellik:** Owner, Reception, Professional
  - **Spa & Masaj:** Owner, Reception, Therapist
  - **Fitness & Spor:** Owner, Reception, Personal Trainer

---

## 📜 Lisans & Telif Hakkı

Tüm hakları saklıdır.  
Geliştirici & Üretici: **Ki Software** ([software.kibusiness.co](https://software.kibusiness.co)) — Ki Business Solutions.
