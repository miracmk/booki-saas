# TokenSave Vault - Son Güncelleme Bilgisi

Proje (vault root): `/opt/ki-ecosystem/ki-reservation-src`
Vault dizini: `.tokensave/` (config `docs_dir: "tokensave-docs"`)
Bilgi dosyası oluşturulma zamanı (UTC): 2026-09-16 10:27:27

## Kayıtlı senkron zamanları (.tokensave/branch-meta.json)

- Branch: `main`
- `created_at`: 1788020492 (2026-08-29 ~09:41:32 UTC)
- `last_synced_at`: 1789046181 (2026-09-10 13:16:21 UTC) - **beş gün bayat, aşağıdaki `tokensave sync` ile tazelenecek**

## Önceki senkron anlık görüntüsü (tokensave status)

- sürüm: v7.10.0
- dosyalar: 1.339 (PHP 1.224, JavaScript 105, diğer 10)
- node: 6.387, edge: 4.757
- kaynak boyutu: 8.9 MB, DB: 15.7 MB

## Bu güncelleme (2026-09-16)

Payment katmanı sessiz-hata düzeltmeleri (bkz. `src/docs/SESSION_NOTES.md`):

- `Iyzico_gateway::charge()` sahte `succeeded` yerine artık açıkça `RuntimeException` fırlatıyor.
- `Iyzico_gateway::parse_webhook_event()` webhook'ta `intent_id` (conversationId) döndürüyor +
  `paymentStatus` anahtarını da kabul ediyor.
- `Payment_webhooks::handle_webhook()` başarılı ödemede randevuyu `set_payment()` ile mutabakatlaştırıyor.
- `Booking::register` / `Appointment_booking_service::create` depozito zorunluyken ödeme hatasında
  randevuyu silip açıkça hata döndürüyor (sessiz devam kaldırıldı).