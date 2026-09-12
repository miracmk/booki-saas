/* ----------------------------------------------------------------------------
 * Salon Flora customization - Reports page (daily revenue).
 * ---------------------------------------------------------------------------- */

App.Pages.Reports = (function () {
    const $reportDate = $('#report-date');
    const $reportSummary = $('#report-summary');
    const $reportTableBody = $('#report-table tbody');

    function formatCurrency(amount) {
        return Number(amount).toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' TL';
    }

    function formatCommission(provider) {
        if (provider.commission_type === 'fixed') {
            return formatCurrency(provider.commission_value) + ' / seans';
        }

        if (provider.commission_type === 'hourly') {
            return formatCurrency(provider.commission_value) + ' / saat';
        }

        return Number(provider.commission_value).toLocaleString('tr-TR') + ' %';
    }

    /**
     * Salon Flora customization - "Xs Ym" duration formatting for the worked-time column.
     *
     * @param {Number} totalMinutes
     */
    function formatDuration(totalMinutes) {
        const hours = Math.floor(totalMinutes / 60);
        const minutes = totalMinutes % 60;

        if (hours === 0) {
            return minutes + ' dk';
        }

        return hours + ' sa ' + minutes + ' dk';
    }

    function load(date) {
        const url = App.Utils.Url.siteUrl('reports/get_daily_revenue');

        $.post(url, {
            csrf_token: vars('csrf_token'),
            date: date,
        })
            .done((response) => {
            if (response.success === false) {
                $reportTableBody.empty();
                $reportSummary.html(
                    $('<div/>', {
                        class: 'alert alert-danger mb-0',
                        text: response.message || 'Rapor yüklenirken bir hata oluştu.',
                    }),
                );
                return;
            }

            $reportTableBody.empty();

            response.providers.forEach((provider) => {
                const balanceCell =
                    provider.balance_amount > 0
                        ? $('<span/>', {class: 'text-danger', text: formatCurrency(provider.balance_amount)})
                        : $('<span/>', {class: 'text-muted', text: '-'});

                $reportTableBody.append(
                    $('<tr/>', {
                        html: [
                            $('<td/>', {text: provider.provider_name}),
                            $('<td/>', {text: provider.session_count}),
                            $('<td/>', {text: formatDuration(provider.duration_minutes)}),
                            $('<td/>', {text: formatCurrency(provider.total)}),
                            $('<td/>', {text: formatCurrency(provider.collected_amount)}),
                            $('<td/>', {html: balanceCell}),
                            $('<td/>', {
                                html: provider.pending_payment_count
                                    ? $('<span/>', {
                                          class: 'badge bg-danger',
                                          text: provider.pending_payment_count,
                                      })
                                    : $('<span/>', {class: 'text-muted', text: '-'}),
                            }),
                            $('<td/>', {
                                text:
                                    provider.invoiced_count +
                                    ' / ' +
                                    (provider.invoiced_count + provider.not_invoiced_count),
                            }),
                            $('<td/>', {
                                text: formatCommission(provider),
                                title: 'Terapistin varsayılan/genel oranı. Hizmet bazlı bir override tanımlıysa, o seans için "Terapiste Ödenecek" bu değil override kullanılarak hesaplanır.',
                            }),
                            $('<td/>', {html: $('<strong/>', {text: formatCurrency(provider.payout)})}),
                        ],
                    }),
                );
            });

            if (response.providers.length === 0) {
                $reportTableBody.append(
                    $('<tr/>', {
                        html: [
                            $('<td/>', {colspan: 10, class: 'text-muted', text: 'Bu tarihte tamamlanmış seans yok.'}),
                        ],
                    }),
                );
            }

            $reportSummary.empty().append(
                $('<div/>', {
                    class: 'alert alert-primary mb-0',
                    html: [
                        $('<strong/>', {text: 'Toplam seans: '}),
                        document.createTextNode(response.session_count + '  |  '),
                        $('<strong/>', {text: 'Toplam ciro: '}),
                        document.createTextNode(formatCurrency(response.grand_total) + '  |  '),
                        $('<strong/>', {text: 'Tahsil edilen: '}),
                        document.createTextNode(formatCurrency(response.grand_collected) + '  |  '),
                        $('<strong/>', {text: 'Bakiye: '}),
                        document.createTextNode(formatCurrency(response.grand_balance) + '  |  '),
                        $('<strong/>', {text: 'Toplam terapist ödemesi: '}),
                        document.createTextNode(formatCurrency(response.grand_payout)),
                    ],
                }),
            );
            })
            .fail(() => {
                $reportTableBody.empty();
                $reportSummary.html(
                    $('<div/>', {
                        class: 'alert alert-danger mb-0',
                        text: 'Rapor yüklenirken bir hata oluştu.',
                    }),
                );
            });
    }

    /**
     * Salon Flora customization (2026-08-25) - date-range, per-appointment CSV export (separate from the
     * per-provider daily summary above the fold).
     */
    function initializeExport() {
        const $startDate = $('#export-start-date');
        const $endDate = $('#export-end-date');
        const today = moment().format('YYYY-MM-DD');

        $startDate.val(today);
        $endDate.val(today);

        $('#export-columns-all').on('click', () => {
            $('.export-column-checkbox').prop('checked', true);
        });

        $('#export-columns-none').on('click', () => {
            $('.export-column-checkbox').prop('checked', false);
        });

        $('#export-end-of-day').on('click', () => {
            const todayStr = moment().format('YYYY-MM-DD');
            $startDate.val(todayStr);
            $endDate.val(todayStr);
            $('.export-column-checkbox').prop('checked', true);
            $('#export-csv').trigger('click');
        });

        $('#export-csv').on('click', () => {
            const startDate = $startDate.val();
            const endDate = $endDate.val();

            if (!startDate || !endDate) {
                App.Layouts.Backend.displayNotification('Başlangıç ve bitiş tarihi seçmelisiniz.');
                return;
            }

            if (endDate < startDate) {
                App.Layouts.Backend.displayNotification('Bitiş tarihi başlangıç tarihinden önce olamaz.');
                return;
            }

            const columns = $('.export-column-checkbox:checked')
                .map((index, checkbox) => $(checkbox).val())
                .get();

            if (!columns.length) {
                App.Layouts.Backend.displayNotification('En az bir sütun seçmelisiniz.');
                return;
            }

            window.location.href = App.Utils.Url.siteUrl(
                'reports/export_csv?start_date=' + startDate + '&end_date=' + endDate + '&columns=' + columns.join(','),
            );
        });
    }

    function initialize() {
        const today = moment().format('YYYY-MM-DD');
        $reportDate.val(today);

        $reportDate.on('change', () => {
            load($reportDate.val());
        });

        load(today);

        initializeExport();
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {
        load,
    };
})();
