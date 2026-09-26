# 🖥️ BooKi DesktopApp — Windows & macOS Masaüstü Uygulaması

Bu dizin, **BooKi SaaS** platformunun **Windows** ve **macOS** işletim sistemleri için geliştirilen masaüstü uygulamasını barındırır.

- **Geliştirici Firma:** [Ki Software](https://software.kibusiness.co)
- **Ekosistem:** BooKi SaaS (`https://bookiapp.kibusiness.co`, `https://booki.kibusiness.co`) & RandevuBurada (`https://randevuburada.kibusiness.co`)

---

## 🎯 Masaüstü Uygulamasının Temel Amaçları

1. **Donanım Entegrasyonu (Hardware-First):**
   - **Termal Fiş & Adisyon Yazıcıları (ESC/POS):** USB, Seri Port (COM) ve Yerel Ağ (TCP/IP) üzerinden anında otomatik fiş yazdırma.
   - **Barkod ve QR Okuyucular:** Doğrudan donanım seviyesinde müşteri kartı ve randevu QR tarama.
   - **Müşteri Göstergesi (Customer Facing Display):** İkincil ekran desteği ile randevu ve ödeme detayı sunumu.

2. **Offline-First & Kesintisiz Çalışma:**
   - İnternet bağlantısı kesildiğinde yerel SQLite veritabanı ile randevu ve ajanda takibi.
   - Bağlantı geri geldiğinde `Backend/` REST API (`/api/v1/`) üzerinden çift yönlü otomatik senkronizasyon.

3. **Çoklu Pencere & Kasa (POS) Desteği:**
   - Resepsiyon/karşılama ekranı ve canlı ajanda için çoklu monitör pencereleme.

---

## 📁 Dizin Düzeni

- `windows/` — Windows yerel derleme, manifest ve MSIX paketleme dosyaları.
- `macos/` — macOS derleme, Info.plist ve DMG paketleme yapılandırması.
- `docs/` — Masaüstü mimarisi, donanım sürücü protokolleri ve offline senkronizasyon dokümanları.

---

## 🛠️ Geliştirme ve Derleme (Flutter Desktop)

```bash
# Windows derlemesi
flutter build windows --release

# macOS derlemesi
flutter build macos --release
```
