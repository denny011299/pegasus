/**
 * Shared SSE client for Asisten Pegasus (/ai/chat).
 * Expects AiChatImage on the page when attachments are used.
 */
(function (global) {
    'use strict';

    function parseSseChunk(buffer, onEvent) {
        var parts = buffer.split('\n\n');
        var rest = parts.pop() || '';
        for (var i = 0; i < parts.length; i++) {
            var block = parts[i];
            if (!block || block.charAt(0) === ':') continue;
            var event = 'message';
            var dataLines = [];
            var lines = block.split(/\r?\n/);
            for (var j = 0; j < lines.length; j++) {
                var line = lines[j];
                if (line.indexOf('event:') === 0) {
                    event = line.slice(6).trim();
                } else if (line.indexOf('data:') === 0) {
                    dataLines.push(line.slice(5).trim());
                }
            }
            if (!dataLines.length) continue;
            var raw = dataLines.join('\n');
            var data = {};
            try { data = JSON.parse(raw); } catch (e) { data = { message: raw }; }
            onEvent(event, data);
        }
        return rest;
    }

    /**
     * @param {object} opts
     * @param {string} opts.url
     * @param {FormData} opts.body
     * @param {string} opts.csrf
     * @param {function(string, object)} opts.onEvent
     * @param {function(string)} opts.onError
     * @param {function()} opts.onFinally
     */
    function postStream(opts) {
        var url = opts.url;
        var body = opts.body;
        var csrf = opts.csrf || '';
        var onEvent = opts.onEvent || function () {};
        var onError = opts.onError || function () {};
        var onFinally = opts.onFinally || function () {};

        fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'text/event-stream',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: body
        }).then(function (res) {
            if (res.status === 429) {
                onError('Terlalu banyak permintaan. Coba lagi sebentar.');
                return null;
            }
            var ct = (res.headers.get('content-type') || '').toLowerCase();
            if (!res.ok) {
                return res.json().catch(function () { return null; }).then(function (data) {
                    onError((data && data.message) ? data.message : 'Gagal mendapat jawaban.');
                    return null;
                });
            }
            // Fallback: server returned classic JSON (stream=0 / proxy strip).
            if (ct.indexOf('application/json') !== -1) {
                return res.json().then(function (data) {
                    if (!data || data.status !== 1) {
                        onError((data && data.message) ? data.message : 'Gagal mendapat jawaban.');
                        return null;
                    }
                    onEvent('final', { text: data.answer || '(kosong)' });
                    onEvent('done', { answer: data.answer || '', conversation_id: data.conversation_id });
                    return null;
                });
            }
            if (!res.body || !res.body.getReader) {
                onError('Browser tidak mendukung streaming.');
                return null;
            }

            var reader = res.body.getReader();
            var decoder = new TextDecoder('utf-8');
            var buf = '';

            var completed = false;
            var wrappedOnEvent = function (event, data) {
                if (event === 'done' || event === 'error' || event === 'final') {
                    completed = true;
                }
                onEvent(event, data);
            };

            function pump() {
                return reader.read().then(function (result) {
                    if (result.done) {
                        if (buf.trim()) parseSseChunk(buf + '\n\n', wrappedOnEvent);
                        // Putus di tengah (PHP timeout / proxy) tanpa event selesai.
                        if (!completed) {
                            onError('Koneksi terputus sebelum jawaban selesai. Biasanya timeout server — coba lagi.');
                        }
                        return;
                    }
                    buf += decoder.decode(result.value, { stream: true });
                    buf = parseSseChunk(buf, wrappedOnEvent);
                    return pump();
                });
            }
            return pump();
        }).catch(function (err) {
            var name = (err && err.name) ? String(err.name) : '';
            if (name === 'AbortError') {
                onError('Permintaan dibatalkan.');
            } else {
                // Seringnya PHP mati (max_execution_time) → TCP reset → fetch reject.
                onError('Koneksi terputus. Server mungkin timeout — coba lagi atau perpendek pertanyaan.');
            }
        }).finally(onFinally);
    }

    /**
     * Wire a chat form to streaming UI.
     * @param {object} cfg
     * @param {HTMLElement} cfg.messages
     * @param {HTMLFormElement} cfg.form
     * @param {HTMLInputElement} cfg.input
     * @param {HTMLButtonElement} cfg.sendBtn
     * @param {HTMLButtonElement} [cfg.attachBtn]
     * @param {object} [cfg.attachment] AiChatImage.attach result
     * @param {string} cfg.url
     * @param {function(): string} cfg.getCsrf
     * @param {function()} [cfg.clearDraft]
     * @param {function()} [cfg.onScroll]
     */
    function bindForm(cfg) {
        var messages = cfg.messages;
        var form = cfg.form;
        var input = cfg.input;
        var sendBtn = cfg.sendBtn;
        var attachBtn = cfg.attachBtn || null;
        var attachment = cfg.attachment || null;
        var botEl = null;
        var statusEl = null;

        function appendMsg(text, cls, imageUrl) {
            var el = document.createElement('div');
            el.className = 'ai-msg ' + cls;
            if (imageUrl && global.AiChatImage) {
                global.AiChatImage.fillBubble(el, text, imageUrl);
            } else {
                el.textContent = text;
            }
            messages.appendChild(el);
            messages.scrollTop = messages.scrollHeight;
            return el;
        }

        function ensureBot() {
            if (botEl && botEl.parentNode) return botEl;
            botEl = document.createElement('div');
            botEl.className = 'ai-msg bot ai-streaming';
            botEl.innerHTML = '<div class="ai-stream-status">Sedang menyusun jawaban…</div><span class="ai-stream-text"></span><span class="ai-caret" aria-hidden="true"></span>';
            statusEl = botEl.querySelector('.ai-stream-status');
            messages.appendChild(botEl);
            messages.scrollTop = messages.scrollHeight;
            return botEl;
        }

        function setStatus(msg) {
            ensureBot();
            if (!statusEl) statusEl = botEl.querySelector('.ai-stream-status');
            if (statusEl) {
                statusEl.style.display = msg ? '' : 'none';
                statusEl.textContent = msg || '';
            }
        }

        function textNode() {
            ensureBot();
            return botEl.querySelector('.ai-stream-text');
        }

        function appendDelta(t) {
            ensureBot();
            setStatus('');
            var span = textNode();
            if (span) span.textContent += t;
            messages.scrollTop = messages.scrollHeight;
        }

        function setFinal(t) {
            ensureBot();
            setStatus('');
            var span = textNode();
            if (span) span.textContent = t || '(kosong)';
            botEl.classList.remove('ai-streaming');
            var caret = botEl.querySelector('.ai-caret');
            if (caret) caret.remove();
            if (statusEl) statusEl.remove();
            messages.scrollTop = messages.scrollHeight;
        }

        function resetBot() {
            ensureBot();
            var span = textNode();
            if (span) span.textContent = '';
            messages.scrollTop = messages.scrollHeight;
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var text = (input.value || '').trim();
            var att = attachment && attachment.current ? attachment.current() : null;
            if (!text && !att) return;

            appendMsg(text, 'user', att ? URL.createObjectURL(att.blob) : null);
            input.value = '';
            if (cfg.clearDraft) cfg.clearDraft();
            sendBtn.disabled = true;
            if (attachBtn) attachBtn.disabled = true;

            botEl = null;
            statusEl = null;
            ensureBot();

            var csrf = cfg.getCsrf ? cfg.getCsrf() : '';
            var body = new FormData();
            body.append('message', text);
            body.append('_token', csrf);
            body.append('stream', '1');
            if (att) body.append('image', att.blob, 'lampiran.jpg');
            if (attachment && attachment.clear) attachment.clear();

            var finished = false;
            postStream({
                url: cfg.url,
                body: body,
                csrf: csrf,
                onEvent: function (event, data) {
                    if (event === 'status') {
                        setStatus((data && data.message) || 'Sedang menyusun jawaban…');
                    } else if (event === 'delta') {
                        appendDelta((data && data.text) || '');
                    } else if (event === 'reset') {
                        resetBot();
                    } else if (event === 'final') {
                        setFinal((data && data.text) || '');
                        finished = true;
                    } else if (event === 'done') {
                        if (!finished) setFinal((data && data.answer) || '(kosong)');
                        finished = true;
                    } else if (event === 'error') {
                        if (botEl && botEl.parentNode) botEl.parentNode.removeChild(botEl);
                        botEl = null;
                        appendMsg((data && data.message) || 'Gagal mendapat jawaban.', 'err');
                        finished = true;
                    }
                },
                onError: function (msg) {
                    if (botEl && botEl.parentNode) botEl.parentNode.removeChild(botEl);
                    botEl = null;
                    appendMsg(msg, 'err');
                },
                onFinally: function () {
                    sendBtn.disabled = false;
                    if (attachBtn) attachBtn.disabled = false;
                    input.focus();
                    if (cfg.onScroll) cfg.onScroll();
                }
            });
        });

        return { appendMsg: appendMsg };
    }

    global.AiChatStream = { postStream: postStream, bindForm: bindForm };
})(window);
