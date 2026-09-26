# 🌐 BooKi WebApp — React Web Platformu

Bu dizin, **BooKi SaaS** platformunun resmi vitrin, pazarlama, özellik tanıtımı ve paket fiyatlandırma web uygulamasını barındırır.

- **Yapımcı Firma:** [Ki Software](https://software.kibusiness.co)
- **Canlı Domain:** [booki.kibusiness.co](https://booki.kibusiness.co)
- **Ekosistem:** BooKi SaaS (`bookiapp.kibusiness.co`) & RandevuBurada (`randevuburada.kibusiness.co`)

---

## 🛠️ Teknoloji Yığını

- **Frontend:** React 19, Vite 7, TypeScript, Tailwind CSS, Radix UI, Lucide Icons, Framer Motion
- **Backend Servisi:** Node.js, Express, tRPC 11, Nodemailer (SMTP), Zoho CRM API
- **Konteynerizasyon:** Docker (`booki-website`), Nginx Proxy Manager entegrasyonu (Port 8091 &rarr; 3000)

---

## 📁 Dizin Düzeni

- `client/` — React bileşenleri, sayfalar (`Home`, `PricingPage`, `FeaturePage`, `SectorPage`, `ComparisonPage`, `LegalPage`)
- `server/` — Express API, demo talep e-postaları, Zoho CRM senkronizasyonu
- `drizzle/` — Veri şeması ve migrasyon tanımları
- `Dockerfile` & `docker-compose.yml` — WebApp üretim konteyneri

---

## 🚀 Çalıştırma

```bash
# Geliştirme ortamı
pnpm install
pnpm dev

# Üretim ortamı derleme ve başlatma
pnpm build
pnpm start
```
