<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div class="container-fluid backend-page p-0" id="unified-chat-portal" style="height: calc(100vh - 72px); display: flex; flex-direction: column; overflow: hidden; background: #f0f2f5;">

  <!-- Chat Portal Top Header Bar -->
  <div class="px-3 py-2 bg-white border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2 shadow-xs" style="z-index: 10;">
    <div class="d-flex align-items-center gap-3">
      <div class="avatar-icon-circle bg-primary bg-opacity-10 text-primary p-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
        <i class="fas fa-comments fs-5"></i>
      </div>
      <div>
        <h5 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
          Birleşik Canlı Chat & AI Portalı
          <span class="badge bg-success-subtle text-success border border-success-subtle fs-8 rounded-pill fw-semibold">
            <span class="spinner-grow spinner-grow-sm me-1" style="width: 6px; height: 6px;" role="status"></span>Canlı Akış
          </span>
        </h5>
        <div class="text-muted small" style="font-size: 11.5px;">
          WhatsApp, Instagram Direct, Telegram, Web Widget ve BooKi AI Copilot birleşik gelen kutusu
        </div>
      </div>
    </div>

    <!-- Channel Filter Quick Pills -->
    <div class="d-flex align-items-center gap-1 flex-wrap">
      <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill channel-filter-btn active" data-channel="all" style="font-size: 12px;">
        <i class="fas fa-layer-group me-1"></i> Tümü
      </button>
      <button type="button" class="btn btn-sm btn-outline-purple rounded-pill channel-filter-btn" data-channel="ai" style="font-size: 12px; color: #7c3aed; border-color: #ddd6fe;">
        <i class="fas fa-robot me-1 text-purple"></i> AI Copilot
      </button>
      <button type="button" class="btn btn-sm btn-outline-success rounded-pill channel-filter-btn" data-channel="whatsapp" style="font-size: 12px;">
        <i class="fab fa-whatsapp me-1 text-success"></i> WhatsApp
      </button>
      <button type="button" class="btn btn-sm btn-outline-danger rounded-pill channel-filter-btn" data-channel="instagram" style="font-size: 12px;">
        <i class="fab fa-instagram me-1" style="color: #E1306C;"></i> Instagram
      </button>
      <button type="button" class="btn btn-sm btn-outline-info rounded-pill channel-filter-btn" data-channel="telegram" style="font-size: 12px;">
        <i class="fab fa-telegram-plane me-1 text-info"></i> Telegram
      </button>
      <button type="button" class="btn btn-sm btn-outline-primary rounded-pill channel-filter-btn" data-channel="widget" style="font-size: 12px;">
        <i class="fas fa-globe me-1 text-primary"></i> Web Widget
      </button>

      <!-- 2-Option WhatsApp Connection Dropdown -->
      <div class="dropdown d-inline-block ms-1">
        <button type="button" class="btn btn-sm <?= (($bridge_status ?? '') === 'connected' || ($wa_mode ?? '') === 'official') ? 'btn-outline-success' : 'btn-outline-secondary' ?> rounded-pill dropdown-toggle d-flex align-items-center gap-1 shadow-2xs" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 12px;" id="wa-connection-btn">
          <i class="fab fa-whatsapp text-success fs-6"></i>
          <span id="wa-active-badge-label">
            <?= (($wa_mode ?? '') === 'unofficial' ? 'WhatsApp: QR Web' : 'WhatsApp: Meta Cloud') ?>
          </span>
          <span class="badge <?= (($bridge_status ?? '') === 'connected' && ($wa_mode ?? '') === 'unofficial') ? 'bg-success text-white' : (($wa_mode ?? '') === 'official' ? 'bg-primary text-white' : 'bg-warning text-dark') ?> rounded-pill ms-1" style="font-size: 9px;" id="wa-active-status-dot">
            <?= (($wa_mode ?? '') === 'unofficial' ? (($bridge_status ?? '') === 'connected' ? 'Bağlı' : 'Bağlantı Yok') : (!empty($meta_configured) ? 'Resmi' : 'Ayar Gerekli')) ?>
          </span>
        </button>
        
        <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-3 rounded-3" style="width: 350px; z-index: 1060;">
          <div class="d-flex align-items-center justify-content-between pb-2 mb-2 border-bottom">
            <span class="fw-bold text-dark fs-7"><i class="fab fa-whatsapp text-success me-1"></i> WhatsApp Çift Uçlu Bağlantı</span>
            <span class="badge bg-secondary-subtle text-secondary fs-8">2 Seçenekli</span>
          </div>

          <!-- Seçenek 1: WhatsApp Web QR Kod (Unofficial) -->
          <div class="border rounded-3 p-2 mb-2 <?= (($wa_mode ?? '') === 'unofficial') ? 'border-success bg-success-subtle bg-opacity-10' : 'bg-light bg-opacity-50' ?>">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <span class="fw-bold text-dark fs-7 d-flex align-items-center gap-1">
                <i class="fas fa-qrcode text-primary"></i> 1. WhatsApp Web (QR Kod)
              </span>
              <span class="badge <?= (($bridge_status ?? '') === 'connected') ? 'bg-success text-white' : 'bg-secondary text-white' ?>" id="qr-status-badge">
                <?= (($bridge_status ?? '') === 'connected') ? 'Bağlı (' . e($bridge_session_name ?? 'BooKi Demo') . ')' : 'Bağlı Değil' ?>
              </span>
            </div>
            <p class="text-muted small mb-2" style="font-size: 11px; line-height: 1.3;">
              Meta onay süreci ve şablon kısıtlaması olmadan, normal veya Business WhatsApp uygulamanızdan QR kod taratarak doğrudan kullanın.
            </p>
            <div class="d-flex align-items-center gap-2">
              <button type="button" class="btn btn-xs btn-primary text-white rounded-pill px-2 py-1" style="font-size: 11px;" onclick="openQrModal()">
                <i class="fas fa-qrcode me-1"></i> <?= (($bridge_status ?? '') === 'connected') ? 'Cihazı Gör / QR' : 'QR Kod İle Bağla' ?>
              </button>
              <?php if (($wa_mode ?? '') === 'unofficial'): ?>
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 10.5px;">
                  <i class="fas fa-check-circle me-1"></i> Aktif Çıkış Kanalı
                </span>
              <?php else: ?>
                <button type="button" class="btn btn-xs btn-outline-success rounded-pill px-2 py-1" style="font-size: 11px;" onclick="switchWhatsAppMode('unofficial')">
                  Bu Modu Aktif Et
                </button>
              <?php endif; ?>
            </div>
          </div>

          <!-- Seçenek 2: Meta Cloud API (Official) -->
          <div class="border rounded-3 p-2 <?= (($wa_mode ?? '') === 'official') ? 'border-primary bg-primary-subtle bg-opacity-10' : 'bg-light bg-opacity-50' ?>">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <span class="fw-bold text-dark fs-7 d-flex align-items-center gap-1">
                <i class="fab fa-meta text-primary"></i> 2. Resmi Meta Cloud API
              </span>
              <span class="badge <?= !empty($meta_configured) ? 'bg-primary text-white' : 'bg-warning text-dark' ?>">
                <?= !empty($meta_configured) ? 'WABA Tanımlı' : 'Token Gerekli' ?>
              </span>
            </div>
            <p class="text-muted small mb-2" style="font-size: 11px; line-height: 1.3;">
              Meta Business Manager ve WhatsApp Cloud API üzerinden resmi kurumsal entegrasyon (onaylı şablonlar ile).
            </p>
            <div class="d-flex align-items-center gap-2">
              <a href="<?= site_url('meta/connect_oauth/whatsapp') ?>" target="_blank" class="btn btn-xs btn-outline-primary rounded-pill px-2 py-1" style="font-size: 11px;">
                <i class="fab fa-facebook me-1"></i> Meta ile Bağlan
              </a>
              <?php if (($wa_mode ?? '') === 'official'): ?>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" style="font-size: 10.5px;">
                  <i class="fas fa-check-circle me-1"></i> Aktif Çıkış Kanalı
                </span>
              <?php else: ?>
                <button type="button" class="btn btn-xs btn-outline-primary rounded-pill px-2 py-1" style="font-size: 11px;" onclick="switchWhatsAppMode('official')">
                  Bu Modu Aktif Et
                </button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <button type="button" class="btn btn-sm btn-light border rounded-circle ms-2" onclick="refreshPortal()" title="Konuşmaları Yenile">
        <i class="fas fa-sync-alt" id="refresh-icon"></i>
      </button>
    </div>
  </div>

  <!-- Chat Split Screen Layout -->
  <div class="d-flex flex-fill overflow-hidden" style="position: relative;">

    <!-- Left Column: Threads List Sidebar -->
    <div class="chat-sidebar bg-white border-end d-flex flex-column" style="width: 380px; min-width: 320px; max-width: 420px; height: 100%;">
      
      <!-- Search Box -->
      <div class="p-2 border-bottom bg-light bg-opacity-50">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
          <input type="text" class="form-control border-start-0 ps-0 bg-white" id="thread-search-input" placeholder="Kişi, telefon veya mesaj ara..." oninput="debounceSearch()">
        </div>
      </div>

      <!-- Threads Scroll Area -->
      <div class="threads-scroll-container flex-fill overflow-auto" id="threads-container">
        <!-- Rendered via PHP and updated via JS -->
        <?php if (!empty($threads)): ?>
          <?php foreach ($threads as $t): ?>
            <?php 
              $is_active = (!empty($initial_thread) && $initial_thread['id'] === $t['id']);
              $is_ai = ($t['channel'] === 'ai');
            ?>
            <div class="thread-item p-3 border-bottom d-flex align-items-start gap-2 cursor-pointer <?= $is_active ? 'active-thread bg-primary bg-opacity-10' : '' ?> <?= $is_ai ? 'pinned-ai-thread' : '' ?>"
                 data-thread-key="<?= e($t['id']) ?>"
                 data-channel="<?= e($t['channel']) ?>"
                 data-thread-id="<?= e($t['thread_id']) ?>"
                 onclick="selectThread('<?= e($t['channel']) ?>', '<?= e($t['thread_id']) ?>', '<?= e($t['id']) ?>')">
              
              <!-- Avatar with Channel Icon Overlay -->
              <div class="position-relative flex-shrink-0">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-xs" 
                     style="width: 44px; height: 44px; background: <?= e($t['avatar_color']) ?>; font-size: 18px;">
                  <i class="<?= e($t['avatar_icon']) ?>"></i>
                </div>
                <!-- Platform Badge on Avatar -->
                <span class="position-absolute bottom-0 end-0 badge p-1 rounded-circle border border-white shadow-xs" style="background: #fff; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; font-size: 10px;">
                  <i class="<?= e($t['channel_icon']) ?>"></i>
                </span>
              </div>

              <!-- Thread Info -->
              <div class="flex-grow-1 min-w-0 ms-1">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="fw-bold text-dark text-truncate d-flex align-items-center gap-1" style="font-size: 13.5px;">
                    <?php if ($is_ai): ?>
                      <i class="fas fa-thumbtack text-purple me-1" style="font-size: 11px;" title="Sabitlenmiş Asistan"></i>
                    <?php endif; ?>
                    <?= e($t['name']) ?>
                  </span>
                  <span class="text-muted small text-nowrap" style="font-size: 11px;">
                    <?= e($t['last_time']) ?>
                  </span>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                  <p class="mb-0 text-muted text-truncate" style="font-size: 12px; max-width: 210px;" id="last-msg-<?= e($t['id']) ?>">
                    <?= e($t['last_message']) ?>
                  </p>
                  
                  <!-- Handoff & Channel Status -->
                  <div class="d-flex align-items-center gap-1 flex-shrink-0">
                    <?php if (!$is_ai): ?>
                      <?php if ($t['handoff_status'] === 'human_handoff'): ?>
                        <span class="badge bg-warning text-dark px-1 py-0" style="font-size: 10px;" title="Manuel Devralındı (AI Duraklatıldı)"><i class="fas fa-user me-1"></i>Handoff</span>
                      <?php else: ?>
                        <span class="badge bg-purple-subtle text-purple border px-1 py-0" style="font-size: 10px; color: #7c3aed; background: #f5f3ff;" title="AI Otomatik Yanıtlıyor"><i class="fas fa-robot me-1"></i>AI</span>
                      <?php endif; ?>
                    <?php else: ?>
                      <span class="badge bg-purple text-white px-1 py-0" style="font-size: 10px; background: #7c3aed;">Copilot</span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="p-4 text-center text-muted">
            <i class="fas fa-inbox fa-2x mb-2 text-muted opacity-50"></i>
            <p class="small mb-0">Henüz mesaj kaydı bulunmuyor.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Right Column: Active Conversation Pane -->
    <div class="chat-main flex-fill d-flex flex-column bg-light" style="height: 100%; position: relative;">
      
      <!-- Thread Header -->
      <div class="chat-header px-3 py-2 bg-white border-bottom d-flex align-items-center justify-content-between shadow-xs" id="chat-header">
        <div class="d-flex align-items-center gap-3">
          <div class="position-relative flex-shrink-0" id="header-avatar-container">
            <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-xs" 
                 id="header-avatar"
                 style="width: 42px; height: 42px; background: <?= e($initial_thread['avatar_color'] ?? '#6366f1') ?>; font-size: 18px;">
              <i class="<?= e($initial_thread['avatar_icon'] ?? 'fas fa-robot') ?>" id="header-avatar-icon"></i>
            </div>
            <span class="position-absolute bottom-0 end-0 badge p-1 rounded-circle border border-white shadow-xs" style="background: #fff; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; font-size: 10px;">
              <i class="<?= e($initial_thread['channel_icon'] ?? 'fas fa-robot text-purple') ?>" id="header-channel-icon"></i>
            </span>
          </div>

          <div>
            <div class="d-flex align-items-center gap-2">
              <h6 class="mb-0 fw-bold text-dark" id="header-title"><?= e($initial_thread['name'] ?? 'BooKi AI Asistan') ?></h6>
              <span class="badge <?= e($initial_thread['badge_bg'] ?? 'bg-purple text-white') ?> px-2 py-0 rounded-pill" id="header-channel-badge" style="font-size: 10.5px;">
                <?= e($initial_thread['channel_name'] ?? 'AI Copilot') ?>
              </span>
            </div>
            <div class="text-muted small d-flex align-items-center gap-2 mt-0" style="font-size: 11.5px;">
              <span id="header-subtitle"><?= e($initial_thread['subtitle'] ?? '7/24 Akıllı Yönetim & Handoff Yöneticisi') ?></span>
              <span class="text-muted">•</span>
              <span id="header-status-indicator" class="text-success"><i class="fas fa-circle" style="font-size: 8px;"></i> Çevrimiçi</span>
            </div>
          </div>
        </div>

        <!-- Handoff & Controls -->
        <div class="d-flex align-items-center gap-2" id="header-actions">
          <!-- Handoff Action Container -->
          <div id="handoff-control-container">
            <?php if (!empty($initial_thread) && $initial_thread['channel'] !== 'ai'): ?>
              <?php if ($initial_thread['handoff_status'] === 'human_handoff'): ?>
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-warning text-dark border px-2 py-1"><i class="fas fa-user me-1"></i>Manuel Mod (AI Kapalı)</span>
                  <button type="button" class="btn btn-sm btn-outline-purple rounded-pill px-3" onclick="toggleHandoff('ai')" style="font-size: 11.5px; color:#7c3aed; border-color:#7c3aed;">
                    <i class="fas fa-robot me-1"></i> AI'ya Devret
                  </button>
                </div>
              <?php else: ?>
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-success-subtle text-success border px-2 py-1"><i class="fas fa-robot me-1"></i>AI Yanıtlıyor</span>
                  <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-3 text-dark" onclick="toggleHandoff('human')" style="font-size: 11.5px;">
                    <i class="fas fa-user me-1"></i> Manuel Devral (Handoff)
                  </button>
                </div>
              <?php endif; ?>
            <?php else: ?>
              <span class="badge bg-purple-subtle text-purple border px-3 py-1.5" style="color: #7c3aed; background:#f5f3ff;">
                <i class="fas fa-sparkles me-1 text-purple"></i> Sürekli Öğrenen AI
              </span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Messages Bubble Scroll Area -->
      <div class="chat-messages-container flex-fill overflow-auto p-3 d-flex flex-column gap-3" 
           id="messages-container"
           style="background-color: #efeae2; background-image: radial-gradient(#cbd5e1 1px, transparent 1px); background-size: 20px 20px;">
        
        <?php if (!empty($initial_messages)): ?>
          <?php foreach ($initial_messages as $m): ?>
            <?php 
              $is_out = ($m['direction'] === 'out');
              $is_ai_agent = ($m['sender'] === 'ai');
            ?>
            <div class="d-flex <?= $is_out ? 'justify-content-end' : 'justify-content-start' ?>">
              <div class="message-bubble rounded-3 p-2 px-3 shadow-xs position-relative <?= $is_out ? 'bg-success bg-opacity-10 border border-success-subtle text-dark' : 'bg-white border text-dark' ?>" 
                   style="max-width: 72%; min-width: 140px; <?= $is_ai_agent ? 'border-left: 3px solid #7c3aed !important;' : '' ?>">
                
                <!-- Bubble Meta Top -->
                <div class="d-flex justify-content-between align-items-center gap-2 mb-1" style="font-size: 11px;">
                  <span class="fw-bold <?= $is_out ? 'text-primary' : ($is_ai_agent ? 'text-purple' : 'text-secondary') ?>">
                    <?php if ($is_ai_agent): ?>
                      <i class="fas fa-robot me-1 text-purple"></i> BooKi AI
                    <?php else: ?>
                      <?= e($m['sender_name']) ?>
                    <?php endif; ?>
                  </span>
                  <span class="text-muted" style="font-size: 10px;">
                    <?= e($m['time']) ?>
                  </span>
                </div>

                <!-- Message Content -->
                <div class="message-text text-break" style="font-size: 13.5px; line-height: 1.45; white-space: pre-wrap;"><?= nl2br(htmlspecialchars($m['content'])) ?></div>

                <!-- Delivery Status -->
                <?php if ($is_out): ?>
                  <div class="text-end mt-1" style="font-size: 10px; line-height: 1;">
                    <i class="fas fa-check-double text-primary"></i>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="text-center my-auto p-4 text-muted">
            <i class="fas fa-comment-dots fa-3x mb-2 text-muted opacity-50"></i>
            <h6>Henüz Mesaj Yok</h6>
            <p class="small">Aşağıdaki mesaj alanını kullanarak ilk mesajı gönderebilirsiniz.</p>
          </div>
        <?php endif; ?>
      </div>

      <!-- Chat Composer Box (Bottom) -->
      <div class="chat-composer p-2 bg-white border-top shadow-sm">
        
        <!-- Smart Toolbar (AI Suggestion & Canned Responses) -->
        <div class="d-flex align-items-center justify-content-between px-2 pb-2 gap-2 flex-wrap">
          <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-purple rounded-pill px-3 py-1 shadow-xs" 
                    id="btn-ai-suggest"
                    onclick="getAiDraftSuggestion()" 
                    style="font-size: 11.5px; color:#7c3aed; border-color:#ddd6fe; background:#faf5ff;">
              <i class="fas fa-magic me-1 text-purple"></i> 🤖 AI Yanıtı Öner
            </button>
            
            <div class="dropdown">
              <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 dropdown-toggle" type="button" data-bs-toggle="dropdown" style="font-size: 11.5px;">
                <i class="fas fa-bolt me-1 text-warning"></i> Hızlı Şablonlar
              </button>
              <ul class="dropdown-menu shadow-sm border-0 rounded-3" style="font-size: 12.5px;">
                <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="insertTemplate('Merhaba! Size nasıl yardımcı olabilirim?')">👋 Standart Karşılama</a></li>
                <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="insertTemplate('Randevu oluşturmak için lütfen size en uygun gün ve saati iletebilir misiniz?')">📅 Randevu Talebi</a></li>
                <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="insertTemplate('Hizmet listemiz ve güncel fiyatlarımız için randevu sayfamızı ziyaret edebilirsiniz: ' + window.location.origin + '/booking')">💰 Fiyat ve Hizmet Listesi</a></li>
                <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="insertTemplate('Randevunuz başarıyla onaylanmıştır. Belirtilen saatte sizi ağırlamaktan mutluluk duyarız.')">✅ Randevu Onayı</a></li>
                <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="insertTemplate('Konu yetkili ekip arkadaşlarımıza aktarılmıştır. En kısa sürede sizinle iletişime geçeceğiz.')">👤 Temsilciye Aktarma</a></li>
              </ul>
            </div>
          </div>

          <div class="small text-muted" style="font-size: 11px;">
            <kbd class="bg-light text-muted border px-1">Enter</kbd> Gönder • <kbd class="bg-light text-muted border px-1">Shift+Enter</kbd> Yeni Satır
          </div>
        </div>

        <!-- Input Row -->
        <div class="d-flex align-items-end gap-2 px-1">
          <textarea class="form-control border rounded-3 p-2" 
                    id="chat-message-input" 
                    rows="2" 
                    placeholder="Mesajınızı yazınız..." 
                    style="resize: none; font-size: 13.5px;"
                    onkeydown="handleMessageKeydown(event)"></textarea>

          <button type="button" 
                  class="btn btn-primary rounded-circle d-flex align-items-center justify-content-center shadow-xs flex-shrink-0" 
                  id="btn-send-message" 
                  onclick="sendMessage()" 
                  style="width: 44px; height: 44px;">
            <i class="fas fa-paper-plane" id="send-icon"></i>
          </button>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- WhatsApp QR Code Modal -->
