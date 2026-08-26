/* ----------------------------------------------------------------------------
 * Salon Flora customization - Stations HTTP client.
 * ---------------------------------------------------------------------------- */

App.Http.Stations = (function () {
    function save(station) {
        return station.id ? update(station) : store(station);
    }

    function store(station) {
        const url = App.Utils.Url.siteUrl('stations/store');

        return $.post(url, {
            csrf_token: vars('csrf_token'),
            station: station,
        });
    }

    function update(station) {
        const url = App.Utils.Url.siteUrl('stations/update');

        return $.post(url, {
            csrf_token: vars('csrf_token'),
            station: station,
        });
    }

    function destroy(stationId) {
        const url = App.Utils.Url.siteUrl('stations/destroy');

        return $.post(url, {
            csrf_token: vars('csrf_token'),
            station_id: stationId,
        });
    }

    function search(keyword, limit = null, offset = null) {
        const url = App.Utils.Url.siteUrl('stations/search');

        return $.post(url, {
            csrf_token: vars('csrf_token'),
            keyword,
            limit,
            offset,
        });
    }

    function find(stationId) {
        const url = App.Utils.Url.siteUrl('stations/find');

        return $.post(url, {
            csrf_token: vars('csrf_token'),
            station_id: stationId,
        });
    }

    return {
        save,
        store,
        update,
        destroy,
        search,
        find,
    };
})();
