<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div id="data-transfer-page" class="container backend-page py-3">
<div class="row">
    <div class="col-sm-3">
        <?php component('settings_nav'); ?>
    </div>

    <div class="col-sm-9">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Dışa Aktarma</h5>
                <p class="text-muted small">
                    Müşteri, hizmet, istasyon, sağlayıcı ve randevu verinizi tek bir JSON dosyası olarak indirin.
                </p>
                <a href="<?= site_url('data_transfer/export') ?>" class="btn btn-outline-primary">
                    <i class="fas fa-download me-1"></i> Dışa Aktar
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title">İçe Aktarma</h5>
                <p class="text-muted small">
                    Daha önce Ki Reservation'dan alınmış bir dışa aktarma dosyasını (.json) yükleyin. Önce bir
                    önizleme göreceksiniz, hiçbir şey onaylamadan yazılmaz.
                </p>

                <input type="file" id="import-file" accept="application/json" class="form-control mb-3" style="max-width: 400px;">

                <div id="import-preview" class="d-none">
                    <table class="table table-sm">
                        <tbody>
                            <tr><td>Kategori</td><td id="pv-categories"></td></tr>
                            <tr><td>Hizmet</td><td id="pv-services"></td></tr>
                            <tr><td>İstasyon</td><td id="pv-stations"></td></tr>
                            <tr><td>Sağlayıcı</td><td id="pv-providers"></td></tr>
                            <tr><td>Müşteri (ham → tekilleştirilmiş)</td><td id="pv-customers"></td></tr>
                            <tr><td>Randevu</td><td id="pv-appointments"></td></tr>
                        </tbody>
                    </table>
                    <button type="button" id="import-confirm-btn" class="btn btn-primary">İçe Aktarımı Onayla</button>
                </div>

                <div id="import-msg" class="mt-3" style="display:none;"></div>
            </div>
        </div>
    </div>
</div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>
<script>
    let selectedFile = null;

    document.getElementById('import-file').addEventListener('change', function (event) {
        selectedFile = event.target.files[0] || null;
        document.getElementById('import-preview').classList.add('d-none');

        if (!selectedFile) {
            return;
        }

        const formData = new FormData();
        formData.append('csrf_token', vars('csrf_token'));
        formData.append('file', selectedFile);

        fetch(App.Utils.Url.siteUrl('data_transfer/import_preview'), { method: 'POST', body: formData })
            .then((r) => r.json())
            .then((data) => {
                const msg = document.getElementById('import-msg');

                if (!data.success) {
                    msg.style.display = 'block';
                    msg.className = 'alert alert-danger mt-3';
                    msg.textContent = data.message || 'Dosya okunamadı.';
                    return;
                }

                msg.style.display = 'none';
                document.getElementById('pv-categories').textContent = data.categories;
                document.getElementById('pv-services').textContent = data.services;
                document.getElementById('pv-stations').textContent = data.stations;
                document.getElementById('pv-providers').textContent = data.providers;
                document.getElementById('pv-customers').textContent = data.raw_customers + ' → ' + data.unique_customers;
                document.getElementById('pv-appointments').textContent = data.appointments;
                document.getElementById('import-preview').classList.remove('d-none');
            });
    });

    document.getElementById('import-confirm-btn').addEventListener('click', function () {
        if (!selectedFile) {
            return;
        }

        if (!confirm('İçe aktarımı onaylıyor musunuz? Bu işlem geri alınamaz.')) {
            return;
        }

        const formData = new FormData();
        formData.append('csrf_token', vars('csrf_token'));
        formData.append('file', selectedFile);

        fetch(App.Utils.Url.siteUrl('data_transfer/import_commit'), { method: 'POST', body: formData })
            .then((r) => r.json())
            .then((data) => {
                const msg = document.getElementById('import-msg');
                msg.style.display = 'block';

                if (data.success) {
                    msg.className = 'alert alert-success mt-3';
                    msg.textContent = data.categories + ' kategori, ' + data.services + ' hizmet, ' +
                        data.stations + ' istasyon, ' + data.providers + ' sağlayıcı, ' +
                        data.customers + ' müşteri, ' + data.appointments + ' randevu aktarıldı (' +
                        data.skipped_appointments + ' randevu atlandı).';
                } else {
                    msg.className = 'alert alert-danger mt-3';
                    msg.textContent = data.message || 'İçe aktarma başarısız.';
                }
            });
    });
</script>
<?php end_section('scripts'); ?>