<div class="modal fade" id="whatsappQrModal" tabindex="-1" aria-labelledby="whatsappQrModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow-lg border-0 rounded-4">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="whatsappQrModalLabel">
          <i class="fab fa-whatsapp text-success fs-4"></i> WhatsApp Web QR Bağlantısı
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
      </div>
      <div class="modal-body text-center p-4">
        <div id="qr-loading-spinner" class="py-4">
          <div class="spinner-border text-success" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Yükleniyor...</span>
          </div>
          <p class="text-muted mt-3 mb-0">WhatsApp Köprüsü ile iletişim kuruluyor...</p>
        </div>
        
        <div id="qr-connected-box" class="d-none py-3">
          <div class="avatar-icon-circle bg-success bg-opacity-10 text-success p-3 rounded-circle d-inline-flex mb-3">
            <i class="fas fa-check-circle fs-1"></i>
          </div>
          <h5 class="fw-bold text-dark">WhatsApp Cihazınız Bağlı!</h5>
          <p class="text-muted small" id="qr-connected-name">Bağlı Cihaz: <?= e($bridge_session_name ?? 'BooKi Demo') ?></p>
          <div class="alert alert-success d-inline-block text-start py-2 px-3 mb-3" style="font-size: 12.5px;">
            <i class="fas fa-shield-alt me-1"></i> WhatsApp üzerinden gelen ve giden tüm mesajlar bu cihaz üzerinden doğrudan canlı akar.
          </div>
          <div>
            <button type="button" class="btn btn-outline-success btn-sm rounded-pill me-2" onclick="switchWhatsAppMode('unofficial')">
              <i class="fas fa-toggle-on me-1"></i> Bu Cihazı Aktif Çıkış Kanalı Yap
            </button>
          </div>
        </div>

        <div id="qr-code-box" class="d-none">
          <p class="text-muted small mb-3">
            1. Telefonunuzda <strong>WhatsApp</strong> uygulamasını açın.<br>
            2. <strong>Bağlı Cihazlar</strong> &gt; <strong>Cihaz Bağla</strong> seçeneğine dokunun.<br>
            3. Aşağıdaki QR kodu telefon kameranızla tarayın:
          </p>
          <div class="d-flex justify-content-center mb-3">
            <div id="qr-image-container" class="p-2 border rounded-3 bg-white shadow-sm" style="display: inline-block;">
              <!-- QR Image injected here -->
            </div>
          </div>
          <p class="text-muted small" style="font-size: 11.5px;">
            <i class="fas fa-info-circle me-1"></i> QR kod taranana kadar otomatik olarak yenilenir.
          </p>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0 justify-content-between">
        <button type="button" class="btn btn-sm btn-light border rounded-pill" data-bs-dismiss="modal">Kapat</button>
        <button type="button" class="btn btn-sm btn-primary rounded-pill" onclick="checkQrStatusNow()">
          <i class="fas fa-sync-alt me-1"></i> Durumu Yenile
        </button>
      </div>
    </div>
  </div>
