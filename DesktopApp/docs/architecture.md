# 📐 BooKi Desktop Mimari Spesifikasyonu

Bu doküman, BooKi Masaüstü istemcisinin teknik mimarisini, veri akışını ve donanım katmanını tanımlar.

- **Yapımcı Firma:** [Ki Software](https://software.kibusiness.co)
- **Ürün:** BooKi Desktop Client

---

## 1. Veri Akışı ve Offline Senkronizasyon

```
[BooKi Desktop UI] <---> [Local SQLite Cache]
                              ^
                              | (Background Sync Worker)
                              v
                   [BooKi SaaS Backend API]
```

1. **Yerel Veri Depolama:**
   - Kiracı ve personel bilgileri yerel SQLite tablosunda önbelleğe alınır.
   - Oluşturulan veya güncellenen randevular önce yerel SQLite'a `sync_status = 'pending'` olarak yazılır.

2. **Senkronizasyon Protokolü:**
   - Ağ bağlantısı aktif olduğunda periyodik (30 saniye) veya olay tabanlı (event-driven) olarak `POST /api/v1/operations/sync` endpoint'ine push edilir.
   - Çakışma durumunda (conflict resolution) sunucu zaman damgası (server-timestamp) üstün kabul edilir.

---

## 2. Donanım Katmanı (ESC/POS & Tarayıcılar)

- **ESC/POS Yazıcı Protokolü:** 80mm ve 58mm termal yazıcılar için UTF-8 Türkçe karakter tablosu (`PC857` / `CP1254`) desteklenir.
- **Barkod / QR Tarayıcı:** Standart HID klavye emülasyonu veya seri bağlantı ile anında müşteri doğrulama.
