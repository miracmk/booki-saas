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

  let selectedProvider = '';
  let selectedStation = '';

  function init() {
    reviews = (window.scriptVars.reviews || []).slice();
    counts = (window.scriptVars.counts || {});

    $('#review-filter-provider').on('change', function () {
      selectedProvider = $(this).val();
      TABS.forEach((status) => renderTable(status));
    });

    $('#review-filter-station').on('change', function () {
      selectedStation = $(this).val();
      TABS.forEach((status) => renderTable(status));
    });

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

    let rows = reviews.filter((r) => r.status === status);

    if (selectedProvider) {
      rows = rows.filter((r) => String(r.id_users_provider) === selectedProvider);
    }
    if (selectedStation) {
      rows = rows.filter((r) => String(r.id_stations) === selectedStation);
    }

    if (rows.length === 0) {
      if (selectedProvider || selectedStation) {
        $tbody.html(
          '<tr><td colspan="8" class="text-center text-muted py-5">' +
          '<div class="py-3">' +
          '<i class="fas fa-filter fa-3x text-muted opacity-25 d-block mb-3"></i>' +
          '<h6 class="fw-semibold text-dark mb-1">Seçilen filtrelere uygun yorum bulunmuyor</h6>' +
          '<p class="small text-muted mb-3">Farklı bir uzman veya istasyon seçerek filtreleri sıfırlayabilirsiniz.</p>' +
          '<button class="btn btn-sm btn-outline-secondary" onclick="$(\'#review-filter-provider, #review-filter-station\').val(\'\').trigger(\'change\')">' +
          '<i class="fas fa-times me-1"></i>Filtreleri Sıfırla' +
          '</button>' +
          '</div></td></tr>'
        );
      } else {
        const tabMessages = {
          requested: 'Henüz gönderilmiş değerlendirme talebi yok',
          pending: 'Şu anda onay veya red bekleyen yeni bir değerlendirme bulunmuyor',
          published: 'Henüz yayınlanmış bir müşteri değerlendirmesi yok',
          rejected: 'Reddedilmiş bir değerlendirme bulunmuyor'
        };
        $tbody.html(
          '<tr><td colspan="8" class="text-center text-muted py-5">' +
          '<div class="py-3">' +
          '<i class="fas fa-star fa-3x text-warning opacity-50 d-block mb-3"></i>' +
          '<h6 class="fw-semibold text-dark mb-1">' + (tabMessages[status] || 'Değerlendirme bulunmuyor') + '</h6>' +
          '<p class="small text-muted mb-3">Tamamlanan randevulardan sonra müşterilere otomatik olarak değerlendirme bağlantısı gönderilir.</p>' +
          '<a href="' + App.Utils.Url.siteUrl('calendar') + '" class="btn btn-sm btn-primary">' +
          '<i class="fas fa-calendar-check me-1"></i>Randevuları Görüntüle' +
          '</a>' +
          '</div></td></tr>'
        );
      }
      return;
    }

    let html = '';

    rows.forEach((review) => {
      html += '<tr>';

      // 1. Kim (Müşteri)
      html += '<td><strong>' + escapeHtml(displayName(review)) + '</strong></td>';

      // 2. Kime (Uzman)
      html += '<td>' + (review.provider_name_display ? '<span class="badge bg-light text-dark border">' + escapeHtml(review.provider_name_display) + '</span>' : '—') + '</td>';

      // 3. Oda / İstasyon
      html += '<td>' + (review.station_name ? '<span class="badge bg-secondary-subtle text-dark border"><i class="fas fa-door-open me-1"></i>' + escapeHtml(review.station_name) + '</span>' : '—') + '</td>';

      // 4. Genel Puan
      html += '<td><span class="text-warning">' + STAR.repeat(review.rating || 0) + STAR_EMPTY.repeat(Math.max(0, 5 - (review.rating || 0))) + '</span> ' + (review.rating ? '(' + review.rating + '/5)' : '') + '</td>';

      // 5. Oda Değerlendirmesi
      let roomHtml = '<span class="text-muted">—</span>';
      if (review.station_rating) {
        const roomHappy = review.station_rating >= 4
          ? '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fas fa-smile me-1"></i>Beğendi</span>'
          : (review.station_rating <= 2
            ? '<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="fas fa-frown me-1"></i>Beğenmedi</span>'
            : '<span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="fas fa-meh me-1"></i>Orta</span>');

        roomHtml = '<div class="d-flex flex-column gap-1">' +
          '<div><span class="badge bg-light text-primary border">★ ' + review.station_rating + '/5</span> ' + roomHappy + '</div>';

        if (review.station_comment) {
          roomHtml += '<div class="small text-muted text-truncate" style="max-width: 200px;" title="' + escapeHtml(review.station_comment) + '"><i class="fas fa-comment-dots me-1 text-secondary"></i>' + escapeHtml(review.station_comment) + '</div>';
        }
        roomHtml += '</div>';
      }
      html += '<td>' + roomHtml + '</td>';

      // 6. Yorum
      html += '<td class="text-truncate" style="max-width: 240px;" title="' + escapeHtml(review.comment || '') + '">' + escapeHtml(review.comment || '—') + '</td>';

      // 7. Tarih
      html += '<td class="text-muted small">' + formattedDate(review.submitted_at || review.created_at) + '</td>';

      // 8. İşlemler
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