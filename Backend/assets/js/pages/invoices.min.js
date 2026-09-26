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
      $itemsContainer.html('<p class="text-muted">Önce müşteri seçin veya aşağıdan doğrudan kalem ekleyin.</p>');
      $customerSelect.val('');
      billableItems = [];
      $invoiceModal.modal('show');
    });

    $customerSelect.on('change', onCustomerChange);
    $('#save-invoice').on('click', onSaveInvoiceClick);
    $tbody.on('click', 'button.issue-btn', onIssueClick);
    $tbody.on('click', 'button.paid-btn', onMarkPaidClick);
    $tbody.on('click', 'button.void-btn', onVoidClick);

    $('#btn-add-custom-item').on('click', function () {
      const desc = $('#custom-item-desc').val().trim();
      const qty = parseFloat($('#custom-item-qty').val()) || 1;
      const price = parseFloat($('#custom-item-price').val());

      if (!desc) {
        App.Utils.message('Lütfen kalem açıklaması girin.', 'error');
        return;
      }
      if (isNaN(price) || price <= 0) {
        App.Utils.message('Lütfen geçerli bir birim fiyat girin.', 'error');
        return;
      }

      billableItems.push({
        item_type: 'custom',
        id_appointments: null,
        description: desc,
        unit_price: price,
        quantity: qty,
        tax_rate: 20,
      });

      $('#custom-item-desc').val('');
      $('#custom-item-price').val('');
      $('#custom-item-qty').val('1');

      renderBillableItems();
    });

    $tbody.on('click', 'button.print-invoice-btn', function () {
      const id = $(this).data('id');
      window.open(App.Utils.Url.siteUrl('invoices/print_view/' + id), '_blank');
    });

    $tbody.on('click', 'button.sync-erp-btn', function () {
      const id = $(this).data('id');
      const $btn = $(this);
      $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

      $.ajax({
        url: App.Utils.ajaxUrl('invoices/sync_erp'),
        type: 'POST',
        dataType: 'json',
        data: { invoice_id: id },
        headers: { 'X-CSRF-Token': App.Security.csrfToken },
      })
        .done(function (res) {
          if (res.success) {
            App.Utils.message(res.message || 'ERP sistemine başarıyla aktarıldı.', 'success');
            search();
          }
        })
        .fail(function (jqxhr) {
          App.Utils.ajaxErrorMsg(jqxhr);
        })
        .always(function () {
          $btn.prop('disabled', false).html('<i class="fas fa-cloud-upload-alt me-1"></i>ERP');
        });
    });
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
        item_type: item.item_type || 'appointment',
        id_reference: item.id_appointments || null,
        description: item.description,
        quantity: item.quantity || 1,
        unit_price: item.unit_price,
        tax_rate: item.tax_rate || 20,
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
      const customerName = ((invoice.customer_first_name || '') + ' ' + (invoice.customer_last_name || '')).trim() || '—';
      const badgeClass =
        invoice.status === 'paid' ? 'success' : invoice.status === 'void' ? 'secondary' : 'info';

      let erpBadge = '<span class="badge bg-secondary-subtle text-secondary border">Aktarılmadı</span>';
      if (invoice.erp_status === 'synced') {
        erpBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle" title="ERP ID: ' + (invoice.erp_invoice_id || '') + '"><i class="fas fa-check-circle me-1"></i>' + (invoice.erp_provider ? invoice.erp_provider.toUpperCase() : 'ERP') + '</span>';
      } else if (invoice.erp_status === 'failed') {
        erpBadge = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle" title="' + escapeHtml(invoice.erp_error || '') + '"><i class="fas fa-exclamation-triangle me-1"></i>Hata</span>';
      }

      const $row = $(
        '<tr>' +
          '<td><strong>' + invoice.invoice_number + '</strong></td>' +
          '<td>' + customerName + '</td>' +
          '<td><span class="badge bg-' + badgeClass + '">' + invoice.status + '</span></td>' +
          '<td>' + erpBadge + '</td>' +
          '<td>' + invoice.total + ' ' + invoice.currency + '</td>' +
          '<td><small class="text-muted">' + invoice.created_at + '</small></td>' +
          '<td>' +
          '<button class="btn btn-sm btn-outline-primary issue-btn me-1" data-id="' + invoice.id + '">Kes</button>' +
          '<button class="btn btn-sm btn-outline-success paid-btn me-1" data-id="' + invoice.id + '">Ödendi</button>' +
          '<button class="btn btn-sm btn-outline-danger void-btn me-1" data-id="' + invoice.id + '">İptal</button>' +
          '<button class="btn btn-sm btn-outline-info print-invoice-btn me-1" data-id="' + invoice.id + '" title="Yazdır / e-Arşiv Görünümü"><i class="fas fa-print"></i></button>' +
          '<button class="btn btn-sm btn-outline-secondary sync-erp-btn" data-id="' + invoice.id + '" title="ERP\'ye Aktar"><i class="fas fa-cloud-upload-alt me-1"></i>ERP</button>' +
          '</td>' +
          '</tr>',
      );

      $tbody.append($row);
    });
  }

  document.addEventListener('DOMContentLoaded', init);

  return {};
})();
