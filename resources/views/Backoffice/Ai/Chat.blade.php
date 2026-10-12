<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Asisten Pegasus</title>
    <style>
        :root {
            --ai-primary: #0f172a;
            --ai-primary-accent: #2563eb;
            --ai-primary-soft: #eff6ff;
            --ai-border: #e2e8f0;
            --ai-bg: #f8fafc;
            --ai-text-main: #1e293b;
            --ai-text-muted: #64748b;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            background: #f1f5f9; color: #0a1f3d; height: 100dvh; display: flex; flex-direction: column;
        }
        header {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            color: #fff; padding: 14px 20px;
            display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.15);
        }
        .ai-header-brand { display: flex; align-items: center; gap: 10px; }
        .ai-header-avatar {
            width: 36px; height: 36px; border-radius: 10px;
            background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0.06) 100%);
            border: 1px solid rgba(255,255,255,0.25);
            display: flex; align-items: center; justify-content: center;
        }
        .ai-header-avatar svg { width: 20px; height: 20px; color: #fff; }
        .ai-header-titles { display: flex; flex-direction: column; }
        .ai-header-titles strong { font-size: 15px; font-weight: 700; color: #fff; }
        .ai-header-status { font-size: 11px; color: #93c5fd; display: inline-flex; align-items: center; gap: 4px; font-weight: 500; }
        .ai-status-pulse { width: 6px; height: 6px; border-radius: 50%; background: #34d399; box-shadow: 0 0 6px #34d399; }
        .ai-robot-eye {
            transform-box: fill-box;
            transform-origin: center center;
            animation: aiEyeBlink 4.2s ease-in-out infinite;
        }
        @keyframes aiEyeBlink {
            0%, 93%, 100% { transform: scaleY(1); }
            95.5% { transform: scaleY(0.1); }
            97% { transform: scaleY(1); }
        }
        header a {
            color: #fff; text-decoration: none; font-size: 13px; font-weight: 500;
            background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.25);
            border-radius: 8px; padding: 7px 14px; transition: all 0.15s ease;
        }
        header a:hover { background: rgba(255,255,255,.2); border-color: rgba(255,255,255,.4); }
        #ai-chat-messages {
            flex: 1; overflow-y: auto; padding: 24px 20px; background: var(--ai-bg); font-size: 14px; line-height: 1.55;
            max-width: 900px; width: 100%; margin: 0 auto;
        }
        .ai-msg {
            margin-bottom: 14px; width: fit-content; max-width: min(720px, 88%); padding: 11px 16px;
            white-space: pre-wrap; word-break: break-word; font-size: 13.5px;
        }
        .ai-msg.user {
            margin-left: auto; background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #fff; border-radius: 16px 16px 4px 16px; box-shadow: 0 2px 8px rgba(37,99,235,0.22); font-weight: 500;
        }
        .ai-msg.bot {
            margin-right: auto; background: #fff; border: 1px solid var(--ai-border);
            border-radius: 16px 16px 16px 4px; box-shadow: 0 1px 4px rgba(15,23,42,0.04);
            color: var(--ai-text-main);
        }
        .ai-msg.err {
            margin-right: auto; background: #fff1f2; border: 1px solid #fecdd3;
            color: #9f1239; border-radius: 14px; padding: 10px 14px;
        }
        .ai-msg.err::before { content: "⚠️ "; margin-right: 2px; }
        .ai-msg.thinking {
            margin-right: auto; background: #fff; border: 1px solid var(--ai-border);
            border-radius: 16px 16px 16px 4px; min-width: 200px; padding: 12px 16px;
        }
        .ai-thinking-label { font-size: 11px; font-weight: 600; color: #2563eb; margin-bottom: 8px; }
        .ai-skel {
            height: 9px; border-radius: 5px; margin-bottom: 6px;
            background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
            background-size: 200% 100%; animation: ai-shimmer 1.2s ease-in-out infinite;
        }
        .ai-skel.short { width: 55%; } .ai-skel.mid { width: 80%; } .ai-skel.long { width: 95%; margin-bottom: 0; }
        @keyframes ai-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        .ai-msg.bot.ai-streaming { min-width: 48px; }
        .ai-stream-status { font-size: 11px; font-weight: 600; color: #2563eb; margin-bottom: 4px; }
        .ai-stream-status:empty { display: none; }
        .ai-caret {
            display: inline-block; width: 2px; height: 0.95em; margin-left: 1px;
            vertical-align: text-bottom; background: #2563eb;
            animation: ai-caret-blink 1s step-end infinite;
        }
        @keyframes ai-caret-blink { 50% { opacity: 0; } }

        .ai-form-wrap {
            background: #fff; border-top: 1px solid var(--ai-border); padding: 14px 20px; flex-shrink: 0;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.02);
        }
        #ai-chat-form {
            display: flex; gap: 10px; align-items: center; max-width: 900px; margin: 0 auto;
        }
        .ai-input-wrap { flex: 1; min-width: 0; }
        #ai-chat-input {
            width: 100%; border: 1.5px solid var(--ai-border); background: #f8fafc;
            border-radius: 24px; padding: 11px 18px; font-size: 14px; outline: none; transition: all 0.2s ease;
        }
        #ai-chat-input:focus { background: #fff; border-color: var(--ai-primary-accent); box-shadow: 0 0 0 3px rgba(37,99,235,0.12); }
        #ai-chat-send {
            border: 0; border-radius: 24px; background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #fff; padding: 10px 20px; cursor: pointer; font-size: 13.5px; font-weight: 600;
            display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 8px rgba(37,99,235,0.28);
            transition: all 0.18s ease; flex-shrink: 0;
        }
        #ai-chat-send:hover:not(:disabled) {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            box-shadow: 0 4px 12px rgba(37,99,235,0.38); transform: translateY(-1px);
        }
        #ai-chat-send:disabled { opacity: .55; cursor: not-allowed; }

        #ai-chat-attach {
            border: 1px solid var(--ai-border); border-radius: 50%; background: #f8fafc;
            color: #64748b; width: 40px; height: 40px; flex-shrink: 0; cursor: pointer;
            display: flex; align-items: center; justify-content: center; padding: 0; transition: all 0.18s ease;
        }
        #ai-chat-attach:hover:not(:disabled) { background: #eff6ff; border-color: #bfdbfe; color: #2563eb; }
        #ai-chat-attach svg { width: 19px; height: 19px; }
        #ai-chat-attach:disabled { opacity: .5; cursor: not-allowed; }
        #ai-chat-preview {
            max-width: 900px; margin: 8px auto 0; position: relative; align-self: flex-start;
            border: 1px solid #bfdbfe; border-radius: 8px; padding: 4px; background: #eff6ff;
        }
        #ai-chat-preview img { display: block; max-height: 72px; max-width: 120px; border-radius: 4px; }
        #ai-chat-preview button {
            position: absolute; top: -8px; right: -8px; width: 20px; height: 20px;
            border-radius: 50%; border: 0; background: #ef4444; color: #fff;
            font-size: 13px; line-height: 20px; padding: 0; cursor: pointer;
        }
        .ai-msg-img { display: block; max-width: 220px; max-height: 220px; border-radius: 8px; margin-bottom: 8px; }
    </style>
