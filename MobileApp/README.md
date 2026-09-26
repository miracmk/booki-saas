# 📱 BooKi MobileApp — iOS & Android Mobil Uygulaması

Bu dizin, **BooKi SaaS** platformunun hem işletme sahipleri/personeller hem de müşteriler için geliştirilen **Flutter** tabanlı mobil uygulamasını barındırır.

- **Yapımcı Firma:** [Ki Software](https://software.kibusiness.co)
- **Ekosistem:** BooKi SaaS (`bookiapp.kibusiness.co`) & RandevuBurada (`randevuburada.kibusiness.co`)

---

## 🚀 Temel Yetenekler

1. **İki Aşamalı Kurumsal Giriş:**
   - Aşama 1: İşletme kodu (subdomain, örn: `salonflora`) girişi veya arama.
   - Aşama 2: Kullanıcı adı / e-posta ve parola girişi.
2. **Rol Tabanlı Arayüz:**
   - **Müşteri:** Aktif randevular, QR geçiş kartı, randevu alma ve iptal/erteleme.
   - **Personel / Admin:** Günlük/haftalık canlı ajanda, masa/istasyon takibi, hızlı durum değiştirme (Geldi, Başladı, Tamamlandı), POS entegrasyonu.
3. **QR Kod ile Hızlı Check-in:**
   - Dahili kamera tarayıcısı (`mobile_scanner`) ile müşteri QR kodunu okutup anında randevuyu karşılama.

---

## 🛠️ Derleme ve Dağıtım

```bash
# Bağımlılıkları yükleme
flutter pub get

# Android Release APK
flutter build apk --release

# iOS Release Paketi
flutter build ipa --release
```
