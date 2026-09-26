<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>BooKi - İşletme Kurulum Sihirbazı</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --border: #e2e8f0;
            --text: #0f172a;
            --text-muted: #64748b;
            --success: #10b981;
            --danger: #ef4444;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', -apple-system, sans-serif; }
        body { background: var(--bg); color: var(--text); min-height: 100vh; padding-bottom: 60px; }

        /* Header */
        header { background: #0f172a; color: white; padding: 16px 24px; position: sticky; top: 0; z-index: 30; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.07); }
        .header-content { max-width: 900px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .brand { display: flex; align-items: center; gap: 12px; }
        .logo-badge { background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; font-weight: 800; font-size: 18px; padding: 4px 10px; border-radius: 8px; }
        .brand-title { font-size: 16px; font-weight: 700; color: white; }
        .brand-sub { font-size: 11px; color: #94a3b8; }
        .token-badge { font-size: 11px; background: rgba(255,255,255,0.1); padding: 4px 8px; border-radius: 6px; color: #cbd5e1; }

        /* Container */
        .wizard-container { max-width: 900px; margin: 24px auto; padding: 0 16px; }

        /* Progress Bar */
        .progress-card { background: white; border: 1px solid var(--border); border-radius: 14px; padding: 16px 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .progress-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: var(--text-muted); }
        .progress-bar-wrap { height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden; }
        .progress-bar-fill { height: 100%; background: linear-gradient(90deg, #3b82f6, #2563eb); width: 10%; transition: width 0.3s ease; }

        /* Step Indicators */
        .steps-nav { display: flex; gap: 6px; overflow-x: auto; padding-bottom: 8px; margin-bottom: 16px; scrollbar-width: none; }
        .step-pill { flex: 1; min-width: 75px; text-align: center; padding: 8px 4px; border-radius: 8px; background: white; border: 1px solid var(--border); font-size: 11px; font-weight: 600; color: var(--text-muted); cursor: pointer; transition: all 0.15s; white-space: nowrap; }
        .step-pill.active { background: #2563eb; color: white; border-color: #2563eb; }
        .step-pill.done { background: #f0fdf4; color: #16a34a; border-color: #bbf7d0; }

        /* Step Card */
        .step-card { background: white; border: 1px solid var(--border); border-radius: 16px; padding: 28px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); display: none; }
        .step-card.active { display: block; animation: fadeIn 0.2s ease-out; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }

        .step-header { margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #f1f5f9; }
        .step-header h2 { font-size: 20px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px; }
        .step-header p { font-size: 13px; color: var(--text-muted); margin-top: 4px; }

        /* Form Controls */
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; margin-bottom: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { font-size: 12px; font-weight: 700; color: #334155; }
        .form-group input, .form-group select, .form-group textarea {
            padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; background: #fff; transition: all 0.15s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
        }

        /* Lists & Tables inside steps */
        .data-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px; }
        .data-item-card { background: #f8fafc; border: 1px solid var(--border); border-radius: 10px; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        .data-item-card .info strong { display: block; font-size: 14px; color: #0f172a; }
        .data-item-card .info span { font-size: 12px; color: var(--text-muted); }

        /* Action Buttons */
        .wizard-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 28px; padding-top: 20px; border-top: 1px solid #f1f5f9; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; border: 1px solid transparent; text-decoration: none; transition: all 0.15s; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-outline { background: white; border-color: var(--border); color: #334155; }
        .btn-outline:hover { background: #f8fafc; border-color: #cbd5e1; }
        .btn-success { background: var(--success); color: white; }
        .btn-success:hover { background: #059669; }
        .btn-danger-sm { background: #fee2e2; color: #dc2626; padding: 4px 8px; font-size: 12px; border-radius: 6px; }

        /* Blueprint pill recommendation */
        .blueprint-recommendation { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 14px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .blueprint-recommendation p { font-size: 13px; color: #1e40af; }

        /* Table view */
        .table-wrap { overflow-x: auto; border: 1px solid var(--border); border-radius: 10px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 10px 14px; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: #f8fafc; font-weight: 600; color: #475569; }

        /* Toast */
        .toast { position: fixed; bottom: 24px; right: 24px; background: #0f172a; color: white; padding: 12px 20px; border-radius: 10px; font-size: 14px; font-weight: 500; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.15); z-index: 100; display: none; }
    </style>
</head>
<body>

    <!-- Header -->
    <header>
        <div class="header-content">
            <div class="brand">
                <div class="logo-badge">BooKi</div>
                <div>
                    <div class="brand-title"><?= htmlspecialchars($tenant['company_name'] ?: $tenant['subdomain']) ?></div>
                    <div class="brand-sub">Kurulum & Onboarding Sihirbazı</div>
                </div>
            </div>
            <div class="token-badge">Adım <span id="currentStepText">1</span> / 10</div>
        </div>
    </header>

    <div class="wizard-container">

        <!-- Progress Bar -->
        <div class="progress-card">
            <div class="progress-top">
                <span>Kurulum İlerlemesi</span>
                <span id="progressPctText"><?= $progress_percent ?>%</span>
            </div>
            <div class="progress-bar-wrap">
                <div class="progress-bar-fill" id="progressBarFill" style="width: <?= max(10, $progress_percent) ?>%;"></div>
            </div>
        </div>

        <!-- Step Nav Pills -->
        <div class="steps-nav">
            <div class="step-pill active" data-step="1" onclick="goToStep(1)">1. İşletme</div>
            <div class="step-pill" data-step="2" onclick="goToStep(2)">2. Çalışma Saatleri</div>
            <div class="step-pill" data-step="3" onclick="goToStep(3)">3. Hizmetler</div>
            <div class="step-pill" data-step="4" onclick="goToStep(4)">4. Personel</div>
            <div class="step-pill" data-step="5" onclick="goToStep(5)">5. Kaynaklar</div>
            <div class="step-pill" data-step="6" onclick="goToStep(6)">6. Randevu</div>
            <div class="step-pill" data-step="7" onclick="goToStep(7)">7. Müşteriler</div>
            <div class="step-pill" data-step="8" onclick="goToStep(8)">8. Bildirimler</div>
            <div class="step-pill" data-step="9" onclick="goToStep(9)">9. Marka</div>
            <div class="step-pill" data-step="10" onclick="goToStep(10)">10. Tamamla</div>
        </div>

        <!-- STEP 1: Business Information -->
        <div class="step-card active" id="step-1">
            <div class="step-header">
                <h2>🏢 1. İşletme Temel Bilgileri</h2>
                <p>İşletmenizin müşterilere görünecek resmi adı, telefon ve adres detaylarını kontrol edin.</p>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label>İşletme Adı *</label>
                    <input type="text" id="biz_name" value="<?= htmlspecialchars($tenant['company_name'] ?: ($lead['name'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label>Sektör</label>
                    <input type="text" id="biz_sector" value="<?= htmlspecialchars($tenant['business_type'] ?: ($lead['sector'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label>Telefon Numarası *</label>
                    <input type="text" id="biz_phone" value="<?= htmlspecialchars($tenant['phone_number'] ?: ($lead['phone'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label>WhatsApp İletişim Numarası</label>
                    <input type="text" id="biz_whatsapp" value="<?= htmlspecialchars($lead['whatsapp'] ?? ($tenant['phone_number'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label>E-Posta Adresi</label>
                    <input type="email" id="biz_email" value="<?= htmlspecialchars($lead['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Web Sitesi</label>
                    <input type="text" id="biz_website" value="<?= htmlspecialchars($lead['website'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Instagram Hesabı</label>
                    <input type="text" id="biz_instagram" value="<?= htmlspecialchars($lead['instagram'] ?? '') ?>">
                </div>
                <div class="form-group full">
                    <label>Adres Bilgisi</label>
                    <textarea id="biz_address"><?= htmlspecialchars($tenant['address'] ?: ($lead['address'] ?? '')) ?></textarea>
                </div>
            </div>
            <div class="wizard-footer">
                <div></div>
                <button class="btn btn-primary" onclick="nextStep(1)">Kaydet ve Devam Et →</button>
            </div>
        </div>

        <!-- STEP 2: Business Hours -->
        <div class="step-card" id="step-2">
            <div class="step-header">
                <h2>🕒 2. Çalışma Saatleri & Açılış Takvimi</h2>
                <p>İşletmenizin randevu kabul edeceği haftalık çalışma saatlerini belirleyin.</p>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Gün</th>
                            <th>Durum</th>
                            <th>Açılış Saati</th>
                            <th>Kapanış Saati</th>
                        </tr>
                    </thead>
                    <tbody id="workingHoursBody">
                        <!-- Populated via JS -->
                    </tbody>
                </table>
            </div>
            <div class="wizard-footer">
                <button class="btn btn-outline" onclick="prevStep(2)">← Geri</button>
                <button class="btn btn-primary" onclick="nextStep(2)">Kaydet ve Devam Et →</button>
            </div>
        </div>

        <!-- STEP 3: Services -->
        <div class="step-card" id="step-3">
            <div class="step-header">
                <h2>✂️ 3. Hizmetler & Fiyat Listesi</h2>
                <p>Müşterilerinizin online randevu alabileceği hizmetleri ve sürelerini ekleyin.</p>
            </div>

            <div class="blueprint-recommendation">
                <div>
                    <strong>💡 Sektörünüze Özel Önerilen Hizmetler</strong>
                    <p>Tek tıklamayla sektörünüz için hazırlanmış popüler hizmetleri ekleyebilirsiniz.</p>
                </div>
                <button class="btn btn-outline" onclick="loadRecommendedServices()">Önerileri Ekle</button>
            </div>

            <div class="data-list" id="servicesList">
                <!-- Services rendered here -->
            </div>

            <div style="background:#f8fafc; border:1px dashed #cbd5e1; border-radius:12px; padding:16px; margin-top:16px;">
                <h4 style="font-size:13px; margin-bottom:12px;">+ Yeni Hizmet Ekle</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Hizmet Adı</label>
                        <input type="text" id="new_service_name" placeholder="Örn. Cilt Bakımı">
                    </div>
                    <div class="form-group">
                        <label>Süre (Dakika)</label>
                        <input type="number" id="new_service_duration" value="45" step="5">
                    </div>
                    <div class="form-group">
                        <label>Fiyat (₺)</label>
                        <input type="number" id="new_service_price" value="500">
                    </div>
                </div>
                <button class="btn btn-outline" onclick="addNewService()">Listeye Ekle</button>
            </div>

            <div class="wizard-footer">
                <button class="btn btn-outline" onclick="prevStep(3)">← Geri</button>
                <button class="btn btn-primary" onclick="nextStep(3)">Kaydet ve Devam Et →</button>
            </div>
        </div>

        <!-- STEP 4: Employees / Providers -->
        <div class="step-card" id="step-4">
            <div class="step-header">
                <h2>👥 4. Çalışanlar & Uzman Kadro</h2>
                <p>Randevuları karşılayacak personellerinizi ve uzmanlıklarını ekleyin.</p>
            </div>

            <div class="data-list" id="employeesList">
                <!-- Employees rendered here -->
            </div>

            <div style="background:#f8fafc; border:1px dashed #cbd5e1; border-radius:12px; padding:16px;">
                <h4 style="font-size:13px; margin-bottom:12px;">+ Yeni Çalışan Ekle</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Ad</label>
                        <input type="text" id="new_emp_first_name" placeholder="Örn. Elif">
                    </div>
                    <div class="form-group">
                        <label>Soyad</label>
                        <input type="text" id="new_emp_last_name" placeholder="Örn. Kaya">
                    </div>
                    <div class="form-group">
                        <label>Telefon</label>
                        <input type="text" id="new_emp_phone" placeholder="+90 5XX XXX XX XX">
                    </div>
                </div>
                <button class="btn btn-outline" onclick="addNewEmployee()">Çalışanı Ekle</button>
            </div>

            <div class="wizard-footer">
                <button class="btn btn-outline" onclick="prevStep(4)">← Geri</button>
                <button class="btn btn-primary" onclick="nextStep(4)">Kaydet ve Devam Et →</button>
            </div>
        </div>

        <!-- STEP 5: Rooms / Stations / Cabins / Resources -->
        <div class="step-card" id="step-5">
            <div class="step-header">
                <h2>🚪 5. Odalar, İstasyonlar ve Kaynaklar</h2>
                <p>İşletmenizin fiziksel alanlarını ekleyin (Oda, Koltuk, Masaj Kabini, Pilates Reformer, Masa vb.).</p>
            </div>

            <div class="data-list" id="resourcesList">
                <!-- Resources rendered here -->
            </div>

            <div style="background:#f8fafc; border:1px dashed #cbd5e1; border-radius:12px; padding:16px;">
                <h4 style="font-size:13px; margin-bottom:12px;">+ Yeni Kaynak / Alan Ekle</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Kaynak Adı</label>
                        <input type="text" id="new_res_name" placeholder="Örn. Kabin 1 / Masa 4">
                    </div>
                    <div class="form-group">
                        <label>Kaynak Tipi</label>
                        <select id="new_res_type">
                            <option value="Room">Oda (Room)</option>
                            <option value="Station">İstasyon / Masa (Station)</option>
                            <option value="Cabin">Kabin (Cabin)</option>
                            <option value="Table">Restoran Masası (Table)</option>
                            <option value="Chair">Koltuk / Berber Koltuğu (Chair)</option>
                            <option value="Equipment">Özel Ekipman / Cihaz</option>
                            <option value="Other">Diğer</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Kapasite (Eşzamanlı Kişi)</label>
                        <input type="number" id="new_res_capacity" value="1" min="1">
                    </div>
                </div>
                <button class="btn btn-outline" onclick="addNewResource()">Kaynağı Ekle</button>
            </div>

            <div class="wizard-footer">
                <button class="btn btn-outline" onclick="prevStep(5)">← Geri</button>
                <button class="btn btn-primary" onclick="nextStep(5)">Kaydet ve Devam Et →</button>
            </div>
        </div>

        <!-- STEP 6: Appointment Settings -->
        <div class="step-card" id="step-6">
            <div class="step-header">
                <h2>⚙️ 6. Randevu Kuralları & Aralıkları</h2>
                <p>Randevu takviminizin slot aralıklarını ve rezervasyon kurallarını belirleyin.</p>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Randevu Slot Aralığı</label>
                    <select id="slot_interval">
                        <option value="15">15 Dakika</option>
                        <option value="30" selected>30 Dakika (Standart)</option>
                        <option value="45">45 Dakika</option>
                        <option value="60">60 Dakika</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>En Erken Randevu Alma Süresi</label>
                    <select id="min_advance_hours">
                        <option value="1">1 Saat Önceden</option>
                        <option value="2" selected>2 Saat Önceden</option>
                        <option value="6">6 Saat Önceden</option>
                        <option value="24">1 Gün Önceden</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>En Geç Kaç Gün Sonrasına Randevu Alınabilir?</label>
                    <input type="number" id="max_advance_days" value="30">
                </div>
                <div class="form-group">
                    <label>Otomatik Randevu Onayı</label>
                    <select id="auto_confirm_appointments">
                        <option value="1" selected>Evet - Randevular doğrudan onaylansın</option>
                        <option value="0">Hayır - Biz manuel onaylayalım</option>
                    </select>
                </div>
            </div>

            <div class="wizard-footer">
                <button class="btn btn-outline" onclick="prevStep(6)">← Geri</button>
                <button class="btn btn-primary" onclick="nextStep(6)">Kaydet ve Devam Et →</button>
            </div>
        </div>

        <!-- STEP 7: Customers / Import -->
        <div class="step-card" id="step-7">
            <div class="step-header">
                <h2>👤 7. Mevcut Müşteri Verilerini Aktarma</h2>
                <p>Eski sisteminizden veya Excel listenizden müşterilerinizi tek seferde sisteme aktarın.</p>
            </div>

            <div style="background:#f8fafc; border:1px solid var(--border); border-radius:12px; padding:20px; margin-bottom:20px;">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                    <div>
                        <h4 style="font-size:14px; margin-bottom:4px;">Excel / CSV Dosyası Yükleyin</h4>
                        <p style="font-size:12px; color:var(--text-muted);">Müşteri listenizi CSV formatında yükleyebilirsiniz.</p>
                    </div>
                    <div style="display:flex; gap:8px;">
                        <a href="<?= site_url('customer_onboarding/download_template/customers') ?>" class="btn btn-outline" style="font-size:12px;">📥 Şablon İndir</a>
                        <button class="btn btn-primary" style="font-size:12px;" onclick="document.getElementById('custCsvInput').click()">Dosya Seç</button>
                        <input type="file" id="custCsvInput" accept=".csv" style="display:none" onchange="uploadCustomerCsv(event)">
                    </div>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Ad Soyad</th>
                            <th>Telefon</th>
                            <th>E-Posta</th>
                            <th>Notlar</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody id="customersTableBody">
                        <!-- Rendered rows -->
                    </tbody>
                </table>
            </div>

            <div style="display:flex; gap:8px;">
                <input type="text" id="manual_cust_name" placeholder="Ad Soyad" style="flex:1; padding:8px 12px; border:1px solid var(--border); border-radius:6px; font-size:13px;">
                <input type="text" id="manual_cust_phone" placeholder="Telefon" style="flex:1; padding:8px 12px; border:1px solid var(--border); border-radius:6px; font-size:13px;">
                <button class="btn btn-outline" onclick="addManualCustomer()">+ Manuel Müşteri Ekle</button>
            </div>

            <div class="wizard-footer">
                <button class="btn btn-outline" onclick="prevStep(7)">← Geri</button>
                <button class="btn btn-primary" onclick="nextStep(7)">Kaydet ve Devam Et →</button>
            </div>
        </div>

        <!-- STEP 8: Notifications & WhatsApp -->
        <div class="step-card" id="step-8">
            <div class="step-header">
                <h2>💬 8. Otomatik WhatsApp & Bildirim Ayarları</h2>
                <p>Randevu hatırlatmaları ve teyit mesajlarının gönderim kurallarını yapılandırın.</p>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Otomatik WhatsApp Teyit Mesajı</label>
                    <select id="wa_confirm_enabled">
                        <option value="1" selected>Aktif (Randevu anında otomatik WhatsApp gitsin)</option>
                        <option value="0">Pasif</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Randevu Öncesi Hatırlatma Zamanı</label>
                    <select id="wa_reminder_hours">
                        <option value="2" selected>Randevudan 2 saat önce</option>
                        <option value="4">Randevudan 4 saat önce</option>
                        <option value="24">Randevudan 1 gün önce (Sabah 09:00)</option>
                    </select>
                </div>
                <div class="form-group full">
                    <label>WhatsApp Teyit Mesajı Şablonu</label>
                    <textarea id="wa_template_text" rows="3">Merhaba {MUSTERI_ADI}, {ISLETME_ADI} üzerinden {TARIH_SAAT} tarihindeki {HIZMET_ADI} randevunuz başarıyla oluşturulmuştur. Randevunuza vaktinde gelmenizi rica ederiz.</textarea>
                </div>
            </div>

            <div class="wizard-footer">
                <button class="btn btn-outline" onclick="prevStep(8)">← Geri</button>
                <button class="btn btn-primary" onclick="nextStep(8)">Kaydet ve Devam Et →</button>
            </div>
        </div>

        <!-- STEP 9: Branding -->
        <div class="step-card" id="step-9">
            <div class="step-header">
                <h2>🎨 9. Renk & Görsel Tercihleri</h2>
                <p>Müşterilerinizin göreceği randevu sayfasının renk ve marka temasını seçin.</p>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Ana Tema Rengi</label>
                    <input type="color" id="brand_color" value="#2563eb" style="height:44px; padding:4px; cursor:pointer;">
                </div>
                <div class="form-group">
                    <label>Slogan / Karşılama Mesajı</label>
                    <input type="text" id="brand_slogan" value="Online Randevu ve Rezervasyon Merkezi" placeholder="Örn. Güzellikte Güvenilir Adresiniz">
                </div>
            </div>

            <div class="wizard-footer">
                <button class="btn btn-outline" onclick="prevStep(9)">← Geri</button>
                <button class="btn btn-primary" onclick="nextStep(9)">Kaydet ve Devam Et →</button>
            </div>
        </div>

        <!-- STEP 10: Review & Complete -->
        <div class="step-card" id="step-10">
            <div class="step-header">
                <h2>✅ 10. Özet & Kurulumu Tamamlama</h2>
                <p>Girilen tüm bilgileri gözden geçirin ve BooKi platformunuzu anında canlıya alın.</p>
            </div>

            <div style="background:#f8fafc; border:1px solid var(--border); border-radius:14px; padding:20px; margin-bottom:24px;">
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px;">
                    <div>
                        <div style="font-size:11px; color:var(--text-muted); font-weight:600;">İŞLETME</div>
                        <div style="font-size:15px; font-weight:700;" id="review_biz_name">-</div>
                    </div>
                    <div>
                        <div style="font-size:11px; color:var(--text-muted); font-weight:600;">HİZMET SAYISI</div>
                        <div style="font-size:15px; font-weight:700;" id="review_srv_count">0 Hizmet</div>
                    </div>
                    <div>
                        <div style="font-size:11px; color:var(--text-muted); font-weight:600;">PERSONEL SAYISI</div>
                        <div style="font-size:15px; font-weight:700;" id="review_emp_count">0 Personel</div>
                    </div>
                    <div>
                        <div style="font-size:11px; color:var(--text-muted); font-weight:600;">KAYNAK / ALAN</div>
                        <div style="font-size:15px; font-weight:700;" id="review_res_count">0 Kaynak</div>
                    </div>
                    <div>
                        <div style="font-size:11px; color:var(--text-muted); font-weight:600;">MÜŞTERİ PORTFÖYÜ</div>
                        <div style="font-size:15px; font-weight:700;" id="review_cust_count">0 Müşteri</div>
                    </div>
                </div>
            </div>

            <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:12px; padding:16px; margin-bottom:24px;">
                <div style="font-size:14px; font-weight:700; color:#1e40af; margin-bottom:6px;">🚀 Hazırsınız!</div>
                <p style="font-size:13px; color:#1e3a8a; line-height:1.5;">
                    "Kurulumu Tamamla" butonuna bastığınızda işletme profiliniz ve randevu altyapınız anında aktif hale gelecektir.
                </p>
            </div>

            <div class="wizard-footer">
                <button class="btn btn-outline" onclick="prevStep(10)">← Geri</button>
                <button class="btn btn-success" style="padding:12px 28px; font-size:15px;" onclick="completeOnboarding()">🎉 Kurulumu Tamamla ve Başlat</button>
            </div>
        </div>

    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast">Adım kaydedildi</div>

    <script>
        const TOKEN = "<?= htmlspecialchars($token ?? ($session['token'] ?? '')) ?>";
        let currentStep = <?= (int) ($current_step ?? 1) ?>;
        
        // Data stores
        let servicesData = [];
        let employeesData = [];
        let resourcesData = [];
        let customersData = [];

        const daysOfWeek = ['Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];

        // Init Working Hours
        function initWorkingHours() {
            const tbody = document.getElementById('workingHoursBody');
            tbody.innerHTML = '';
            daysOfWeek.forEach((day, idx) => {
                const isWeekend = (idx === 6); // Sunday
                tbody.innerHTML += `
                    <tr>
                        <td><strong>${day}</strong></td>
                        <td>
                            <label style="font-size:12px; display:inline-flex; align-items:center; gap:4px; cursor:pointer;">
                                <input type="checkbox" id="wh_open_${idx}" ${!isWeekend ? 'checked' : ''} onchange="toggleDayRow(${idx})"> Açık
                            </label>
                        </td>
                        <td><input type="time" id="wh_start_${idx}" value="09:00" style="padding:4px 8px; border:1px solid #e2e8f0; border-radius:6px; font-size:13px;" ${isWeekend ? 'disabled' : ''}></td>
                        <td><input type="time" id="wh_end_${idx}" value="19:00" style="padding:4px 8px; border:1px solid #e2e8f0; border-radius:6px; font-size:13px;" ${isWeekend ? 'disabled' : ''}></td>
                    </tr>
                `;
            });
        }

        function toggleDayRow(idx) {
            const open = document.getElementById(`wh_open_${idx}`).checked;
            document.getElementById(`wh_start_${idx}`).disabled = !open;
            document.getElementById(`wh_end_${idx}`).disabled = !open;
        }

        // Navigation
        function goToStep(step) {
            document.querySelectorAll('.step-card').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.step-pill').forEach(p => p.classList.remove('active'));

            const targetCard = document.getElementById(`step-${step}`);
            if (targetCard) targetCard.classList.add('active');

            const targetPill = document.querySelector(`.step-pill[data-step="${step}"]`);
            if (targetPill) targetPill.classList.add('active');

            currentStep = step;
            document.getElementById('currentStepText').textContent = step;

            if (step === 10) {
                updateReviewSummary();
            }

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function nextStep(step) {
            saveStepData(step, () => {
                const next = Math.min(10, step + 1);
                const pill = document.querySelector(`.step-pill[data-step="${step}"]`);
                if (pill) pill.classList.add('done');
                goToStep(next);
            });
        }

        function prevStep(step) {
            const prev = Math.max(1, step - 1);
            goToStep(prev);
        }

        // Save Step AJAX
        function saveStepData(step, callback) {
            let payload = {};

            if (step === 1) {
                payload = {
                    company_name: document.getElementById('biz_name').value,
                    sector: document.getElementById('biz_sector').value,
                    phone: document.getElementById('biz_phone').value,
                    whatsapp: document.getElementById('biz_whatsapp').value,
                    email: document.getElementById('biz_email').value,
                    website: document.getElementById('biz_website').value,
                    instagram: document.getElementById('biz_instagram').value,
                    address: document.getElementById('biz_address').value,
                };
            } else if (step === 2) {
                const hours = {};
                daysOfWeek.forEach((day, idx) => {
                    hours[day] = {
                        open: document.getElementById(`wh_open_${idx}`).checked,
                        start: document.getElementById(`wh_start_${idx}`).value,
                        end: document.getElementById(`wh_end_${idx}`).value,
                    };
                });
                payload = { business_hours: hours };
            } else if (step === 3) {
                payload = { services: servicesData };
            } else if (step === 4) {
                payload = { employees: employeesData };
            } else if (step === 5) {
                payload = { resources: resourcesData };
            } else if (step === 6) {
                payload = {
                    slot_interval: document.getElementById('slot_interval').value,
                    min_advance_hours: document.getElementById('min_advance_hours').value,
                    max_advance_days: document.getElementById('max_advance_days').value,
                    auto_confirm: document.getElementById('auto_confirm_appointments').value,
                };
            } else if (step === 7) {
                payload = { customers: customersData };
            } else if (step === 8) {
                payload = {
                    wa_confirm_enabled: document.getElementById('wa_confirm_enabled').value,
                    wa_reminder_hours: document.getElementById('wa_reminder_hours').value,
                    wa_template_text: document.getElementById('wa_template_text').value,
                };
            } else if (step === 9) {
                payload = {
                    brand_color: document.getElementById('brand_color').value,
                    brand_slogan: document.getElementById('brand_slogan').value,
                };
            }

            const formData = new FormData();
            formData.append('token', TOKEN);
            formData.append('step', step);
            formData.append('data', JSON.stringify(payload));

            fetch('<?= site_url("customer_onboarding/save_step") ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.progress_percent) {
                    document.getElementById('progressPctText').textContent = data.progress_percent + '%';
                    document.getElementById('progressBarFill').style.width = data.progress_percent + '%';
                }
                showToast('Değişiklikler kaydedildi');
                if (callback) callback();
            })
            .catch(err => {
                showToast('Hata: Kaydedilemedi');
                if (callback) callback();
            });
        }

        // Services
        function renderServices() {
            const list = document.getElementById('servicesList');
            if (servicesData.length === 0) {
                list.innerHTML = '<div style="color:#94a3b8; font-size:13px; font-style:italic;">Henüz hizmet eklenmedi.</div>';
                return;
            }
            list.innerHTML = servicesData.map((s, idx) => `
                <div class="data-item-card">
                    <div class="info">
                        <strong>${s.name}</strong>
                        <span>⏱️ ${s.duration} dk • ₺${s.price}</span>
                    </div>
                    <button class="btn btn-danger-sm" onclick="removeService(${idx})">Sil</button>
                </div>
            `).join('');
        }

        function addNewService() {
            const name = document.getElementById('new_service_name').value.trim();
            const duration = parseInt(document.getElementById('new_service_duration').value) || 45;
            const price = parseFloat(document.getElementById('new_service_price').value) || 0;

            if (!name) return alert('Lütfen hizmet adı girin');

            servicesData.push({ name, duration, price });
            document.getElementById('new_service_name').value = '';
            renderServices();
        }

        function removeService(idx) {
            servicesData.splice(idx, 1);
            renderServices();
        }

        function loadRecommendedServices() {
            servicesData = [
                { name: 'Klasik Cilt Bakımı', duration: 60, price: 750 },
                { name: 'Protez Tırnak & Nail Art', duration: 90, price: 650 },
                { name: 'Kalıcı Oje', duration: 45, price: 350 },
                { name: 'Kaş Dizaynı & Kirpik Lifting', duration: 45, price: 400 },
            ];
            renderServices();
            showToast('Önerilen hizmetler eklendi');
        }

        // Employees
        function renderEmployees() {
            const list = document.getElementById('employeesList');
            if (employeesData.length === 0) {
                list.innerHTML = '<div style="color:#94a3b8; font-size:13px; font-style:italic;">Henüz personel eklenmedi.</div>';
                return;
            }
            list.innerHTML = employeesData.map((e, idx) => `
                <div class="data-item-card">
                    <div class="info">
                        <strong>${e.first_name} ${e.last_name || ''}</strong>
                        <span>📞 ${e.phone || 'Telefon belirtilmedi'}</span>
                    </div>
                    <button class="btn btn-danger-sm" onclick="removeEmployee(${idx})">Sil</button>
                </div>
            `).join('');
        }

        function addNewEmployee() {
            const first_name = document.getElementById('new_emp_first_name').value.trim();
            const last_name = document.getElementById('new_emp_last_name').value.trim();
            const phone = document.getElementById('new_emp_phone').value.trim();

            if (!first_name) return alert('Lütfen personel adı girin');

            employeesData.push({ first_name, last_name, phone });
            document.getElementById('new_emp_first_name').value = '';
            document.getElementById('new_emp_last_name').value = '';
            document.getElementById('new_emp_phone').value = '';
            renderEmployees();
        }

        function removeEmployee(idx) {
            employeesData.splice(idx, 1);
            renderEmployees();
        }

        // Resources
        function renderResources() {
            const list = document.getElementById('resourcesList');
            if (resourcesData.length === 0) {
                list.innerHTML = '<div style="color:#94a3b8; font-size:13px; font-style:italic;">Henüz kaynak eklenmedi.</div>';
                return;
            }
            list.innerHTML = resourcesData.map((r, idx) => `
                <div class="data-item-card">
                    <div class="info">
                        <strong>${r.name}</strong>
                        <span>Tür: ${r.type} • Kapasite: ${r.capacity}</span>
                    </div>
                    <button class="btn btn-danger-sm" onclick="removeResource(${idx})">Sil</button>
                </div>
            `).join('');
        }

        function addNewResource() {
            const name = document.getElementById('new_res_name').value.trim();
            const type = document.getElementById('new_res_type').value;
            const capacity = parseInt(document.getElementById('new_res_capacity').value) || 1;

            if (!name) return alert('Lütfen kaynak adı girin');

            resourcesData.push({ name, type, capacity });
            document.getElementById('new_res_name').value = '';
            renderResources();
        }

        function removeResource(idx) {
            resourcesData.splice(idx, 1);
            renderResources();
        }

        // Customers
        function renderCustomers() {
            const tbody = document.getElementById('customersTableBody');
            if (customersData.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; color:#94a3b8;">Henüz müşteri aktarılmadı.</td></tr>';
                return;
            }
            tbody.innerHTML = customersData.map((c, idx) => `
                <tr>
                    <td><strong>${c.first_name} ${c.last_name || ''}</strong></td>
                    <td>${c.phone || '-'}</td>
                    <td>${c.email || '-'}</td>
                    <td>${c.notes || '-'}</td>
                    <td><button class="btn btn-danger-sm" onclick="removeCustomer(${idx})">Sil</button></td>
                </tr>
            `).join('');
        }

        function addManualCustomer() {
            const name = document.getElementById('manual_cust_name').value.trim();
            const phone = document.getElementById('manual_cust_phone').value.trim();
            if (!name) return alert('Lütfen müşteri adı girin');

            const parts = name.split(' ');
            const first_name = parts[0];
            const last_name = parts.slice(1).join(' ');

            customersData.push({ first_name, last_name, phone });
            document.getElementById('manual_cust_name').value = '';
            document.getElementById('manual_cust_phone').value = '';
            renderCustomers();
        }

        function removeCustomer(idx) {
            customersData.splice(idx, 1);
            renderCustomers();
        }

        function uploadCustomerCsv(event) {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('file', file);

            fetch('<?= site_url("customer_onboarding/parse_import_file") ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.rows && data.rows.length > 0) {
                    data.rows.forEach(r => {
                        const fullName = r['Ad'] || r['Ad Soyad'] || r['İsim'] || '';
                        const phone = r['Telefon'] || r['Phone'] || '';
                        const email = r['E-Posta'] || r['Email'] || '';
                        const notes = r['Notlar'] || '';

                        const parts = fullName.split(' ');
                        customersData.push({
                            first_name: parts[0] || 'Müşteri',
                            last_name: parts.slice(1).join(' '),
                            phone, email, notes
                        });
                    });
                    renderCustomers();
                    showToast(`${data.rows.length} müşteri listeye aktarıldı!`);
                } else {
                    alert('Dosyada geçerli satır bulunamadı.');
                }
            })
            .catch(err => alert('CSV işlenirken hata oluştu: ' + err.message));
        }

        // Review Summary
        function updateReviewSummary() {
            document.getElementById('review_biz_name').textContent = document.getElementById('biz_name').value || 'Belirtilmedi';
            document.getElementById('review_srv_count').textContent = servicesData.length + ' Hizmet';
            document.getElementById('review_emp_count').textContent = employeesData.length + ' Personel';
            document.getElementById('review_res_count').textContent = resourcesData.length + ' Kaynak';
            document.getElementById('review_cust_count').textContent = customersData.length + ' Müşteri';
        }

        // Final Completion
        function completeOnboarding() {
            if (!confirm('Kurulum adımlarını tamamlayıp sisteminizi aktif hale getirmek istediğinize emin misiniz?')) {
                return;
            }

            const formData = new FormData();
            formData.append('token', TOKEN);

            fetch('<?= site_url("customer_onboarding/complete") ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Hata: ' + (data.error || 'Tamamlanamadı'));
                }
            })
            .catch(err => alert('Sunucu bağlantı hatası: ' + err.message));
        }

        function showToast(msg) {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.style.display = 'block';
            setTimeout(() => { t.style.display = 'none'; }, 2500);
        }

        // Init
        initWorkingHours();
        renderServices();
        renderEmployees();
        renderResources();
        renderCustomers();
        goToStep(currentStep);
    </script>
</body>
</html>
