/**
 * E-sign pad 16:9 — gambar di canvas (bukan upload file).
 * Pakai: EsignPad.mount('#el', { value: dataUri }) → { getValue, isEmpty, isDirty, clear, setValue }
 */
(function (window) {
    "use strict";

    var STYLE_ID = "pg-esign-pad-style";
    var DEFAULT_WIDTH = 560; // resolusi internal lebar 16:9
    var ASPECT_H = 9 / 16;

    function ensureStyles() {
        if (document.getElementById(STYLE_ID)) return;
        var css =
            ".pg-esign{max-width:360px;width:100%;}" +
            ".pg-esign.pg-esign--lg{max-width:min(560px,92vw);}" +
            ".pg-esign-frame{aspect-ratio:16/9;width:100%;border:1px solid #ced4da;border-radius:8px;" +
            "background:#fff;touch-action:none;cursor:crosshair;position:relative;overflow:hidden;" +
            "box-shadow:inset 0 1px 2px rgba(15,23,42,.04);}" +
            ".pg-esign-frame.is-disabled{opacity:.65;pointer-events:none;cursor:not-allowed;}" +
            ".pg-esign-frame canvas{display:block;width:100%;height:100%;}" +
            ".pg-esign-toolbar{display:flex;gap:8px;margin-top:8px;flex-wrap:wrap;justify-content:center;}" +
            ".pg-esign-toolbar .btn{min-height:36px;}" +
            ".pg-esign-hint{font-size:12px;color:#64748b;margin-top:6px;line-height:1.35;text-align:center;}";
        var style = document.createElement("style");
        style.id = STYLE_ID;
        style.textContent = css;
        document.head.appendChild(style);
    }

    function blankCanvas(ctx, w, h) {
        ctx.save();
        ctx.setTransform(1, 0, 0, 1, 0, 0);
        ctx.fillStyle = "#ffffff";
        ctx.fillRect(0, 0, w, h);
        ctx.restore();
    }

    function pointerPos(canvas, evt) {
        var rect = canvas.getBoundingClientRect();
        var src = evt.touches && evt.touches[0] ? evt.touches[0] : evt;
        var x = ((src.clientX - rect.left) / rect.width) * canvas.width;
        var y = ((src.clientY - rect.top) / rect.height) * canvas.height;
        return { x: x, y: y };
    }

    function mount(target, options) {
        ensureStyles();
        options = options || {};
        var width = Math.max(
            240,
            Math.min(960, Number(options.size) || (options.large ? 720 : DEFAULT_WIDTH))
        );
        var height = Math.round(width * ASPECT_H);
        var $root = $(target);
        if (!$root.length) return null;

        $root.empty().addClass("pg-esign");
        if (options.large) $root.addClass("pg-esign--lg");
        var $frame = $('<div class="pg-esign-frame" aria-label="Area tanda tangan"></div>');
        var canvas = document.createElement("canvas");
        canvas.width = width;
        canvas.height = height;
        canvas.setAttribute("role", "img");
        $frame.append(canvas);
        var $toolbar = $(
            '<div class="pg-esign-toolbar">' +
                '<button type="button" class="btn btn-outline-secondary btn-sm pg-esign-clear">' +
                '<i class="fe fe-trash-2 me-1"></i>Hapus</button>' +
                "</div>"
        );
        var hint =
            options.hint ||
            "Gambar tanda tangan di kotak (rasio 16:9). Sentuh atau drag mouse.";
        var $hint = $('<div class="pg-esign-hint"></div>').text(hint);
        $root.append($frame, $toolbar, $hint);

        var ctx = canvas.getContext("2d");
        ctx.lineCap = "round";
        ctx.lineJoin = "round";
        ctx.strokeStyle = "#0f172a";
        ctx.lineWidth = Math.max(2.5, width / 180);

        var drawing = false;
        var dirty = false;
        var hasInk = false;
        var last = null;

        function markInk() {
            hasInk = true;
            dirty = true;
            if (typeof options.onStroke === "function") options.onStroke();
        }

        function clearPad(silent) {
            blankCanvas(ctx, width, height);
            hasInk = false;
            dirty = !silent;
            last = null;
        }

        function setValue(dataUri) {
            clearPad(true);
            if (!dataUri || typeof dataUri !== "string") return;
            var img = new Image();
            img.onload = function () {
                blankCanvas(ctx, width, height);
                // Fit ke kotak 16:9 tanpa stretch kasar (letterbox jika sumber 1:1 lama)
                var scale = Math.min(width / img.width, height / img.height);
                var w = img.width * scale;
                var h = img.height * scale;
                ctx.drawImage(img, (width - w) / 2, (height - h) / 2, w, h);
                hasInk = true;
                dirty = false;
            };
            img.src = dataUri;
        }

        function startDraw(evt) {
            if (options.disabled) return;
            evt.preventDefault();
            drawing = true;
            last = pointerPos(canvas, evt);
        }

        function moveDraw(evt) {
            if (!drawing) return;
            evt.preventDefault();
            var pos = pointerPos(canvas, evt);
            ctx.beginPath();
            ctx.moveTo(last.x, last.y);
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
            last = pos;
            markInk();
        }

        function endDraw(evt) {
            if (!drawing) return;
            if (evt) evt.preventDefault();
            drawing = false;
            last = null;
        }

        canvas.addEventListener("mousedown", startDraw);
        canvas.addEventListener("mousemove", moveDraw);
        canvas.addEventListener("mouseup", endDraw);
        canvas.addEventListener("mouseleave", endDraw);
        canvas.addEventListener("touchstart", startDraw, { passive: false });
        canvas.addEventListener("touchmove", moveDraw, { passive: false });
        canvas.addEventListener("touchend", endDraw);
        canvas.addEventListener("touchcancel", endDraw);

        $toolbar.find(".pg-esign-clear").on("click", function () {
            clearPad(false);
            if (typeof options.onClear === "function") options.onClear();
        });

        blankCanvas(ctx, width, height);
        if (options.value) setValue(options.value);
        if (options.disabled) $frame.addClass("is-disabled");

        var api = {
            el: $root[0],
            getValue: function () {
                if (!hasInk) return null;
                return canvas.toDataURL("image/png");
            },
            isEmpty: function () {
                return !hasInk;
            },
            isDirty: function () {
                return dirty;
            },
            clear: function () {
                clearPad(false);
            },
            setValue: setValue,
            setDisabled: function (off) {
                options.disabled = !!off;
                $frame.toggleClass("is-disabled", !!off);
            },
        };
        $root.data("esignPad", api);
        return api;
    }

    function get(target) {
        var $el = $(target);
        return $el.length ? $el.data("esignPad") || null : null;
    }

    window.EsignPad = { mount: mount, get: get, DEFAULT_SIZE: DEFAULT_WIDTH };
})(window);
