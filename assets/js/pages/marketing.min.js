/**
 * Marketing page JavaScript (BooKi, Dalga 3 / Faz 3.3 + Enterprise Suite).
 *
 * Handles:
 * 1. Segments CRUD + count recalculations
 * 2. Campaigns CRUD + pause/resume lifecycle + batched broadcast sends
 * 3. Google & Meta marketing integrations (Google Ads, GA4, GTM, Search Console, Trends, Meta Ads, Pixel, Status)
 * 4. Attribution Intelligence (ad click tracking, click timestamps, heatmap telemetry, customer identity extraction)
 * 5. Landing Pages CRUD + conversion metrics
 */

'use strict';

App.Pages.Marketing = (function () {
  const $segmentTable = $('#segments-table');
  const $segmentTbody = $segmentTable.find('tbody');
  const $campaignTable = $('#campaigns-table');
  const $campaignTbody = $campaignTable.find('tbody');
  const $landingPagesTable = $('#landing-pages-table');
  const $landingPagesTbody = $landingPagesTable.find('tbody');
  const $attributionsTable = $('#attributions-table');
  const $attributionsTbody = $attributionsTable.find('tbody');

  const $segmentModal = $('#segment-modal');
  const $campaignModal = $('#campaign-modal');
  const $sendModal = $('#send-modal');
  const $landingPageModal = $('#landing-page-modal');
  const $attributionModal = $('#attribution-modal');

  let segments = [];
  let campaigns = [];
  let landingPages = [];
  let attributions = [];
  let integrations = {};
  const initials = (window.scriptVars && window.scriptVars.initials) || {};

  let editingSegmentId = null;
  let editingCampaignId = null;
  let editingLandingId = null;
  let sendingCampaignId = null;
  let sendLoopActive = false;

  const TYPE_LABELS = {
    vip: 'VIP',
    inactive: 'Pasif',
    birthday: 'Doğum günü',
    all: 'Tümü',
    custom: 'Özel',
  };

  const CHANNEL_LABELS = {
    email: 'E-posta',
    sms: 'SMS',
    whatsapp: 'WhatsApp',
    telegram: 'Telegram',
    google_ads: 'Google Ads',
    meta_ads: 'Meta Ads',
  };

  const STATUS_BADGES = {
    draft: 'secondary',
    queued: 'info',
    sending: 'warning',
    sent: 'success',
    failed: 'danger',
    active: 'success',
    paused: 'warning',
  };

  function init() {
    addEventListeners();
    segments = (window.scriptVars.segments || []).slice();
    campaigns = (window.scriptVars.campaigns || []).slice();
    landingPages = (window.scriptVars.landing_pages || []).slice();
    attributions = (window.scriptVars.attributions || []).slice();
    integrations = Object.assign({}, window.scriptVars.integrations || {});

    renderSegments();
    renderCampaigns();
    renderIntegrations();
    renderAttributions();
    renderLandingPages();
    loadTrends();
  }

  function addEventListeners() {
    // Segments
    $('#add-segment').on('click', onAddSegmentClick);
    $('#save-segment').on('click', onSaveSegmentClick);
    $('#refresh-all-segments').on('click', onRefreshAllSegmentsClick);
    $('#segment-type').on('change', onSegmentTypeChange);
    $segmentTbody.on('click', 'button.edit-segment-btn', onEditSegmentClick);
    $segmentTbody.on('click', 'button.delete-segment-btn', onDeleteSegmentClick);
    $segmentTbody.on('click', 'button.refresh-segment-btn', onRefreshSegmentClick);

    // Campaigns
    $('#add-campaign').on('click', onAddCampaignClick);
    $('#save-campaign').on('click', onSaveCampaignClick);
    $('#campaign-type').on('change', onCampaignTypeChange);
    $campaignTbody.on('click', 'button.edit-campaign-btn', onEditCampaignClick);
    $campaignTbody.on('click', 'button.delete-campaign-btn', onDeleteCampaignClick);
    $campaignTbody.on('click', 'button.prepare-campaign-btn', onPrepareCampaignClick);
    $campaignTbody.on('click', 'button.send-campaign-btn', onSendCampaignClick);
    $campaignTbody.on('click', 'button.pause-campaign-btn', onPauseCampaignClick);
    $campaignTbody.on('click', 'button.resume-campaign-btn', onResumeCampaignClick);
    $('#confirm-send').on('click', onConfirmSendClick);

    // Integrations
    $('#save-integrations').on('click', onSaveIntegrationsClick);
    $('#sync-meta-status-btn').on('click', onSyncMetaStatusClick);
    $('#refresh-trends-btn').on('click', loadTrends);

    // Attributions
    $('#refresh-attributions-btn').on('click', loadAttributions);
    $attributionsTbody.on('click', 'button.extract-attr-btn', onExtractAttributionClick);
    $attributionsTbody.on('click', 'button.view-attr-btn', onViewAttributionClick);

    // Landing Pages
    $('#add-landing-page').on('click', onAddLandingPageClick);
    $('#save-landing-page').on('click', onSaveLandingPageClick);
    $landingPagesTbody.on('click', 'button.edit-landing-btn', onEditLandingPageClick);
    $landingPagesTbody.on('click', 'button.delete-landing-btn', onDeleteLandingPageClick);
  }

  /* ------------------------------ segments ------------------------------ */

  function onAddSegmentClick() {
    editingSegmentId = null;
    $('#segment-modal-title').text('Yeni Segment');
    $('#segment-name').val('');
    $('#segment-type').val('vip');
    $('#rule-min-appointments').val(5);
    $('#rule-inactive-days').val(60);
    $('#rule-days-ahead').val(14);
    $('#rule-customer-ids').val('');
    $('#segment-enabled').prop('checked', true);
    onSegmentTypeChange();
    $segmentModal.modal('show');
  }

  function onEditSegmentClick() {
    const id = parseInt($(this).data('id'), 10);
    const segment = segments.find(function (s) {
      return parseInt(s.id, 10) === id;
    });

    if (!segment) {
      return;
    }

    editingSegmentId = id;
    $('#segment-modal-title').text('Segmenti Düzenle');
    $('#segment-name').val(segment.name);
    $('#segment-type').val(segment.type);
    $('#segment-enabled').prop('checked', segment.enabled === 1);

    let rules = {};
    try {
      rules = JSON.parse(segment.rules || '{}');
    } catch (e) {
      rules = {};
    }

    $('#rule-min-appointments').val(rules.min_appointments || 5);
    $('#rule-inactive-days').val(rules.inactive_days || 60);
    $('#rule-days-ahead').val(rules.days_ahead || 14);
    $('#rule-customer-ids').val((rules.customer_ids || []).join(', '));
    onSegmentTypeChange();
    $segmentModal.modal('show');
  }

  function onSegmentTypeChange() {
    const type = $('#segment-type').val();
    $('#segment-rule-vip').toggleClass('d-none', type !== 'vip');
    $('#segment-rule-inactive').toggleClass('d-none', type !== 'inactive');
    $('#segment-rule-birthday').toggleClass('d-none', type !== 'birthday');
    $('#segment-rule-custom').toggleClass('d-none', type !== 'custom');
  }

  function onSaveSegmentClick() {
    const name = $('#segment-name').val().trim();
    const type = $('#segment-type').val();

    if (!name) {
      App.Utils.message('Segment adı zorunludur.', 'warning');
      return;
    }

    const rules = {};

    if (type === 'vip') {
      rules.min_appointments = parseInt($('#rule-min-appointments').val(), 10) || 5;
    }

    if (type === 'inactive') {
      rules.inactive_days = parseInt($('#rule-inactive-days').val(), 10) || 60;
    }

    if (type === 'birthday') {
      rules.days_ahead = parseInt($('#rule-days-ahead').val(), 10) || 14;
    }

    if (type === 'custom') {
      rules.customer_ids = $('#rule-customer-ids')
        .val()
        .split(',')
        .map(function (v) {
          return parseInt(v.trim(), 10);
        })
        .filter(function (v) {
          return Number.isInteger(v) && v > 0;
        });
    }

    const payload = {
      name: name,
      type: type,
      rules: rules,
      enabled: $('#segment-enabled').is(':checked') ? 1 : 0,
    };

    if (editingSegmentId) {
      payload.id = editingSegmentId;
    }

    $.ajax({
      url: App.Utils.ajaxUrl(editingSegmentId ? 'marketing/update_segment' : 'marketing/create_segment'),
      type: 'POST',
      dataType: 'json',
      data: payload,
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        if (response.success !== false) {
          $segmentModal.modal('hide');
          App.Utils.message(editingSegmentId ? 'Segment güncellendi.' : 'Segment oluşturuldu.', 'success');
          loadSegments();
        }
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  function onDeleteSegmentClick() {
    const id = $(this).data('id');
    const segment = segments.find(function (s) {
      return parseInt(s.id, 10) === id;
    });

    if (!confirm('«' + (segment ? segment.name : '') + '» segmentini silmek istediğinize emin misiniz?')) {
      return;
    }

    $.ajax({
      url: App.Utils.ajaxUrl('marketing/delete_segment'),
      type: 'POST',
      dataType: 'json',
      data: { id: id },
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        if (response.success !== false) {
          App.Utils.message('Segment silindi.', 'success');
          loadSegments();
        }
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  function onRefreshSegmentClick() {
    const id = $(this).data('id');

    $.ajax({
      url: App.Utils.ajaxUrl('marketing/refresh_segment'),
      type: 'POST',
      dataType: 'json',
      data: { id: id },
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        if (response.success !== false) {
          App.Utils.message('Segment güncellendi: ' + response.member_count + ' üye.', 'success');
          loadSegments();
        }
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  function onRefreshAllSegmentsClick() {
    $.ajax({
      url: App.Utils.ajaxUrl('marketing/refresh_all_segments'),
      type: 'POST',
      dataType: 'json',
      data: {},
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        if (response.success !== false) {
          App.Utils.message('Tüm segmentler güncellendi.', 'success');
          loadSegments();
        }
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  function renderSegments() {
    $segmentTbody.empty();

    if (!segments.length) {
      $segmentTbody.append('<tr><td colspan="6" class="text-center text-muted">Henüz segment yok.</td></tr>');
      return;
    }

    segments.forEach(function (segment) {
      let rulesLabel = '-';

      try {
        const rules = JSON.parse(segment.rules || '{}');

        if (segment.type === 'vip') {
          rulesLabel = '≥ ' + (rules.min_appointments || 5) + ' randevu';
        } else if (segment.type === 'inactive') {
          rulesLabel = (rules.inactive_days || 60) + ' gün pasif';
        } else if (segment.type === 'birthday') {
          rulesLabel = (rules.days_ahead || 14) + ' gün içinde';
        } else if (segment.type === 'custom') {
          rulesLabel = (rules.customer_ids || []).length + ' müşteri ID';
        }
      } catch (e) {
        rulesLabel = '-';
      }

      const $row = $(
        '<tr>' +
          '<td>' + ($('<div>').text(segment.name).html()) + '</td>' +
          '<td><span class="badge bg-info">' + (TYPE_LABELS[segment.type] || segment.type) + '</span></td>' +
          '<td>' + rulesLabel + '</td>' +
          '<td class="text-center">' +
            (Number.isInteger(segment.member_count) ? segment.member_count : 0) +
            ' <button class="btn btn-sm btn-outline-secondary refresh-segment-btn" data-id="' + segment.id + '" title="Yeniden hesapla"><i class="fas fa-sync-alt"></i></button>' +
          '</td>' +
          '<td class="text-center">' +
            '<span class="badge bg-' + (segment.enabled === 1 ? 'success' : 'secondary') + '">' +
            (segment.enabled === 1 ? 'Aktif' : 'Pasif') +
            '</span></td>' +
          '<td>' +
            (initials.can_edit
              ? '<button class="btn btn-sm btn-outline-primary edit-segment-btn" data-id="' + segment.id + '"><i class="fas fa-edit"></i></button> '
              : '') +
            (initials.can_delete
              ? '<button class="btn btn-sm btn-outline-danger delete-segment-btn" data-id="' + segment.id + '"><i class="fas fa-trash"></i></button>'
              : '') +
          '</td>' +
          '</tr>',
      );

      $segmentTbody.append($row);
    });
  }

  /* ------------------------------ campaigns ----------------------------- */

  function onAddCampaignClick() {
    editingCampaignId = null;
    $('#campaign-modal-title').text('Yeni Kampanya');
    $('#campaign-name').val('');
    $('#campaign-type').val('broadcast');
    $('#campaign-segment').empty().append('<option value="">Segment seçin</option>');

    segments.forEach(function (segment) {
      if (segment.enabled === 1) {
        $('#campaign-segment').append('<option value="' + segment.id + '">' + segment.name + '</option>');
      }
    });

    $('#campaign-channel').val('email');
    $('#campaign-budget').val('');
    $('#campaign-target-url').val('');
    $('#campaign-subject').val('');
    $('#campaign-message').val('');
    onCampaignTypeChange();
    $campaignModal.modal('show');
  }

  function onCampaignTypeChange() {
    const type = $('#campaign-type').val();
    if (type === 'google_ads') {
      $('#campaign-channel').val('google_ads');
      $('#campaign-subject-group').addClass('d-none');
    } else if (type === 'meta_ads') {
      $('#campaign-channel').val('meta_ads');
      $('#campaign-subject-group').addClass('d-none');
    } else {
      $('#campaign-channel').val('email');
      $('#campaign-subject-group').removeClass('d-none');
    }
  }

  function onEditCampaignClick() {
    const id = parseInt($(this).data('id'), 10);
    const campaign = campaigns.find(function (c) {
      return parseInt(c.id, 10) === id;
    });

    if (!campaign) {
      return;
    }

    editingCampaignId = id;
    $('#campaign-modal-title').text('Kampanyayı Düzenle');
    $('#campaign-name').val(campaign.name);
    $('#campaign-type').val(campaign.campaign_type || 'broadcast');
    $('#campaign-segment').empty().append('<option value="">Segment seçin</option>');

    segments.forEach(function (segment) {
      $('#campaign-segment').append(
        '<option value="' + segment.id + '"' + (parseInt(segment.id, 10) === campaign.segment_id ? ' selected' : '') + '>' + segment.name + '</option>',
      );
    });

    $('#campaign-channel').val(campaign.channel);
    $('#campaign-budget').val(campaign.budget || '');
    $('#campaign-target-url').val(campaign.target_url || '');
    $('#campaign-subject').val(campaign.subject || '');
    $('#campaign-message').val(campaign.message || '');
    onCampaignTypeChange();
    $campaignModal.modal('show');
  }

  function onSaveCampaignClick() {
    const name = $('#campaign-name').val().trim();
    const segmentId = $('#campaign-segment').val();
    const channel = $('#campaign-channel').val();
    const message = $('#campaign-message').val().trim();
    const type = $('#campaign-type').val();

    if (!name) {
      App.Utils.message('Kampanya adı zorunludur.', 'warning');
      return;
    }

    if (type === 'broadcast' && (!segmentId || !message)) {
      App.Utils.message('Segment ve mesaj zorunludur.', 'warning');
      return;
    }

    const payload = {
      name: name,
      segment_id: segmentId ? parseInt(segmentId, 10) : 0,
      channel: channel,
      campaign_type: type,
      budget: $('#campaign-budget').val().trim(),
      target_url: $('#campaign-target-url').val().trim(),
      subject: $('#campaign-subject').val().trim(),
      message: message,
    };

    if (editingCampaignId) {
      payload.id = editingCampaignId;
    }

    $.ajax({
      url: App.Utils.ajaxUrl(editingCampaignId ? 'marketing/update_campaign' : 'marketing/create_campaign'),
      type: 'POST',
      dataType: 'json',
      data: payload,
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        if (response.success !== false) {
          $campaignModal.modal('hide');
          App.Utils.message(editingCampaignId ? 'Kampanya güncellendi.' : 'Kampanya oluşturuldu.', 'success');
          loadCampaigns();
        }
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  function onPauseCampaignClick() {
    const id = $(this).data('id');
    $.ajax({
      url: App.Utils.ajaxUrl('marketing/pause_campaign'),
      type: 'POST',
      dataType: 'json',
      data: { id: id },
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        if (response.success !== false) {
          App.Utils.message('Kampanya durduruldu.', 'info');
          loadCampaigns();
        }
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  function onResumeCampaignClick() {
    const id = $(this).data('id');
    $.ajax({
      url: App.Utils.ajaxUrl('marketing/resume_campaign'),
      type: 'POST',
      dataType: 'json',
      data: { id: id },
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        if (response.success !== false) {
          App.Utils.message('Kampanya devam ettiriliyor.', 'success');
          loadCampaigns();
        }
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  function onDeleteCampaignClick() {
    const id = $(this).data('id');
    const campaign = campaigns.find(function (c) {
      return parseInt(c.id, 10) === id;
    });

    if (!confirm('«' + (campaign ? campaign.name : '') + '» kampanyasını silmek istediğinize emin misiniz?')) {
      return;
    }

    $.ajax({
      url: App.Utils.ajaxUrl('marketing/delete_campaign'),
      type: 'POST',
      dataType: 'json',
      data: { id: id },
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        if (response.success !== false) {
          App.Utils.message('Kampanya silindi.', 'success');
          loadCampaigns();
        }
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  function onPrepareCampaignClick() {
    const id = $(this).data('id');
    const campaign = campaigns.find(function (c) {
      return parseInt(c.id, 10) === id;
    });

    if (campaign && campaign.status !== 'draft' && campaign.status !== 'failed') {
      App.Utils.message('Bu kampanya zaten hazırlanmış.', 'warning');
      return;
    }

    if (!confirm('Alıcı listesini hedef segmentten hazırlamak istediğinize emin misiniz?')) {
      return;
    }

    $.ajax({
      url: App.Utils.ajaxUrl('marketing/prepare_campaign'),
      type: 'POST',
      dataType: 'json',
      data: { id: id },
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        if (response.success !== false) {
          App.Utils.message('Alıcı listesi hazırlandı: ' + response.recipients + ' alıcı.', 'success');
          loadCampaigns();
        }
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  function onSendCampaignClick() {
    const id = parseInt($(this).data('id'), 10);
    const campaign = campaigns.find(function (c) {
      return parseInt(c.id, 10) === id;
    });

    if (!campaign) {
      return;
    }

    if (campaign.status === 'draft') {
      if (!confirm('Kampanya henüz hazırlanmadı. Önce alıcı listesi hazırlanacak. Devam edilsin mi?')) {
        return;
      }

      prepareThenSend(id);
      return;
    }

    if (campaign.status === 'sent') {
      App.Utils.message('Bu kampanya zaten tamamlanmış.', 'warning');
      return;
    }

    openSendModal(id);
  }

  function prepareThenSend(campaignId) {
    $.ajax({
      url: App.Utils.ajaxUrl('marketing/prepare_campaign'),
      type: 'POST',
      dataType: 'json',
      data: { id: campaignId },
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        if (response.success !== false) {
          loadCampaigns();
          openSendModal(campaignId);
        }
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  function openSendModal(campaignId) {
    sendingCampaignId = campaignId;
    sendLoopActive = false;
    $('#send-progress-container').addClass('d-none');
    $('#send-progress-bar').css('width', '0%').text('0%');
    $('#send-status').text('Gönderim başlatılıyor...');
    $sendModal.modal('show');
  }

  function onConfirmSendClick() {
    if (sendLoopActive) {
      return;
    }

    sendLoopActive = true;
    $('#send-progress-container').removeClass('d-none');
    $('#confirm-send').prop('disabled', true);
    sendNextBatch();
  }

  function sendNextBatch() {
    $.ajax({
      url: App.Utils.ajaxUrl('marketing/send_campaign'),
      type: 'POST',
      dataType: 'json',
      data: { id: sendingCampaignId, limit: 50 },
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        if (response.success === false) {
          sendLoopActive = false;
          $('#confirm-send').prop('disabled', false);
          App.Utils.ajaxErrorMsg(response);
          return;
        }

        const stats = response.stats || {};
        const total = stats.total || 0;
        const sent = stats.sent || 0;
        const failed = stats.failed || 0;
        const done = sent + failed;
        const pct = total > 0 ? Math.round((done / total) * 100) : 100;

        $('#send-progress-bar').css('width', pct + '%').text(pct + '%');
        $('#send-status').text(
          'Toplam ' + total + ' | Gönderilen: ' + sent + ' | Başarısız: ' + failed + (total > 0 ? ' | ' + pct + '%' : ''),
        );

        if (response.status === 'sending' || response.status === 'queued') {
          setTimeout(sendNextBatch, 250);
        } else {
          sendLoopActive = false;
          $('#confirm-send').prop('disabled', false);
          App.Utils.message('Gönderim tamamlandı.', response.status === 'failed' ? 'warning' : 'success');
          setTimeout(function () {
            $sendModal.modal('hide');
            loadCampaigns();
          }, 400);
        }
      })
      .fail(function (jqxhr) {
        sendLoopActive = false;
        $('#confirm-send').prop('disabled', false);
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  function renderCampaigns() {
    $campaignTbody.empty();

    if (!campaigns.length) {
      $campaignTbody.append('<tr><td colspan="9" class="text-center text-muted">Henüz kampanya yok.</td></tr>');
      return;
    }

    campaigns.forEach(function (campaign) {
      const segment = segments.find(function (s) {
        return parseInt(s.id, 10) === campaign.segment_id;
      });

      const isEditable = campaign.status === 'draft' || campaign.status === 'failed' || campaign.status === 'paused' || campaign.status === 'active';
      const isPaused = campaign.status === 'paused';
      const isActive = campaign.status === 'active';

      const budgetStr = campaign.budget ? parseFloat(campaign.budget).toLocaleString('tr-TR', { minimumFractionDigits: 2 }) + ' ₺' : '-';

      const $row = $(
        '<tr>' +
          '<td>' + ($('<div>').text(campaign.name).html()) + '</td>' +
          '<td>' + (segment ? $('<div>').text(segment.name).html() : '<span class="badge bg-light text-dark">Genel / Ad</span>') + '</td>' +
          '<td>' + (CHANNEL_LABELS[campaign.channel] || campaign.channel) + '</td>' +
          '<td class="text-center">' + budgetStr + '</td>' +
          '<td class="text-center">' + (campaign.total_recipients || 0) + '</td>' +
          '<td class="text-center">' + (campaign.sent_count || 0) + '</td>' +
          '<td class="text-center">' + (campaign.failed_count || 0) + '</td>' +
          '<td><span class="badge bg-' + (STATUS_BADGES[campaign.status] || 'secondary') + '">' +
            $('<div>').text(campaign.status).html() + '</span></td>' +
          '<td>' +
            (initials.can_edit && isEditable
              ? '<button class="btn btn-sm btn-outline-primary edit-campaign-btn" data-id="' + campaign.id + '" title="Düzenle"><i class="fas fa-edit"></i></button> '
              : '') +
            (initials.can_edit && (isActive || campaign.status === 'queued')
              ? '<button class="btn btn-sm btn-outline-warning pause-campaign-btn" data-id="' + campaign.id + '" title="Durdur"><i class="fas fa-pause"></i></button> '
              : '') +
            (initials.can_edit && isPaused
              ? '<button class="btn btn-sm btn-outline-success resume-campaign-btn" data-id="' + campaign.id + '" title="Devam Ettir"><i class="fas fa-play"></i></button> '
              : '') +
            (initials.can_edit && campaign.campaign_type === 'broadcast'
              ? '<button class="btn btn-sm btn-outline-info prepare-campaign-btn" data-id="' + campaign.id + '" title="Alıcı listesi hazırla"><i class="fas fa-list-ol"></i></button> '
              : '') +
            (initials.can_edit && campaign.status !== 'sent' && campaign.campaign_type === 'broadcast'
              ? '<button class="btn btn-sm btn-success send-campaign-btn" data-id="' + campaign.id + '" title="Gönder"><i class="fas fa-paper-plane"></i></button> '
              : '') +
            (initials.can_delete
              ? '<button class="btn btn-sm btn-outline-danger delete-campaign-btn" data-id="' + campaign.id + '" title="Sil"><i class="fas fa-trash"></i></button>'
              : '') +
          '</td>' +
          '</tr>',
      );

      $campaignTbody.append($row);
    });
  }

  /* -------------------------- integrations -------------------------- */

  function renderIntegrations() {
    $('#int-google-ads-id').val(integrations.google_ads_id || '');
    $('#int-google-analytics-id').val(integrations.google_analytics_id || '');
    $('#int-gtm-container-id').val(integrations.gtm_container_id || '');
    $('#int-google-search-console-token').val(integrations.google_search_console_token || '');
    $('#int-google-trends-keywords').val(integrations.google_trends_keywords || '');
    $('#int-google-business-profile-id').val(integrations.google_business_profile_id || '');

    $('#int-meta-pixel-id').val(integrations.meta_pixel_id || '');
    $('#int-meta-capi-token').val(integrations.meta_capi_token || '');
    $('#int-meta-ad-account-id').val(integrations.meta_ad_account_id || '');
    $('#int-meta-page-id').val(integrations.meta_page_id || '');
    $('#int-meta-status-sync-enabled').prop('checked', integrations.meta_status_sync_enabled === '1');
  }

  function onSaveIntegrationsClick() {
    const payload = {
      google_ads_id: $('#int-google-ads-id').val().trim(),
      google_analytics_id: $('#int-google-analytics-id').val().trim(),
      gtm_container_id: $('#int-gtm-container-id').val().trim(),
      google_search_console_token: $('#int-google-search-console-token').val().trim(),
      google_trends_keywords: $('#int-google-trends-keywords').val().trim(),
      google_business_profile_id: $('#int-google-business-profile-id').val().trim(),
      meta_pixel_id: $('#int-meta-pixel-id').val().trim(),
      meta_capi_token: $('#int-meta-capi-token').val().trim(),
      meta_ad_account_id: $('#int-meta-ad-account-id').val().trim(),
      meta_page_id: $('#int-meta-page-id').val().trim(),
      meta_status_sync_enabled: $('#int-meta-status-sync-enabled').is(':checked') ? '1' : '0',
    };

    $.ajax({
      url: App.Utils.ajaxUrl('marketing/save_integrations'),
      type: 'POST',
      dataType: 'json',
      data: payload,
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        if (response.success !== false) {
          integrations = payload;
          App.Utils.message('Google & Meta entegrasyon ayarları kaydedildi.', 'success');
        }
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  function onSyncMetaStatusClick() {
    const text = $('#meta-status-text').val().trim();

    $.ajax({
      url: App.Utils.ajaxUrl('marketing/sync_meta_status'),
      type: 'POST',
      dataType: 'json',
      data: { status_text: text },
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        if (response.success !== false) {
          $('#meta-status-result')
            .removeClass('d-none')
            .text('Durum paylaşıldı: ' + response.synced_at + ' (Sayfa ID: ' + response.page_id + ')');
          App.Utils.message('Meta durumu güncellendi.', 'success');
        }
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  function loadTrends() {
    $.ajax({
      url: App.Utils.ajaxUrl('marketing/get_trends_data'),
      type: 'GET',
      dataType: 'json',
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        const trends = response.trends || [];
        const $preview = $('#trends-preview');
        $preview.empty();

        if (!trends.length) {
          $preview.html('<div class="text-muted">Arama trendi verisi bulunamadı.</div>');
          return;
        }

        trends.forEach(function (t) {
          const chip = $(
            '<span class="badge bg-white text-dark border me-2 mb-2 p-2 shadow-sm">' +
              '<i class="fas fa-arrow-trend-up text-success me-1"></i> ' +
              $('<div>').text(t.keyword).html() +
              ' <span class="badge bg-primary ms-1">' + t.score + '/100</span> ' +
              '<span class="text-success ms-1 small">' + t.momentum + '</span>' +
            '</span>'
          );
          $preview.append(chip);
        });
      })
      .fail(function () {});
  }

  /* -------------------------- attributions -------------------------- */

  function renderAttributions() {
    $attributionsTbody.empty();

    let totalClicks = attributions.length;
    let leadsCount = 0;
    let conversionsCount = 0;
    let totalRevenue = 0;

    attributions.forEach(function (a) {
      if (a.display_identity && a.display_identity !== 'Anonim Ziyaretçi') {
        leadsCount++;
      }
      if (a.converted === 1) {
        conversionsCount++;
        totalRevenue += parseFloat(a.revenue || 0);
      }
    });

    $('#attr-stat-total-clicks').text(totalClicks);
    $('#attr-stat-leads').text(leadsCount);
    $('#attr-stat-conversions').text(conversionsCount);
    $('#attr-stat-revenue').text(totalRevenue.toLocaleString('tr-TR', { minimumFractionDigits: 2 }) + ' ₺');

    if (!attributions.length) {
      $attributionsTbody.append('<tr><td colspan="7" class="text-center text-muted">Henüz reklam tıklaması veya atıf kaydı bulunmuyor.</td></tr>');
      return;
    }

    attributions.forEach(function (attr) {
      const clickTime = attr.click_timestamp || attr.created_at || '-';
      const heatmap = attr.heatmap_summary || {};
      const clickCount = (heatmap.clicks || []).length;
      const timeSpent = heatmap.time_on_page || 0;

      const campaignStr = attr.utm_campaign || (attr.landing_page_slug ? 'Landing: ' + attr.landing_page_slug : '-');

      const convertedBadge = attr.converted === 1
        ? '<span class="badge bg-success">Randevu Alındı (' + (parseFloat(attr.revenue || 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 }) + ' ₺') + ')</span>'
        : '<span class="badge bg-secondary">Ziyaretçi</span>';

      const identityBadge = (attr.display_identity && attr.display_identity !== 'Anonim Ziyaretçi')
        ? '<div class="fw-bold text-dark"><i class="fas fa-user-check text-success me-1"></i>' + ($('<div>').text(attr.display_identity).html()) + '</div>' +
          '<div class="small text-muted">' + ($('<div>').text(attr.display_phone || '-').html()) + '</div>' +
          '<span class="badge bg-info-subtle text-info border border-info-subtle mt-1 small">' + attr.identity_status + '</span>'
        : '<div class="text-muted"><i class="fas fa-user-clock me-1"></i> Anonim Ziyaretçi</div>' +
          '<span class="badge bg-light text-muted border mt-1">Eşleşme Bekleniyor</span>';

      const $row = $(
        '<tr>' +
          '<td>' +
            '<span class="badge bg-' + (attr.channel_badge || 'primary') + ' me-1">' + (attr.channel_source || 'Ad') + '</span>' +
            (attr.gclid ? '<div class="small text-muted text-truncate" style="max-width: 130px;">gclid: ' + attr.gclid + '</div>' : '') +
            (attr.fbclid ? '<div class="small text-muted text-truncate" style="max-width: 130px;">fbclid: ' + attr.fbclid + '</div>' : '') +
          '</td>' +
          '<td><span class="small font-monospace">' + clickTime + '</span></td>' +
          '<td>' + identityBadge + '</td>' +
          '<td>' +
            '<div class="small"><i class="fas fa-mouse me-1 text-primary"></i> ' + clickCount + ' Tıklama</div>' +
            '<div class="small text-muted"><i class="far fa-clock me-1"></i> ' + timeSpent + ' sn Süre</div>' +
          '</td>' +
          '<td><span class="small">' + $('<div>').text(campaignStr).html() + '</span></td>' +
          '<td class="text-center">' + convertedBadge + '</td>' +
          '<td>' +
            '<button class="btn btn-sm btn-outline-info view-attr-btn me-1" data-id="' + attr.id + '" title="Heatmap & Oturum Detayı"><i class="fas fa-eye"></i></button>' +
            '<button class="btn btn-sm btn-outline-primary extract-attr-btn" data-id="' + attr.id + '" title="Kimliği Çıkar / Yeniden Eşle"><i class="fas fa-fingerprint"></i></button>' +
          '</td>' +
        '</tr>'
      );

      $attributionsTbody.append($row);
    });
  }

  function loadAttributions() {
    $.ajax({
      url: App.Utils.ajaxUrl('marketing/get_attributions'),
      type: 'GET',
      dataType: 'json',
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        attributions = response.attributions || [];
        renderAttributions();
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  function onExtractAttributionClick() {
    const id = $(this).data('id');

    $.ajax({
      url: App.Utils.ajaxUrl('marketing/extract_attribution'),
      type: 'POST',
      dataType: 'json',
      data: { id: id },
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        if (response.success !== false) {
          const ex = response.extracted || {};
          App.Utils.message('Kimlik çıkarma tamamlandı: ' + (ex.full_name || 'Eşleşme sağlandı (%' + (ex.confidence || 0) + ')'), 'success');
          loadAttributions();
        }
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  function onViewAttributionClick() {
    const id = parseInt($(this).data('id'), 10);
    const attr = attributions.find(function (a) {
      return parseInt(a.id, 10) === id;
    });

    if (!attr) return;

    const heatmap = attr.heatmap_summary || {};
    const extracted = attr.extracted_customer_data || {};
    const clicks = heatmap.clicks || [];

    let clicksHtml = '';
    if (clicks.length) {
      clicksHtml = '<div class="table-responsive"><table class="table table-sm small"><thead><tr><th>Sn</th><th>Element</th><th>Metin / ID</th><th>X, Y</th></tr></thead><tbody>';
      clicks.forEach(function (c) {
        clicksHtml += '<tr><td>' + (c.t || 0) + 's</td><td><code>&lt;' + (c.tag || 'el') + '&gt;</code></td><td>' + (c.text || c.id || '-') + '</td><td>' + (c.x || 0) + ', ' + (c.y || 0) + '</td></tr>';
      });
      clicksHtml += '</tbody></table></div>';
    } else {
      clicksHtml = '<div class="text-muted small">Kayıtlı tıklama yok.</div>';
    }

    const modalContent =
      '<div class="row g-3">' +
        '<div class="col-md-6">' +
          '<h6 class="fw-bold text-primary">Oturum & Reklam Bilgileri</h6>' +
          '<ul class="list-group list-group-flush small">' +
            '<li class="list-group-item d-flex justify-content-between"><span>Oturum ID:</span><span class="font-monospace">' + (attr.session_id || '-') + '</span></li>' +
            '<li class="list-group-item d-flex justify-content-between"><span>Tıklama Saati:</span><span>' + (attr.click_timestamp || '-') + '</span></li>' +
            '<li class="list-group-item d-flex justify-content-between"><span>IP Adresi:</span><span>' + (attr.ip_address || '-') + '</span></li>' +
            '<li class="list-group-item d-flex justify-content-between"><span>Kanal:</span><span class="badge bg-primary">' + (attr.channel_source || '-') + '</span></li>' +
            '<li class="list-group-item d-flex justify-content-between"><span>UTM Campaign:</span><span>' + (attr.utm_campaign || '-') + '</span></li>' +
          '</ul>' +
        '</div>' +
        '<div class="col-md-6">' +
          '<h6 class="fw-bold text-success">Çıkarılan Müşteri Kimliği</h6>' +
          '<div class="p-3 bg-light rounded border">' +
            '<div><strong>İsim:</strong> ' + (extracted.full_name || attr.display_identity || '-') + '</div>' +
            '<div><strong>Telefon:</strong> ' + (extracted.phone || attr.display_phone || '-') + '</div>' +
            '<div><strong>E-posta:</strong> ' + (extracted.email || attr.customer_email || '-') + '</div>' +
            '<div><strong>Eşleşme Metodu:</strong> <span class="badge bg-info">' + (extracted.match_method || attr.identity_status || '-') + '</span></div>' +
            '<div><strong>Güven Skoru:</strong> %' + (extracted.confidence || 0) + '</div>' +
          '</div>' +
        '</div>' +
      '</div>' +
      '<hr>' +
      '<h6 class="fw-bold"><i class="fas fa-fire text-danger me-1"></i> Heatmap Tıklama Akışı (' + clicks.length + ' Etkileşim)</h6>' +
      clicksHtml;

    $('#attribution-modal-body').html(modalContent);
    $attributionModal.modal('show');
  }

  /* -------------------------- landing pages -------------------------- */

  function renderLandingPages() {
    $landingPagesTbody.empty();

    if (!landingPages.length) {
      $landingPagesTbody.append('<tr><td colspan="8" class="text-center text-muted">Henüz açılış sayfası yok.</td></tr>');
      return;
    }

    landingPages.forEach(function (p) {
      const views = p.views_count || 0;
      const convs = p.conversions_count || 0;
      const rate = views > 0 ? ((convs / views) * 100).toFixed(1) + '%' : '0.0%';

      const serviceInfo = p.service_name
        ? $('<div>').text(p.service_name).html() + ' (' + (parseFloat(p.service_price || 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 }) + ' ₺') + ')'
        : '-';

      const $row = $(
        '<tr>' +
          '<td><strong>' + ($('<div>').text(p.title).html()) + '</strong></td>' +
          '<td><code>/p/' + ($('<div>').text(p.slug).html()) + '</code></td>' +
          '<td>' + serviceInfo + '</td>' +
          '<td class="text-center">' + views + '</td>' +
          '<td class="text-center">' + convs + '</td>' +
          '<td class="text-center"><span class="badge bg-info">' + rate + '</span></td>' +
          '<td class="text-center"><span class="badge bg-' + (p.is_active === 1 ? 'success' : 'secondary') + '">' + (p.is_active === 1 ? 'Aktif' : 'Pasif') + '</span></td>' +
          '<td>' +
            '<a href="/p/' + encodeURIComponent(p.slug) + '" target="_blank" class="btn btn-sm btn-outline-secondary me-1" title="Önizle"><i class="fas fa-external-link-alt"></i></a>' +
            (initials.can_edit ? '<button class="btn btn-sm btn-outline-primary edit-landing-btn me-1" data-id="' + p.id + '" title="Düzenle"><i class="fas fa-edit"></i></button>' : '') +
            (initials.can_delete ? '<button class="btn btn-sm btn-outline-danger delete-landing-btn" data-id="' + p.id + '" title="Sil"><i class="fas fa-trash"></i></button>' : '') +
          '</td>' +
        '</tr>'
      );

      $landingPagesTbody.append($row);
    });
  }

  function onAddLandingPageClick() {
    editingLandingId = null;
    $('#landing-page-modal-title').text('Yeni Açılış Sayfası');
    $('#landing-title').val('');
    $('#landing-slug').val('');
    $('#landing-headline').val('');
    $('#landing-service').val('');
    $('#landing-cta-text').val('Hemen Randevu Al');
    $('#landing-content').val('');
    $('#landing-is-active').prop('checked', true);
    $landingPageModal.modal('show');
  }

  function onEditLandingPageClick() {
    const id = parseInt($(this).data('id'), 10);
    const p = landingPages.find(function (item) {
      return parseInt(item.id, 10) === id;
    });

    if (!p) return;

    editingLandingId = id;
    $('#landing-page-modal-title').text('Açılış Sayfasını Düzenle');
    $('#landing-title').val(p.title);
    $('#landing-slug').val(p.slug);
    $('#landing-headline').val(p.headline || '');
    $('#landing-service').val(p.id_services || '');
    $('#landing-cta-text').val(p.cta_text || 'Hemen Randevu Al');
    $('#landing-content').val(p.content || '');
    $('#landing-is-active').prop('checked', p.is_active === 1);
    $landingPageModal.modal('show');
  }

  function onSaveLandingPageClick() {
    const title = $('#landing-title').val().trim();
    const slug = $('#landing-slug').val().trim();

    if (!title || !slug) {
      App.Utils.message('Başlık ve slug zorunludur.', 'warning');
      return;
    }

    const payload = {
      title: title,
      slug: slug,
      headline: $('#landing-headline').val().trim(),
      id_services: $('#landing-service').val() || null,
      cta_text: $('#landing-cta-text').val().trim(),
      content: $('#landing-content').val().trim(),
      is_active: $('#landing-is-active').is(':checked') ? 1 : 0,
    };

    if (editingLandingId) {
      payload.id = editingLandingId;
    }

    $.ajax({
      url: App.Utils.ajaxUrl('marketing/save_landing_page'),
      type: 'POST',
      dataType: 'json',
      data: payload,
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        if (response.success !== false) {
          $landingPageModal.modal('hide');
          App.Utils.message(editingLandingId ? 'Açılış sayfası güncellendi.' : 'Açılış sayfası oluşturuldu.', 'success');
          loadLandingPages();
        }
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  function onDeleteLandingPageClick() {
    const id = $(this).data('id');
    if (!confirm('Açılış sayfasını silmek istediğinize emin misiniz?')) {
      return;
    }

    $.ajax({
      url: App.Utils.ajaxUrl('marketing/delete_landing_page'),
      type: 'POST',
      dataType: 'json',
      data: { id: id },
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        if (response.success !== false) {
          App.Utils.message('Açılış sayfası silindi.', 'success');
          loadLandingPages();
        }
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  function loadLandingPages() {
    $.ajax({
      url: App.Utils.ajaxUrl('marketing/get_landing_pages'),
      type: 'GET',
      dataType: 'json',
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        landingPages = response.landing_pages || [];
        renderLandingPages();
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  /* -------------------------------- data -------------------------------- */

  function loadSegments() {
    $.ajax({
      url: App.Utils.ajaxUrl('marketing/get_segments'),
      type: 'GET',
      dataType: 'json',
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        segments = response.segments || [];
        renderSegments();
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  function loadCampaigns() {
    $.ajax({
      url: App.Utils.ajaxUrl('marketing/get_campaigns'),
      type: 'GET',
      dataType: 'json',
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        campaigns = response.campaigns || [];
        renderCampaigns();
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  document.addEventListener('DOMContentLoaded', init);

  return {};
})();