# 🌟 BooKi SaaS — Çok Kiracılı Rezervasyon, CRM & RandevuBurada Pazaryeri Ekosistemi

> **Geliştirici & Üretici Firma:** [Ki Software](https://software.kibusiness.co) (`software.kibusiness.co`)  
> **Ana SaaS Platformu:** [BooKi SaaS](https://bookiapp.kibusiness.co) & [booki.kibusiness.co](https://booki.kibusiness.co)  
> **Tüketici Pazaryeri:** [RandevuBurada](https://randevuburada.kibusiness.co)  
> **Proje Haritası & Beyin Dokümanı:** [PROJECTMAP.md](file:///opt/ki-ecosystem/ki-reservation-src/PROJECTMAP.md)

---

## 🧠 Merkezi Proje Haritası (PROJECTMAP)

Bu deponun tüm mimari yapısı, API uç noktaları, MCP araçları, ortam değişkenleri ve servis iletişim şemaları **[PROJECTMAP.md](file:///opt/ki-ecosystem/ki-reservation-src/PROJECTMAP.md)** içinde eksiksiz olarak haritalandırılmıştır. Geliştirme yaparken veya yapay zeka ajanları ile çalışırken bağlam (context) ve token tasarrufu sağlamak için doğrudan `PROJECTMAP.md` belgesine başvurunuz.

---

## 🏛️ Monorepo Mimarisi ve Dizin Düzeni

```text
booki-saas/
├── ⚙️ Backend/                        # PHP CodeIgniter 3 SaaS Çekirdeği, REST API & Sidecar'lar
│   ├── application/                   # Controllers (API, Admin, Marketplace), Models, Libraries
│   ├── system/                        # CodeIgniter Framework Çekirdeği
│   ├── assets/                        # Panel & Randevu Widget CSS/JS Varlıkları
│   ├── storage/                       # Loglar, Yedekler, Oturumlar
│   ├── deploy/                        # Dockerfile, docker-compose.yml, wa-bridge, booki-mcp
│   ├── composer.json                  # PHP Bağımlılıkları
│   └── README.md                      # Backend Mimari Dokümantasyonu
│
├── 🌐 WebApp/                         # React 19 + Vite + Tailwind Resmi Tanıtım & Fiyatlandırma Sitesi
│   ├── client/                        # React Sayfaları, UI Bileşenleri, Tema ve Formlar
│   ├── server/                        # Node.js Express, tRPC, Zoho CRM & SMTP Entegrasyonu
│   ├── Dockerfile & compose           # booki-website Konteyner Yapılandırması (Port 8091)
│   └── README.md                      # WebApp Dokümantasyonu
│
├── 📱 MobileApp/                      # Flutter Çoklu Platform Mobil Uygulama (iOS & Android)
│   ├── android/                       # Android Yerel Projesi & Gradle
│   ├── ios/                           # iOS Xcode Projesi & Podfile
│   ├── lib/                           # Flutter/Dart UI, Riverpod State, Dio API İstemcisi
│   ├── pubspec.yaml                   # booki_mobile Paket Tanımı
│   └── README.md                      # Mobil Uygulama Kılavuzu
│
├── 🖥️ DesktopApp/                     # Windows & macOS Masaüstü Uygulaması
│   ├── windows/                       # Windows Runner & MSIX Dağıtım
│   ├── macos/                         # macOS Runner & DMG Dağıtım
│   ├── docs/                          # ESC/POS Termal Fiş Yazıcı & Offline SQLite Senkronizasyon Mimarisi
│   └── README.md                      # Masaüstü Yol Haritası
│
├── 🏪 RandevuBurada/                  # Tüketici Hizmet & Randevu Pazaryeri (Marketplace)
│   ├── assets/                        # RandevuBurada Kurumsal Logoları, Rozetleri ve İkonları
│   ├── docs/                          # PSEO, Google Places Crawler & Sahiplenme Sözleşmesi
│   └── README.md                      # RandevuBurada Kılavuzu
│
├── 📂 docs/                           # Sistem Mimarisi, Saha Satış Platformu & Denetim Notları
├── 🧠 PROJECTMAP.md                   # Proje Beyni: Tüm API, MCP, ENV ve Mimari Haritası
├── .github/                           # CI/CD GitHub Actions İş Akışları
├── .gitignore                         # Kapsamlı Monorepo Dışlama Kuralları
└── README.md                          # Genel BooKi SaaS Ekosistem Kılavuzu
```

---

## 🌐 Ekosistem Domain Haritası

| Domain | Servis / Rol | Port / Konteyner | Açıklama |
| :--- | :--- | :--- | :--- |
| `booki.kibusiness.co` | **WebApp** | 8091 &rarr; `booki-website` | React tanıtım, vitrin ve fiyatlandırma platformu |
| `bookiapp.kibusiness.co` | **Backend (SaaS)** | 80 &rarr; `booki-app` | Ana SaaS platformu ve müşteri randevu arayüzü |
| `admin-bookiapp.kibusiness.co` | **Backend (Admin)** | 80 &rarr; `booki-app` | Çok kiracılı SaaS yönetim ve lisans paneli |
| `{tenant}-bookiapp.kibusiness.co` | **Backend (Kiracı)** | 80 &rarr; `booki-app` | Kiracıya özel personel paneli ve online randevu sayfası |
| `randevuburada.kibusiness.co` | **RandevuBurada** | 80 &rarr; `booki-app` | Tüketici keşif ve online randevu pazaryeri (Marketplace) |
| `127.0.0.1:3039` | **WhatsApp Bridge** | 3000 &rarr; `booki-wa` | Baileys izole WhatsApp oturum köprüsü |
| Port `8765` | **MCP Server** | 8765 &rarr; `booki-mcp` | Harici AI ajanları için Model Context Protocol sunucusu |

---

## 🚀 Canlı Üretim Ortamı Konteynerleri

Tüm servisler `/opt/ki-ecosystem/` üzerinde Docker ile izole olarak çalışmaktadır:

1. **`booki-app`:** Çok kiracılı PHP/Apache SaaS motoru.
2. **`booki-db`:** MySQL 8.0 master ve izole kiracı veritabanları.
3. **`booki-wa`:** Baileys tabanlı izole WhatsApp Web oturum köprüsü.
4. **`booki-mcp`:** Dış AI asistanları için Model Context Protocol sunucusu.
5. **`booki-website`:** React 19 / Node.js web uygulaması.

---

## 📜 Lisans & Telif Hakkı

Tüm hakları saklıdır.  
Geliştirici & Üretici: **Ki Software** ([software.kibusiness.co](https://software.kibusiness.co)) — Ki Business Solutions.
