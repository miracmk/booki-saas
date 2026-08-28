/**
 * POS page JavaScript (Ki Reservation, Dalga 1).
 *
 * Builds a basket, creates an order, lists existing orders, checks out via
 * the active payment gateway, voids an order.
 */

'use strict';

App.Pages.Pos = (function () {
  const $basket = $('#pos-basket');
  const $total = $('#pos-total');
  const $table = $('table').eq(1);
  const $tbody = $table.find('tbody');

  let basketItems = [];
  let tableRows = [];

  function init() {
    addEventListeners();
    populateSelects();
    search();
  }

  function addEventListeners() {
    $('#add-item').on('click', onAddItemClick);
    $('#create-order').on('click', onCreateOrderClick);
    $tbody.on('click', 'button.checkout-btn', onCheckoutClick);
    $tbody.on('click', 'button.void-btn', onVoidClick);
  }

  function populateSelects() {
    const $customerSelect = $('#pos-customer');
    const $productSelect = $('#pos-product');

    $customerSelect.empty().append('<option value="">Müşterisiz (walk-in)</option>');
    $productSelect.empty().append('<option value="">' + App.Lang.select + '</option>');

    (window.scriptVars.customers || []).forEach(function (customer) {
      const name = (customer.first_name || '') + ' ' + (customer.last_name || '');
      $customerSelect.append('<option value="' + customer.id + '">' + name.trim() + '</option>');
    });

    (window.scriptVars.products || []).forEach(function (product) {
      $productSelect.append(
        '<option value="' + product.id + '" data-price="' + product.sale_price + '" data-name="' + product.name + '">' +
          product.name + ' (' + product.sale_price + ')' +
          '</option>',
      );
    });
  }

  function onAddItemClick() {
    const $productSelect = $('#pos-product');
    const productId = $productSelect.val();

    if (!productId) {
      return;
    }

    const $selected = $productSelect.find('option:selected');
    const quantity = parseFloat($('#pos-quantity').val()) || 1;
    const unitPrice = parseFloat($selected.data('price'));

    basketItems.push({
      item_type: 'product',
      id_reference: parseInt(productId, 10),
      description: $selected.data('name'),
      quantity: quantity,
      unit_price: unitPrice,
    });

    renderBasket();
  }

  function renderBasket() {
    $basket.empty();
    let total = 0;

    basketItems.forEach(function (item, index) {
      const lineTotal = item.quantity * item.unit_price;
      total += lineTotal;

      $basket.append(
        '<tr>' +
          '<td>' + item.description + '</td>' +
          '<td>' + item.quantity + '</td>' +
          '<td>' + lineTotal.toFixed(2) + '</td>' +
          '<td><button class="btn btn-sm btn-outline-danger remove-item" data-index="' + index + '">×</button></td>' +
          '</tr>',
      );
    });

    $basket.find('.remove-item').on('click', function () {
      basketItems.splice($(this).data('index'), 1);
      renderBasket();
    });

    $total.text(total.toFixed(2));
  }

  function onCreateOrderClick() {
    if (!basketItems.length) {
      App.Utils.message('Sepet boş.', 'error');
      return;
    }

    const customerId = $('#pos-customer').val();

    $.ajax({
      url: App.Utils.ajaxUrl('pos/store'),
      type: 'POST',
      dataType: 'json',
      data: { customer_id: customerId || null, items: basketItems },
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        if (response.success) {
          basketItems = [];
          renderBasket();
          search();
          App.Utils.message('Sipariş oluşturuldu. Ödeme almak için listeden "Öde" butonunu kullanın.', 'success');
        }
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  function onCheckoutClick() {
    const orderId = $(this).data('id');

    $.ajax({
      url: App.Utils.ajaxUrl('pos/checkout'),
      type: 'POST',
      dataType: 'json',
      data: { order_id: orderId },
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        if (response.success) {
          if (response.payment.checkout_form) {
            App.Utils.message('Ödeme formu oluşturuldu.', 'success');
          }
          search();
        }
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  function onVoidClick() {
    const orderId = $(this).data('id');

    $.ajax({
      url: App.Utils.ajaxUrl('pos/void'),
      type: 'POST',
      dataType: 'json',
      data: { order_id: orderId },
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

  function search() {
    $.ajax({
      url: App.Utils.ajaxUrl('pos/search'),
      type: 'POST',
      dataType: 'json',
      data: {},
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (orders) {
        tableRows = orders;
        renderTable();
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  function renderTable() {
    $tbody.empty();

    tableRows.forEach(function (order) {
      const customerName = ((order.customer_first_name || '') + ' ' + (order.customer_last_name || '')).trim() || 'Walk-in';
      const badgeClass = order.status === 'paid' ? 'success' : order.status === 'open' ? 'info' : 'secondary';

      $tbody.append(
        '<tr>' +
          '<td>#' + order.id + '</td>' +
          '<td>' + customerName + '</td>' +
          '<td><span class="badge bg-' + badgeClass + '">' + order.status + '</span></td>' +
          '<td>' + order.total + ' ' + order.currency + '</td>' +
          '<td>' +
          '<button class="btn btn-sm btn-outline-success checkout-btn" data-id="' + order.id + '">Öde</button> ' +
          '<button class="btn btn-sm btn-outline-danger void-btn" data-id="' + order.id + '">İptal</button>' +
          '</td>' +
          '</tr>',
      );
    });
  }

  document.addEventListener('DOMContentLoaded', init);

  return {};
})();