</head>
<body>
@php
    $aiUser = Session::get('user');
    $aiName = trim((string) (is_array($aiUser)
        ? ($aiUser['staff_name'] ?? 'Pengguna')
        : ($aiUser->staff_name ?? 'Pengguna')));
    $aiHour = (int) now()->format('H');
    $aiGreeting = $aiHour < 11 ? 'pagi' : ($aiHour < 15 ? 'siang' : ($aiHour < 18 ? 'sore' : 'malam'));
@endphp
<header>
    <div class="ai-header-brand">
        <div class="ai-header-avatar">
            <img src="{{ asset('assets/img/ai-pegasus-avatar.png') }}?v={{ @filemtime(public_path('assets/img/ai-pegasus-avatar.png')) ?: 2 }}" alt="Pegasus" class="ai-header-avatar-img">
        </div>
        <div class="ai-header-titles">
            <strong>Asisten Pegasus</strong>
            <span class="ai-header-status"><span class="ai-status-pulse"></span> Online • Siap Membantu</span>
        </div>
    </div>
    <a href="{{ url('/') }}">Kembali ke aplikasi</a>
</header>
<div id="ai-chat-messages">
    <div class="ai-msg bot">Selamat {{ $aiGreeting }} {{ $aiName }}, ada yang bisa saya bantu hari ini?</div>
