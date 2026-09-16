/**
 * Reviews moderation page JavaScript (BooKi, Dalga 3 / Faz 3.4).
 *
 * Renders review requests per status tab and handles publish/reject moderation.
 */

'use strict';

App.Pages.Reviews = (function () {
  const STATUS_LABELS = {
    requested: 'Talep Edildi',
    pending: 'Bekliyor',
    published: 'Yayınlandı',
    rejected: 'Reddedildi',
  };

  const TABS = ['requested', 'pending', 'published', 'rejected'];

  let reviews = [];
  let counts = [];
  const initials = (window.scriptVars && window.scriptVars.initials) || {};
  const routes = (window.scriptVars && window.scriptVars.routes) || {};

  const STAR = '★';
  const STAR_EMPTY = '☆';

  function init() {
    reviews = (window.scriptVars.reviews || []).slice();
    counts = (window.scriptVars.counts || {});

    renderCounts();
    TABS.forEach((status) => renderTable(status));
  }

  function renderCounts() {
    TABS.forEach((status) => {
      const $badge = $('#count-' + status);
      if ($badge.length) {
        $badge.text(counts[status] || 0);
      }
    });
  }

  function recalculateCounts() {
    counts = { requested: 0, pending: 0, published: 0, rejected: 0 };
    reviews.forEach((r) => {
      if (counts[r.status] !== undefined) {
        counts[r.status] += 1;
      }
    });
    renderCounts();
  }

  function renderTable(status) {
    const $tbody = $('#reviews-table-' + status + ' tbody');
    if (!$tbody.length) {
      return;
    }

    const rows = reviews.filter((r) => r.status === status);

    if (rows.length === 0) {
      $tbody.html('<tr><td colspan="5" class="text-center text-muted py-4">Bu durumda yorum bulunmuyor.</td></tr>');
      return;
    }

    let html = '';

    rows.forEach((review) => {
      html += '<tr>';

      html += '<td>' + escapeHtml(displayName(review)) + '</td>';

      html += '<td><span class="text-warning">' + STAR.repeat(review.rating || 0) + STAR_EMPTY.repeat(Math.max(0, 5 - (review.rating || 0))) + '</span></td>';

      html += '<td class="text-truncate" style="max-width: 300px;" title="' + escapeHtml(review.comment || '') + '">' + escapeHtml(review.comment || '—') + '</td>';

      html += '<td class="text-muted">' + formattedDate(review.submitted_at || review.created_at) + '</td>';

      html += '<td class="text-end">';
      if (status === 'pending' && initials.can_edit) {
        html += '<button type="button" class="btn btn-sm btn-success publish-review-btn me-1" data-id="' + review.id + '">';
        html += '<i class="fas fa-check"></i> Yayınla</button>';
        html += '<button type="button" class="btn btn-sm btn-outline-danger reject-review-btn" data-id="' + review.id + '">';
        html += '<i class="fas fa-times"></i> Reddet</button>';
      } else {
        html += '<span class="badge ' + statusBadge(status) + '">' + STATUS_LABELS[status] + '</span>';
      }
      html += '</td>';

      html += '</tr>';
    });

    $tbody.html(html);
  }

  function displayName(review) {
    if (review.customer_name_display) {
      return review.customer_name_display;
    }

    if (review.customer_name) {
      return review.customer_name;
    }

    return '—';
  }

  function formattedDate(dateStr) {
    if (!dateStr) {
      return '—';
    }

    const d = new Date(dateStr.replace(' ', 'T'));

    if (isNaN(d.getTime())) {
      return '—';
    }

    return d.toLocaleDateString('tr-TR', { day: '2-digit', month: '2-digit', year: 'numeric' }) +
      ' ' + d.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' });
  }

  function statusBadge(status) {
    const map = {
      requested: 'bg-secondary',
      pending: 'bg-warning',
      published: 'bg-success',
      rejected: 'bg-danger',
    };

    return map[status] || 'bg-secondary';
  }

  function moderate(reviewId, action) {
    const url = action === 'publish' ? routes.publish : routes.reject;

    if (!url) {
      return;
    }

    const confirmation =
      action === 'publish'
        ? 'Bu yorum yayınlanacak ve marketplace profilinizde görünecek. Devam edilsin mi?'
        : 'Bu yorum reddedilecek ve marketplace\'ten kaldırılacak. Devam edilsin mi?';

    if (!window.confirm(confirmation)) {
      return;
    }

    fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: new URLSearchParams({ review_id: reviewId }),
    })
      .then((res) => res.json())
      .then((result) => {
        if (result.success) {
          reviews = (window.scriptVars.reviews || []).slice();
          const idx = reviews.findIndex((r) => r.id === reviewId);
          if (idx >= 0) {
            reviews[idx] = result.review;
          } else {
            reviews.unshift(result.review);
          }
          recalculateCounts();
          TABS.forEach((status) => renderTable(status));
        } else {
          window.alert(result.message || 'Bir hata oluştu.');
        }
      })
      .catch(() => window.alert('İsteğiniz işlenirken bir hata oluştu.'));
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  document.addEventListener('DOMContentLoaded', function () {
    // Event delegation on a stable container (tbody content is re-rendered).
    $('#reviews-tab-content').on('click', '.publish-review-btn', function () {
      moderate($(this).data('id'), 'publish');
    });

    $('#reviews-tab-content').on('click', '.reject-review-btn', function () {
      moderate($(this).data('id'), 'reject');
    });

    init();
  });
})();