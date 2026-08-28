/**
 * Memberships page JavaScript (Ki Reservation, Dalga 1).
 *
 * Handles plan creation, selling a plan to a customer, listing/renewing/
 * cancelling customer memberships.
 */

'use strict';

App.Pages.Memberships = (function () {
  const $planModal = $('#plan-modal');
  const $planForm = $('#plan-form');
  const $membershipModal = $('#membership-modal');
  const $membershipForm = $('#membership-form');
  const $table = $('table');
  const $tbody = $table.find('tbody');

  let tableRows = [];

  function init() {
    addEventListeners();
    populateSelects();
    search();
  }

  function addEventListeners() {
    $('#add-plan').on('click', function () {
      $planForm[0].reset();
      $planModal.modal('show');
    });

    $('#add-membership').on('click', function () {
      $membershipForm[0].reset();
      $membershipModal.modal('show');
    });

    $('#save-plan').on('click', onSavePlanClick);
    $('#save-membership').on('click', onSaveMembershipClick);
    $tbody.on('click', 'button.renew-btn', onRenewClick);
    $tbody.on('click', 'button.cancel-btn', onCancelClick);
  }

  function populateSelects() {
    const $planService = $('#plan-service');
    const $membershipCustomer = $('#membership-customer');
    const $membershipPlan = $('#membership-plan');

    $planService.empty().append('<option value="">' + App.Lang.select + '</option>');
    $membershipCustomer.empty().append('<option value="">' + App.Lang.select + '</option>');
    $membershipPlan.empty().append('<option value="">' + App.Lang.select + '</option>');

    (window.scriptVars.services || []).forEach(function (service) {
      $planService.append('<option value="' + service.id + '">' + service.name + '</option>');
    });

    (window.scriptVars.customers || []).forEach(function (customer) {
      const name = (customer.first_name || '') + ' ' + (customer.last_name || '');
      $membershipCustomer.append('<option value="' + customer.id + '">' + name.trim() + '</option>');
    });

    (window.scriptVars.plans || []).forEach(function (plan) {
      $membershipPlan.append('<option value="' + plan.id + '">' + plan.name + '</option>');
    });
  }

  function onSavePlanClick() {
    if (!$planForm[0].checkValidity()) {
      $planForm[0].reportValidity();
      return;
    }

    const formData = new FormData($planForm[0]);
    const planData = {};

    formData.forEach(function (value, key) {
      if (key.startsWith('plan[') && value !== '') {
        planData[key.replace('plan[', '').replace(']', '')] = value;
      }
    });

    $.ajax({
      url: App.Utils.ajaxUrl('memberships/store_plan'),
      type: 'POST',
      dataType: 'json',
      data: { plan: planData },
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        if (response.success) {
          $planModal.modal('hide');
          App.Utils.message('Plan oluşturuldu. Sayfayı yenileyin.', 'success');
        }
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  function onSaveMembershipClick() {
    if (!$membershipForm[0].checkValidity()) {
      $membershipForm[0].reportValidity();
      return;
    }

    const formData = new FormData($membershipForm[0]);
    const membershipData = {};

    formData.forEach(function (value, key) {
      if (key.startsWith('membership[') && value !== '') {
        membershipData[key.replace('membership[', '').replace(']', '')] = value;
      }
    });

    $.ajax({
      url: App.Utils.ajaxUrl('memberships/store'),
      type: 'POST',
      dataType: 'json',
      data: { membership: membershipData },
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        if (response.success) {
          $membershipModal.modal('hide');
          search();
          App.Utils.message('Üyelik satıldı.', 'success');
        }
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  function onRenewClick() {
    const membershipId = $(this).data('id');

    $.ajax({
      url: App.Utils.ajaxUrl('memberships/renew'),
      type: 'POST',
      dataType: 'json',
      data: { membership_id: membershipId },
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

  function onCancelClick() {
    const membershipId = $(this).data('id');

    $.ajax({
      url: App.Utils.ajaxUrl('memberships/cancel'),
      type: 'POST',
      dataType: 'json',
      data: { membership_id: membershipId },
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
      url: App.Utils.ajaxUrl('memberships/search'),
      type: 'POST',
      dataType: 'json',
      data: {},
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (memberships) {
        tableRows = memberships;
        renderTable();
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  function renderTable() {
    $tbody.empty();

    tableRows.forEach(function (membership) {
      const customerName = ((membership.customer_first_name || '') + ' ' + (membership.customer_last_name || '')).trim();
      const badgeClass =
        membership.status === 'active' ? 'success' : membership.status === 'past_due' ? 'warning' : 'secondary';

      const $row = $(
        '<tr>' +
          '<td>#' + membership.id + '</td>' +
          '<td>' + customerName + '</td>' +
          '<td>' + (membership.plan_name || '') + '</td>' +
          '<td><span class="badge bg-' + badgeClass + '">' + membership.status + '</span></td>' +
          '<td>' + (membership.current_period_end || '-') + '</td>' +
          '<td>' + membership.sessions_used_this_period + '</td>' +
          '<td>' +
          '<button class="btn btn-sm btn-outline-success renew-btn" data-id="' + membership.id + '">Yenile</button> ' +
          '<button class="btn btn-sm btn-outline-danger cancel-btn" data-id="' + membership.id + '">İptal</button>' +
          '</td>' +
          '</tr>',
      );

      $tbody.append($row);
    });
  }

  document.addEventListener('DOMContentLoaded', init);

  return {};
})();
