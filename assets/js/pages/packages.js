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

    tableRows.forEach(function (package_) {
      const statusBadge =
        '<span class="badge bg-' +
        (package_.status === 'active'
          ? 'success'
          : package_.status === 'exhausted'
            ? 'warning'
            : 'secondary') +
        '">' +
        package_.status +
        '</span>';

      const expiresDate = package_.expires_at
        ? new Date(package_.expires_at).toLocaleDateString('tr-TR')
        : App.Lang.no_expiry;

      const $row = $(
        '<tr>' +
          '<td>#' + package_.id + '</td>' +
          '<td>' + package_.id_users_customer + '</td>' +
          '<td>' + package_.id_services + '</td>' +
          '<td>' + package_.total_sessions + '</td>' +
          '<td>' + package_.used_sessions + '</td>' +
          '<td>' + statusBadge + '</td>' +
          '<td>' + expiresDate + '</td>' +
          '<td>' +
          '<button class="btn btn-sm btn-info edit-btn" data-id="' +
          package_.id +
          '" title="' +
          App.Lang.edit +
          '">' +
          '<i class="fas fa-edit"></i>' +
          '</button> ' +
          '<button class="btn btn-sm btn-danger delete-btn" data-id="' +
          package_.id +
          '" title="' +
          App.Lang.delete +
          '">' +
          '<i class="fas fa-trash"></i>' +
          '</button>' +
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
