/**
 * Waitlist page JavaScript (BooKi, Dalga 1).
 *
 * Handles join/list/cancel operations for the customer waitlist.
 */

'use strict';

App.Pages.Waitlist = (function () {
  const $entryModal = $('#entry-modal');
  const $entryForm = $('#entry-form');
  const $addEntryBtn = $('#add-entry');
  const $saveEntryBtn = $('#save-entry');
  const $table = $('table');
  const $tbody = $table.find('tbody');

  const $notifyModal = $('#notify-modal');
  const $confirmNotifyBtn = $('#confirm-notify-btn');

  let tableRows = [];

  /**
   * Initialize page.
   */
  function init() {
    addEventListeners();
    search();
  }

  /**
   * Add event listeners to page elements.
   */
  function addEventListeners() {
    $addEntryBtn.on('click', onAddEntryClick);
    $saveEntryBtn.on('click', onSaveEntryClick);
    $tbody.on('click', 'button.cancel-btn', onCancelButtonClick);
    $tbody.on('click', 'button.notify-btn', onNotifyButtonClick);
    $confirmNotifyBtn.on('click', onConfirmNotifyClick);
  }

  /**
   * Handle proactive notification button click.
   */
  function onNotifyButtonClick() {
    const entryId = $(this).data('id');
    const entry = tableRows.find(function (r) { return r.id == entryId; });

    if (!entry) {
      return;
    }

    const customerName = ((entry.customer_first_name || '') + ' ' + (entry.customer_last_name || '')).trim();
    $('#notify-entry-id').val(entryId);
    $('#notify-customer-display').text(customerName || ('#' + entry.id_users_customer));
    $('#notify-service-display').text(entry.service_name || '-');

    const defaultDate = entry.requested_date ? entry.requested_date + 'T10:00' : (new Date(Date.now() + 86400000).toISOString().slice(0, 10) + 'T10:00');
    $('#notify-slot-datetime').val(defaultDate);

    $notifyModal.modal('show');
  }

  /**
   * Confirm and send proactive notification.
   */
  function onConfirmNotifyClick() {
    const entryId = $('#notify-entry-id').val();
    const slotDatetime = $('#notify-slot-datetime').val();
    const channel = $('#notify-channel').val();

    if (!entryId) {
      return;
    }

    $confirmNotifyBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Gönderiliyor...');

    $.ajax({
      url: App.Utils.ajaxUrl('waitlist/notify'),
      type: 'POST',
      dataType: 'json',
      data: {
        entry_id: entryId,
        slot_datetime: slotDatetime ? slotDatetime.replace('T', ' ') + ':00' : null,
        channel: channel,
      },
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        if (response.success) {
          $notifyModal.modal('hide');
          search();
          App.Utils.message(response.message || 'Müşteriye ön bilgilendirme başarıyla gönderildi.', 'success');
        }
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      })
      .always(function () {
        $confirmNotifyBtn.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i> Bildirimi Gönder');
      });
  }

  /**
   * Handle add entry button click.
   */
  function onAddEntryClick() {
    $entryForm[0].reset();
    populateSelects();
    $entryModal.modal('show');
  }

  /**
   * Populate select dropdowns from window.scriptVars.
   */
  function populateSelects() {
    const $customerSelect = $('#entry-customer');
    const $serviceSelect = $('#entry-service');
    const $providerSelect = $('#entry-provider');

    $customerSelect.empty().append('<option value="">' + App.Lang.select + '</option>');
    $serviceSelect.empty().append('<option value="">' + App.Lang.select + '</option>');
    $providerSelect.empty().append('<option value="">Herhangi bir sağlayıcı</option>');

    (window.scriptVars.customers || []).forEach(function (customer) {
      const name = (customer.first_name || '') + ' ' + (customer.last_name || '');
      $customerSelect.append('<option value="' + customer.id + '">' + name.trim() + '</option>');
    });

    (window.scriptVars.services || []).forEach(function (service) {
      $serviceSelect.append('<option value="' + service.id + '">' + service.name + '</option>');
    });

    (window.scriptVars.providers || []).forEach(function (provider) {
      const name = (provider.first_name || '') + ' ' + (provider.last_name || '');
      $providerSelect.append('<option value="' + provider.id + '">' + name.trim() + '</option>');
    });
  }

  /**
   * Handle save entry button click.
   */
  function onSaveEntryClick() {
    if (!$entryForm[0].checkValidity()) {
      $entryForm[0].reportValidity();
      return;
    }

    const formData = new FormData($entryForm[0]);
    const entryData = {};

    formData.forEach(function (value, key) {
      if (key.startsWith('entry[')) {
        const fieldName = key.replace('entry[', '').replace(']', '');
        if (value !== '') {
          entryData[fieldName] = value;
        }
      }
    });

    $.ajax({
      url: App.Utils.ajaxUrl('waitlist/store'),
      type: 'POST',
      dataType: 'json',
      data: { entry: entryData },
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        if (response.success) {
          $entryModal.modal('hide');
          search();
          App.Utils.message('Bekleme listesine eklendi.', 'success');
        }
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  /**
   * Handle cancel button click on a row.
   */
  function onCancelButtonClick() {
    const entryId = $(this).data('id');

    $.ajax({
      url: App.Utils.ajaxUrl('waitlist/destroy'),
      type: 'POST',
      dataType: 'json',
      data: { entry_id: entryId },
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        if (response.success) {
          search();
        }
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  /**
   * Fetch the current waiting list.
   */
  function search() {
    $.ajax({
      url: App.Utils.ajaxUrl('waitlist/search'),
      type: 'POST',
      dataType: 'json',
      data: {},
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (entries) {
        tableRows = entries;
        renderTable();
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  /**
   * Render the waiting-list table.
   */
  function renderTable() {
    $tbody.empty();

    tableRows.forEach(function (entry) {
      const customerName = ((entry.customer_first_name || '') + ' ' + (entry.customer_last_name || '')).trim();

      let statusBadge = '<span class="badge bg-info">' + (entry.status || 'waiting') + '</span>';
      if (entry.status === 'notified') {
        statusBadge = '<span class="badge bg-warning text-dark"><i class="fas fa-bell me-1"></i>Bilgilendirildi</span>';
      } else if (entry.status === 'converted') {
        statusBadge = '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Dönüştü</span>';
      }

      const $row = $(
        '<tr>' +
          '<td>#' + entry.id + '</td>' +
          '<td><strong>' + (customerName || '-') + '</strong></td>' +
          '<td>' + (entry.service_name || '') + '</td>' +
          '<td>' + statusBadge + '</td>' +
          '<td>' + (entry.requested_date || '-') + '</td>' +
          '<td>' +
          '<button class="btn btn-sm btn-outline-primary notify-btn me-1" data-id="' + entry.id + '" title="Müşteriyi Önden Bilgilendir">' +
          '<i class="fas fa-paper-plane me-1"></i>Önden Bildir</button>' +
          '<button class="btn btn-sm btn-outline-danger cancel-btn" data-id="' + entry.id + '" title="İptal">' +
          '<i class="fas fa-times"></i></button>' +
          '</td>' +
          '</tr>',
      );

      $tbody.append($row);
    });
  }

  document.addEventListener('DOMContentLoaded', init);

  return {};
})();
