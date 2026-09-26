# 🌟 BooKi SaaS — Çok Kiracılı Rezervasyon, CRM & RandevuBurada Pazaryeri Ekosistemi

> **Geliştirici & Üretici Firma:** [Ki Software](https://software.kibusiness.co) (`software.kibusiness.co`)  
> **Ana SaaS Platformu:** [BooKi](https://bookiapp.kibusiness.co) & [booki.kibusiness.co](https://booki.kibusiness.co)  
> **Tüketici Pazaryeri:** [RandevuBurada](https://randevuburada.kibusiness.co)

---

## 🏛️ Monorepo Mimarisi ve Dizin Düzeni

Bu repo (`booki-saas`), BooKi SaaS platformunun tüm bileşenlerini tek bir çatı altında toplayan modern bir monorepo yapısına sahiptir:

```
booki-saas/
├── ⚙️ Backend/                        # PHP CodeIgniter 3 SaaS Çekirdeği, REST API & Sidecar'lar
│   ├── application/                   # Controllers (API, Admin, Marketplace), Models, Libraries
│   ├── system/                        # CodeIgniter Framework
│   ├── assets/                        # Panel & Widget CSS/JS Varlıkları
│   ├── storage/                       # Loglar, Yedekler, Oturumlar
│   ├── deploy/                        # Dockerfile, docker-compose.yml, wa-bridge, booki-mcp
│   ├── composer.json                  # PHP Bağımlılıkları
│   └── README.md                      # Backend Dokümantasyonu
│
├── 🌐 WebApp/                         # React 19 + Vite + Tailwind Resmi Tanıtım & Fiyatlandırma Sitesi
│   ├── client/                        # React Sayfaları, Bileşenler, Tema ve Formlar
│   ├── server/                        # Node.js Express, tRPC, Zoho CRM & SMTP Entegrasyonu
│   ├── Dockerfile                     # WebApp Konteyner Yapılandırması (booki-website)
│   └── README.md                      # WebApp Dokümantasyonu
│
├── 📱 MobileApp/                      # Flutter Çoklu Platform Mobil Uygulama (iOS & Android)
│   ├── android/                       # Android Yerel Projesi
│   ├── ios/                           # iOS Xcode Projesi
│   ├── lib/                           # Flutter/Dart UI, Riverpod State, Dio API İstemcisi
│   └── README.md                      # Mobil Uygulama Kılavuzu
│
├── 🖥️ DesktopApp/                     # Windows & macOS Masaüstü Uygulama Altyapısı
│   ├── windows/                       # Windows Runner
│   ├── macos/                         # macOS Runner
│   ├── docs/                          # ESC/POS Fiş Yazıcı & Offline SQLite Senkronizasyon Mimarisi
│   └── README.md                      # Masaüstü Yol Haritası
│
├── 🏪 RandevuBurada/                  # Tüketici Hizmet & Randevu Pazaryeri
│   ├── assets/                        # RandevuBurada Kurumsal Logoları ve Varlıkları
│   ├── docs/                          # PSEO, Google Places Crawler & Sahiplenme Sözleşmesi
│   └── README.md                      # RandevuBurada Pazaryeri Kılavuzu
│
├── 📂 docs/                           # Sistem Mimarisi, Denetim ve Oturum Notları
├── .github/                           # CI/CD GitHub Actions İş Akışları
├── .gitignore                         # Kapsamlı Monorepo Dışlama Kuralları
└── README.md                          # Genel Ekosistem Dokümantasyonu
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
Geliştirici: **Ki Software** ([software.kibusiness.co](https://software.kibusiness.co)) — Ki Business Solutions.