</div>

<style>
.cursor-pointer { cursor: pointer; }
.thread-item { transition: background-color 0.15s ease; }
.thread-item:hover { background-color: #f8fafc; }
.active-thread { border-left: 3px solid #0d6efd !important; background-color: #eff6ff !important; }
.pinned-ai-thread { background: linear-gradient(to right, #fbf7ff, #ffffff); border-bottom: 2px solid #ede9fe !important; }
.pinned-ai-thread.active-thread { border-left: 3px solid #7c3aed !important; background: #f5f3ff !important; }
.text-purple { color: #7c3aed !important; }
.bg-purple { background-color: #7c3aed !important; }
.bg-purple-subtle { background-color: #f5f3ff !important; }
.message-bubble { word-wrap: break-word; }
.shadow-xs { box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
</style>

<script>
let currentChannel = '<?= addslashes($initial_thread['channel'] ?? 'ai') ?>';
let currentThreadId = '<?= addslashes($initial_thread['thread_id'] ?? 'ai_copilot') ?>';
let currentThreadKey = '<?= addslashes($initial_thread['id'] ?? 'ai_assistant') ?>';
let activeFilter = 'all';
let searchTimeout = null;

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    scrollToBottom();
    setupChannelFilterButtons();
});

function scrollToBottom() {
    const container = document.getElementById('messages-container');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
}

function setupChannelFilterButtons() {
    document.querySelectorAll('.channel-filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.channel-filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            activeFilter = this.getAttribute('data-channel');
            loadThreads();
        });
    });
}

function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        loadThreads();
    }, 300);
}

async function loadThreads() {
    const q = document.getElementById('thread-search-input').value.trim();
    const url = '<?= site_url('chat_portal/api_threads') ?>?channel=' + encodeURIComponent(activeFilter) + '&q=' + encodeURIComponent(q);

    try {
        const res = await fetch(url);
        const data = await res.json();
        if (data.status === 'success') {
            renderThreads(data.threads);
        }
    } catch(e) {
        console.error('Error loading threads:', e);
    }
}

function renderThreads(threads) {
    const container = document.getElementById('threads-container');
    if (!threads || threads.length === 0) {
        container.innerHTML = '<div class="p-4 text-center text-muted"><p class="small mb-0">Eşleşen konuşma bulunamadı.</p></div>';
        return;
    }

    let html = '';
    threads.forEach(t => {
        const isActive = (t.id === currentThreadKey);
        const isAi = (t.channel === 'ai');
        
        let handoffBadge = '';
        if (!isAi) {
            if (t.handoff_status === 'human_handoff') {
                handoffBadge = '<span class="badge bg-warning text-dark px-1 py-0" style="font-size:10px;"><i class="fas fa-user me-1"></i>Handoff</span>';
            } else {
                handoffBadge = '<span class="badge bg-purple-subtle text-purple border px-1 py-0" style="font-size:10px; color:#7c3aed; background:#f5f3ff;"><i class="fas fa-robot me-1"></i>AI</span>';
            }
        } else {
            handoffBadge = '<span class="badge bg-purple text-white px-1 py-0" style="font-size:10px; background:#7c3aed;">Copilot</span>';
        }

        html += `
            <div class="thread-item p-3 border-bottom d-flex align-items-start gap-2 cursor-pointer ${isActive ? 'active-thread bg-primary bg-opacity-10' : ''} ${isAi ? 'pinned-ai-thread' : ''}"
                 data-thread-key="${t.id}"
                 data-channel="${t.channel}"
                 data-thread-id="${t.thread_id}"
                 onclick="selectThread('${t.channel}', '${t.thread_id}', '${t.id}')">
              <div class="position-relative flex-shrink-0">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-xs" 
                     style="width: 44px; height: 44px; background: ${t.avatar_color}; font-size: 18px;">
                  <i class="${t.avatar_icon}"></i>
                </div>
                <span class="position-absolute bottom-0 end-0 badge p-1 rounded-circle border border-white shadow-xs" style="background: #fff; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; font-size: 10px;">
                  <i class="${t.channel_icon}"></i>
                </span>
              </div>
              <div class="flex-grow-1 min-w-0 ms-1">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="fw-bold text-dark text-truncate d-flex align-items-center gap-1" style="font-size: 13.5px;">
                    ${isAi ? '<i class="fas fa-thumbtack text-purple me-1" style="font-size: 11px;"></i>' : ''}
                    ${escapeHtml(t.name)}
                  </span>
                  <span class="text-muted small text-nowrap" style="font-size: 11px;">${escapeHtml(t.last_time)}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                  <p class="mb-0 text-muted text-truncate" style="font-size: 12px; max-width: 210px;">${escapeHtml(t.last_message)}</p>
                  <div class="d-flex align-items-center gap-1 flex-shrink-0">${handoffBadge}</div>
                </div>
              </div>
            </div>
        `;
    });
    container.innerHTML = html;
}

async function selectThread(channel, threadId, threadKey) {
    currentChannel = channel;
    currentThreadId = threadId;
    currentThreadKey = threadKey;

    // Highlight active thread in left bar
    document.querySelectorAll('.thread-item').forEach(el => el.classList.remove('active-thread', 'bg-primary', 'bg-opacity-10'));
    const activeEl = document.querySelector(`[data-thread-key="${threadKey}"]`);
    if (activeEl) {
        activeEl.classList.add('active-thread', 'bg-primary', 'bg-opacity-10');
    }

    // Load messages
    const url = '<?= site_url('chat_portal/api_messages') ?>?channel=' + encodeURIComponent(channel) + '&thread_id=' + encodeURIComponent(threadId);
    try {
        const res = await fetch(url);
        const data = await res.json();
        if (data.status === 'success') {
            renderMessages(data.messages);
            updateHeader(data);
        }
    } catch(e) {
        console.error('Error fetching messages:', e);
    }
}

function updateHeader(data) {
    const isAi = (data.channel === 'ai' || data.thread_id === 'ai_copilot');
    const handoffContainer = document.getElementById('handoff-control-container');

    if (isAi) {
        document.getElementById('header-title').innerText = 'BooKi AI Asistan';
        document.getElementById('header-subtitle').innerText = '7/24 Akıllı Yönetim & Handoff Yöneticisi';
        document.getElementById('header-channel-badge').className = 'badge bg-purple text-white px-2 py-0 rounded-pill';
        document.getElementById('header-channel-badge').innerText = 'AI Copilot';
        document.getElementById('header-avatar').style.background = 'linear-gradient(135deg, #6366f1 0%, #a855f7 100%)';
        document.getElementById('header-avatar-icon').className = 'fas fa-robot';
        document.getElementById('header-channel-icon').className = 'fas fa-robot text-purple';

        handoffContainer.innerHTML = '<span class="badge bg-purple-subtle text-purple border px-3 py-1.5" style="color: #7c3aed; background:#f5f3ff;"><i class="fas fa-sparkles me-1 text-purple"></i> Sürekli Öğrenen AI</span>';
    } else {
        const threadItem = document.querySelector(`[data-thread-key="${currentThreadKey}"]`);
        const name = threadItem ? threadItem.querySelector('.fw-bold').innerText.trim() : ('+' + data.thread_id);
        
        document.getElementById('header-title').innerText = name;
        document.getElementById('header-subtitle').innerText = (data.channel === 'whatsapp' ? '+' + data.thread_id : ucfirst(data.channel));
        
        const channelClasses = {
            'whatsapp': { badge: 'bg-success text-white', name: 'WhatsApp', color: '#25D366', icon: 'fab fa-whatsapp', chIcon: 'fab fa-whatsapp text-success' },
            'instagram': { badge: 'bg-danger text-white', name: 'Instagram', color: '#E1306C', icon: 'fab fa-instagram', chIcon: 'fab fa-instagram text-danger' },
            'telegram': { badge: 'bg-info text-white', name: 'Telegram', color: '#0088cc', icon: 'fab fa-telegram-plane', chIcon: 'fab fa-telegram-plane text-info' },
            'widget': { badge: 'bg-primary text-white', name: 'Web Widget', color: '#0d6efd', icon: 'fas fa-globe', chIcon: 'fas fa-globe text-primary' }
        };
        const cfg = channelClasses[data.channel] || channelClasses['whatsapp'];
        document.getElementById('header-channel-badge').className = 'badge ' + cfg.badge + ' px-2 py-0 rounded-pill';
        document.getElementById('header-channel-badge').innerText = cfg.name;
        document.getElementById('header-avatar').style.background = cfg.color;
        document.getElementById('header-avatar-icon').className = cfg.icon;
        document.getElementById('header-channel-icon').className = cfg.chIcon;

        if (data.handoff_active) {
            handoffContainer.innerHTML = `
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-warning text-dark border px-2 py-1"><i class="fas fa-user me-1"></i>Manuel Mod (AI Kapalı)</span>
                  <button type="button" class="btn btn-sm btn-outline-purple rounded-pill px-3" onclick="toggleHandoff('ai')" style="font-size:11.5px; color:#7c3aed; border-color:#7c3aed;">
                    <i class="fas fa-robot me-1"></i> AI'ya Devret
                  </button>
                </div>
            `;
        } else {
            handoffContainer.innerHTML = `
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-success-subtle text-success border px-2 py-1"><i class="fas fa-robot me-1"></i>AI Yanıtlıyor</span>
                  <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-3 text-dark" onclick="toggleHandoff('human')" style="font-size:11.5px;">
                    <i class="fas fa-user me-1"></i> Manuel Devral (Handoff)
                  </button>
                </div>
            `;
        }
    }
}

function renderMessages(messages) {
    const container = document.getElementById('messages-container');
    if (!messages || messages.length === 0) {
        container.innerHTML = `
            <div class="text-center my-auto p-4 text-muted">
              <i class="fas fa-comment-dots fa-3x mb-2 text-muted opacity-50"></i>
              <h6>Henüz Mesaj Yok</h6>
              <p class="small">Aşağıdaki mesaj alanını kullanarak konuşmayı başlatabilirsiniz.</p>
            </div>
        `;
        return;
    }

    let html = '';
    messages.forEach(m => {
        const isOut = (m.direction === 'out');
        const isAi = (m.sender === 'ai');

        html += `
            <div class="d-flex ${isOut ? 'justify-content-end' : 'justify-content-start'}">
              <div class="message-bubble rounded-3 p-2 px-3 shadow-xs position-relative ${isOut ? 'bg-success bg-opacity-10 border border-success-subtle text-dark' : 'bg-white border text-dark'}" 
                   style="max-width: 72%; min-width: 140px; ${isAi ? 'border-left: 3px solid #7c3aed !important;' : ''}">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-1" style="font-size: 11px;">
                  <span class="fw-bold ${isOut ? 'text-primary' : (isAi ? 'text-purple' : 'text-secondary')}">
                    ${isAi ? '<i class="fas fa-robot me-1 text-purple"></i> BooKi AI' : escapeHtml(m.sender_name)}
                  </span>
                  <span class="text-muted" style="font-size: 10px;">${escapeHtml(m.time)}</span>
                </div>
                <div class="message-text text-break" style="font-size: 13.5px; line-height: 1.45; white-space: pre-wrap;">${escapeHtml(m.content)}</div>
                ${isOut ? '<div class="text-end mt-1" style="font-size: 10px; line-height: 1;"><i class="fas fa-check-double text-primary"></i></div>' : ''}
              </div>
            </div>
        `;
    });
    container.innerHTML = html;
    scrollToBottom();
}

function handleMessageKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
}

