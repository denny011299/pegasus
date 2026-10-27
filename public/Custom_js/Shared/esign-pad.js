/**
 * E-sign pad — gambar di canvas (bukan upload file).
 * Pakai: EsignPad.mount('#el', { value: dataUri, aspectRatio: 16/9 }) → { getValue, isEmpty, isDirty, clear, setValue }
 * aspectRatio = lebar/tinggi (default & large: 16/9; large dibatasi max-height agar modal tidak scroll).
 */
(function (window) {
    "use strict";

    var STYLE_ID = "pg-esign-pad-style";
    var DEFAULT_WIDTH = 560;
    var DEFAULT_ASPECT = 16 / 9;
    // Hampir 16:9 — area tanda tangan lebih besar, modal masih tanpa scroll
    var LARGE_ASPECT = 16 / 9;

    function ensureStyles() {
        if (document.getElementById(STYLE_ID)) return;
        var css =
            ".pg-esign{max-width:360px;width:100%;}" +
            ".pg-esign.pg-esign--lg{max-width:min(580px,92vw);margin:0 auto;}" +
            ".pg-esign-frame{aspect-ratio:16/9;width:100%;border:1px solid #cbd5e1;border-radius:12px;" +
            "background:#ffffff;touch-action:none;cursor:crosshair;position:relative;overflow:hidden;" +
            "box-shadow:inset 0 2px 4px rgba(15,23,42,.03);transition:border-color .15s ease,box-shadow .15s ease;}" +
            ".pg-esign--lg .pg-esign-frame{aspect-ratio:16/9;max-height:min(380px,48vh);}" +
            ".pg-esign-frame:hover,.pg-esign-frame.is-active{border-color:#2563eb;box-shadow:inset 0 2px 4px rgba(15,23,42,.03),0 0 0 3px rgba(37,99,235,.08);}" +
            ".pg-esign-frame.is-disabled{opacity:.65;pointer-events:none;cursor:not-allowed;}" +
            ".pg-esign-frame canvas{display:block;width:100%;height:100%;position:relative;z-index:2;}" +
            ".pg-esign-placeholder{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);" +
            "pointer-events:none;color:#94a3b8;text-align:center;user-select:none;z-index:1;transition:opacity .2s ease;}" +
            ".pg-esign-placeholder.is-hidden{opacity:0;}" +
            ".pg-esign-placeholder i{font-size:24px;display:block;margin-bottom:4px;opacity:.6;}" +
            ".pg-esign-placeholder span{font-size:13px;font-weight:500;letter-spacing:.2px;}" +
            ".pg-esign-guideline{position:absolute;bottom:22%;left:8%;right:8%;border-bottom:1px dashed #e2e8f0;" +
            "pointer-events:none;user-select:none;z-index:1;display:flex;justify-content:flex-end;}" +
            ".pg-esign-guideline-label{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;" +
            "color:#cbd5e1;margin-top:4px;}" +
            ".pg-esign-toolbar{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:8px;flex-wrap:wrap;}" +
            ".pg-esign-toolbar .pg-esign-clear{height:34px;padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;" +
            "color:#475569;border:1px solid #cbd5e1;background:#fff;display:inline-flex;align-items:center;gap:6px;transition:all .15s ease;}" +
            ".pg-esign-toolbar .pg-esign-clear:hover{background:#f8fafc;border-color:#94a3b8;color:#0f172a;}" +
            ".pg-esign-hint{font-size:12px;color:#64748b;line-height:1.35;display:flex;align-items:center;gap:5px;}";
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
        var aspect =
            Number(options.aspectRatio) > 0
                ? Number(options.aspectRatio)
                : options.large
                  ? LARGE_ASPECT
                  : DEFAULT_ASPECT;
        var height = Math.max(80, Math.round(width / aspect));
        var $root = $(target);
        if (!$root.length) return null;

        $root.empty().addClass("pg-esign");
        if (options.large) $root.addClass("pg-esign--lg");
        var $frame = $('<div class="pg-esign-frame" aria-label="Area tanda tangan"></div>');
        $frame.css("aspect-ratio", aspect);
        var canvas = document.createElement("canvas");
        canvas.width = width;
        canvas.height = height;
        canvas.setAttribute("role", "img");

        var $placeholder = $(
            '<div class="pg-esign-placeholder">' +
                '<i class="fe fe-edit-3"></i>' +
                '<span>Goreskan tanda tangan di area ini</span>' +
            '</div>'
        );
        var $guideline = $(
            '<div class="pg-esign-guideline">' +
                '<span class="pg-esign-guideline-label">Garis Tanda Tangan</span>' +
            '</div>'
        );
        $frame.append(canvas, $placeholder, $guideline);

        var hint =
            options.hint ||
            "Sentuh atau drag mouse untuk membuat tanda tangan.";
        var $hint = $(
            '<div class="pg-esign-hint">' +
                '<i class="fe fe-info text-primary"></i>' +
                '<span>' + hint + '</span>' +
            '</div>'
        );
        var $toolbar = $(
            '<div class="pg-esign-toolbar">' +
                '<div class="d-flex align-items-center">' + $hint[0].outerHTML + '</div>' +
                '<button type="button" class="btn pg-esign-clear">' +
                    '<i class="fe fe-rotate-ccw"></i> Bersihkan / Ulangi' +
                '</button>' +
            '</div>'
        );
        $root.append($frame, $toolbar);

        var ctx = canvas.getContext("2d");
        ctx.lineCap = "round";
        ctx.lineJoin = "round";
        ctx.strokeStyle = "#0f172a";
        ctx.lineWidth = Math.max(2.8, width / 180);

        var drawing = false;
        var dirty = false;
        var hasInk = false;
        var last = null;

        function markInk() {
            hasInk = true;
            dirty = true;
            $placeholder.addClass("is-hidden");
            if (typeof options.onStroke === "function") options.onStroke();
        }

        function clearPad(silent) {
            blankCanvas(ctx, width, height);
            hasInk = false;
            dirty = !silent;
            last = null;
            $placeholder.removeClass("is-hidden");
        }

        function setValue(dataUri) {
            clearPad(true);
            if (!dataUri || typeof dataUri !== "string") {
                $placeholder.removeClass("is-hidden");
                return;
            }
            var img = new Image();
            img.onload = function () {
                blankCanvas(ctx, width, height);
                // Fit ke kotak tanpa stretch kasar
                var scale = Math.min(width / img.width, height / img.height);
                var w = img.width * scale;
                var h = img.height * scale;
                ctx.drawImage(img, (width - w) / 2, (height - h) / 2, w, h);
                hasInk = true;
                dirty = false;
                $placeholder.addClass("is-hidden");
            };
            img.src = dataUri;
        }

        function startDraw(evt) {
            if (options.disabled) return;
            evt.preventDefault();
            drawing = true;
            $frame.addClass("is-active");
            $placeholder.addClass("is-hidden");
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
            $frame.removeClass("is-active");
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
