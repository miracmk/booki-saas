# BooKi Yazılım Yetenekleri & Kod Tabanı Özellik Kataloğu

**Sistem:** BooKi Enterprise SaaS Appointment & Business Operating System
**Git Sürüm / Commit:** `128ec4a7c46789d51a76aa7f98d5c837d8644cb4`
**Son Tarama Tarihi:** 2026-09-26 10:19:14
**Kapsam:** 100 Controller, 61 Veri Modeli

---

### İzole Multi-Tenant SaaS Mimarisi
Her işletmeye özel dinamik veritabanı, subdomain ({subdomain}-bookiapp.kibusiness.co), özel domain desteği (custom_domain), master katalog ve lisans takibi.

*İlgili Çekirdek Bileşenler:* `App_Controller.php`, `Tenants_model.php`, `Superadmin_tenants.php`

### Gelişmiş Randevu & Müsaitlik Motoru
Personel takvimi, çalışma saatleri, molalar, servis süreleri, tampon süreler (buffer time), çoklu slot hesaplama ve çifte rezervasyon önleme.

*İlgili Çekirdek Bileşenler:* `Appointments.php`, `Appointments_model.php`, `Availability_model.php`, `Calendar.php`

### İstasyon, Koltuk & Kaynak Yönetimi
Uzman bağımsız veya uzmanla eşleşen istasyonlar (örn. Manikür Masası, Yıkama Koltuğu, Lazer Cihazı, Muayene Odası).

*İlgili Çekirdek Bileşenler:* `Stations.php`, `Stations_model.php`

### Paket & Üyelik Ayrımı (Session Tracking)
10 seanslık epilasyon/masaj paketleri ile aylık/yıllık üyelik planlarının tam ayrımı; seans düşümü, bonus seans, dondurma ve kullanım logları.

*İlgili Çekirdek Bileşenler:* `Customer_packages_model.php`, `Customer_memberships_model.php`, `Packages.php`, `Memberships.php`

### Adisyon, POS, Kasa & Masraf Yönetimi
Randevudan veya doğrudan açılan adisyonlar, parça ödemeler (nakit, kredi kartı, havale), kasa hareketleri, gider kayıtları ve günlük z-raporu.

*İlgili Çekirdek Bileşenler:* `Adisyons_model.php`, `Cash_registers_model.php`, `Expenses_model.php`, `Invoices_model.php`

### Personel Komisyon & Hakediş Motoru
Hizmet veya paket bazlı yüzdesel/sabit komisyon hesaplama, mesai primleri ve hakediş ödeme takibi.

*İlgili Çekirdek Bileşenler:* `Staff_commissions_model.php`, `Staff_payouts_model.php`

### Çok Kanallı AI Asistan (WhatsApp, Telegram, Instagram)
Baileys tabanlı yerel WhatsApp köprüsü, Telegram botu ve Instagram DM; Gemini 3.8 / LLM Gateway ile 7/24 konuşma, müsaitlik sorgulama ve randevu alma.

*İlgili Çekirdek Bileşenler:* `Ai_channel_responder.php`, `Ai_llm_gateway.php`, `Whatsapp.php`, `Telegram.php`, `Instagram.php`

### Yönetici Onay Mekanizması (Pending Changes)
AI'ın oluşturduğu veya değiştirdiği randevu ve müşteri verileri doğrudan veritabanına yazılmaz; yönetici onay kuyruğunda bekletilir.

*İlgili Çekirdek Bileşenler:* `Ai_agent_pending_changes_model.php`, `Ai_channel_handoffs_model.php`

### Model Context Protocol (MCP) Sunucusu
Harici AI modelleri ve IDE/Agent araçlarının BooKi ile güvenli konuşmasını sağlayan streamable HTTP MCP sunucusu (:8765/mcp).

*İlgili Çekirdek Bileşenler:* `deploy/mcp/reservation-mcp/server.js`, `Agent_v1.php`

### Lead CRM & Google Places Taraması
Bölge ve sektör bazlı potansiyel müşteri keşfi (Google Places API), arama ve ziyaret takibi, otomatik SMS/WhatsApp teklif gönderimi ve tek tıkla kiracı oluşturma.

*İlgili Çekirdek Bileşenler:* `Leads_model.php`, `Superadmin_tenants.php`, `Lead_activities_model.php`

### Pazarlama & İtibar Otomasyonu
Akıllı müşteri segmentasyonu, hedefli SMS/WhatsApp kampanyaları, randevu sonrası otomatik Google inceleme ve puanlama döngüsü.

*İlgili Çekirdek Bileşenler:* `Marketing_campaigns_model.php`, `Marketing_segments_model.php`, `Reviews_model.php`, `Communication_rules_model.php`

### KVKK / GDPR & PII Şifreleme Güvenliği
Müşteri telefon ve kişisel verilerinde AES-256-GCM şifreleme (`SFENC1`), HMAC-SHA256 arama hashleri, rıza kayıtları, veri silme/anonimleştirme ve detaylı audit log.

*İlgili Çekirdek Bileşenler:* `sf_pii_helper.php`, `Audit_logs_model.php`, `Consents_model.php`, `Data_requests_model.php`

### 9 Sektörel Dikey Şablon (Blueprints)
Güzellik, restoran, gym, klinik/diş, oto servis, etkinlik, butik otel, eğitim/kurs ve profesyonel danışmanlık sektörleri için tek tıkla hazır hizmet, istasyon ve iş akışı şablonları.

*İlgili Çekirdek Bileşenler:* `Industry_blueprints.php`, `Blueprints_model.php`

### Telefoni & Dış Sistem Entegrasyonları
Zadarma VoIP santral entegrasyonu, Netgsm SMS gateway, Google Takvim iki yönlü senkronizasyonu ve Google E-Tablolar veri aktarımı.

*İlgili Çekirdek Bileşenler:* `Zadarma.php`, `Google_sync.php`, `Google_sheet_syncs_model.php`

