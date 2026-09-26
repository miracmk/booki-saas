/**
 * Data Requests page JavaScript (BooKi, Faz 30, KVKK/GDPR, 2026-08-28).
 *
 * Lets staff review customer KVKK requests: export requests are informational
 * (fully self-service, built and emailed automatically); erasure requests need
 * an explicit approve/reject decision here.
 */

'use strict';

App.Pages.DataRequests = (function () {
  const $table = $('table');
  const $tbody = $table.find('tbody');
  const $filterType = $('#filter-type');
  const $filterStatus = $('#filter-status');

  let requestData = [];

  const statusLabels = {
    pending: 'Bekliyor',
    processing: 'Hazırlanıyor',
    ready: 'Hazır',
    failed: 'Başarısız',
    expired: 'Süresi Doldu',
    completed: 'Tamamlandı',
  };

  const statusBadgeClass = {
    pending: 'bg-secondary',
    processing: 'bg-info',
    ready: 'bg-success',
    failed: 'bg-danger',
    expired: 'bg-dark',
    completed: 'bg-primary',
  };

  function init() {
    addEventListeners();
    search();
  }

  function addEventListeners() {
    $filterType.on('change', search);
    $filterStatus.on('change', search);
    $tbody.on('click', 'button.approve-btn', onApproveClick);
    $tbody.on('click', 'button.reject-btn', onRejectClick);
  }

  function onApproveClick() {
    const requestId = $(this).data('id');

    if (!confirm('Bu müşterinin anonimleştirilmesini onaylamak istediğinizden emin misiniz? Bu işlem geri alınamaz.')) {
      return;
    }

    $.ajax({
      url: App.Utils.ajaxUrl('data_requests/approve_erasure'),
      type: 'POST',
      dataType: 'json',
      data: { request_id: requestId },
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        if (response.success) {
          search();
          App.Utils.message('Müşteri anonimleştirildi.', 'success');
        }
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  function onRejectClick() {
    const requestId = $(this).data('id');
    const reason = prompt('Reddetme nedeni (müşteriye görünmez, sadece kayıt için):', '');

    if (reason === null) {
      return;
    }

    $.ajax({
      url: App.Utils.ajaxUrl('data_requests/reject_erasure'),
      type: 'POST',
      dataType: 'json',
      data: { request_id: requestId, reason: reason },
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        if (response.success) {
          search();
          App.Utils.message('Talep reddedildi.', 'success');
        }
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  function search() {
    $.ajax({
      url: App.Utils.ajaxUrl('data_requests/search'),
      type: 'POST',
      dataType: 'json',
      data: {
        request_type: $filterType.val(),
        status: $filterStatus.val(),
        limit: 100,
        offset: 0,
      },
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        if (response.counts) {
          updateCounts(response.counts);
        }
        if (response.requests) {
          requestData = response.requests;
          renderTable();
        }
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  function updateCounts(counts) {
    $('#count-pending').text(counts.pending || 0);
    $('#count-processing').text(counts.processing || 0);
    $('#count-ready').text(counts.ready || 0);
    $('#count-failed').text(counts.failed || 0);
    $('#count-expired').text(counts.expired || 0);
    $('#count-completed').text(counts.completed || 0);
  }

  function renderTable() {
    $tbody.empty();

    requestData.forEach(function (row) {
      const typeLabel = row.request_type === 'export' ? 'Dışa Aktarma' : 'Silme';
      const badgeClass = statusBadgeClass[row.status] || 'bg-secondary';
      const statusLabel = statusLabels[row.status] || row.status;
      const note = escapeHtml((row.error_message || row.notes || '-').toString().substring(0, 120));

      let actions = '-';

      if (row.request_type === 'erasure' && row.status === 'pending') {
        actions =
          '<button class="btn btn-sm btn-outline-success approve-btn" data-id="' + row.id + '" title="Onayla">' +
          '<i class="fas fa-check"></i></button> ' +
          '<button class="btn btn-sm btn-outline-danger reject-btn" data-id="' + row.id + '" title="Reddet">' +
          '<i class="fas fa-times"></i></button>';
      }

      const $row = $(
        '<tr>' +
          '<td>#' + row.id + '</td>' +
          '<td>' + escapeHtml(row.customer_name || '-') + '</td>' +
          '<td>' + typeLabel + '</td>' +
          '<td><span class="badge ' + badgeClass + '">' + statusLabel + '</span></td>' +
          '<td>' + (row.created_at || '-') + '</td>' +
          '<td style="font-size: 0.85rem; max-width: 240px; word-break: break-word;">' + note + '</td>' +
          '<td>' + actions + '</td>' +
          '</tr>',
      );

      $tbody.append($row);
    });

    if (requestData.length === 0) {
      $tbody.append('<tr><td colspan="7" class="text-center text-muted">Kayıt yok.</td></tr>');
    }
  }

  function escapeHtml(text) {
    if (!text) return '';
    const map = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;',
    };
    return text.replace(/[&<>"']/g, function (m) {
      return map[m];
    });
  }

  document.addEventListener('DOMContentLoaded', init);

  return {};
})();
