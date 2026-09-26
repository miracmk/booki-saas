<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div id="telegram-page" class="container backend-page py-3">
    <div class="row">
        <div class="col-sm-3">
            <?php component('settings_nav'); ?>
        </div>
        <div class="col-sm-9">
            <h4 class="border-bottom py-3 mb-3 fw-light">
                Telegram Entegrasyonu
            </h4>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="fw-light mb-0">1. Bot Ayarları</h5>
                </div>
                <div class="card-body">
                    <p class="form-text text-muted">
                        @BotFather'a Telegram'dan yazıp <code>/newbot</code> ile bir bot oluşturun, verdiği
                        token'ı buraya yapıştırın.
                    </p>

                    <div class="mb-3">
                        <label class="form-label" for="bot-token">Bot Token</label>
                        <input type="password" id="bot-token" class="form-control"
                               placeholder="<?= vars('telegram_bot_token_set') ? 'Kayıtlı (değiştirmek için yeni token girin)' : '123456:ABC-DEF...' ?>">
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" id="notifications-enabled" class="form-check-input"
                               <?= vars('telegram_notifications_enabled') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="notifications-enabled">
                            Telegram bildirimleri aktif
                        </label>
                    </div>

                    <button id="save-telegram-settings" class="btn btn-primary">Kaydet</button>

                    <?php if (vars('telegram_bot_username')): ?>
                        <span class="ms-2 text-muted">
                            Bağlı bot: <strong>@<?= e(vars('telegram_bot_username')) ?></strong>
                        </span>
                    <?php endif; ?>

                    <hr>

                    <p class="form-text text-muted">
                        Bot token kaydedildikten sonra, Telegram'ın mesajları bu sisteme iletmesi için
                        webhook'u kaydedin (tek seferlik).
                    </p>
                    <button id="setup-webhook" class="btn btn-outline-secondary">Webhook'u Kur</button>
                    <?php if (vars('telegram_webhook_configured')): ?>
                        <span class="ms-2 text-success"><i class="fas fa-check-circle"></i> Kurulu</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="fw-light mb-0">2. Personel Bağlantıları</h5>
                </div>
                <div class="card-body">
                    <p class="form-text text-muted">
                        Her personel için bir bağlantı linki oluşturun, kendilerine iletin (WhatsApp/SMS
                        vb.); linke tıklayıp bot'ta "Başlat"a bastıklarında Telegram hesapları otomatik
                        bağlanır.
                    </p>
                    <table class="table table-sm">
                        <thead>
                        <tr>
                            <th>Ad Soyad</th>
                            <th>Rol</th>
                            <th>Telegram Durumu</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach (vars('staff') as $member): ?>
                            <tr>
                                <td><?= e($member['name']) ?></td>
                                <td><?= e($member['role']) ?></td>
                                <td>
                                    <?php if ($member['telegram_chat_id']): ?>
                                        <span class="text-success">
                                            <i class="fas fa-check-circle"></i>
                                            Bağlı<?= $member['telegram_username'] ? ' (@' . e($member['telegram_username']) . ')' : '' ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">Bağlı değil</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary generate-link-btn"
                                            data-user-id="<?= (int) $member['id'] ?>">
                                        Bağlantı Linki Oluştur
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="fw-light mb-0">3. Gelen Mesajlar</h5>
                </div>
                <div class="card-body">
                    <p class="form-text text-muted">
                        Müşteriler veya personel bot'a doğrudan yazdığında burada görünür; alttan cevap
                        yazabilirsiniz.
                    </p>
                    <div id="telegram-messages" style="max-height: 500px; overflow-y: auto;">
                        <?php foreach (vars('messages') as $msg): ?>
                            <div class="border-bottom py-2">
                                <strong>
                                    <?= $msg['direction'] === 'in' ? '⬅' : '➡' ?>
                                    <?= $msg['first_name'] ? e($msg['first_name'] . ' ' . $msg['last_name']) : 'Bilinmeyen (chat ' . e($msg['chat_id']) . ')' ?>
                                </strong>
                                <small class="text-muted"><?= e($msg['created_at']) ?></small>
                                <p class="mb-1"><?= e($msg['message']) ?></p>
                                <?php if ($msg['direction'] === 'in'): ?>
                                    <div class="input-group input-group-sm mb-2" style="max-width: 400px;">
                                        <input type="text" class="form-control reply-input"
                                               placeholder="Cevap yaz...">
                                        <button class="btn btn-outline-primary reply-btn"
                                                data-chat-id="<?= e($msg['chat_id']) ?>"
                                                data-user-id="<?= $msg['id_users'] ? (int) $msg['id_users'] : '' ?>">
                                            Gönder
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty(vars('messages'))): ?>
                            <p class="text-muted">Henüz mesaj yok.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/pages/telegram.js') ?>"></script>

<?php end_section('scripts'); ?>
