/* ----------------------------------------------------------------------------
 * BooKi - "İlk Müsaitlik" widget (2026-09-10, redesigned 2026-09-12).
 *
 * Renders a horizontal strip of compact pills, one per provider, each showing
 * room/station, next available time, how long that window lasts, and the
 * provider's name - reception sees who's free where at a glance instead of
 * eyeballing the calendar grid. Reuses Calendar::get_next_availability()
 * (server-side, itself built on the same Availability library the booking
 * wizard uses for provider/station matching) which as of 2026-09-12 returns
 * one row PER PROVIDER instead of a single globally-earliest slot.
 *
 * Mounts in up to two places, sharing the same render logic:
 *   - `#next-availability-strip` (dashboard.php) - unfiltered, all providers.
 *   - `#calendar-filter` (calendar.php toolbar) - respects the provider/service
 *     filter select, same as the original single-badge version.
 * ---------------------------------------------------------------------------- */
App.Components.NextAvailabilityWidget = (function () {
    const POLL_INTERVAL_MS = 60000;

    const mounts = []; // [{ $container, getProviderId }]

    /**
     * @returns {number|null} The selected provider's ID, or null if "Tümü"/a service is selected.
     */
    function getCalendarFilterProviderId() {
        const $selected = $('#select-filter-item option:selected');

        if ($selected.attr('type') !== 'provider') {
            return null;
        }

        return $selected.val();
    }

    function pillClass(row) {
        if (!row.available) {
            return 'kcc-availability-pill kcc-availability-pill-none';
        }

        if (row.is_now) {
            return 'kcc-availability-pill kcc-availability-pill-now';
        }

        if (Number.isFinite(row.window_minutes) && row.window_minutes < 20) {
            return 'kcc-availability-pill kcc-availability-pill-tight';
        }

        return 'kcc-availability-pill kcc-availability-pill-ok';
    }

    /**
     * A labeled "Terapist: X" / "Oda: Y" chip - always both present (even when unavailable, with a
     * "-" placeholder) so every pill has the same two-section shape, per user feedback.
     */
    function field(label, value) {
        return $('<span/>', { class: 'kcc-availability-field' })
            .append($('<span/>', { class: 'kcc-availability-field-label', text: label }))
            .append($('<span/>', { class: 'kcc-availability-field-value', text: value || '-' }));
    }

    function renderRow(row) {
        const $pill = $('<div/>', { class: pillClass(row) });

        $pill.append(field('Terapist', row.provider_name));
        $pill.append(field('Oda', row.station_name));

        if (!row.available) {
            $pill.append($('<span/>', { class: 'kcc-availability-time', text: 'Müsaitlik yok' }));
            return $pill;
        }

        $pill.append($('<span/>', { class: 'kcc-availability-time', text: row.is_now ? 'Şimdi' : row.time }));

        if (Number.isFinite(row.window_minutes)) {
            $pill.append($('<span/>', { class: 'kcc-availability-window', text: '~' + row.window_minutes + ' dk' }));
        }

        return $pill;
    }

    function renderGroup($container, label, rows, iconClass) {
        const $group = $('<div/>', { class: 'kcc-availability-group flex-grow-1' });
        const iconHtml = iconClass ? `<i class="${iconClass} me-1 opacity-75"></i> ` : '';
        $group.append($('<div/>', { class: 'kcc-availability-group-label d-flex align-items-center mb-1' }).html(iconHtml + label));

        const $row = $('<div/>', { class: 'kcc-availability-row d-flex gap-2' });

        if (!rows || !rows.length) {
            $row.append($('<span/>', { class: 'text-muted small fst-italic py-1', text: 'Müsaitlik bilgisi yok.' }));
        } else {
            rows.forEach((row) => $row.append(renderRow(row)));
        }

        $group.append($row);
        $container.append($group);
    }

    /**
     * 2026-09-12 - user feedback: the strip needs to be grouped ("gruplandırabilmeli"), separately
     * showing therapist-based AND room-based availability (rooms weren't checked at all before) - two
     * always-visible sections per mount rather than one flat provider-only list.
     */
    function render($container, providerRows, roomRows) {
        $container.empty();
        renderGroup($container, 'Terapistler', providerRows, 'fas fa-user-clock text-primary');
        renderGroup($container, 'Odalar', roomRows, 'fas fa-door-open text-info');
    }

    function pollMount(mount) {
        $.when(
            App.Http.Calendar.getNextAvailability(mount.getProviderId()),
            App.Http.Calendar.getRoomAvailability(),
        )
            .done((providerResponse, roomResponse) => {
                const providerRows = (providerResponse[0] && providerResponse[0].rows) || [];
                const roomRows = (roomResponse[0] && roomResponse[0].rows) || [];
                render(mount.$container, providerRows, roomRows);
            })
            .fail(() => {
                mount.$container.empty().append(
                    $('<span/>', { class: 'text-danger small', text: 'İlk müsaitlik alınamadı.' }),
                );
            });
    }

    function pollAll() {
        mounts.forEach(pollMount);
    }

    function initialize() {
        const $dashboardStrip = $('#next-availability-strip');

        if ($dashboardStrip.length) {
            mounts.push({ $container: $dashboardStrip, getProviderId: () => null });
        }

        if ($('#calendar-page').length) {
            let $calendarStrip = $('#next-availability-widget');
            if (!$calendarStrip.length) {
                $calendarStrip = $('<div/>', {
                    id: 'next-availability-widget',
                    class: 'kcc-availability-card p-3 bg-white rounded-3 shadow-sm border',
                });
                if ($('#next-availability-container').length) {
                    $('#next-availability-container').append($calendarStrip);
                } else if ($('#calendar-filter').length) {
                    $('#calendar-filter').prepend($calendarStrip);
                }
            } else {
                $calendarStrip.addClass('kcc-availability-card p-3 bg-white rounded-3 shadow-sm border');
            }

            mounts.push({ $container: $calendarStrip, getProviderId: getCalendarFilterProviderId });

            $('#select-filter-item').on('change', () => pollMount(mounts[mounts.length - 1]));
        }

        if (!mounts.length) {
            return;
        }

        pollAll();
        setInterval(pollAll, POLL_INTERVAL_MS);
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {};
})();
