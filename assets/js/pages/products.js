/**
 * Products page JavaScript.
 *
 * Handles CRUD operations for inventory/product management.
 */

'use strict';

App.Pages.Products = (function () {
  const $productModal = $('#product-modal');
  const $productForm = $('#product-form');
  const $addProductBtn = $('#add-product');
  const $saveProductBtn = $('#save-product');
  const $keywordInput = $('.keyword');
  const $table = $('table');
  const $tbody = $table.find('tbody');

  let tableRows = [];

  /**
   * Initialize page.
   */
  function init() {
    addEventListeners();
    search('');
  }

  /**
   * Add event listeners to page elements.
   */
  function addEventListeners() {
    $addProductBtn.on('click', onAddProductClick);
    $saveProductBtn.on('click', onSaveProductClick);
    $keywordInput.on('keyup', onKeywordInputChange);
    $tbody.on('click', 'button', onActionButtonClick);
  }

  /**
   * Handle add product button click.
   */
  function onAddProductClick() {
    $productForm[0].reset();
    $productForm.find('input[name="product[id]"]').remove();
    $productModal.modal('show');
  }

  /**
   * Handle save product button click.
   */
  function onSaveProductClick() {
    if (!$productForm[0].checkValidity()) {
      $productForm[0].reportValidity();
      return;
    }

    const formData = new FormData($productForm[0]);
    const productData = {};

    formData.forEach(function (value, key) {
      if (key.startsWith('product[')) {
        const fieldName = key.replace('product[', '').replace(']', '');
        if (fieldName === 'is_active') {
          productData[fieldName] = value === '1' ? 1 : 0;
        } else {
          productData[fieldName] = value;
        }
      }
    });

    const isNew = !productData.id;
    const url = isNew ? App.Utils.ajaxUrl('products/store') : App.Utils.ajaxUrl('products/update');

    $.ajax({
      url: url,
      type: 'POST',
      dataType: 'json',
      data: { product: productData },
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        if (response.success) {
          $productModal.modal('hide');
          search('');
          App.Utils.message(
            isNew ? App.Lang.product_created : App.Lang.product_updated,
            'success'
          );
        }
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  /**
   * Handle keyword input change.
   */
  function onKeywordInputChange() {
    const keyword = $keywordInput.val();
    search(keyword);
  }

  /**
   * Search products by keyword.
   */
  function search(keyword) {
    $.ajax({
      url: App.Utils.ajaxUrl('products/search'),
      type: 'POST',
      dataType: 'json',
      data: { keyword: keyword },
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (products) {
        tableRows = products;
        renderTable();
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  /**
   * Render products table.
   */
  function renderTable() {
    $tbody.empty();

    if (!tableRows || tableRows.length === 0) {
      $tbody.html(
        '<tr><td colspan="8" class="text-center text-muted py-5">' +
        '<i class="fas fa-cubes fa-3x text-secondary opacity-50 mb-3 d-block"></i>' +
        '<span>Henüz kayıtlı ürün bulunmuyor.</span></td></tr>'
      );
      return;
    }

    tableRows.forEach(function (product) {
      const statusClass =
        product.stock_quantity <= 0
          ? 'bg-danger-subtle text-danger border border-danger-subtle'
          : product.stock_quantity <= product.low_stock_threshold
            ? 'bg-warning-subtle text-warning-emphasis border border-warning-subtle'
            : 'bg-success-subtle text-success border border-success-subtle';

      const statusBadge =
        '<span class="badge ' +
        statusClass +
        ' px-2 py-1">' +
        (product.stock_quantity <= 0
          ? (App.Lang.out_of_stock || 'Tükendi')
          : product.stock_quantity <= product.low_stock_threshold
            ? (App.Lang.low_stock || 'Kritik Stok')
            : (App.Lang.in_stock || 'Stokta')) +
        '</span>';

      const formattedPrice = parseFloat(product.sale_price || 0).toLocaleString('tr-TR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }) + ' ₺';

      const $row = $(
        '<tr>' +
          '<td class="ps-3 fw-bold text-muted">#' + product.id + '</td>' +
          '<td class="fw-semibold text-dark">' + product.name + '</td>' +
          '<td><code class="text-muted">' + (product.sku || '-') + '</code></td>' +
          '<td class="text-end fw-semibold text-dark">' + formattedPrice + '</td>' +
          '<td class="text-center"><span class="fw-semibold">' + product.stock_quantity + '</span> <span class="text-muted small">Adet</span></td>' +
          '<td class="text-center text-muted">' + product.low_stock_threshold + ' <span class="small">Adet</span></td>' +
          '<td class="text-center">' + statusBadge + '</td>' +
          '<td class="text-end pe-3">' +
          '<div class="btn-group btn-group-sm">' +
          '<button class="btn btn-outline-primary edit-btn" data-id="' +
          product.id +
          '" title="' +
          (App.Lang.edit || 'Düzenle') +
          '">' +
          '<i class="fas fa-edit"></i>' +
          '</button>' +
          '<button class="btn btn-outline-danger delete-btn" data-id="' +
          product.id +
          '" title="' +
          (App.Lang.delete || 'Sil') +
          '">' +
          '<i class="fas fa-trash-alt"></i>' +
          '</button>' +
          '</div>' +
          '</td>' +
          '</tr>'
      );

      $tbody.append($row);
    });
  }

  /**
   * Handle action button click.
   */
  function onActionButtonClick(event) {
    const $button = $(event.target).closest('button');

    if ($button.hasClass('edit-btn')) {
      onEditClick($button.data('id'));
    } else if ($button.hasClass('delete-btn')) {
      onDeleteClick($button.data('id'));
    }
  }

  /**
   * Handle edit button click.
   */
  function onEditClick(productId) {
    $.ajax({
      url: App.Utils.ajaxUrl('products/find'),
      type: 'POST',
      dataType: 'json',
      data: { product_id: productId },
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (product) {
        $productForm[0].reset();

        // Add hidden input for ID
        $productForm.append('<input type="hidden" name="product[id]" value="' + product.id + '" />');

        // Populate form fields
        $('#product-name').val(product.name);
        $('#product-sku').val(product.sku || '');
        $('#product-sale-price').val(parseFloat(product.sale_price).toFixed(2));
        $('#product-cost-price').val(product.cost_price ? parseFloat(product.cost_price).toFixed(2) : '');
        $('#product-stock-quantity').val(product.stock_quantity);
        $('#product-low-stock-threshold').val(product.low_stock_threshold);
        $('#product-is-active').prop('checked', product.is_active === 1 || product.is_active === true);

        $productModal.modal('show');
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  /**
   * Handle delete button click.
   */
  function onDeleteClick(productId) {
    if (!confirm(App.Lang.confirm_delete)) {
      return;
    }

    $.ajax({
      url: App.Utils.ajaxUrl('products/destroy'),
      type: 'POST',
      dataType: 'json',
      data: { product_id: productId },
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        if (response.success) {
          search('');
          App.Utils.message(App.Lang.product_deleted, 'success');
        }
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  return {
    init: init,
  };
})();

// Initialize page when ready
$(document).ready(function () {
  App.Pages.Products.init();
});
