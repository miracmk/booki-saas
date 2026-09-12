/* ----------------------------------------------------------------------------
 * Ki Reservation - Notification bell panel (Dalga 4, 2026-09-12).
 *
 * Minimum viable default: polls Notifications_feed::recent() (currently just
 * normalized whatsapp_messages), renders into every `.kcc-notif-panel` on the
 * page (there can be two - mobile top bar + desktop sidebar header), and
 * tracks "seen" state client-side only (localStorage) - no DB write, no
 * migration. See Notifications_feed.php docblock for the natural v2 (server-
 * side read/unread, more event types).
 * ---------------------------------------------------------------------------- */
App.Components.NotificationPanel = (function () {
    const POLL_INTERVAL_MS = 60000;
    const LAST_SEEN_KEY = 'KiReservation.NotifLastSeenId';

    function getLastSeenId() {
        try {
            return parseInt(localStorage.getItem(LAST_SEEN_KEY), 10) || 0;
        } catch (e) {
            return 0;
        }
    }

    function setLastSeenId(id) {
        try {
            localStorage.setItem(LAST_SEEN_KEY, String(id));
        } catch (e) {
            // Private browsing / storage blocked - badge just won't persist across reloads.
        }
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function itemIcon(type) {
        return type === 'whatsapp_in' ? 'fa-arrow-down text-success' : 'fa-arrow-up text-muted';
    }

    function renderItems(items) {
        if (!items.length) {
            return '<div class="kcc-notif-empty text-muted small p-3">Henüz bildirim yok.</div>';
        }

        const lastSeen = getLastSeenId();

        return items
            .map((item) => {
                const unread = item.id > lastSeen;
                return (
                    '<div class="kcc-notif-item' + (unread ? ' kcc-notif-item-unread' : '') + '">' +
                        '<i class="fas ' + itemIcon(item.type) + ' kcc-notif-item-icon"></i>' +
                        '<div class="kcc-notif-item-body">' +
                            '<div class="kcc-notif-item-title">' + escapeHtml(item.title) + '</div>' +
                            '<div class="kcc-notif-item-subtitle">' + escapeHtml(item.subtitle) + '</div>' +
                        '</div>' +
                    '</div>'
                );
            })
            .join('');
    }

    function updateBadge(items) {
        const lastSeen = getLastSeenId();
        const unreadCount = items.filter((item) => item.id > lastSeen).length;

        $('.kcc-notif-badge').each(function () {
            const $badge = $(this);
            if (unreadCount > 0) {
                $badge.text(unreadCount > 9 ? '9+' : unreadCount).show();
            } else {
                $badge.hide();
            }
        });
    }

    function poll() {
        const url = App.Utils.Url.siteUrl('notifications_feed/recent');

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then((response) => response.json())
            .then((json) => {
                if (!json.success) {
                    return;
                }

                const items = json.items || [];
                $('.kcc-notif-list').html(renderItems(items));
                updateBadge(items);

                window.__kiNotifLatestItems = items;
            })
            .catch(() => {});
    }

    function markAllSeen() {
        const items = window.__kiNotifLatestItems || [];
        if (!items.length) {
            return;
        }

        const maxId = Math.max(...items.map((item) => item.id));
        setLastSeenId(maxId);

        $('.kcc-notif-item-unread').removeClass('kcc-notif-item-unread');
        $('.kcc-notif-badge').hide();
    }

    function initialize() {
        if (!$('.kcc-notif-trigger').length) {
            return;
        }

        poll();
        setInterval(poll, POLL_INTERVAL_MS);

        $(document).on('click', '.kcc-notif-trigger', () => {
            // Bootstrap opens the dropdown itself; give the panel a beat to render, then mark seen.
            setTimeout(markAllSeen, 300);
        });
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {};
})();
