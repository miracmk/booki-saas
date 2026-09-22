# BooKi Mobil - Randevu ve Rezervasyon Uygulaması (Flutter)

BooKi / Ki-Reservation SaaS platformu için geliştirilmiş **All-in-One Rol Tabanlı** mobil uygulamadır.

## 🚀 Öne Çıkan Özellikler

- **Çift Platform (Android & iOS):** Tek Flutter kod tabanı ile modern Material 3 tasarımı.
- **Akıllı Çoklu İşletme (Multi-Tenant) UX:**
  - Kullanıcı e-posta/kullanıcı adı ve şifresiyle giriş yaptığında sistem tüm aktif işletmelerdeki hesaplarını tarar.
  - Tek bir işletmeye aitse **otomatik ve doğrudan giriş** yapılır.
  - Birden fazla işletmede hesabı varsa şık bir **İşletme Seçim Ekranı** (Tenant Selection) açılır; kullanıcı istediği işletmeyi seçerek devam eder.
  - Profil sayfasından istenildiği an tek dokunuşla **İşletme Değiştirilebilir** (Switch Tenant).
- **Rol Bazlı Dinamik Arayüz (Role Gate):**
  - **Müşteri Rolü (Customer):**
    - Ana Sayfa & Yaklaşan Randevu Kartı
    - 4 Adımlı İnteraktif Randevu Alma Sihirbazı (Hizmet Seçimi -> Uzman Seçimi -> Canlı Müsait Saat Seçimi -> Özet ve Not Ekleme)
    - Randevularım (Yaklaşanlar ve Geçmiş filtreleri, randevu iptali)
    - Müşteri Profil Yönetimi ve Bildirim Durumu
  - **İşletme / Personel Rolü (Provider / Admin):**
    - Günün Ajandası & Hızlı KPI Sayaçları (Toplam, Bekleyen, Geldi/Tamamlandı)
    - Hızlı Durum Güncelleme Butonları (Onayla, Geldi, Tamamlandı, İptal)
    - Takvim Görünümü (Yatay tarih şeridi ve gün seçimi)
    - Hızlı Randevu Ekleme (Walk-in kapı müşterisi kaydı)
    - Personel Çalışma Planı ve Profil Yönetimi

---

## 💻 Android Studio ile Çalıştırma

Projeyi Android Studio üzerinde açıp doğrudan Android Emülatör veya gerçek cihazda test etmek için:

1. **Android Studio'yu Açın:**
   - `File` -> `Open` seçin.
   - Klasör olarak `/opt/ki-ecosystem/apps/ki-reservation-mobile` seçin ve açın.
2. **Flutter ve Dart Eklentileri:**
   - Android Studio -> Plugins -> `Flutter` ve `Dart` eklentilerinin kurulu olduğundan emin olun.
3. **Emülatör Başlatma:**
   - `Device Manager` üzerinden bir Android Emülatör (örn. Pixel 8 / Android 14/15) başlatın.
4. **Bağlantı Ayarı (Localhost):**
   - Android emülatörler yerel geliştirme makinesinin localhost'una `http://10.0.2.2` adresi üzerinden erişir.
   - Giriş ekranının sağ üst köşesindeki ⚙️ **Ayarlar** simgesine dokunarak:
     - Emülatör için: `http://10.0.2.2` (veya sunucu IP'si)
     - Canlı sunucu için: `https://bookiapp.kibusiness.co` seçebilirsiniz.
5. **Çalıştırma:**
   - Sağ üstteki yeşil **Run ▶** butonuna basın veya terminalden `flutter run` komutunu verin.

---

## 🔑 Hızlı Test Kullanıcıları (Demo Girişler)

Giriş ekranında yer alan **⚡ Hızlı Test Girişleri** butonlarına basarak veya aşağıdaki hesaplarla giriş yapabilirsiniz:

| Rol | Kullanıcı Adı / E-posta | Şifre | İşletme (Tenant) | Açıklama |
| :--- | :--- | :--- | :--- | :--- |
| **Admin** | `administrator` | `administrator` | *(Boş bırakın)* | Çoklu işletme seçim ekranını tetikler (3 farklı salonda kayıtlıdır). |
| **Personel** | `janedoe` | `administrator` | `demo-guzellik` | Lumiere Güzellik Merkezi personel ajandası. |
| **Müşteri** | `miracmurat` | *(veya Kayıt Ol)* | `salonflora` | Salon Flora müşteri randevu arayüzü. |

---

## 🛠️ Klasör ve Mimari Yapısı

```
lib/
├── core/
│   ├── constants/api_constants.dart      # API uç noktaları ve zaman aşımı süreleri
│   ├── network/api_client.dart           # Dio HTTP istemcisi, Bearer Token ve X-Tenant interceptor
│   ├── storage/storage_service.dart      # Güvenli token ve kullanıcı saklama
│   └── theme/app_theme.dart              # Material 3 BooKi koyu/açık marka teması
├── data/
│   ├── models/                           # User, Tenant, Service, Provider, Appointment modelleri
│   └── repositories/                     # Auth, Booking ve Appointment API servisleri
├── providers/                            # Riverpod 3 (AuthNotifier, BookingWizardNotifier)
├── presentation/
│   ├── screens/
│   │   ├── auth/                         # Giriş, Çoklu İşletme Seçimi, Kayıt
│   │   ├── role_gate_screen.dart         # Role göre Müşteri veya Personel kabuğuna yönlendirme
│   │   ├── customer/                     # Müşteri ekranları (Ana Sayfa, Sihirbaz, Randevularım, Profil)
│   │   └── staff/                        # Personel ekranları (Ajanda, Takvim, Hızlı Ekle, Profil)
│   └── widgets/                          # Ortak kartlar, işletme rozeti, butonlar
```
└── main.dart                             # Uygulama başlangıcı ve yerelleştirme
```

---

## 🍏 iOS (.ipa) GitHub Actions ile Bulutta Derleme

iOS derlemeleri macOS ve Xcode gerektirdiğinden, projeye `.github/workflows/build-ios.yml` dosyası dahil edilmiştir.

### Nasıl Kullanılır?
1. Bu projeyi GitHub reponuza push edin:
   ```bash
   git remote add origin https://github.com/<kullanici-adiniz>/<repo-adiniz>.git
   git push -u origin main
   ```
2. GitHub reponuzda **Actions** sekmesine gidin.
3. Soldaki menüden **"Build iOS IPA"** workflow'unu seçin.
4. **Run workflow** butonuna tıklayın (veya `main` dalına push yaptığınızda otomatik başlar).
5. macOS runner (`macos-latest`) üzerinde Flutter ve Xcode otomatik çalışır, testleri koşar ve `.ipa` paketini hazırlar.
6. Derleme bittiğinde sayfanın en altındaki **Artifacts** bölümünden **`BooKi-iOS-IPA`** dosyasını tek tıkla bilgisayarınıza indirebilirsiniz.

