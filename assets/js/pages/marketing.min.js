/**
 * Marketing page JavaScript (BooKi, Dalga 3 / Faz 3.3).
 *
 * Handles segment CRUD + counting and campaign CRUD + batched broadcast sends.
 */

'use strict';

App.Pages.Marketing = (function () {
  const $segmentTable = $('#segments-table');
  const $segmentTbody = $segmentTable.find('tbody');
  const $campaignTable = $('#campaigns-table');
  const $campaignTbody = $campaignTable.find('tbody');
  const $segmentModal = $('#segment-modal');
  const $campaignModal = $('#campaign-modal');
  const $sendModal = $('#send-modal');

  let segments = [];
  let campaigns = [];
  const initials = (window.scriptVars && window.scriptVars.initials) || {};
  let editingSegmentId = null;
  let editingCampaignId = null;
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
  };

  const STATUS_BADGES = {
    draft: 'secondary',
    queued: 'info',
    sending: 'warning',
    sent: 'success',
    failed: 'danger',
  };

  function init() {
    addEventListeners();
    segments = (window.scriptVars.segments || []).slice();
    campaigns = (window.scriptVars.campaigns || []).slice();
    renderSegments();
    renderCampaigns();
  }

  function addEventListeners() {
    $('#add-segment').on('click', onAddSegmentClick);
    $('#save-segment').on('click', onSaveSegmentClick);
    $('#refresh-all-segments').on('click', onRefreshAllSegmentsClick);
    $('#segment-type').on('change', onSegmentTypeChange);
    $segmentTbody.on('click', 'button.edit-segment-btn', onEditSegmentClick);
    $segmentTbody.on('click', 'button.delete-segment-btn', onDeleteSegmentClick);
    $segmentTbody.on('click', 'button.refresh-segment-btn', onRefreshSegmentClick);

    $('#add-campaign').on('click', onAddCampaignClick);
    $('#save-campaign').on('click', onSaveCampaignClick);
    $campaignTbody.on('click', 'button.edit-campaign-btn', onEditCampaignClick);
    $campaignTbody.on('click', 'button.delete-campaign-btn', onDeleteCampaignClick);
    $campaignTbody.on('click', 'button.prepare-campaign-btn', onPrepareCampaignClick);
    $campaignTbody.on('click', 'button.send-campaign-btn', onSendCampaignClick);
    $('#confirm-send').on('click', onConfirmSendClick);
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
          '<td><span class="badge bg-info">' + TYPE_LABELS[segment.type] + '</span></td>' +
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
    $('#campaign-segment').empty();
    $('#campaign-segment').append('<option value="">Segment seçin</option>');

    segments.forEach(function (segment) {
      if (segment.enabled === 1) {
        $('#campaign-segment').append('<option value="' + segment.id + '">' + segment.name + '</option>');
      }
    });

    $('#campaign-channel').val('email');
    $('#campaign-subject').val('');
    $('#campaign-message').val('');
    $campaignModal.modal('show');
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
    $('#campaign-segment').empty();
    $('#campaign-segment').append('<option value="">Segment seçin</option>');

    segments.forEach(function (segment) {
      $('#campaign-segment').append(
        '<option value="' + segment.id + '"' + (parseInt(segment.id, 10) === campaign.segment_id ? ' selected' : '') + '>' + segment.name + '</option>',
      );
    });

    $('#campaign-channel').val(campaign.channel);
    $('#campaign-subject').val(campaign.subject || '');
    $('#campaign-message').val(campaign.message || '');
    $campaignModal.modal('show');
  }

  function onSaveCampaignClick() {
    const name = $('#campaign-name').val().trim();
    const segmentId = $('#campaign-segment').val();
    const channel = $('#campaign-channel').val();
    const message = $('#campaign-message').val().trim();

    if (!name || !segmentId || !message) {
      App.Utils.message('Ad, segment ve mesaj zorunludur.', 'warning');
      return;
    }

    const payload = {
      name: name,
      segment_id: parseInt(segmentId, 10),
      channel: channel,
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
      $campaignTbody.append('<tr><td colspan="8" class="text-center text-muted">Henüz kampanya yok.</td></tr>');
      return;
    }

    campaigns.forEach(function (campaign) {
      const segment = segments.find(function (s) {
        return parseInt(s.id, 10) === campaign.segment_id;
      });
      const isEditable = campaign.status === 'draft' || campaign.status === 'failed';

      const $row = $(
        '<tr>' +
          '<td>' + ($('<div>').text(campaign.name).html()) + '</td>' +
          '<td>' + (segment ? $('<div>').text(segment.name).html() : '#') + '</td>' +
          '<td>' + (CHANNEL_LABELS[campaign.channel] || campaign.channel) + '</td>' +
          '<td class="text-center">' + (campaign.total_recipients || 0) + '</td>' +
          '<td class="text-center">' + (campaign.sent_count || 0) + '</td>' +
          '<td class="text-center">' + (campaign.failed_count || 0) + '</td>' +
          '<td><span class="badge bg-' + (STATUS_BADGES[campaign.status] || 'secondary') + '">' +
            $('<div>').text(campaign.status).html() + '</span></td>' +
          '<td>' +
            (initials.can_edit && isEditable
              ? '<button class="btn btn-sm btn-outline-primary edit-campaign-btn" data-id="' + campaign.id + '"><i class="fas fa-edit"></i></button> '
              : '') +
            (initials.can_edit
              ? '<button class="btn btn-sm btn-outline-info prepare-campaign-btn" data-id="' + campaign.id + '" title="Alıcı listesi hazırla"><i class="fas fa-list-ol"></i></button> '
              : '') +
            (initials.can_edit && campaign.status !== 'sent'
              ? '<button class="btn btn-sm btn-success send-campaign-btn" data-id="' + campaign.id + '"><i class="fas fa-paper-plane"></i></button> '
              : '') +
            (initials.can_delete
              ? '<button class="btn btn-sm btn-outline-danger delete-campaign-btn" data-id="' + campaign.id + '"><i class="fas fa-trash"></i></button>'
              : '') +
          '</td>' +
          '</tr>',
      );

      $campaignTbody.append($row);
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