async function sendMessage() {
    const input = document.getElementById('chat-message-input');
    const text = input.value.trim();
    if (!text) return;

    const btn = document.getElementById('btn-send-message');
    const sendIcon = document.getElementById('send-icon');
    
    // Temporarily add bubble to UI immediately
    const container = document.getElementById('messages-container');
    const now = new Date();
    const timeStr = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
    
    const tempHtml = `
        <div class="d-flex justify-content-end" id="temp-pending-msg">
          <div class="message-bubble rounded-3 p-2 px-3 shadow-xs bg-success bg-opacity-10 border border-success-subtle text-dark" style="max-width: 72%; min-width: 140px;">
            <div class="d-flex justify-content-between align-items-center gap-2 mb-1" style="font-size: 11px;">
              <span class="fw-bold text-primary">Siz (Yönetici)</span>
              <span class="text-muted" style="font-size: 10px;">${timeStr}</span>
            </div>
            <div class="message-text text-break" style="font-size: 13.5px; line-height: 1.45; white-space: pre-wrap;">${escapeHtml(text)}</div>
            <div class="text-end mt-1" style="font-size: 10px;"><i class="fas fa-clock text-muted"></i></div>
          </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', tempHtml);
    scrollToBottom();
    input.value = '';

    btn.disabled = true;
    sendIcon.className = 'fas fa-spinner fa-spin';

    try {
        const csrfToken = '<?= vars('csrf_token') ?? '' ?>';
        const res = await fetch('<?= site_url('chat_portal/api_send') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                'channel': currentChannel,
                'thread_id': currentThreadId,
                'message': text,
                'csrf_token': csrfToken
            })
        });

        const data = await res.json();
        btn.disabled = false;
        sendIcon.className = 'fas fa-paper-plane';

        if (data.status === 'success') {
            const temp = document.getElementById('temp-pending-msg');
            if (temp) {
                const clock = temp.querySelector('.fa-clock');
                if (clock) clock.className = 'fas fa-check-double text-primary';
                temp.removeAttribute('id');
            }

            // If replying to AI Copilot, append AI response
            if (data.data && data.data.reply) {
                const aiBubble = `
                    <div class="d-flex justify-content-start">
                      <div class="message-bubble rounded-3 p-2 px-3 shadow-xs bg-white border text-dark" style="max-width: 72%; min-width: 140px; border-left: 3px solid #7c3aed !important;">
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-1" style="font-size: 11px;">
                          <span class="fw-bold text-purple"><i class="fas fa-robot me-1 text-purple"></i> BooKi AI</span>
                          <span class="text-muted" style="font-size: 10px;">${data.data.time || timeStr}</span>
                        </div>
                        <div class="message-text text-break" style="font-size: 13.5px; line-height: 1.45; white-space: pre-wrap;">${escapeHtml(data.data.reply)}</div>
                      </div>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', aiBubble);
                scrollToBottom();
            }

            // Update thread list preview
            loadThreads();
        } else {
            alert('Hata: ' + (data.message || 'Mesaj gönderilemedi.'));
        }
    } catch(e) {
        btn.disabled = false;
        sendIcon.className = 'fas fa-paper-plane';
        alert('Bağlantı hatası oluştu.');
    }
}

