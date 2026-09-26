<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div id="payment-settings-page" class="container backend-page py-3">
    <div class="row">
        <div class="col-sm-3">
            <?php component('settings_nav'); ?>
        </div>
        <div class="col-sm-9">
            <h4 class="border-bottom py-3 mb-3 fw-light">
                Ödeme Ayarları
            </h4>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="fw-light mb-0">Aktif Ödeme Ağ Geçidi</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="active-gateway">Ödeme Ağ Geçidi Seçin</label>
                        <select id="active-gateway" class="form-select">
                            <option value="none" <?= vars('settings')['active_gateway'] === 'none' ? 'selected' : '' ?>>
                                Hiçbiri (Ödeme Devre Dışı)
                            </option>
                            <option value="iyzico" <?= vars('settings')['active_gateway'] === 'iyzico' ? 'selected' : '' ?>>
                                iyzico
                            </option>
                            <option value="paytr" <?= vars('settings')['active_gateway'] === 'paytr' ? 'selected' : '' ?>>
                                PayTR
                            </option>
                            <option value="stripe" <?= vars('settings')['active_gateway'] === 'stripe' ? 'selected' : '' ?>>
                                Stripe
                            </option>
                        </select>
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" id="is-sandbox" class="form-check-input"
                               <?= vars('settings')['is_sandbox'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is-sandbox">
                            Sandbox Modu (Test)
                        </label>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="fw-light mb-0">Depozito Ayarları</h5>
                </div>
                <div class="card-body">
                    <div class="form-check mb-3">
                        <input type="checkbox" id="require-deposit" class="form-check-input"
                               <?= vars('settings')['require_deposit'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="require-deposit">
                            Randevular İçin Depozito Gerekli
                        </label>
                    </div>

                    <div id="deposit-settings" style="display: <?= vars('settings')['require_deposit'] ? 'block' : 'none' ?>;">
                        <div class="mb-3">
                            <label class="form-label" for="deposit-type">Depozito Türü</label>
                            <select id="deposit-type" class="form-select">
                                <option value="fixed" <?= vars('settings')['deposit_type'] === 'fixed' ? 'selected' : '' ?>>
                                    Sabit Tutar
                                </option>
                                <option value="percentage" <?= vars('settings')['deposit_type'] === 'percentage' ? 'selected' : '' ?>>
                                    Yüzde
                                </option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="deposit-value">Depozito Değeri</label>
                            <input type="number" id="deposit-value" class="form-control" step="0.01" min="0"
                                   value="<?= vars('settings')['deposit_value'] ?? '' ?>"
                                   placeholder="<?= vars('settings')['deposit_type'] === 'percentage' ? '20' : '100.00' ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- iyzico Gateway Settings -->
            <div class="card mb-4" id="iyzico-settings" style="display: <?= vars('settings')['active_gateway'] === 'iyzico' ? 'block' : 'none' ?>;">
                <div class="card-header">
                    <h5 class="fw-light mb-0">iyzico Ayarları</h5>
                </div>
                <div class="card-body">
                    <p class="form-text text-muted mb-3">
                        <a href="https://www.iyzipay.com" target="_blank">iyzipay.com</a> adresinden API kimlik bilgilerinizi alabilirsiniz.
                    </p>

                    <div class="mb-3">
                        <label class="form-label" for="iyzico-api-key">API Anahtarı</label>
                        <input type="password" id="iyzico-api-key" class="form-control"
                               placeholder="<?= vars('settings')['iyzico_api_key_set'] ? 'Kayıtlı (değiştirmek için yeni anahtar girin)' : 'API anahtarınız...' ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="iyzico-secret-key">Gizli Anahtar</label>
                        <input type="password" id="iyzico-secret-key" class="form-control"
                               placeholder="<?= vars('settings')['iyzico_secret_key_set'] ? 'Kayıtlı (değiştirmek için yeni anahtar girin)' : 'Gizli anahtarınız...' ?>">
                    </div>
                </div>
            </div>

            <!-- PayTR Gateway Settings -->
            <div class="card mb-4" id="paytr-settings" style="display: <?= vars('settings')['active_gateway'] === 'paytr' ? 'block' : 'none' ?>;">
                <div class="card-header">
                    <h5 class="fw-light mb-0">PayTR Ayarları</h5>
                </div>
                <div class="card-body">
                    <p class="form-text text-muted mb-3">
                        <a href="https://www.paytr.com" target="_blank">paytr.com</a> adresinden API kimlik bilgilerinizi alabilirsiniz.
                    </p>

                    <div class="mb-3">
                        <label class="form-label" for="paytr-merchant-id">Mağaza Kimliği</label>
                        <input type="text" id="paytr-merchant-id" class="form-control"
                               placeholder="<?= vars('settings')['paytr_merchant_id_set'] ? 'Kayıtlı' : 'Mağaza kimliğiniz...' ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="paytr-merchant-key">Mağaza Anahtarı</label>
                        <input type="password" id="paytr-merchant-key" class="form-control"
                               placeholder="<?= vars('settings')['paytr_merchant_key_set'] ? 'Kayıtlı (değiştirmek için yeni anahtar girin)' : 'Mağaza anahtarınız...' ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="paytr-merchant-salt">Mağaza Salt</label>
                        <input type="password" id="paytr-merchant-salt" class="form-control"
                               placeholder="<?= vars('settings')['paytr_merchant_salt_set'] ? 'Kayıtlı (değiştirmek için yeni salt girin)' : 'Mağaza salt\'ınız...' ?>">
                    </div>
                </div>
            </div>

            <!-- Stripe Gateway Settings -->
            <div class="card mb-4" id="stripe-settings" style="display: <?= vars('settings')['active_gateway'] === 'stripe' ? 'block' : 'none' ?>;">
                <div class="card-header">
                    <h5 class="fw-light mb-0">Stripe Ayarları</h5>
                </div>
                <div class="card-body">
                    <p class="form-text text-muted mb-3">
                        <a href="https://www.stripe.com" target="_blank">stripe.com</a> adresinden API anahtarlarınızı alabilirsiniz.
                    </p>

                    <div class="mb-3">
                        <label class="form-label" for="stripe-publishable-key">Yayınlanabilir Anahtar</label>
                        <input type="text" id="stripe-publishable-key" class="form-control"
                               placeholder="<?= vars('settings')['stripe_publishable_key_set'] ? 'Kayıtlı' : 'pk_...' ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="stripe-secret-key">Gizli Anahtar</label>
                        <input type="password" id="stripe-secret-key" class="form-control"
                               placeholder="<?= vars('settings')['stripe_secret_key_set'] ? 'Kayıtlı (değiştirmek için yeni anahtar girin)' : 'sk_...' ?>">
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <button id="save-payment-settings" class="btn btn-primary">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    const requireDepositCheckbox = $('#require-deposit');
    const depositSettingsDiv = $('#deposit-settings');
    const activeGatewaySelect = $('#active-gateway');

    // Toggle deposit settings visibility
    requireDepositCheckbox.on('change', function() {
        depositSettingsDiv.toggle(this.checked);
    });

    // Toggle gateway-specific settings visibility
    activeGatewaySelect.on('change', function() {
        $('#iyzico-settings, #paytr-settings, #stripe-settings').hide();

        const selectedGateway = $(this).val();
        if (selectedGateway !== 'none') {
            $('#' + selectedGateway + '-settings').show();
        }
    });

    // Save settings
    $('#save-payment-settings').on('click', function() {
        $.ajax({
            type: 'POST',
            url: '<?php echo site_url('payment_settings/save_settings'); ?>',
            dataType: 'json',
            data: {
                active_gateway: $('#active-gateway').val(),
                require_deposit: $('#require-deposit').is(':checked'),
                deposit_type: $('#deposit-type').val(),
                deposit_value: $('#deposit-value').val(),
                is_sandbox: $('#is-sandbox').is(':checked'),
                iyzico_api_key: $('#iyzico-api-key').val(),
                iyzico_secret_key: $('#iyzico-secret-key').val(),
                paytr_merchant_id: $('#paytr-merchant-id').val(),
                paytr_merchant_key: $('#paytr-merchant-key').val(),
                paytr_merchant_salt: $('#paytr-merchant-salt').val(),
                stripe_publishable_key: $('#stripe-publishable-key').val(),
                stripe_secret_key: $('#stripe-secret-key').val(),
            },
            success: function(response) {
                if (response.success) {
                    alert('Ödeme ayarları başarıyla kaydedildi.');
                    location.reload();
                }
            },
            error: function(xhr) {
                try {
                    const error = JSON.parse(xhr.responseText);
                    alert('Hata: ' + (error.message || 'Bilinmeyen hata'));
                } catch (e) {
                    alert('Ödeme ayarları kaydedilirken bir hata oluştu.');
                }
            }
        });
    });
});
</script>

<?php end_section('content'); ?>
