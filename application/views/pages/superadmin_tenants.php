<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BooKi - Admin</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; background: #f5f6f8; margin: 0; color: #222; }
        header { background: #1b1f24; color: #fff; padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; }
        header h1 { font-size: 1.1rem; margin: 0; }
        header a { color: #ccc; text-decoration: none; font-size: .85rem; }
        main { padding: 1.5rem; max-width: 1200px; margin: 0 auto; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        button.primary { background: #1b1f24; color: #fff; border: none; border-radius: 6px; padding: .6rem 1rem; font-weight: 600; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
        th, td { text-align: left; padding: .7rem .9rem; border-bottom: 1px solid #eee; font-size: .88rem; }
        th { background: #fafafa; font-weight: 600; color: #555; }
        .badge { padding: .15rem .5rem; border-radius: 12px; font-size: .75rem; font-weight: 600; }
        .badge.active { background: #e3f7e9; color: #1e8a4c; }
        .badge.suspended { background: #fdeaea; color: #c0392b; }
        .badge.expired { background: #fff4e0; color: #b3720a; }
        .actions button { background: none; border: 1px solid #d7d9dd; border-radius: 6px; padding: .3rem .6rem; font-size: .78rem; cursor: pointer; margin-right: .3rem; }
        .actions button.danger { border-color: #f0b9b9; color: #c0392b; }
        .modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,.4); display: none; align-items: center; justify-content: center; z-index: 10; }
        .modal-backdrop.open { display: flex; }
        .modal { background: #fff; border-radius: 10px; padding: 1.5rem; width: 100%; max-width: 420px; }
        .modal h2 { margin: 0 0 1rem; font-size: 1.05rem; }
        .modal label { display: block; font-size: .82rem; font-weight: 600; margin: .7rem 0 .3rem; }
        .modal input { width: 100%; padding: .5rem .6rem; border: 1px solid #d7d9dd; border-radius: 6px; font-size: .9rem; }
        .modal .row { display: flex; gap: 1.5rem; margin-top: 1.2rem; }
        .modal .row button { flex: 1; padding: .6rem; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; }
        .modal .row button.cancel { background: #eee; color: #333; }
        .modal .row button.confirm { background: #1b1f24; color: #fff; }
        .msg { font-size: .82rem; color: #c0392b; margin-top: .5rem; display: none; }
        .success-box { background: #e3f7e9; border: 1px solid #b7e5c6; border-radius: 8px; padding: 1rem; font-size: .85rem; margin-bottom: 1rem; display: none; }
        .search-box { padding: .55rem .8rem; border: 1px solid #d7d9dd; border-radius: 6px; font-size: .88rem; width: 260px; }
        .pagination { display: flex; gap: .4rem; justify-content: center; margin-top: 1rem; }
        .pagination a { padding: .4rem .7rem; border: 1px solid #d7d9dd; border-radius: 6px; font-size: .82rem; text-decoration: none; color: #333; background: #fff; }
        .pagination a.active { background: #1b1f24; color: #fff; border-color: #1b1f24; }
    </style>
</head>
<body>
    <header>
        <h1>BooKi — SaaS Yönetimi</h1>
        <div>
            <span style="margin-right:1rem"><?= e(vars('superadmin_username')) ?></span>
            <a href="<?= site_url('superadmin_settings') ?>" style="margin-right:1rem;">Platform Ayarları</a>
            <a href="<?= site_url('superadmin_auth/logout') ?>">Çıkış</a>
        </div>
    </header>

    <main>
        <div id="success-box" class="success-box"></div>

        <div style="display:flex;gap:1.5rem;margin-bottom:2rem;">
            <div style="background:#fff;padding:1.5rem;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.06);flex:1;">
                <div style="font-size:.85rem;color:#666;text-transform:uppercase;margin-bottom:.8rem;font-weight:600;">Toplam Kiracı</div>
                <div style="font-size:1.8rem;font-weight:bold;"><?= vars('total_tenants') ?></div>
                <div style="font-size:.85rem;color:#888;margin-top:.4rem;"><span style="color:#1e8a4c;font-weight:600;"><?= vars('active_tenants') ?> Aktif</span> / <span style="color:#c0392b;font-weight:600;"><?= vars('total_tenants') - vars('active_tenants') ?> Askıda</span></div>
            </div>
            <div style="background:#fff;padding:1.5rem;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.06);flex:1;">
                <div style="font-size:.85rem;color:#666;text-transform:uppercase;margin-bottom:.8rem;font-weight:600;">Toplam Müşteri Portföyü</div>
                <div style="font-size:1.8rem;font-weight:bold;"><?= number_format(vars('total_customers')) ?></div>
            </div>
            <div style="background:#fff;padding:1.5rem;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.06);flex:1;">
                <div style="font-size:.85rem;color:#666;text-transform:uppercase;margin-bottom:.8rem;font-weight:600;">Randevular</div>
                <div style="font-size:1.8rem;font-weight:bold;"><?= number_format(vars('total_monthly_appointments')) ?> <span style="font-size:1.1rem;font-weight:normal;color:#888;">/ <?= number_format(vars('total_appointments')) ?></span></div>
                <div style="font-size:.85rem;color:#888;margin-top:.4rem;">Bu Ay / Toplam</div>
            </div>
            <div style="background:#fff;padding:1.5rem;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.06);flex:1;">
                <div style="font-size:.85rem;color:#666;text-transform:uppercase;margin-bottom:.8rem;font-weight:600;">Platform MRR</div>
                <div style="font-size:1.8rem;font-weight:bold;">₺<?= number_format(vars('total_mrr'), 2) ?></div>
            </div>
        </div>

        <div class="toolbar">
            <div><?= e(vars('total')) ?> kiracı<?= vars('search') ? ' ("' . e(vars('search')) . '" için)' : '' ?></div>
            <form method="get" style="display:flex;gap:.5rem;">
                <input type="text" name="q" class="search-box" placeholder="Subdomain, custom domain veya plan ara..." value="<?= e(vars('search')) ?>">
                <button type="submit" class="primary" style="background:#fff;color:#1b1f24;border:1px solid #d7d9dd;">Ara</button>
            </form>
            <button class="primary" onclick="document.getElementById('create-modal').classList.add('open')">+ Yeni Kiracı</button>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Subdomain</th>
                    <th>İşletme Türü</th>
                    <th>Plan & Döngü</th>
                    <th>Durum</th>
                    <th>Müşteriler</th>
                    <th>Randevular</th>
                    <th>Ciro / MRR</th>
                    <th>Oluşturma</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach (vars('tenants') as $tenant): ?>
                <?php
                $now = date('Y-m-d H:i:s');
                $expired = (!empty($tenant['license_expires_at']) && $tenant['license_expires_at'] < $now)
                    || (!empty($tenant['trial_ends_at']) && $tenant['trial_ends_at'] < $now);
                $badge_class = $expired ? 'expired' : $tenant['status'];
                $badge_label = $expired ? 'süresi doldu' : $tenant['status'];
                ?>
                <tr data-tenant-id="<?= e($tenant['id']) ?>" data-subdomain="<?= e($tenant['subdomain']) ?>">
                    <td>
                        <strong><?= e($tenant['subdomain']) ?></strong><br>
                        <span style="font-size:.75rem;color:#666;"><?= e($tenant['custom_domain'] ?? '—') ?></span>
                    </td>
                    <td><?= e($tenant['business_type'] ?? '—') ?></td>
                    <td>
                        <?= e($tenant['plan'] ?? 'Free') ?> <br>
                        <span style="font-size:.75rem;color:#666;"><?= e($tenant['billing_cycle'] == 'yearly' ? 'Yıllık' : 'Aylık') ?></span>
                    </td>
                    <td><span class="badge <?= e($badge_class) ?>"><?= e($badge_label) ?></span></td>
                    <td><?= number_format($tenant['customer_count'] ?? 0) ?></td>
                    <td><?= number_format($tenant['monthly_appointments'] ?? 0) ?> <span style="color:#888;font-size:.75rem;">/ <?= number_format($tenant['appointment_count'] ?? 0) ?></span></td>
                    <td>
                        ₺<?= number_format($tenant['total_revenue'] ?? 0, 2) ?> <br>
                        <span style="font-size:.75rem;color:#666;">MRR: ₺<?= number_format($tenant['mrr_amount'] ?? 0, 2) ?></span>
                    </td>
                    <td><?= date('d.m.Y', strtotime($tenant['created_at'])) ?></td>
                    <td class="actions">
                        <button onclick="openDetailsModal(<?= e($tenant['id']) ?>)">Detay</button>
                        <?php if ($tenant['status'] === 'active'): ?>
                            <button onclick="setStatus(<?= e($tenant['id']) ?>, 'suspended')">Askıya Al</button>
                        <?php else: ?>
                            <button onclick="setStatus(<?= e($tenant['id']) ?>, 'active')">Aktifleştir</button>
                        <?php endif; ?>
                        <button onclick="openPlanModal(<?= e($tenant['id']) ?>, '<?= e($tenant['plan'] ?? '') ?>', '<?= e($tenant['billing_cycle'] ?? 'monthly') ?>', '<?= e($tenant['mrr_amount'] ?? 0) ?>', '<?= e($tenant['trial_ends_at'] ?? '') ?>', '<?= e($tenant['license_expires_at'] ?? '') ?>')">Plan</button>
                        <button onclick="openAdminModal(<?= e($tenant['id']) ?>, '<?= e($tenant['subdomain']) ?>')">Admin</button>
                        <button class="danger" onclick="openDeleteModal(<?= e($tenant['id']) ?>, '<?= e($tenant['subdomain']) ?>')">Sil</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (vars('total_pages') > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= vars('total_pages'); $i++): ?>
                    <a href="?q=<?= urlencode(vars('search')) ?>&page=<?= $i ?>" class="<?= $i === vars('page') ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Create modal -->
    <div class="modal-backdrop" id="create-modal">
        <div class="modal" style="max-width:480px; max-height:90vh; overflow-y:auto;">
            <h2>Yeni Kiracı Oluştur</h2>
            <form id="create-form">
                <label>Subdomain *</label>
                <input type="text" id="c-subdomain" placeholder="orn: acme" required>
                <label>İşletme Türü</label>
                <select id="c-business-type">
                    <option value="">— Seçiniz —</option>
                    <option value="Güzellik Salonu">Güzellik Salonu</option>
                    <option value="Masaj & Spa">Masaj & Spa</option>
                    <option value="Klinik & Sağlık">Klinik & Sağlık</option>
                    <option value="Restoran">Restoran</option>
                    <option value="Spor & Fitness">Spor & Fitness</option>
                    <option value="Eğitim & Danışmanlık">Eğitim & Danışmanlık</option>
                    <option value="Diğer">Diğer</option>
                </select>
                <label>Plan</label>
                <select id="c-plan">
                    <option value="">— (Free varsayılan)</option>
                    <option value="Free">Free</option>
                    <option value="Basic">Basic</option>
                    <option value="Premium">Premium</option>
                    <option value="Elite">Elite</option>
                </select>
                <label>Deneme Süresi (gün, opsiyonel)</label>
                <input type="number" id="c-trial-days" placeholder="14">
                
                <h3 style="margin-top:1.5rem; font-size:.95rem;">Yönetici Bilgileri (Opsiyonel)</h3>
                <label>Ad Soyad</label>
                <input type="text" id="c-admin-name" placeholder="Yönetici Adı Soyadı">
                <label>E-posta</label>
                <input type="email" id="c-admin-email" placeholder="yonetici@acme.com">
                <label>Telefon</label>
                <input type="text" id="c-admin-phone" placeholder="05XX XXX XX XX">
                <label>Özel Şifre (boşsa rastgele)</label>
                <input type="text" id="c-admin-password" placeholder="En az 8 karakter">

                <div class="msg" id="create-msg"></div>
                <div class="row">
                    <button type="button" class="cancel" onclick="document.getElementById('create-modal').classList.remove('open')">Vazgeç</button>
                    <button type="submit" class="confirm">Oluştur</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Plan/license modal -->
    <div class="modal-backdrop" id="plan-modal">
        <div class="modal">
            <h2>Plan / Lisans Düzenle</h2>
            <form id="plan-form">
                <input type="hidden" id="p-tenant-id">
                <label>Plan</label>
                <select id="p-plan">
                    <option value="">— (Free varsayılan)</option>
                    <option value="Free">Free</option>
                    <option value="Basic">Basic</option>
                    <option value="Premium">Premium</option>
                    <option value="Elite">Elite</option>
                </select>
                <label>Döngü</label>
                <select id="p-billing-cycle">
                    <option value="monthly">Aylık</option>
                    <option value="yearly">Yıllık</option>
                </select>
                <label>MRR (Aylık Tekrarlayan Gelir)</label>
                <input type="number" step="0.01" id="p-mrr-amount" placeholder="0.00">
                <label>Deneme Bitişi (YYYY-MM-DD HH:MM:SS)</label>
                <input type="text" id="p-trial-ends-at">
                <label>Lisans Bitişi (YYYY-MM-DD HH:MM:SS)</label>
                <input type="text" id="p-license-expires-at">
                <div class="msg" id="plan-msg"></div>
                <div class="row">
                    <button type="button" class="cancel" onclick="document.getElementById('plan-modal').classList.remove('open')">Vazgeç</button>
                    <button type="submit" class="confirm">Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Details Modal -->
    <div class="modal-backdrop" id="details-modal">
        <div class="modal" style="max-width:500px;">
            <h2>Kiracı Detayları</h2>
            <div id="details-loading" style="padding:1rem;text-align:center;color:#666;">Yükleniyor...</div>
            <div id="details-content" style="display:none;">
                <div style="display:flex;gap:1rem;margin-bottom:1rem;">
                    <div style="flex:1;background:#f9f9f9;padding:.8rem;border-radius:6px;">
                        <div style="font-size:.75rem;color:#666;">Hizmetler</div>
                        <div style="font-size:1.2rem;font-weight:bold;" id="det-services"></div>
                    </div>
                    <div style="flex:1;background:#f9f9f9;padding:.8rem;border-radius:6px;">
                        <div style="font-size:.75rem;color:#666;">Personeller</div>
                        <div style="font-size:1.2rem;font-weight:bold;" id="det-providers"></div>
                    </div>
                </div>
                
                <h3 style="font-size:.9rem;margin-bottom:.5rem;">Randevu Durumları</h3>
                <div style="display:flex;gap:.5rem;margin-bottom:1rem;font-size:.85rem;">
                    <span style="color:#b3720a;">Beklemede: <strong id="det-st-pending"></strong></span> |
                    <span style="color:#1e8a4c;">Onaylı: <strong id="det-st-approved"></strong></span> |
                    <span style="color:#1e8a4c;">Tamamlanan: <strong id="det-st-completed"></strong></span> |
                    <span style="color:#c0392b;">İptal: <strong id="det-st-canceled"></strong></span>
                </div>
                
                <h3 style="font-size:.9rem;margin-bottom:.5rem;">İletişim</h3>
                <div style="font-size:.85rem;margin-bottom:1rem;line-height:1.4;">
                    <strong>Yönetici:</strong> <span id="det-contact-name"></span><br>
                    <strong>E-posta:</strong> <span id="det-contact-email"></span><br>
                    <strong>Telefon:</strong> <span id="det-contact-phone"></span>
                </div>
                
                <h3 style="font-size:.9rem;margin-bottom:.5rem;">Son 5 Randevu</h3>
                <ul id="det-last-appointments" style="font-size:.8rem;padding-left:1rem;color:#555;margin:0;"></ul>
            </div>
            <div class="row">
                <button type="button" class="cancel" onclick="document.getElementById('details-modal').classList.remove('open')">Kapat</button>
            </div>
        </div>
    </div>

    <!-- Admin account modal -->
    <div class="modal-backdrop" id="admin-modal">
        <div class="modal" style="max-width:480px;">
            <h2>Admin Hesabı — <span id="a-subdomain-label"></span></h2>
            <input type="hidden" id="a-tenant-id">
            <p class="hint" style="margin:0 0 .5rem;font-size:.82rem;color:#666;">
                E-posta: <strong id="a-email-label">—</strong>
            </p>

            <label>Kullanıcı Adı</label>
            <div style="display:flex;gap:.5rem;">
                <input type="text" id="a-username" style="flex:1;">
                <button type="button" onclick="saveAdminUsername()" style="white-space:nowrap;padding:0 .8rem;border-radius:6px;border:1px solid #d7d9dd;background:#fff;cursor:pointer;">Kaydet</button>
            </div>
            <div class="msg" id="a-username-msg"></div>

            <label>Yeni Şifre Belirle</label>
            <div style="display:flex;gap:.5rem;">
                <input type="text" id="a-password" placeholder="en az 8 karakter" style="flex:1;">
                <button type="button" onclick="saveAdminPassword()" style="white-space:nowrap;padding:0 .8rem;border-radius:6px;border:1px solid #d7d9dd;background:#fff;cursor:pointer;">Kaydet</button>
            </div>
            <div class="msg" id="a-password-msg"></div>

            <div class="row">
                <button type="button" class="cancel" onclick="sendAdminReset()" style="background:#eee;color:#333;">Şifre Sıfırlama E-postası Gönder</button>
                <button type="button" class="cancel" onclick="document.getElementById('admin-modal').classList.remove('open')">Kapat</button>
            </div>
            <div class="msg" id="a-reset-msg"></div>
        </div>
    </div>

    <!-- Delete modal -->
    <div class="modal-backdrop" id="delete-modal">
        <div class="modal">
            <h2>Kiracıyı Kalıcı Olarak Sil</h2>
            <p style="font-size:.85rem;color:#c0392b">Bu işlem GERİ ALINAMAZ - kiracının tüm veritabanı silinir. Onaylamak için subdomain'i yazın: <strong id="delete-subdomain-label"></strong></p>
            <form id="delete-form">
                <input type="hidden" id="d-tenant-id">
                <input type="text" id="d-confirm">
                <div class="msg" id="delete-msg"></div>
                <div class="row">
                    <button type="button" class="cancel" onclick="document.getElementById('delete-modal').classList.remove('open')">Vazgeç</button>
                    <button type="submit" class="confirm" style="background:#c0392b">Sil</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const csrfToken = '<?= e(vars('csrf_token')) ?>';

        function post(url, data) {
            const params = new URLSearchParams(data);
            params.set('csrf_token', csrfToken);
            return fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: params.toString() })
                .then((r) => r.json());
        }

        function showSuccess(html) {
            const box = document.getElementById('success-box');
            box.innerHTML = html;
            box.style.display = 'block';
        }

        document.getElementById('create-form').addEventListener('submit', function (event) {
            event.preventDefault();
            const msg = document.getElementById('create-msg');
            msg.style.display = 'none';

            post('<?= site_url('superadmin_tenants/store') ?>', {
                subdomain: document.getElementById('c-subdomain').value,
                business_type: document.getElementById('c-business-type').value,
                plan: document.getElementById('c-plan').value,
                trial_days: document.getElementById('c-trial-days').value,
                admin_name: document.getElementById('c-admin-name').value,
                admin_email: document.getElementById('c-admin-email').value,
                admin_phone: document.getElementById('c-admin-phone').value,
                admin_password: document.getElementById('c-admin-password').value,
            }).then((data) => {
                if (!data.success) {
                    msg.textContent = data.message || 'Hata oluştu.';
                    msg.style.display = 'block';
                    return;
                }

                showSuccess(
                    'Kiracı oluşturuldu: <strong>' + data.subdomain + '</strong><br>' +
                    'URL: <a href="' + data.login_url + '" target="_blank">' + data.login_url + '</a><br>' +
                    'Giriş: administrator / ' + data.admin_password,
                );
                window.location.reload();
            });
        });

        function setStatus(tenantId, status) {
            const verb = status === 'suspended' ? 'askıya almak' : 'aktifleştirmek';
            if (!confirm('Bu kiracıyı ' + verb + ' istediğinize emin misiniz?')) {
                return;
            }

            post('<?= site_url('superadmin_tenants/update_status') ?>', { tenant_id: tenantId, status: status })
                .then((data) => { if (data.success) window.location.reload(); else alert(data.message || 'Hata'); });
        }

        function openAdminModal(tenantId, subdomain) {
            document.getElementById('a-tenant-id').value = tenantId;
            document.getElementById('a-subdomain-label').textContent = subdomain;
            document.getElementById('a-email-label').textContent = '…';
            document.getElementById('a-username').value = '';
            document.getElementById('a-password').value = '';
            ['a-username-msg', 'a-password-msg', 'a-reset-msg'].forEach((id) => {
                const el = document.getElementById(id);
                el.style.display = 'none';
                el.className = 'msg';
            });
            document.getElementById('admin-modal').classList.add('open');

            fetch('<?= site_url('superadmin_tenants/get_admin_account') ?>?tenant_id=' + tenantId)
                .then((r) => r.json())
                .then((data) => {
                    if (data.success) {
                        document.getElementById('a-username').value = data.username;
                        document.getElementById('a-email-label').textContent = data.email || '—';
                    } else {
                        document.getElementById('a-email-label').textContent = data.message || 'Bulunamadı';
                    }
                });
        }
        
        function openDetailsModal(tenantId) {
            document.getElementById('details-loading').style.display = 'block';
            document.getElementById('details-content').style.display = 'none';
            document.getElementById('details-modal').classList.add('open');
            
            fetch('<?= site_url('superadmin_tenants/get_tenant_details') ?>?tenant_id=' + tenantId)
                .then((r) => r.json())
                .then((data) => {
                    if (data.success) {
                        const m = data.metrics;
                        document.getElementById('det-services').textContent = m.services_count;
                        document.getElementById('det-providers').textContent = m.providers_count;
                        
                        document.getElementById('det-st-pending').textContent = m.appointments_status.pending || 0;
                        document.getElementById('det-st-approved').textContent = m.appointments_status.approved || 0;
                        document.getElementById('det-st-completed').textContent = m.appointments_status.completed || 0;
                        document.getElementById('det-st-canceled').textContent = m.appointments_status.canceled || 0;
                        
                        document.getElementById('det-contact-name').textContent = m.contact.admin_name || '—';
                        document.getElementById('det-contact-email').textContent = m.contact.admin_email || '—';
                        document.getElementById('det-contact-phone').textContent = m.contact.admin_phone || '—';
                        
                        const list = document.getElementById('det-last-appointments');
                        list.innerHTML = '';
                        if (m.last_appointments.length === 0) {
                            list.innerHTML = '<li>Randevu yok</li>';
                        } else {
                            m.last_appointments.forEach(a => {
                                const li = document.createElement('li');
                                li.style.marginBottom = '0.3rem';
                                li.innerHTML = `<strong>${a.book_datetime}</strong>: ${a.service_name || 'Hizmet silinmiş'} <span class="badge ${a.status}">${a.status}</span>`;
                                list.appendChild(li);
                            });
                        }
                        
                        document.getElementById('details-loading').style.display = 'none';
                        document.getElementById('details-content').style.display = 'block';
                    } else {
                        document.getElementById('details-loading').textContent = data.message || 'Hata';
                    }
                });
        }

        function showFieldMsg(id, text, ok) {
            const el = document.getElementById(id);
            el.textContent = text;
            el.style.display = 'block';
            el.style.color = ok ? '#1e8a4c' : '#c0392b';
        }

        function saveAdminUsername() {
            post('<?= site_url('superadmin_tenants/update_admin_username') ?>', {
                tenant_id: document.getElementById('a-tenant-id').value,
                username: document.getElementById('a-username').value,
            }).then((data) => {
                if (data.success) showFieldMsg('a-username-msg', 'Kullanıcı adı güncellendi: ' + data.username, true);
                else showFieldMsg('a-username-msg', data.message || 'Hata', false);
            });
        }

        function saveAdminPassword() {
            const password = document.getElementById('a-password').value;
            post('<?= site_url('superadmin_tenants/set_admin_password') ?>', {
                tenant_id: document.getElementById('a-tenant-id').value,
                password: password,
            }).then((data) => {
                if (data.success) {
                    showFieldMsg('a-password-msg', 'Şifre güncellendi.', true);
                    document.getElementById('a-password').value = '';
                } else {
                    showFieldMsg('a-password-msg', data.message || 'Hata', false);
                }
            });
        }

        function sendAdminReset() {
            post('<?= site_url('superadmin_tenants/send_admin_password_reset') ?>', {
                tenant_id: document.getElementById('a-tenant-id').value,
            }).then((data) => {
                if (data.success) showFieldMsg('a-reset-msg', 'Sıfırlama e-postası gönderildi.', true);
                else showFieldMsg('a-reset-msg', data.message || 'Gönderilemedi (SMTP yapılandırılmamış olabilir).', false);
            });
        }

        function openPlanModal(tenantId, plan, billingCycle, mrrAmount, trialEndsAt, licenseExpiresAt) {
            document.getElementById('p-tenant-id').value = tenantId;
            document.getElementById('p-plan').value = plan;
            document.getElementById('p-billing-cycle').value = billingCycle;
            document.getElementById('p-mrr-amount').value = mrrAmount;
            document.getElementById('p-trial-ends-at').value = trialEndsAt;
            document.getElementById('p-license-expires-at').value = licenseExpiresAt;
            document.getElementById('plan-modal').classList.add('open');
        }

        document.getElementById('plan-form').addEventListener('submit', function (event) {
            event.preventDefault();
            post('<?= site_url('superadmin_tenants/update_plan') ?>', {
                tenant_id: document.getElementById('p-tenant-id').value,
                plan: document.getElementById('p-plan').value,
                billing_cycle: document.getElementById('p-billing-cycle').value,
                mrr_amount: document.getElementById('p-mrr-amount').value,
                trial_ends_at: document.getElementById('p-trial-ends-at').value,
                license_expires_at: document.getElementById('p-license-expires-at').value,
            }).then((data) => {
                if (data.success) window.location.reload();
                else { const m = document.getElementById('plan-msg'); m.textContent = data.message || 'Hata'; m.style.display = 'block'; }
            });
        });

        function openDeleteModal(tenantId, subdomain) {
            document.getElementById('d-tenant-id').value = tenantId;
            document.getElementById('d-confirm').value = '';
            document.getElementById('delete-subdomain-label').textContent = subdomain;
            document.getElementById('delete-modal').classList.add('open');
        }

        document.getElementById('delete-form').addEventListener('submit', function (event) {
            event.preventDefault();
            post('<?= site_url('superadmin_tenants/destroy') ?>', {
                tenant_id: document.getElementById('d-tenant-id').value,
                confirm_subdomain: document.getElementById('d-confirm').value,
            }).then((data) => {
                if (data.success) window.location.reload();
                else { const m = document.getElementById('delete-msg'); m.textContent = data.message || 'Hata'; m.style.display = 'block'; }
            });
        });
    </script>
</body>
</html>
