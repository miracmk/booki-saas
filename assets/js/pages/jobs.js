/**
 * Jobs page JavaScript (BooKi, Faz 32+33, 2026-08-28).
 *
 * Handles display and management of the job queue, including monitoring
 * queue status and manually retrying failed jobs.
 */

'use strict';

App.Pages.Jobs = (function () {
  const $table = $('table');
  const $tbody = $table.find('tbody');

  let jobData = [];

  /**
   * Initialize page.
   */
  function init() {
    addEventListeners();
    search();
    // Refresh every 30 seconds
    setInterval(search, 30000);
  }

  /**
   * Add event listeners to page elements.
   */
  function addEventListeners() {
    $tbody.on('click', 'button.retry-btn', onRetryButtonClick);
  }

  /**
   * Handle retry button click.
   */
  function onRetryButtonClick() {
    const jobId = $(this).data('id');

    if (!confirm('Bu işi yeniden çalıştırmak istediğinizden emin misiniz?')) {
      return;
    }

    $.ajax({
      url: App.Utils.ajaxUrl('jobs/retry'),
      type: 'POST',
      dataType: 'json',
      data: { job_id: jobId },
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        if (response.success) {
          search();
          App.Utils.message('İş yeniden sıraya eklendi.', 'success');
        }
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  /**
   * Fetch the current queue status and recent failed jobs.
   */
  function search() {
    $.ajax({
      url: App.Utils.ajaxUrl('jobs/search'),
      type: 'POST',
      dataType: 'json',
      data: { limit: 50, offset: 0 },
      headers: { 'X-CSRF-Token': App.Security.csrfToken },
    })
      .done(function (response) {
        if (response.counts) {
          updateCounts(response.counts);
        }
        if (response.failures) {
          jobData = response.failures;
          renderTable();
        }
      })
      .fail(function (jqxhr) {
        App.Utils.ajaxErrorMsg(jqxhr);
      });
  }

  /**
   * Update the queue status summary counts.
   */
  function updateCounts(counts) {
    $('#count-pending').text(counts.pending || 0);
    $('#count-reserved').text(counts.reserved || 0);
    $('#count-succeeded').text(counts.succeeded || 0);
    $('#count-failed').text(counts.failed || 0);
  }

  /**
   * Render the failed jobs table.
   */
  function renderTable() {
    $tbody.empty();

    jobData.forEach(function (job) {
      const lastError = (job.last_error || '').substring(0, 100);
      const errorDisplay = lastError ? (lastError + (job.last_error.length > 100 ? '...' : '')) : '-';

      const $row = $(
        '<tr>' +
          '<td>#' + job.id + '</td>' +
          '<td>' + (job.queue || '-') + '</td>' +
          '<td><code style="font-size: 0.8rem;">' + escapeHtml(job.handler || '-') + '</code></td>' +
          '<td><span class="badge bg-danger">' + (job.status || '-') + '</span></td>' +
          '<td>' + (job.attempts || 0) + ' / ' + (job.max_attempts || 0) + '</td>' +
          '<td style="font-size: 0.85rem; max-width: 200px; word-break: break-all;">' + escapeHtml(errorDisplay) + '</td>' +
          '<td><code style="font-size: 0.75rem;">' + (job.correlation_id ? job.correlation_id.substring(0, 16) + '...' : '-') + '</code></td>' +
          '<td>' +
            '<button class="btn btn-sm btn-outline-primary retry-btn" data-id="' +
            job.id +
            '" title="Yeniden dene">' +
            '<i class="fas fa-redo"></i></button>' +
          '</td>' +
          '</tr>',
      );

      $tbody.append($row);
    });

    if (jobData.length === 0) {
      $tbody.append(
        '<tr><td colspan="8" class="text-center text-muted">Son saatte başarısız iş yok.</td></tr>',
      );
    }
  }

  /**
   * Escape HTML special characters for safe display.
   */
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
