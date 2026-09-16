/**
 * Invoices page JavaScript (BooKi, Dalga 1).
 *
 * Lists invoices, builds a new invoice from a customer's billable
 * (completed, uninvoiced) appointments, issues/marks paid/voids.
 */

'use strict';

App.Pages.Invoices = (function () {
  const $invoiceModal = $('#invoice-modal');
  const $customerSelect = $('#invoice-customer');
  const $itemsContainer = $('#billable-items-container');
  const $table = $('table');
  const $tbody = $table.find('tbody');

  let tableRows = [];
  let billableItems = [];

  function init() {
    addEventListeners();
    populateCustomerSelect();
    search();
    initializeExport();
  }

  function addEventListeners() {
    $('#add-invoice').on('click', function () {
      $itemsContainer.html('<p class="text-muted">Önce müşteri seçin.</p>');
      $customerSelect.val('');
      $invoiceModal.modal('show');
    });

    $customerSelect.on('change', onCustomerChange);
    $('#save-invoice').on('click', onSaveInvoiceClick);
    $tbody.on('click', 'button.issue-btn', onIssueClick);
    $tbody.on('click', 'button.paid-btn', onMarkPaidClick);
    $tbody.on('click', 'button.void-btn', onVoidClick);
  }

  /**
   * "Muhasebe Dışa Aktar" (2026-09-12) - GET-based CSV download, same pattern as
   * Reports.js's export-csv (no CSRF needed, CI3 only checks it on POST).
   */
  function initializeExport() {
    const today = moment().format('YYYY-MM-DD');
    $('#invoice-export-start').val(today);
    $('#invoice-export-end').val(today);

    $('#invoice-export-csv').on('click', function () {
      const startDate = $('#invoice-export-start').val();
      const endDate = $('#invoice-export-end').val();

      if (!startDate || !endDate) {
        App.Layouts.Backend.displayNotification('Başlangıç ve bitiş tarihi seçmelisiniz.');
        return;
      }

      if (endDate < startDate) {
        App.Layouts.Backend.displayNotification('Bitiş tarihi başlangıç tarihinden önce olamaz.');
        return;
      }

      window.location.href = App.Utils.Url.siteUrl(
        'invoices/export_csv?start_date=' + startDate + '&end_date=' + endDate,
      );
    });
  }

  function populateCustomerSelect() {
    $customerSelect.empty().append('<option value="">' + App.Lang.select + '</option>');

    (window.scriptVars.customers || []).forEach(function (customer) {
      const name = (customer.first_name || '') + ' ' + (customer.last_name || '');
      $customerSelect.append('<option value="' + customer.id + '">' + name.trim() + '</option>');
    });
  }

  function onCustomerChange() {
    const customerId = $customerSelect.val();

    if (!customerId) {
      $itemsContainer.html('<p class="text-muted">Önce müşteri seçin.</p>');
      return;
    }

    $.ajax({
      url: App.Utils.ajaxUrl('invoices/billable_items'),
      type: 'POST',
      dataType: 'json',
      data: { customer_id: customerId },
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (items) {
        billableItems = items;
        renderBillableItems();
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  function renderBillableItems() {
    if (!billableItems.length) {
      $itemsContainer.html('<p class="text-muted">Faturalanabilir randevu bulunamadı.</p>');
      return;
    }

    let html = '<table class="table"><thead><tr><th></th><th>Açıklama</th><th>Tutar</th></tr></thead><tbody>';

    billableItems.forEach(function (item, index) {
      html +=
        '<tr>' +
        '<td><input type="checkbox" class="billable-item-checkbox" data-index="' + index + '" checked /></td>' +
        '<td>' + item.description + '</td>' +
        '<td>' + item.unit_price + '</td>' +
        '</tr>';
    });

    html += '</tbody></table>';
    $itemsContainer.html(html);
  }

  function onSaveInvoiceClick() {
    const customerId = $customerSelect.val();

    if (!customerId) {
      App.Utils.message('Lütfen müşteri seçin.', 'error');
      return;
    }

    const selectedItems = [];

    $('.billable-item-checkbox:checked').each(function () {
      const index = $(this).data('index');
      const item = billableItems[index];
      selectedItems.push({
        item_type: 'appointment',
        id_reference: item.id_appointments,
        description: item.description,
        quantity: 1,
        unit_price: item.unit_price,
      });
    });

    if (!selectedItems.length) {
      App.Utils.message('Lütfen en az bir kalem seçin.', 'error');
      return;
    }

    $.ajax({
      url: App.Utils.ajaxUrl('invoices/store'),
      type: 'POST',
      dataType: 'json',
      data: { customer_id: customerId, items: selectedItems },
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        if (response.success) {
          $invoiceModal.modal('hide');
          search();
          App.Utils.message('Fatura oluşturuldu.', 'success');
        }
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  function callAction(url, invoiceId) {
    $.ajax({
      url: App.Utils.ajaxUrl(url),
      type: 'POST',
      dataType: 'json',
      data: { invoice_id: invoiceId },
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

  function onIssueClick() {
    callAction('invoices/issue', $(this).data('id'));
  }

  function onMarkPaidClick() {
    callAction('invoices/mark_paid', $(this).data('id'));
  }

  function onVoidClick() {
    callAction('invoices/void', $(this).data('id'));
  }

  function search() {
    $.ajax({
      url: App.Utils.ajaxUrl('invoices/search'),
      type: 'POST',
      dataType: 'json',
      data: {},
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (invoices) {
        tableRows = invoices;
        renderTable();
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  function renderTable() {
    $tbody.empty();

    tableRows.forEach(function (invoice) {
      const customerName = ((invoice.customer_first_name || '') + ' ' + (invoice.customer_last_name || '')).trim();
      const badgeClass =
        invoice.status === 'paid' ? 'success' : invoice.status === 'void' ? 'secondary' : 'info';

      const $row = $(
        '<tr>' +
          '<td>' + invoice.invoice_number + '</td>' +
          '<td>' + customerName + '</td>' +
          '<td><span class="badge bg-' + badgeClass + '">' + invoice.status + '</span></td>' +
          '<td>' + invoice.total + ' ' + invoice.currency + '</td>' +
          '<td>' + invoice.created_at + '</td>' +
          '<td>' +
          '<button class="btn btn-sm btn-outline-primary issue-btn" data-id="' + invoice.id + '">Kes</button> ' +
          '<button class="btn btn-sm btn-outline-success paid-btn" data-id="' + invoice.id + '">Ödendi</button> ' +
          '<button class="btn btn-sm btn-outline-danger void-btn" data-id="' + invoice.id + '">İptal</button>' +
          '</td>' +
          '</tr>',
      );

      $tbody.append($row);
    });
  }

  document.addEventListener('DOMContentLoaded', init);

  return {};
})();
