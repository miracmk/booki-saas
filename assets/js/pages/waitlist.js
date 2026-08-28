/**
 * Waitlist page JavaScript (Ki Reservation, Dalga 1).
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

      const $row = $(
        '<tr>' +
          '<td>#' + entry.id + '</td>' +
          '<td>' + customerName + '</td>' +
          '<td>' + (entry.service_name || '') + '</td>' +
          '<td><span class="badge bg-info">' + entry.status + '</span></td>' +
          '<td>' + (entry.requested_date || '-') + '</td>' +
          '<td><button class="btn btn-sm btn-outline-danger cancel-btn" data-id="' + entry.id + '">' +
          '<i class="fas fa-times"></i></button></td>' +
          '</tr>',
      );

      $tbody.append($row);
    });
  }

  document.addEventListener('DOMContentLoaded', init);

  return {};
})();