async function toggleHandoff(targetMode) {
    const csrfToken = '<?= vars('csrf_token') ?? '' ?>';
    try {
        const res = await fetch('<?= site_url('chat_portal/api_toggle_handoff') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                'channel': currentChannel,
                'thread_id': currentThreadId,
                'mode': targetMode,
                'csrf_token': csrfToken
            })
        });
        const data = await res.json();
        if (data.status === 'success') {
            selectThread(currentChannel, currentThreadId, currentThreadKey);
            loadThreads();
        } else {
            alert(data.message || 'Handoff değiştirilemedi.');
        }
    } catch(e) {
        console.error(e);
    }
}

async function getAiDraftSuggestion() {
    const btn = document.getElementById('btn-ai-suggest');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> AI Düşünüyor...';

    const csrfToken = '<?= vars('csrf_token') ?? '' ?>';
    try {
        const res = await fetch('<?= site_url('chat_portal/api_suggest') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                'channel': currentChannel,
                'thread_id': currentThreadId,
                'csrf_token': csrfToken
            })
        });
        const data = await res.json();
        btn.disabled = false;
        btn.innerHTML = originalText;

        if (data.status === 'success' && data.suggestion) {
            const input = document.getElementById('chat-message-input');
            input.value = data.suggestion;
            input.focus();
        } else {
            alert('Yanıt önerisi üretilemedi.');
        }
    } catch(e) {
        btn.disabled = false;
        btn.innerHTML = originalText;
        console.error(e);
    }
}

