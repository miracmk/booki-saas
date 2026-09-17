<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>API Dokümantasyonu - <?= e($company_name) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: #f8f9fa; }
        .sidebar { background: #fff; height: 100vh; position: fixed; border-right: 1px solid #dee2e6; overflow-y: auto; padding-top: 1rem; }
        .sidebar .nav-link { color: #495057; font-weight: 500; }
        .sidebar .nav-link.active { color: #0d6efd; background: #e9ecef; border-radius: 4px; }
        .main-content { margin-left: 25%; padding: 2rem; background: #fff; min-height: 100vh; }
        @media (max-width: 768px) {
            .sidebar { position: relative; height: auto; border-right: none; border-bottom: 1px solid #dee2e6; }
            .main-content { margin-left: 0; }
        }
        pre { background: #212529; color: #f8f9fa; padding: 1rem; border-radius: .375rem; }
        h1, h2, h3 { margin-top: 2rem; margin-bottom: 1rem; }
        .endpoint { display: flex; align-items: center; margin-bottom: 1rem; }
        .endpoint-method { font-weight: bold; padding: 0.25rem 0.5rem; border-radius: 4px; margin-right: 0.75rem; color: #fff; }
        .endpoint-method.get { background-color: #0d6efd; }
        .endpoint-method.post { background-color: #198754; }
        .endpoint-path { font-family: monospace; font-size: 1.1rem; }
    </style>
</head>
<body data-bs-spy="scroll" data-bs-target="#docs-nav" data-bs-smooth-scroll="true" tabindex="0">

<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-3 sidebar d-md-block" id="docs-nav">
            <div class="px-3 mb-4">
                <h4 class="fw-bold text-primary"><i class="fas fa-book me-2"></i><?= e($company_name) ?> API</h4>
            </div>
            <ul class="nav flex-column ps-3 pe-3">
                <li class="nav-item"><a class="nav-link" href="#intro">Giriş & Kimlik Doğrulama</a></li>
                <li class="nav-item"><a class="nav-link" href="#endpoints">REST Uç Noktaları</a>
                    <ul class="nav flex-column ps-3 mt-1 mb-2">
                        <li><a class="nav-link text-muted small py-1" href="#ep-appointments">/api/v1/appointments</a></li>
                        <li><a class="nav-link text-muted small py-1" href="#ep-customers">/api/v1/customers</a></li>
                        <li><a class="nav-link text-muted small py-1" href="#ep-services">/api/v1/services</a></li>
                        <li><a class="nav-link text-muted small py-1" href="#ep-availabilities">/api/v1/availabilities</a></li>
                    </ul>
                </li>
                <li class="nav-item"><a class="nav-link" href="#webhooks">Webhooks</a></li>
                <li class="nav-item"><a class="nav-link" href="#mcp">KIRSV-MCP (Model Context Protocol)</a></li>
                <li class="nav-item"><a class="nav-link" href="#ai-assistant">AI Asistan Entegrasyonu</a></li>
            </ul>
        </nav>

        <main class="col-md-9 col-lg-9 main-content">
            <section id="intro">
                <h1>API Dokümantasyonu</h1>
                <p class="lead">Uygulamanızı <?= e($company_name) ?> ile sorunsuz bir şekilde entegre edin.</p>
                <p>Tüm istekler <code>application/json</code> formatında yapılmalı ve yanıtlar da JSON formatında dönecektir.</p>
                
                <h3>Kimlik Doğrulama</h3>
                <p>API'yi kullanabilmek için bir Bearer token'a ihtiyacınız vardır. Token'ınızı Header kısmına ekleyerek isteklerinizi yetkilendirebilirsiniz:</p>
                <pre><code>Authorization: Bearer SIZIN_API_TOKEN_BURAYA</code></pre>
            </section>

            <hr class="my-5">

            <section id="endpoints">
                <h2>REST API (v1) Uç Noktaları</h2>
                
                <div id="ep-appointments" class="mt-4">
                    <div class="endpoint">
                        <span class="endpoint-method get">GET</span>
                        <span class="endpoint-path">/api/v1/appointments</span>
                    </div>
                    <p>Mevcut randevuları listeler. Tarih aralığına göre filtreleme yapabilirsiniz.</p>
                    <pre><code>curl -X GET "https://api.domain.com/api/v1/appointments?start=2026-09-01&end=2026-09-30" \
     -H "Authorization: Bearer TOKEN"</code></pre>

                    <div class="endpoint mt-4">
                        <span class="endpoint-method post">POST</span>
                        <span class="endpoint-path">/api/v1/appointments</span>
                    </div>
                    <p>Yeni bir randevu oluşturur.</p>
                    <pre><code>{
  "customer_id": 12,
  "service_id": 3,
  "provider_id": 2,
  "start_datetime": "2026-09-20 10:00:00",
  "end_datetime": "2026-09-20 11:00:00",
  "notes": "İlk randevu"
}</code></pre>
                </div>

                <div id="ep-customers" class="mt-5">
                    <div class="endpoint">
                        <span class="endpoint-method get">GET</span>
                        <span class="endpoint-path">/api/v1/customers</span>
                    </div>
                    <p>Müşteri listesini getirir. E-posta veya telefon numarası ile arama yapabilirsiniz.</p>
                </div>

                <div id="ep-services" class="mt-5">
                    <div class="endpoint">
                        <span class="endpoint-method get">GET</span>
                        <span class="endpoint-path">/api/v1/services</span>
                    </div>
                    <p>Aktif hizmetlerin ve fiyat/süre bilgilerinin listesini getirir.</p>
                </div>

                <div id="ep-availabilities" class="mt-5">
                    <div class="endpoint">
                        <span class="endpoint-method get">GET</span>
                        <span class="endpoint-path">/api/v1/availabilities</span>
                    </div>
                    <p>Belirli bir tarih ve hizmet/personel kombinasyonu için müsait saat dilimlerini hesaplar.</p>
                    <pre><code>curl -X GET "https://api.domain.com/api/v1/availabilities?service_id=3&provider_id=2&date=2026-09-20"</code></pre>
                </div>
            </section>

            <hr class="my-5">

            <section id="webhooks">
                <h2>Webhooks (Olay Tetikleyicileri)</h2>
                <p>Randevu oluşturulduğunda, iptal edildiğinde veya müşteri güncellendiğinde sisteminizin anında haberdar olması için Webhook'ları kullanabilirsiniz.</p>
                <ul>
                    <li><code>appointment.created</code></li>
                    <li><code>appointment.updated</code></li>
                    <li><code>appointment.deleted</code></li>
                    <li><code>customer.created</code></li>
                </ul>
                <p>Webhook ayarlarını yönetici panelindeki <strong>Ayarlar > Webhooks</strong> bölümünden yapılandırabilirsiniz.</p>
            </section>

            <hr class="my-5">

            <section id="mcp">
                <h2>KIRSV-MCP (Model Context Protocol)</h2>
                <p>BooKi, yapay zeka ajanlarının platform verilerine doğrudan ve güvenli erişimini sağlayan yerleşik bir MCP sunucusu içerir. Bu sayede LLM tabanlı ajanlar, doğal dildeki komutları çalıştırarak API üzerinden işlem yapabilir.</p>
                <p>MCP Kurulumu için standart yönergeleri takip edebilir, <code>kirsv-mcp</code> CLI aracını kullanarak ajanlarınızı kolayca bağlayabilirsiniz.</p>
            </section>

            <hr class="my-5">

            <section id="ai-assistant">
                <h2>AI Asistan Entegrasyonu</h2>
                <p>Elite plan müşterileri için, BooKi'nin Çok Kanallı AI Asistanı (WhatsApp, Telegram, Instagram) işletmenizin takvimiyle %100 senkronize çalışır. Gelen mesajlara doğal dilde yanıt verir, müsaitlik durumunu kontrol eder ve randevu oluşturur.</p>
            </section>

            <footer class="mt-5 pt-4 border-top text-muted text-center text-md-start">
                &copy; <?= date('Y') ?> <?= e($company_name) ?>. Tüm Hakları Saklıdır.
            </footer>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
