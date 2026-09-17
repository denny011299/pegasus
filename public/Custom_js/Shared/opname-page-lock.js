/**
 * Exclusive lock Input Stok Opname (-1): heartbeat + beacon release + list precheck.
 * window.opnameLockToken / window.opnameLockDomain diisi blade saat mode create.
 */
(function (window, $) {
    "use strict";

    var HEARTBEAT_MS = 10000;
    var heartbeatTimer = null;
    var releasing = false;

    function csrfToken() {
        return (
            $('meta[name="csrf-token"]').attr("content") ||
            (typeof token !== "undefined" ? token : "")
        );
    }

    function showLockError(msg) {
        if (typeof notifikasi === "function") {
            notifikasi("error", "Stock Opname", msg);
        } else if (typeof toastr !== "undefined") {
            toastr.error(msg);
        } else {
            alert(msg);
        }
    }

    function releaseLockBeacon() {
        var t = window.opnameLockToken;
        if (!t || releasing) return;
        releasing = true;
        try {
            if (navigator.sendBeacon) {
                var fd = new FormData();
                fd.append("_token", csrfToken());
                fd.append("token", t);
                navigator.sendBeacon("/opnamePageLock/release", fd);
            } else {
                $.ajax({
                    url: "/opnamePageLock/release",
                    type: "POST",
                    async: false,
                    data: { _token: csrfToken(), token: t },
                });
            }
        } catch (e) {}
        window.opnameLockToken = null;
    }

    function releaseLockAjax(done) {
        var t = window.opnameLockToken;
        if (!t) {
            if (done) done();
            return;
        }
        releasing = true;
        $.ajax({
            url: "/opnamePageLock/release",
            type: "POST",
            data: { _token: csrfToken(), token: t },
            complete: function () {
                window.opnameLockToken = null;
                if (done) done();
            },
        });
    }

    function kickFromInput() {
        stopHeartbeat();
        window.opnameLockToken = null;
        showLockError(
            "Sesi Input Stock Opname diambil alih atau berakhir. Anda akan dikembalikan ke daftar."
        );
        var domain = window.opnameLockDomain || "product";
        var list =
            domain === "supplies" ? "/stockOpnameBahan" : "/stockOpname";
        setTimeout(function () {
            window.location.href = list;
        }, 1200);
    }

    function beat() {
        var t = window.opnameLockToken;
        if (!t) return;
        $.ajax({
            url: "/opnamePageLock/heartbeat",
            type: "POST",
            data: { _token: csrfToken(), token: t },
            error: function (xhr) {
                if (xhr && xhr.status === 409) {
                    kickFromInput();
                }
            },
        });
    }

    function startHeartbeat() {
        if (!window.opnameLockToken) return;
        stopHeartbeat();
        beat();
        heartbeatTimer = setInterval(beat, HEARTBEAT_MS);
    }

    function stopHeartbeat() {
        if (heartbeatTimer) {
            clearInterval(heartbeatTimer);
            heartbeatTimer = null;
        }
    }

    function bindInputPageLifecycle() {
        if (!window.opnameLockToken) return;
        startHeartbeat();

        window.addEventListener("pagehide", releaseLockBeacon);
        // Jangan release di visibilitychange — ganti tab sebentar jangan melepaskan lock.

        $(document).on("click", ".btnBack", function (e) {
            e.preventDefault();
            stopHeartbeat();
            var domain = window.opnameLockDomain || "product";
            var list =
                domain === "supplies" ? "/stockOpnameBahan" : "/stockOpname";
            releaseLockAjax(function () {
                window.location.href = list;
            });
        });
    }

    /**
     * Precheck sebelum buka /detail…/-1.
     * @param {string} domain product|supplies
     * @param {string} href target URL
     */
    function precheckAndGo(domain, href) {
        $.ajax({
            url: "/opnamePageLock/status",
            type: "GET",
            data: { domain: domain },
            success: function (res) {
                if (res && res.locked) {
                    var me =
                        window.sessionUser && window.sessionUser.staff_id
                            ? Number(window.sessionUser.staff_id)
                            : 0;
                    if (res.staff_id && me && Number(res.staff_id) === me) {
                        window.location.href = href;
                        return;
                    }
                    showLockError(
                        "Ada user " +
                            (res.held_by || "lain") +
                            " yang sedang membuka halaman Stock Opname."
                    );
                    return;
                }
                window.location.href = href;
            },
            error: function () {
                window.location.href = href;
            },
        });
    }

    function bindListTambahPrecheck(domain, selector) {
        $(document).on("click", selector, function (e) {
            e.preventDefault();
            var href =
                $(this).attr("href") ||
                (domain === "supplies"
                    ? "/detailStockOpnameBahan/-1"
                    : "/detailStockOpname/-1");
            precheckAndGo(domain, href);
        });
    }

    function showFlashErrorIfAny() {
        var msg = window.opnameLockFlashError;
        if (msg) {
            showLockError(msg);
            window.opnameLockFlashError = null;
        }
    }

    window.OpnamePageLockUi = {
        bindInputPageLifecycle: bindInputPageLifecycle,
        bindListTambahPrecheck: bindListTambahPrecheck,
        releaseLockAjax: releaseLockAjax,
        showFlashErrorIfAny: showFlashErrorIfAny,
        precheckAndGo: precheckAndGo,
    };
})(window, jQuery);
