/*
 * Image attachment for the AI chat (floating widget + /ai page).
 * Picks, pastes (Ctrl+V), or drops one image; shrinks aggressif di browser
 * (chat-only: hemat disk/token) — WebP bila support, else JPEG.
 *
 *   var img = AiChatImage.attach({ button, fileInput, input, preview, onError, pasteRoot? });
 *   img.current()  -> { blob, url } or null
 *   img.clear()
 */
(function (window) {
    // Chat-only: kecilkan agresif; kode dokumen di screenshot masih kebaca model.
    var MAX_SIDE = 720;
    var QUALITY = 0.55;
    var MAX_BYTES = 5 * 1024 * 1024;
    var TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    function looksLikeImage(file) {
        if (!file) return false;
        if (file.type && TYPES.indexOf(file.type) !== -1) return true;
        // Snipping Tool / beberapa OS kirim type kosong — cek ekstensi nama.
        var name = (file.name || '').toLowerCase();
        return /\.(jpe?g|png|webp)$/.test(name) || (file.type || '').indexOf('image/') === 0;
    }

    /** Ambil file gambar pertama dari clipboard / DataTransfer. */
    function firstImageFile(dataTransfer) {
        if (!dataTransfer) return null;
        var items = dataTransfer.items;
        if (items && items.length) {
            for (var i = 0; i < items.length; i++) {
                var it = items[i];
                if (it.kind !== 'file') continue;
                var t = it.type || '';
                // image/* eksplisit, atau type kosong (beberapa screenshot Windows).
                if (t.indexOf('image/') === 0 || t === '') {
                    var fromItem = it.getAsFile && it.getAsFile();
                    if (fromItem && (t.indexOf('image/') === 0 || looksLikeImage(fromItem) || !fromItem.type)) {
                        return fromItem;
                    }
                }
            }
        }
        var files = dataTransfer.files;
        if (files && files.length) {
            for (var j = 0; j < files.length; j++) {
                if (looksLikeImage(files[j]) || (files[j].type || '').indexOf('image/') === 0) {
                    return files[j];
                }
            }
        }
        return null;
    }

    function toBlob(canvas, type, quality) {
        return new Promise(function (resolve) {
            canvas.toBlob(function (blob) { resolve(blob || null); }, type, quality);
        });
    }

    function shrink(file) {
        return new Promise(function (resolve, reject) {
            var url = URL.createObjectURL(file);
            var img = new Image();
            img.onload = function () {
                var scale = Math.min(1, MAX_SIDE / Math.max(img.width, img.height));
                var canvas = document.createElement('canvas');
                canvas.width = Math.round(img.width * scale);
                canvas.height = Math.round(img.height * scale);
                var ctx = canvas.getContext('2d');
                ctx.fillStyle = '#fff'; // transparent PNG -> white, not black
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                URL.revokeObjectURL(url);
                // WebP lebih hemat; fallback JPEG kalau browser gagal / hasil kosong.
                toBlob(canvas, 'image/webp', QUALITY).then(function (webp) {
                    if (webp && webp.size > 0 && webp.type === 'image/webp') {
                        resolve(webp);
                        return;
                    }
                    return toBlob(canvas, 'image/jpeg', QUALITY).then(function (jpeg) {
                        jpeg ? resolve(jpeg) : reject(new Error('encode'));
                    });
                }).catch(function () {
                    reject(new Error('encode'));
                });
            };
            img.onerror = function () {
                URL.revokeObjectURL(url);
                reject(new Error('decode'));
            };
            img.src = url;
        });
    }

    function attach(opts) {
        var state = null;

        function render() {
            opts.preview.innerHTML = '';
            opts.preview.style.display = state ? '' : 'none';
            if (!state) return;
            var img = document.createElement('img');
            img.src = state.url;
            img.alt = 'Lampiran';
            var remove = document.createElement('button');
            remove.type = 'button';
            remove.title = 'Hapus gambar';
            remove.setAttribute('aria-label', 'Hapus gambar');
            remove.textContent = '×';
            remove.addEventListener('click', clear);
            opts.preview.appendChild(img);
            opts.preview.appendChild(remove);
        }

        function clear() {
            if (state) URL.revokeObjectURL(state.url);
            state = null;
            opts.fileInput.value = '';
            render();
        }

        function take(file) {
            if (!file) return;
            // Screenshot Windows sering type kosong — tetap coba decode/shrink ke JPEG.
            var typed = (file.type || '').indexOf('image/') === 0;
            if (file.type && !typed && TYPES.indexOf(file.type) === -1) {
                opts.onError('Format gambar harus JPG, PNG, atau WEBP.');
                return;
            }
            if (file.size > MAX_BYTES * 4) {
                opts.onError('Ukuran gambar terlalu besar.');
                return;
            }
            shrink(file).then(function (blob) {
                if (blob.size > MAX_BYTES) {
                    opts.onError('Ukuran gambar terlalu besar.');
                    return;
                }
                clear();
                state = { blob: blob, url: URL.createObjectURL(blob) };
                render();
                opts.input.focus();
            }).catch(function () {
                opts.onError('Gambar tidak bisa dibaca.');
            });
        }

        function onPaste(e) {
            var file = firstImageFile(e.clipboardData);
            if (!file) return;
            e.preventDefault();
            take(file);
        }

        function onDrop(e) {
            var file = firstImageFile(e.dataTransfer);
            if (!file) return;
            e.preventDefault();
            e.stopPropagation();
            take(file);
        }

        opts.button.addEventListener('click', function () { opts.fileInput.click(); });
        opts.fileInput.addEventListener('change', function () {
            take(opts.fileInput.files && opts.fileInput.files[0]);
        });

        // Paste di kotak teks + root panel (fokus di tombol/form tetap jalan).
        opts.input.addEventListener('paste', onPaste);
        var pasteRoot = opts.pasteRoot || opts.input.closest('form') || opts.input;
        if (pasteRoot && pasteRoot !== opts.input) {
            pasteRoot.addEventListener('paste', onPaste);
        }

        // Drag-drop ke form/preview juga (bonus, sama alur lampiran).
        var dropRoot = opts.pasteRoot || opts.input.closest('form') || opts.input;
        if (dropRoot) {
            dropRoot.addEventListener('dragover', function (e) {
                if (firstImageFile(e.dataTransfer)) {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'copy';
                }
            });
            dropRoot.addEventListener('drop', onDrop);
        }

        render();

        return {
            current: function () { return state; },
            clear: clear
        };
    }

    /** User bubble content: optional thumbnail + text (text stays textContent). */
    function fillBubble(el, text, imageUrl) {
        if (imageUrl) {
            var img = document.createElement('img');
            img.src = imageUrl;
            img.alt = 'Lampiran';
            img.className = 'ai-msg-img';
            img.title = 'Klik untuk perbesar';
            img.style.cursor = 'pointer';
            img.addEventListener('click', function () {
                window.open(imageUrl, '_blank', 'noopener');
            });
            el.appendChild(img);
        }
        if (text) {
            var span = document.createElement('div');
            span.textContent = text;
            el.appendChild(span);
        }
    }

    window.AiChatImage = { attach: attach, fillBubble: fillBubble };
})(window);