</div>
<div class="ai-form-wrap">
    <div id="ai-chat-preview" style="display:none"></div>
    <form id="ai-chat-form">
        <button id="ai-chat-attach" type="button" title="Lampirkan gambar (atau tempel screenshot)" aria-label="Lampirkan gambar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
        </button>
        <input id="ai-chat-file" type="file" accept="image/jpeg,image/png,image/webp" hidden>
        <div class="ai-input-wrap">
            <input id="ai-chat-input" type="text" autocomplete="off" placeholder="Tanyakan sesuatu… (Ctrl+V tempel gambar)" maxlength="2000" autofocus>
        </div>
        <button id="ai-chat-send" type="submit">
            <span>Kirim</span>
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
        </button>
    </form>
</div>
<script src="{{ asset('Custom_js/Shared/ai-chat-image.js') }}"></script>
<script src="{{ asset('Custom_js/Shared/ai-chat-stream.js') }}"></script>
<script>
(function () {
    var form = document.getElementById('ai-chat-form');
    var input = document.getElementById('ai-chat-input');
    var sendBtn = document.getElementById('ai-chat-send');
    var messages = document.getElementById('ai-chat-messages');
    var token = document.querySelector('meta[name="csrf-token"]').content;

    // Unsent text survives moving to another menu (per tab, per staff).
    var DRAFT_KEY = 'ai_chat_draft_{{ (int) data_get(Session::get('user'), 'staff_id', 0) }}';
    function saveDraft() {
        try { sessionStorage.setItem(DRAFT_KEY, input.value || ''); } catch (e) {}
    }
    function clearDraft() {
        try { sessionStorage.removeItem(DRAFT_KEY); } catch (e) {}
    }
    try { input.value = sessionStorage.getItem(DRAFT_KEY) || ''; } catch (e) {}
    input.addEventListener('input', saveDraft);

    function scrollToLatest() {
        requestAnimationFrame(function () { messages.scrollTop = messages.scrollHeight; });
    }

    var attachBtn = document.getElementById('ai-chat-attach');
    var attachment = AiChatImage.attach({
        button: attachBtn,
        fileInput: document.getElementById('ai-chat-file'),
        input: input,
        preview: document.getElementById('ai-chat-preview'),
        pasteRoot: document.querySelector('.ai-form-wrap') || form,
        onError: function (msg) {
            var el = document.createElement('div');
            el.className = 'ai-msg err';
            el.textContent = msg;
            messages.appendChild(el);
        }
    });

    // Same login-session chat as the floating widget.
    fetch('{{ url('/ai/history') }}', {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
    })
    .then(function (res) { return res.ok ? res.json() : null; })
    .then(function (data) {
        if (!data || data.status !== 1 || !Array.isArray(data.messages)) return;
        data.messages.forEach(function (m) {
            var el = document.createElement('div');
            el.className = 'ai-msg ' + (m.role === 'user' ? 'user' : 'bot');
            if (m.image_url && window.AiChatImage) {
                AiChatImage.fillBubble(el, m.content, m.image_url);
            } else {
                el.textContent = m.content;
            }
            messages.appendChild(el);
        });
        scrollToLatest();
    })
    .catch(function () {});

    AiChatStream.bindForm({
        messages: messages,
        form: form,
        input: input,
        sendBtn: sendBtn,
        attachBtn: attachBtn,
        attachment: attachment,
        url: '{{ url('/ai/chat') }}',
        getCsrf: function () { return token; },
        clearDraft: clearDraft,
        onScroll: scrollToLatest
    });
})();
</script>
</body>
</html>
