<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>
<style>
    .station-checkbox-label {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        margin: 3px;
        border-radius: 20px;
        font-size: 0.8rem;
        cursor: pointer;
        border: 1px solid #e2e8f0;
        background: #fff;
        transition: all 0.2s ease;
        user-select: none;
    }
    .station-checkbox:checked + .station-checkbox-label {
        color: #fff;
        border-color: transparent;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }
    .staff-card {
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .staff-card:hover {
        box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    }
    .group-header {
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        font-weight: 700;
    }
</style>

<div class="container-fluid backend-page px-4 py-4 flex-grow-1">
        <!-- Top Bar -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="<?= site_url('restaurant') ?>">Restoran</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Personel & İstasyon Dağılımı</li>
                    </ol>
                </nav>
                <h1 class="h3 fw-bold text-dark mb-0">
                    <i class="fas fa-users-cog text-primary me-2"></i>Mutfak, Bar ve Salon İstasyon Yönetimi
                </h1>
                <p class="text-muted small mb-0">
                    Turizm Ansiklopedisi ve Otelcim standartlarında; mutfak tugayı, bar hiyerarşisi ve garson görevlerini günlük dinamik vardiyaya göre atayın.
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <form method="GET" action="<?= site_url('restaurant/staff_stations') ?>" class="d-flex align-items-center gap-2">
                    <label class="small fw-bold text-muted text-nowrap">Vardiya Tarihi:</label>
                    <input type="date" name="shift_date" class="form-control form-control-sm" value="<?= e($shift_date) ?>" onchange="this.form.submit()">
                </form>
                <a href="<?= site_url('restaurant/kitchen_screen') ?>" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-tv me-1"></i>KDS Mutfak Ekranı
                </a>
            </div>
        </div>

        <!-- Station Info / Legend Bar -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-3">
                <div class="row g-3">
                    <div class="col-md-5">
                        <span class="badge bg-danger mb-1"><i class="fas fa-fire me-1"></i>Mutfak Tugayı (Brigade de Cuisine)</span>
                        <div class="small text-muted">
                            Sıcak & Sos (Saucier), Izgara & Et (Rôtisseur), Soğuk (Garde-Manger), Hamur/Fırın (Boulanger), Pastane (Pâtissier), Aboyer (Sunucu), Bulaşık (Steward).
                        </div>
                    </div>
                    <div class="col-md-4">
                        <span class="badge bg-info text-dark mb-1"><i class="fas fa-cocktail me-1"></i>Bar Hiyerarşisi</span>
                        <div class="small text-muted">
                            <strong>Bar Kaptanı</strong> (Genel denetim/yönetim), <strong>Barmen/Barmaid</strong> (Miksoloji/kokteyl hazırlık), <strong>Barboy</strong> (Bardak/buz/hammadde desteği).
                        </div>
                    </div>
                    <div class="col-md-3">
                        <span class="badge bg-success mb-1"><i class="fas fa-concierge-bell me-1"></i>Ön Salon (FOH)</span>
                        <div class="small text-muted">
                            Şef Garson (Head Waiter), Garson (Masa & Adisyon Yetkilisi), Komi/Deberasör (Boş toplama).
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Staff Cards Matrix -->
        <div class="row g-4" id="staff-cards-container">
            <?php foreach ($staff_users as $staff): 
                $uid = (int) $staff['id'];
                $assigned_stations = $daily_assignments[$uid]['stations'] ?? [];
                $custom_role = $daily_assignments[$uid]['role_title'] ?? '';
            ?>
            <div class="col-xl-6">
                <div class="card staff-card bg-white p-4 h-100" data-user-id="<?= $uid ?>">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm"
                                 style="width: 48px; height: 48px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); font-size: 1.1rem;">
                                <?= mb_substr($staff['first_name'], 0, 1) . mb_substr($staff['last_name'], 0, 1) ?>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0 text-dark"><?= e($staff['first_name'] . ' ' . $staff['last_name']) ?></h5>
                                <span class="badge <?= ($staff['id_roles'] == 1) ? 'bg-dark' : 'bg-primary' ?> small">
                                    <?= ($staff['id_roles'] == 1) ? 'Tenant Yöneticisi' : 'Personel' ?>
                                </span>
                                <span class="text-muted small ms-2"><i class="fas fa-envelope me-1"></i><?= e($staff['email']) ?></span>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary btn-save-staff-station px-3 shadow-sm">
                            <i class="fas fa-save me-1"></i>Kaydet
                        </button>
                    </div>

                    <!-- Role Title Input -->
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold mb-1">Günlük Görev / Ünvan Tanımı:</label>
                        <input type="text" class="form-control form-control-sm staff-role-title" value="<?= e($custom_role) ?>" placeholder="Örn: Kıdemli Şef Saucier & Izgara Şefi">
                    </div>

                    <!-- Quick Preset Buttons -->
                    <div class="d-flex flex-wrap gap-1 mb-3">
                        <span class="small text-muted me-1 align-self-center">Hızlı Atama:</span>
                        <button type="button" class="btn btn-xs btn-outline-danger quick-preset" data-stations="hot_sauce,grill_meat" data-title="Sıcak & Izgara Şefi">
                            🔥 Sıcak + Izgara
                        </button>
                        <button type="button" class="btn btn-xs btn-outline-warning quick-preset" data-stations="pastry_dessert,cold_pantry" data-title="Pastane & Soğuk Meze Şefi">
                            🍰 Pastane + Soğuk
                        </button>
                        <button type="button" class="btn btn-xs btn-outline-info quick-preset" data-stations="bartender,barboy" data-title="Barmen & Bar Destek">
                            🍸 Barmen + Barboy
                        </button>
                        <button type="button" class="btn btn-xs btn-outline-success quick-preset" data-stations="waiter" data-title="Masa Servis Garsonu">
                            🛎️ Garson
                        </button>
                        <button type="button" class="btn btn-xs btn-outline-secondary btn-clear-stations">
                            ✕ Temizle
                        </button>
                    </div>

                    <!-- Stations Selection (Grouped) -->
                    <div>
                        <!-- Mutfak Grubu -->
                        <div class="mb-2">
                            <span class="group-header text-danger"><i class="fas fa-utensils me-1"></i>Mutfak İstasyonları (Cook / Chef):</span>
                            <div class="d-flex flex-wrap mt-1">
                                <?php foreach ($stations as $scode => $sinfo): 
                                    if ($sinfo['group'] !== 'Mutfak') continue;
                                    $is_checked = in_array($scode, $assigned_stations, true);
                                ?>
                                <label>
                                    <input type="checkbox" class="d-none station-checkbox" value="<?= $scode ?>" <?= $is_checked ? 'checked' : '' ?>>
                                    <span class="station-checkbox-label" style="<?= $is_checked ? 'background: ' . $sinfo['color'] . '; border-color: ' . $sinfo['color'] . ';' : '' ?>" data-color="<?= $sinfo['color'] ?>">
                                        <i class="fas <?= $sinfo['icon'] ?> me-1"></i><?= e($sinfo['name']) ?>
                                    </span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Bar Grubu -->
                        <div class="mb-2">
                            <span class="group-header text-info"><i class="fas fa-glass-martini-alt me-1"></i>Bar Hiyerarşisi (Bar Team):</span>
                            <div class="d-flex flex-wrap mt-1">
                                <?php foreach ($stations as $scode => $sinfo): 
                                    if ($sinfo['group'] !== 'Bar') continue;
                                    $is_checked = in_array($scode, $assigned_stations, true);
                                ?>
                                <label>
                                    <input type="checkbox" class="d-none station-checkbox" value="<?= $scode ?>" <?= $is_checked ? 'checked' : '' ?>>
                                    <span class="station-checkbox-label" style="<?= $is_checked ? 'background: ' . $sinfo['color'] . '; border-color: ' . $sinfo['color'] . ';' : '' ?>" data-color="<?= $sinfo['color'] ?>">
                                        <i class="fas <?= $sinfo['icon'] ?> me-1"></i><?= e($sinfo['name']) ?>
                                    </span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Salon Grubu -->
                        <div>
                            <span class="group-header text-success"><i class="fas fa-concierge-bell me-1"></i>Salon & Servis (Front of House):</span>
                            <div class="d-flex flex-wrap mt-1">
                                <?php foreach ($stations as $scode => $sinfo): 
                                    if ($sinfo['group'] !== 'Salon') continue;
                                    $is_checked = in_array($scode, $assigned_stations, true);
                                ?>
                                <label>
                                    <input type="checkbox" class="d-none station-checkbox" value="<?= $scode ?>" <?= $is_checked ? 'checked' : '' ?>>
                                    <span class="station-checkbox-label" style="<?= $is_checked ? 'background: ' . $sinfo['color'] . '; border-color: ' . $sinfo['color'] . ';' : '' ?>" data-color="<?= $sinfo['color'] ?>">
                                        <i class="fas <?= $sinfo['icon'] ?> me-1"></i><?= e($sinfo['name']) ?>
                                    </span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const shiftDate = '<?= e($shift_date) ?>';
        const csrfToken = '<?= e(vars('csrf_token')) ?>';

        // Checkbox styling toggle
        document.querySelectorAll('.station-checkbox').forEach(cb => {
            cb.addEventListener('change', function () {
                const label = this.nextElementSibling;
                const color = label.dataset.color || '#3b82f6';
                if (this.checked) {
                    label.style.background = color;
                    label.style.borderColor = color;
                } else {
                    label.style.background = '#fff';
                    label.style.borderColor = '#e2e8f0';
                }
            });
        });

        // Quick Preset buttons
        document.querySelectorAll('.quick-preset').forEach(btn => {
            btn.addEventListener('click', function () {
                const card = this.closest('.staff-card');
                const targetStations = this.dataset.stations.split(',');
                const title = this.dataset.title;

                card.querySelector('.staff-role-title').value = title;
                card.querySelectorAll('.station-checkbox').forEach(cb => {
                    const match = targetStations.includes(cb.value);
                    cb.checked = match;
                    const label = cb.nextElementSibling;
                    const color = label.dataset.color || '#3b82f6';
                    if (match) {
                        label.style.background = color;
                        label.style.borderColor = color;
                    } else {
                        label.style.background = '#fff';
                        label.style.borderColor = '#e2e8f0';
                    }
                });
            });
        });

        // Clear button
        document.querySelectorAll('.btn-clear-stations').forEach(btn => {
            btn.addEventListener('click', function () {
                const card = this.closest('.staff-card');
                card.querySelector('.staff-role-title').value = '';
                card.querySelectorAll('.station-checkbox').forEach(cb => {
                    cb.checked = false;
                    const label = cb.nextElementSibling;
                    label.style.background = '#fff';
                    label.style.borderColor = '#e2e8f0';
                });
            });
        });

        // Individual Save Button
        document.querySelectorAll('.btn-save-staff-station').forEach(btn => {
            btn.addEventListener('click', function () {
                const card = this.closest('.staff-card');
                const userId = card.dataset.userId;
                const roleTitle = card.querySelector('.staff-role-title').value;
                const checkedStations = [];
                card.querySelectorAll('.station-checkbox:checked').forEach(cb => {
                    checkedStations.push(cb.value);
                });

                const payload = {
                    user_id: userId,
                    role_title: roleTitle,
                    stations: checkedStations,
                    shift_date: shiftDate,
                    csrf_token: csrfToken
                };

                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Kaydediliyor...';
                btn.disabled = true;

                fetch('<?= site_url('restaurant/api_save_staff_stations') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(payload)
                })
                .then(r => r.json())
                .then(res => {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    if (res.status === 'success') {
                        card.classList.add('border-success');
                        setTimeout(() => card.classList.remove('border-success'), 1200);
                    } else {
                        alert('Hata: ' + (res.message || 'Kaydedilemedi.'));
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    console.error(err);
                    alert('Bağlantı hatası.');
                });
            });
        });
    });
    </script>
</div>
<?php end_section('content'); ?>
