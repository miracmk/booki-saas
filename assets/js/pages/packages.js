/**
 * Packages page JavaScript.
 *
 * Handles CRUD operations for customer packages.
 */

'use strict';

App.Pages.Packages = (function () {
  const $packageModal = $('#package-modal');
  const $packageForm = $('#package-form');
  const $addPackageBtn = $('#add-package');
  const $savePackageBtn = $('#save-package');
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
    $addPackageBtn.on('click', onAddPackageClick);
    $savePackageBtn.on('click', onSavePackageClick);
    $keywordInput.on('keyup', onKeywordInputChange);
    $tbody.on('click', 'button', onActionButtonClick);
  }

  /**
   * Handle add package button click.
   */
  function onAddPackageClick() {
    $packageForm[0].reset();
    $packageForm.find('input[name="package[id]"]').remove();
    populateSelects();
    $packageModal.modal('show');
  }

  /**
   * Populate select dropdowns.
   */
  function populateSelects() {
    const $customerSelect = $('#package-customer');
    const $serviceSelect = $('#package-service');

    $customerSelect.empty().append('<option value="">' + App.Lang.select + '</option>');
    $serviceSelect.empty().append('<option value="">' + App.Lang.select + '</option>');

    if (window.scriptVars.customers) {
      window.scriptVars.customers.forEach(function (customer) {
        const name = (customer.first_name || '') + ' ' + (customer.last_name || '');
        $customerSelect.append(
          '<option value="' + customer.id + '">' + name.trim() + '</option>'
        );
      });
    }

    if (window.scriptVars.services) {
      window.scriptVars.services.forEach(function (service) {
        $serviceSelect.append(
          '<option value="' + service.id + '">' + service.name + '</option>'
        );
      });
    }
  }

  /**
   * Handle save package button click.
   */
  function onSavePackageClick() {
    if (!$packageForm[0].checkValidity()) {
      $packageForm[0].reportValidity();
      return;
    }

    const formData = new FormData($packageForm[0]);
    const packageData = {};

    formData.forEach(function (value, key) {
      if (key.startsWith('package[')) {
        const fieldName = key.replace('package[', '').replace(']', '');
        packageData[fieldName] = value;
      }
    });

    const isNew = !packageData.id;
    const url = isNew ? App.Utils.ajaxUrl('packages/store') : App.Utils.ajaxUrl('packages/update');

    $.ajax({
      url: url,
      type: 'POST',
      dataType: 'json',
      data: { package: packageData },
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        if (response.success) {
          $packageModal.modal('hide');
          search('');
          App.Utils.message(
            isNew ? App.Lang.package_created : App.Lang.package_updated,
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
   * Search packages by keyword.
   */
  function search(keyword) {
    $.ajax({
      url: App.Utils.ajaxUrl('packages/search'),
      type: 'POST',
      dataType: 'json',
      data: { keyword: keyword },
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (packages) {
        tableRows = packages;
        renderTable();
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  /**
   * Render packages table.
   */
  function renderTable() {
    $tbody.empty();

    if (!tableRows || tableRows.length === 0) {
      $tbody.html(
        '<tr><td colspan="8" class="text-center text-muted py-5">' +
        '<i class="fas fa-boxes fa-3x text-secondary opacity-50 mb-3 d-block"></i>' +
        '<span>Henüz kayıtlı paket seans bulunmuyor.</span></td></tr>'
      );
      return;
    }

    tableRows.forEach(function (package_) {
      const statusText =
        package_.status === 'active'
          ? (App.Lang.active || 'Aktif')
          : package_.status === 'exhausted'
            ? (App.Lang.exhausted || 'Tükendi')
            : (App.Lang.cancelled || 'İptal');

      const statusClass =
        package_.status === 'active'
          ? 'bg-success-subtle text-success border border-success-subtle'
          : package_.status === 'exhausted'
            ? 'bg-warning-subtle text-warning-emphasis border border-warning-subtle'
            : 'bg-secondary-subtle text-secondary border border-secondary-subtle';

      const statusBadge =
        '<span class="badge ' +
        statusClass +
        ' px-2 py-1">' +
        statusText +
        '</span>';

      const expiresDate = package_.expires_at
        ? '<span class="text-nowrap">' + new Date(package_.expires_at).toLocaleDateString('tr-TR') + '</span>'
        : '<span class="text-muted">' + (App.Lang.no_expiry || 'Süresiz') + '</span>';

      let customerName = '';
      if (package_.customer_first_name || package_.customer_last_name) {
        customerName = ((package_.customer_first_name || '') + ' ' + (package_.customer_last_name || '')).trim();
      } else if (window.scriptVars && window.scriptVars.customers) {
        const cust = window.scriptVars.customers.find(function (c) {
          return String(c.id) === String(package_.id_users_customer);
        });
        if (cust) {
          customerName = ((cust.first_name || '') + ' ' + (cust.last_name || '')).trim();
        }
      }
      if (!customerName) {
        customerName = '#' + package_.id_users_customer;
      }

      let serviceName = package_.service_name || '';
      if (!serviceName && window.scriptVars && window.scriptVars.services) {
        const srv = window.scriptVars.services.find(function (s) {
          return String(s.id) === String(package_.id_services);
        });
        if (srv) {
          serviceName = srv.name;
        }
      }
      if (!serviceName) {
        serviceName = '#' + package_.id_services;
      }

      const $row = $(
        '<tr>' +
          '<td class="ps-3 fw-bold text-muted">#' + package_.id + '</td>' +
          '<td class="fw-semibold text-dark">' + customerName + '</td>' +
          '<td><span class="badge bg-light text-dark border">' + serviceName + '</span></td>' +
          '<td class="text-center"><span class="fw-semibold text-primary">' + package_.total_sessions + '</span> <span class="text-muted small">Seans</span></td>' +
          '<td class="text-center"><span class="fw-semibold">' + package_.used_sessions + '</span> <span class="text-muted small">Seans</span></td>' +
          '<td class="text-center">' + statusBadge + '</td>' +
          '<td>' + expiresDate + '</td>' +
          '<td class="text-end pe-3">' +
          '<div class="btn-group btn-group-sm">' +
          '<button class="btn btn-outline-primary edit-btn" data-id="' +
          package_.id +
          '" title="' +
          (App.Lang.edit || 'Düzenle') +
          '">' +
          '<i class="fas fa-edit"></i>' +
          '</button>' +
          '<button class="btn btn-outline-danger delete-btn" data-id="' +
          package_.id +
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
  function onEditClick(packageId) {
    $.ajax({
      url: App.Utils.ajaxUrl('packages/find'),
      type: 'POST',
      dataType: 'json',
      data: { package_id: packageId },
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (package_) {
        populateSelects();
        $packageForm[0].reset();

        // Add hidden input for ID
        $packageForm.append('<input type="hidden" name="package[id]" value="' + package_.id + '" />');

        // Populate form fields
        $('#package-customer').val(package_.id_users_customer);
        $('#package-service').val(package_.id_services);
        $('#package-total-sessions').val(package_.total_sessions);
        $('#package-unit-price').val(package_.unit_price || '');

        if (package_.expires_at) {
          const date = new Date(package_.expires_at);
          const iso = date.toISOString().slice(0, 16);
          $('#package-expires-at').val(iso);
        }

        $('#package-status').val(package_.status);

        $packageModal.modal('show');
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  /**
   * Handle delete button click.
   */
  function onDeleteClick(packageId) {
    if (!confirm(App.Lang.confirm_delete)) {
      return;
    }

    $.ajax({
      url: App.Utils.ajaxUrl('packages/destroy'),
      type: 'POST',
      dataType: 'json',
      data: { package_id: packageId },
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        if (response.success) {
          search('');
          App.Utils.message(App.Lang.package_deleted, 'success');
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
  App.Pages.Packages.init();
});
