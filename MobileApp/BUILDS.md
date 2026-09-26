# 📱 BooKi All-in-One Mobil Uygulama Derlemeleri (Builds)

Bu klasör, BooKi / Ki-Reservation SaaS platformunun hem **Android** hem de **iOS** platformları için derlenmiş hazır paketlerini içerir.

---

## 📦 Paket Listesi

| Dosya Adı | Platform | Mimari / Tür | Boyut | Açıklama / Kullanım Alanı |
| :--- | :--- | :--- | :--- | :--- |
| Dosya Adı | Platform | Mimari / Tür | Boyut | Açıklama / Kullanım Alanı |
| :--- | :--- | :--- | :--- | :--- |
| **`BooKi-Android-release.apk`** | Android | Release (arm64, armv7, x86_64) | ~53 MB | Herhangi bir Android telefona/tablete doğrudan yükleyip (`adb install` veya dosya yöneticisiyle) kullanabileceğiniz optimize edilmiş Release APK. |
| **`BooKi-iOS-unsigned.ipa`** | iOS | Release (Gerçek Cihaz / iphoneos) | ~7.5 MB | Fiziksel iPhone/iPad cihazları için derlenmiş paket. AltStore, Sideloadly veya Apple Developer hesabı ile yüklenir. |
| **`booki-appetize-iOS.zip`** (ve `BooKi-Appetize-iOS-Simulator.zip`) | iOS Simulator | Simulator (x86_64 / arm64) | ~56 MB | **Appetize.io** ve benzeri tarayıcı simülatörleri için özel hazırlanmış `.app` zip paketi. Doğrudan Appetize.io'ya yüklenip tarayıcıda çalıştırılır. |

---

## 🚀 Appetize.io Üzerinde Nasıl Test Edilir?

1. [https://appetize.io/upload](https://appetize.io/upload) sayfasına gidin.
2. **`booki-appetize-iOS.zip`** dosyasını sürükleyip bırakın.
3. Ekrandaki **View App** butonuna tıklayın.
4. Tarayıcınızda açılan sanal iPhone üzerinde BooKi uygulamasını canlı ve etkileşimli olarak deneyimleyin!

---

## 🏢 Web Portalı Gibi 2 Aşamalı Giriş Akışı

1. **Aşama 1 (İşletme Kodu):** Kullanıcı ilk ekranda işletme kodunu (subdomain, örn: `salonflora`) girer veya listeden seçer. Canlı olarak `https://kod-bookiapp.kibusiness.co` adresi önizlenir.
2. **Aşama 2 (Giriş Bilgileri):** İlgili işletmenin kartı ve alan adı üstte görünür. Kullanıcı e-posta/kullanıcı adı ve şifresini girer.
3. **Otomatik Rol Yönlendirmesi:** Sistem kullanıcının rolünü arka planda algılar:
   - **Müşteri** ise -> Müşteri randevu, rezervasyon ve profil ekranı açılır.
   - **İşletme Yetkilisi / Personel / Admin** ise -> Personel ajanda, takvim ve randevu yönetim ekranı açılır.

---

## ⚙️ Sunucu Bağlantı Ayarları

Uygulama ilk açıldığında sağ üstteki ⚙️ **Ayarlar** simgesine dokunarak:
- **Canlı SaaS Platformu:** `https://bookiapp.kibusiness.co` (Varsayılan)
- **Yerel Android Emülatör:** `http://10.0.2.2`
- **Yerel Geliştirme:** `http://<sunucu-ip-adresi>`
olarak yapılandırabilirsiniz.

