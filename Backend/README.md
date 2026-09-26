# ⚙️ BooKi Backend — SaaS Çekirdeği, API & Servisler

Bu dizin, **BooKi SaaS** ve **RandevuBurada Pazaryeri** platformlarının çok kiracılı (multi-tenant) çekirdeğini, REST API servislerini ve yardımcı konteynerlerini barındırır.

- **Yapımcı Firma:** [Ki Software](https://software.kibusiness.co) (`software.kibusiness.co`)
- **Ana Ürün:** BooKi SaaS (`https://bookiapp.kibusiness.co`, `{tenant}-bookiapp.kibusiness.co`, `https://admin-bookiapp.kibusiness.co`)
- **Pazaryeri:** RandevuBurada (`https://randevuburada.kibusiness.co`)

---

## 📁 Mimari ve Dizin Düzeni

- `application/` — MVC yapısı:
  - `controllers/` — Web, API (`api/v1/`), Superadmin, Marketplace ve CLI denetleyicileri.
  - `models/` — Kiracı ve master veritabanı modelleri.
  - `libraries/` — AI Responder (Gemini/Groq), Google Sync, WhatsApp istemcisi, Ödeme (Iyzico), Bildirim servisleri.
  - `migrations/` — Çok kiracılı dinamik veritabanı migrasyonları (1-163).
  - `views/` — Yönetim paneli, randevu sihirbazı, POS ekranı ve RandevuBurada görünümleri.
  - `core/` — `App_Controller`, çok kiracılı domain çözümleme (`resolve_tenant()`), güvenlik kancaları.
- `system/` — CodeIgniter 3 çekirdek kütüphaneleri.
- `assets/` — Yönetim paneli ve randevu widget'ı CSS/JS varlıkları.
- `storage/` — Loglar, oturumlar, yedekler ve yerel önbellek.
- `deploy/` — Canlı Docker dağıtım yapılandırması:
  - `Dockerfile` — Üretim ortamı PHP/Apache imajı.
  - `docker-compose.yml` — `booki-app`, `booki-db`, `booki-wa`, `booki-mcp` servis orkestrasyonu.
  - `bridge/` — Baileys tabanlı izole WhatsApp Web oturum köprüsü (`booki-wa`).
  - `mcp/` — Harici yapay zeka ajanları için Model Context Protocol sunucusu (`booki-mcp`).

---

## 🚀 Canlı Dağıtım & Çalıştırma

```bash
# Backend servislerini başlatma
cd Backend/deploy
docker compose up -d --build
```