function insertTemplate(text) {
    const input = document.getElementById('chat-message-input');
    input.value = text;
    input.focus();
}

function refreshPortal() {
    const icon = document.getElementById('refresh-icon');
    if (icon) icon.classList.add('fa-spin');
    loadThreads().then(() => {
        selectThread(currentChannel, currentThreadId, currentThreadKey).then(() => {
            if (icon) icon.classList.remove('fa-spin');
        });
    });
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function ucfirst(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// -------------------------------------------------------------
// WhatsApp Dual-Mode (QR Web Bridge & Meta Cloud) Handlers
// -------------------------------------------------------------
let qrPollInterval = null;

function switchWhatsAppMode(mode) {
    const fd = new FormData();
    fd.append('mode', mode);
    fd.append('csrf_token', vars('csrf_token') || '');

    fetch(vars('chat_routes').switch_wa_mode, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            window.location.reload();
        } else {
            alert('WhatsApp modu değiştirilemedi: ' + (data.message || 'Hata'));
        }
    })
    .catch(err => alert('Ağ hatası: ' + err.message));
}

function openQrModal() {
    const modalEl = document.getElementById('whatsappQrModal');
    if (!modalEl) return;
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
    checkQrStatusNow();
}

function checkQrStatusNow() {
    const spinner = document.getElementById('qr-loading-spinner');
    const connectedBox = document.getElementById('qr-connected-box');
    const codeBox = document.getElementById('qr-code-box');

    if (!spinner || !connectedBox || !codeBox) return;

    spinner.classList.remove('d-none');
    connectedBox.classList.add('d-none');
    codeBox.classList.add('d-none');

    fetch(vars('chat_routes').qr_status, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        spinner.classList.add('d-none');
        const session = data.session || {};
        if (session.status === 'connected') {
            connectedBox.classList.remove('d-none');
            const nameEl = document.getElementById('qr-connected-name');
            if (nameEl) nameEl.textContent = 'Bağlı Cihaz: ' + (session.name || 'BooKi Demo');
            if (qrPollInterval) { clearInterval(qrPollInterval); qrPollInterval = null; }
        } else if (session.qr) {
            codeBox.classList.remove('d-none');
            renderQrCode(session.qr);
            startQrPolling();
        } else {
            startQrSession();
        }
    })
    .catch(err => {
        spinner.classList.add('d-none');
        console.error('QR durumu hatası:', err);
    });
}

function startQrSession() {
    const fd = new FormData();
    fd.append('csrf_token', vars('csrf_token') || '');

    fetch(vars('chat_routes').qr_start, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(r => r.json())
    .then(() => {
        setTimeout(checkQrStatusNow, 2000);
    })
    .catch(err => console.error(err));
}

function renderQrCode(qrString) {
    const container = document.getElementById('qr-image-container');
    if (!container) return;
    container.innerHTML = `<img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=${encodeURIComponent(qrString)}" alt="WhatsApp QR" class="img-fluid rounded" style="width:220px;height:220px;" />`;
}

function startQrPolling() {
    if (qrPollInterval) return;
    qrPollInterval = setInterval(() => {
        fetch(vars('chat_routes').qr_status, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            if (data.session && data.session.status === 'connected') {
                clearInterval(qrPollInterval);
                qrPollInterval = null;
                checkQrStatusNow();
                setTimeout(() => window.location.reload(), 1200);
            }
        });
    }, 3000);
}
</script>

<?php end_section('content'); ?>
