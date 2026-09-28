# ⚙️ BooKi Backend — Vertical-First SaaS Çekirdeği, API & AI Servisleri

Bu dizin, **BooKi SaaS** ve **RandevuBurada Pazaryeri** platformlarının çok kiracılı (multi-tenant) çekirdeğini, sektörel blueprint motorunu, REST API servislerini ve yardımcı yapay zeka sidecar'larını barındırır.

- **Yapımcı Firma:** [Ki Software](https://software.kibusiness.co) (`software.kibusiness.co`)
- **Ana Ürün:** BooKi SaaS (`https://bookiapp.kibusiness.co`, `{tenant}-bookiapp.kibusiness.co`, `https://admin-bookiapp.kibusiness.co`)
- **Pazaryeri:** RandevuBurada (`https://randevuburada.kibusiness.co`)
- **Merkezi Mimari Belgesi:** [PROJECTMAP.md](file:///opt/ki-ecosystem/ki-reservation-src/PROJECTMAP.md)

---

## 🏛️ Mimari ve Dizin Düzeni

- `application/` — MVC ve Kurumsal Servis Katmanı:
  - `controllers/` — Web, API (`api/v1/`), Superadmin, Marketplace, Catalog ve CLI denetleyicileri.
  - `models/` — Kiracı ve master veritabanı modelleri (`Roles_model`, `Appointments_model` vb.).
  - `libraries/` — Temel servis katmanı:
    - `Vertical_service.php` — 9 Sektör ailesi, işletme tipi çözümleme ve terminoloji yönetimi.
    - `Navigation_service.php` — 12 gruplu, modül ve izin filtrelemeli dinamik menü şeması.
    - `Permission_service.php` — 10 aksiyon, 4 scope ve multi-branch şube erişim kontrolü.
    - `Ai_governance_service.php` — AI yetki tavanı, 5 seviyeli icazet, eskalasyon ve kontrollü öğrenme hattı.
    - `Demo_service.php` — Sektörel roller arasında tek tıkla canlı geçiş (`switch_role`).
    - `Blueprint_service.php` — Sektörel JSON şablon yükleyici ve otomatik demo veri tohumlayıcı.
  - `seeders/blueprints/` — 18 Sektörel blueprint JSON şablonu (Beauty, Restoran, Klinik, Spor, Otomotiv vb.).
  - `migrations/` — Çok kiracılı veritabanı migrasyonları (1-170).
  - `views/` — Dinamik yönetim paneli (`backend_header.php`), birleşik katalog (`catalog.php`) ve dikey modül ekranları.
  - `core/` — `App_Controller`: Çok kiracılı domain çözümleme (`resolve_tenant()`) ve backend route güvenliği (`enforce_route_permissions()`).
- `system/` — CodeIgniter 3 çekirdek kütüphaneleri.
- `assets/` — Yönetim paneli, randevu widget'ı ve sektörel ekran CSS/JS varlıkları.
- `storage/` — Loglar, oturumlar, yedekler ve yerel önbellek.
- `deploy/` — Docker dağıtım yapılandırması:
  - `Dockerfile` — Üretim ortamı PHP 8.2/Apache imajı.
  - `docker-compose.yml` — `booki-app`, `booki-db`, `booki-wa`, `booki-mcp` servis orkestrasyonu.
  - `bridge/` — Baileys tabanlı izole WhatsApp Web oturum köprüsü (`booki-wa`).
  - `mcp/` — Harici yapay zeka ajanları için Model Context Protocol sunucusu (`booki-mcp`).

---

## 🧪 Testleri Çalıştırma

```bash
# Docker konteyneri içinde entegrasyon testlerini çalıştırma
docker exec -e TENANT_MASTER_KEY="..." booki-app ./vendor/bin/phpunit tests/Integration/VerticalRolePermissionIntegrationTest.php

# Tüm test paketini çalıştırma (460 test, 7036 assertions)
docker exec -e TENANT_MASTER_KEY="..." booki-app ./vendor/bin/phpunit
```
