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
                    class: 'row g-2',
                    html: [
                        $('<div/>', {
                            class: 'col-6 col-md-3',
                            html: $('<div/>', {
                                class: 'p-2 border rounded bg-white text-center shadow-sm',
                                html: [
                                    $('<div/>', {class: 'text-muted small fw-bold text-uppercase', text: 'Toplam Seans'}),
                                    $('<div/>', {class: 'fs-5 fw-bold text-dark mt-1', text: response.session_count})
                                ]
                            })
                        }),
                        $('<div/>', {
                            class: 'col-6 col-md-3',
                            html: $('<div/>', {
                                class: 'p-2 border rounded bg-white text-center shadow-sm',
                                html: [
                                    $('<div/>', {class: 'text-muted small fw-bold text-uppercase', text: 'Toplam Ciro'}),
                                    $('<div/>', {class: 'fs-5 fw-bold text-primary mt-1', text: formatCurrency(response.grand_total)})
                                ]
                            })
                        }),
                        $('<div/>', {
                            class: 'col-6 col-md-3',
                            html: $('<div/>', {
                                class: 'p-2 border rounded bg-white text-center shadow-sm',
                                html: [
                                    $('<div/>', {class: 'text-muted small fw-bold text-uppercase', text: 'Tahsil Edilen'}),
                                    $('<div/>', {class: 'fs-5 fw-bold text-success mt-1', text: formatCurrency(response.grand_collected)})
                                ]
                            })
                        }),
                        $('<div/>', {
                            class: 'col-6 col-md-3',
                            html: $('<div/>', {
                                class: 'p-2 border rounded bg-white text-center shadow-sm',
                                html: [
                                    $('<div/>', {class: 'text-muted small fw-bold text-uppercase', text: 'Kalan Bakiye'}),
                                    $('<div/>', {class: 'fs-5 fw-bold ' + (response.grand_balance > 0 ? 'text-danger' : 'text-muted') + ' mt-1', text: formatCurrency(response.grand_balance)})
                                ]
                            })
                        })
                    ]
                })
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

    function initializeAnalytics() {
        $('#analytics-fetch-btn').on('click', () => {
            const dateFrom = $('#analytics-date-from').val();
            const dateTo = $('#analytics-date-to').val();
            const groupBy = $('#analytics-group-by').val();
            const $revTbody = $('#analytics-revenue-table tbody');
            const $utTbody = $('#analytics-utilization-table tbody');
            const $error = $('#analytics-error');

            $error.addClass('d-none').text('');
            $revTbody.html('<tr><td colspan="4" class="text-center text-muted py-3">Yükleniyor...</td></tr>');
            $utTbody.html('<tr><td colspan="4" class="text-center text-muted py-3">Yükleniyor...</td></tr>');

            // Revenue report
            $.get(App.Utils.Url.siteUrl('reports/get_revenue_report'), {
                date_from: dateFrom,
                date_to: dateTo,
                group_by: groupBy
            }).done((res) => {
                $revTbody.empty();
                const series = res.series || [];
                if (!series.length) {
                    $revTbody.html('<tr><td colspan="4" class="text-center text-muted py-3">Bu aralıkta ciro kaydı bulunamadı.</td></tr>');
                    return;
                }
                series.forEach((s) => {
                    $revTbody.append(
                        $('<tr/>', {
                            html: [
                                $('<td/>', {class: 'fw-semibold', text: s.period || s.date || '-'}),
                                $('<td/>', {class: 'text-center', text: s.appointments_count || 0}),
                                $('<td/>', {class: 'text-end fw-bold text-success', text: formatCurrency(s.total_revenue || 0)}),
                                $('<td/>', {class: 'text-end text-muted', text: formatCurrency(s.total_payout || 0)})
                            ]
                        })
                    );
                });
            }).fail((err) => {
                $error.removeClass('d-none').text('Ciro verileri alınırken bir hata oluştu.');
            });

            // Utilization report
            $.get(App.Utils.Url.siteUrl('reports/get_utilization_report'), {
                date_from: dateFrom,
                date_to: dateTo
            }).done((res) => {
                $utTbody.empty();
                const providers = res.providers || [];
                if (!providers.length) {
                    $utTbody.html('<tr><td colspan="4" class="text-center text-muted py-3">Personel doluluk verisi bulunamadı.</td></tr>');
                    return;
                }
                providers.forEach((p) => {
                    const rate = parseFloat(p.utilization_rate || 0);
                    $utTbody.append(
                        $('<tr/>', {
                            html: [
                                $('<td/>', {class: 'fw-semibold', text: p.provider_name || '-'}),
                                $('<td/>', {class: 'text-center', text: formatDuration(p.booked_minutes || 0)}),
                                $('<td/>', {class: 'text-center text-muted', text: formatDuration(p.available_minutes || 0)}),
                                $('<td/>', {
                                    class: 'text-center',
                                    html: $('<span/>', {
                                        class: 'badge ' + (rate > 70 ? 'bg-success' : (rate > 35 ? 'bg-primary' : 'bg-secondary')),
                                        text: '%' + rate.toFixed(1)
                                    })
                                })
                            ]
                        })
                    );
                });
            });
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
        initializeAnalytics();
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {
        load,
    };
})();
