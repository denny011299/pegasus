/**
 * Wizard Sinkronisasi.
 *
 * Seluruh langkah berada di satu halaman; skrip ini hanya mengatur perpindahan
 * antar langkah, menampilkan status/prasyarat/hasil, dan memanggil endpoint
 * eksekusi. Tidak ada logika sinkronisasi di sisi klien.
 */
(function () {
    var activeIndex = 0;
    var running = false;
    var currentState = null;

    var STATUS_CLASS = {
        not_executed: '',
        running: 'is-running',
        success: 'is-success',
        failed: 'is-failed'
    };

    $(document).ready(function () {
        renderState(syncInitialState);
        showStep(firstUnfinishedIndex(syncInitialState));

        $('#syncStepper').on('click', 'li', function () {
            showStep(parseInt($(this).data('index'), 10));
        });

        $('#btnPrevStep').on('click', function () {
            showStep(activeIndex - 1);
        });

        $('#btnNextStep').on('click', function () {
            showStep(activeIndex + 1);
        });

        $('.btn-execute-step').on('click', function () {
            executeStep($(this).data('step'));
        });
    });

    /** Langkah pertama yang belum berhasil — titik masuk yang paling masuk akal. */
    function firstUnfinishedIndex(state) {
        for (var i = 0; i < syncStepKeys.length; i++) {
            var step = state.steps[syncStepKeys[i]];
            if (!step || step.status !== 'success') {
                return i;
            }
        }
        return 0;
    }

    function showStep(index) {
        if (index < 0 || index >= syncStepKeys.length) {
            return;
        }

        activeIndex = index;
        var key = syncStepKeys[index];

        $('.sync-step-pane').removeClass('is-active');
        $('.sync-step-pane[data-step="' + key + '"]').addClass('is-active');

        $('#syncStepper li').removeClass('is-active');
        $('#syncStepper li[data-step="' + key + '"]').addClass('is-active');

        $('#syncStepCounter').text((index + 1) + ' / ' + syncStepKeys.length);
        $('#btnPrevStep').prop('disabled', index === 0);
        $('#btnNextStep').prop('disabled', index === syncStepKeys.length - 1);

        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    }

    function executeStep(stepKey) {
        if (running) {
            return;
        }

        var pane = $('.sync-step-pane[data-step="' + stepKey + '"]');

        running = true;
        markRunning(stepKey);
        LoadingButton('.sync-step-pane[data-step="' + stepKey + '"] .btn-execute-step');

        $.ajax({
            url: '/synchronization/' + syncFlowKey + '/' + stepKey + '/execute',
            method: 'post',
            data: { _token: token },
            success: function (res) {
                renderState(res.state);
                if (res.ok) {
                    notifikasi('success', 'Sinkronisasi Berhasil', res.message || 'Langkah berhasil dijalankan.');
                } else {
                    notifikasi('error', 'Sinkronisasi Gagal', res.message || 'Langkah gagal dijalankan.');
                }
            },
            error: function (xhr) {
                var res = xhr.responseJSON || {};
                if (res.state) {
                    renderState(res.state);
                } else {
                    resetStep(stepKey);
                }
                notifikasi(
                    'error',
                    xhr.status === 422 ? 'Prasyarat Belum Terpenuhi' : 'Sinkronisasi Gagal',
                    res.message || 'Terjadi kesalahan saat menjalankan sinkronisasi.'
                );
            },
            complete: function () {
                running = false;
                ResetLoadingButton(
                    '.sync-step-pane[data-step="' + stepKey + '"] .btn-execute-step',
                    '<i class="fe fe-refresh-cw me-2"></i>Jalankan Sinkronisasi'
                );
                // ResetLoadingButton selalu mengaktifkan tombol, jadi status
                // "boleh dijalankan" harus dipasang ulang setelahnya.
                applyExecutable(pane.find('.btn-execute-step'), currentState && currentState.steps[stepKey]);
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            }
        });
    }

    function markRunning(stepKey) {
        var item = $('#syncStepper li[data-step="' + stepKey + '"]');
        item.removeClass('is-success is-failed').addClass('is-running');
        item.find('.sync-stepper-status').text('Sedang Berjalan');

        $('.sync-step-pane[data-step="' + stepKey + '"] .sync-step-badge')
            .attr('class', 'badge badge-soft-info sync-step-badge')
            .text('Sedang Berjalan');
    }

    function resetStep(stepKey) {
        var item = $('#syncStepper li[data-step="' + stepKey + '"]');
        item.removeClass('is-running');
        item.find('.sync-stepper-status').text('Belum Dijalankan');
    }

    /** Gambar ulang seluruh tampilan dari state yang dikirim server. */
    function renderState(state) {
        if (!state) {
            return;
        }

        currentState = state;

        $('#syncProgressBar').css('width', state.progress + '%').attr('aria-valuenow', state.progress);
        $('#syncProgressText').text(state.completed + ' / ' + state.total + ' selesai');

        syncStepKeys.forEach(function (key) {
            var step = state.steps[key];
            if (!step) {
                return;
            }

            var pane = $('.sync-step-pane[data-step="' + key + '"]');
            var item = $('#syncStepper li[data-step="' + key + '"]');

            item.removeClass('is-running is-success is-failed is-blocked');
            if (STATUS_CLASS[step.status]) {
                item.addClass(STATUS_CLASS[step.status]);
            }
            if (step.status === 'not_executed' && !step.can_execute) {
                item.addClass('is-blocked');
            }
            item.find('.sync-stepper-status').text(step.status_label);

            pane.find('.sync-step-badge')
                .attr('class', 'badge ' + step.badge_class + ' sync-step-badge')
                .text(step.status_label);

            renderPrerequisites(pane, step);
            applyExecutable(pane.find('.btn-execute-step'), step);
            renderExecution(pane, step.execution);
        });
    }

    function renderPrerequisites(pane, step) {
        var list = pane.find('.sync-prereq-list');

        if (list.length) {
            list.empty();
            step.prerequisites.forEach(function (prereq) {
                list.append(
                    $('<li>')
                        .addClass(prereq.satisfied ? 'sync-prereq-ok' : 'sync-prereq-missing')
                        .append($('<i>').addClass(prereq.satisfied ? 'fe fe-check-circle' : 'fe fe-x-circle'))
                        .append($('<span>').text(prereq.reason))
                );
            });
        }

        var blocked = pane.find('.sync-blocked-reason');
        if (step.blocked_reason) {
            blocked.text(step.blocked_reason).removeClass('d-none');
        } else {
            blocked.addClass('d-none').text('');
        }
    }

    function applyExecutable(button, step) {
        if (!step) {
            return;
        }
        button.prop('disabled', !step.can_execute);
    }

    function renderExecution(pane, execution) {
        var box = pane.find('.sync-result');

        if (!execution) {
            box.addClass('d-none');
            return;
        }

        box.removeClass('d-none');

        var alertClass = execution.status === 'success' ? 'alert-success' : 'alert-danger';
        pane.find('.sync-result-message')
            .attr('class', 'sync-result-message mb-3 alert ' + alertClass)
            .text(execution.message || (execution.status === 'success' ? 'Sinkronisasi berhasil.' : 'Sinkronisasi gagal.'));

        pane.find('.sync-started-at').text(execution.started_at || '-');
        pane.find('.sync-finished-at').text(execution.finished_at || '-');
        pane.find('.sync-duration').text(execution.duration || '-');
        pane.find('.sync-executed-by').text(execution.executed_by_name || '-');

        var summary = execution.summary || {};
        pane.find('.sync-total-processed').text(summary.total_processed || 0);
        pane.find('.sync-total-inserted').text(summary.total_inserted || 0);
        pane.find('.sync-total-updated').text(summary.total_updated || 0);
        pane.find('.sync-total-failed').text(summary.total_failed || 0);
        pane.find('.sync-total-skipped').text(summary.total_skipped || 0);

        // Informasi apa pun yang dikirim PMO di luar daftar baris ikut ditampilkan.
        var detailsBox = pane.find('.sync-details');
        var detailsBody = pane.find('.sync-details-body').empty();
        var detailKeys = Object.keys(execution.details || {});
        if (detailKeys.length) {
            detailKeys.forEach(function (label) {
                detailsBody.append(
                    $('<tr>')
                        .append($('<th>').text(label))
                        .append($('<td>').text(formatDetail(execution.details[label])))
                );
            });
            detailsBox.removeClass('d-none');
        } else {
            detailsBox.addClass('d-none');
        }

        var noticesBox = pane.find('.sync-notices');
        var noticeList = pane.find('.sync-notice-list').empty();
        if (execution.notices && execution.notices.length) {
            execution.notices.forEach(function (message) {
                noticeList.append($('<li>').text(message));
            });
            noticesBox.removeClass('d-none');
        } else {
            noticesBox.addClass('d-none');
        }

        var errorsBox = pane.find('.sync-errors');
        var errorList = pane.find('.sync-error-list').empty();
        if (execution.errors && execution.errors.length) {
            execution.errors.forEach(function (message) {
                errorList.append($('<li>').text(message));
            });
            errorsBox.removeClass('d-none');
        } else {
            errorsBox.addClass('d-none');
        }
    }

    function formatDetail(value) {
        if (value === null || typeof value === 'undefined') {
            return '-';
        }
        if (typeof value === 'boolean') {
            return value ? 'Ya' : 'Tidak';
        }
        return String(value);
    }
})();
