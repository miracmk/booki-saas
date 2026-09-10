/* ----------------------------------------------------------------------------
 * Ki Reservation - "İlk Müsaitlik" widget (2026-09-10).
 *
 * Small toolbar indicator, left side of the calendar filter row, answering "who's free, in which
 * room, and for how long" at a glance - reception doesn't have to eyeball the grid. Reuses
 * Calendar::get_next_availability() (server-side, itself built on the same Availability library the
 * booking wizard uses for provider/station matching).
 * ---------------------------------------------------------------------------- */
App.Components.NextAvailabilityWidget = (function () {
    const POLL_INTERVAL_MS = 60000;

    let $widget;
    let pollTimer = null;

    /**
     * Inject the widget element into the toolbar's left column.
     */
    function buildDom() {
        $widget = $('<div/>', {
            id: 'next-availability-widget',
            class: 'badge bg-light text-dark mb-2 mb-lg-0 d-inline-block',
            css: {fontSize: '.8rem', fontWeight: 'normal', padding: '.5rem .75rem'},
            text: '…',
        });

        $('#calendar-filter').prepend($widget);
    }

    /**
     * @returns {number|null} The selected provider's ID, or null if "Tümü"/a service is selected.
     */
    function getSelectedProviderId() {
        const $selected = $('#select-filter-item option:selected');

        if ($selected.attr('type') !== 'provider') {
            return null;
        }

        return $selected.val();
    }

    /**
     * Fetch and render the current "next availability" state.
     */
    function poll() {
        if (!$widget) {
            return;
        }

        App.Http.Calendar.getNextAvailability(getSelectedProviderId())
            .done((response) => {
                render(response);
            })
            .fail(() => {
                $widget.text('İlk müsaitlik alınamadı').removeClass('bg-danger text-white').addClass('bg-light text-dark');
            });
    }

    /**
     * @param {Object} data - Response from get_next_availability.
     */
    function render(data) {
        if (!data || !data.available) {
            $widget
                .text('Müsaitlik yok')
                .removeClass('bg-light text-dark')
                .addClass('bg-danger text-white');
            return;
        }

        const parts = [];

        if (data.provider_name) {
            parts.push(data.provider_name);
        }

        if (data.station_name) {
            parts.push(data.station_name);
        }

        parts.push(data.is_now ? 'Şimdi müsait' : data.time);

        let text = 'İlk müsait: ' + parts.join(' · ');

        if (Number.isFinite(data.window_minutes)) {
            text += ' (~' + data.window_minutes + ' dk)';
        }

        $widget
            .text(text)
            .removeClass('bg-danger text-white')
            .addClass('bg-light text-dark');
    }

    /**
     * Initialize the widget. Only meaningful on the calendar page (default view, not the table view -
     * that one has no #calendar-filter/#select-filter-item single-select).
     */
    function initialize() {
        if (!$('#calendar-page').length || !$('#calendar-filter').length) {
            return;
        }

        buildDom();
        poll();

        // Re-poll whenever the provider/service filter changes (same event calendar_default_view.js
        // already listens on for reloading appointments).
        $('#select-filter-item').on('change', poll);

        pollTimer = setInterval(poll, POLL_INTERVAL_MS);
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {};
})();
