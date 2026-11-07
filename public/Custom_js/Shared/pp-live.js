/**
 * Production floor live — Laravel Echo + Reverb (bukan poll).
 * window.PpLive.subscribe(warehouseId, onChange) → true jika WS siap.
 */
(function (window) {
    "use strict";

    var cfg = window.PEGASUS_REVERB || null;
    var channelCache = {};

    function ensureEcho() {
        if (window.__pegasusEchoInstance) {
            window.Echo = window.__pegasusEchoInstance;
            return true;
        }
        if (!cfg || !cfg.key) return false;
        if (typeof window.Pusher === "undefined") return false;
        var EchoLib = window.LaravelEcho;
        if (typeof EchoLib !== "function") return false;
        try {
            window.__pegasusEchoInstance = new EchoLib({
                broadcaster: "reverb",
                key: cfg.key,
                wsHost: cfg.host || window.location.hostname,
                wsPort: Number(cfg.port) || 8080,
                wssPort: Number(cfg.port) || 8080,
                forceTLS: (cfg.scheme || "http") === "https",
                enabledTransports: ["ws", "wss"],
                disableStats: true,
            });
            window.Echo = window.__pegasusEchoInstance;
            return true;
        } catch (e) {
            console.warn("PpLive: Echo init failed", e);
            return false;
        }
    }

    function subscribe(warehouseId, onChange) {
        var wh = Number(warehouseId) || 0;
        if (!wh || typeof onChange !== "function") return false;
        if (!ensureEcho()) return false;

        var name = "pp.warehouse." + wh;
        if (channelCache[name]) {
            channelCache[name].callbacks.push(onChange);
            return true;
        }

        var ch = window.Echo.channel(name);
        var entry = { channel: ch, callbacks: [onChange] };
        channelCache[name] = entry;

        ch.listen(".floor.changed", function (payload) {
            var list = entry.callbacks.slice();
            for (var i = 0; i < list.length; i++) {
                try {
                    list[i](payload || {});
                } catch (err) {
                    console.error("PpLive callback error", err);
                }
            }
        });

        return true;
    }

    window.PpLive = {
        subscribe: subscribe,
        ready: ensureEcho,
    };
})(window);
