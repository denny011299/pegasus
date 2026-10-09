{{-- Floating AI chat — covers theme .settings-icon; desktop/tablet: drag + fullscreen + new tab --}}
@php
    $aiUser = Session::get('user');
    $aiName = trim((string) (is_array($aiUser)
        ? ($aiUser['staff_name'] ?? 'Pengguna')
        : ($aiUser->staff_name ?? 'Pengguna')));
    $aiHour = (int) now()->format('H');
    $aiGreeting = $aiHour < 11 ? 'pagi' : ($aiHour < 15 ? 'siang' : ($aiHour < 18 ? 'sore' : 'malam'));
    $aiWelcome = "Selamat {$aiGreeting} {$aiName}, ada yang bisa saya bantu hari ini?";
    $aiPageUrl = route('ai.page');
@endphp
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
    #ai-chat-root {
        position: fixed;
        right: 18px;
        bottom: 18px;
        z-index: 1100;
        font-family: inherit;
        width: auto;
    }
    #ai-chat-toggle {
        position: fixed;
        right: 18px;
        bottom: 18px;
        z-index: 1100;
        width: 60px; height: 60px; border: 0; border-radius: 50%;
        background: transparent;
        cursor: pointer; display: inline-flex; align-items: center; justify-content: center;
        padding: 0; transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), filter 0.25s ease;
        animation: aiFloatFab 3.6s ease-in-out infinite;
        filter: drop-shadow(0 10px 22px rgba(15, 23, 42, .38));
    }
    #ai-chat-toggle:hover {
        animation-play-state: paused;
        transform: translateY(-4px) scale(1.08);
        filter: drop-shadow(0 14px 28px rgba(30, 58, 138, .55)) drop-shadow(0 0 12px rgba(56, 189, 248, .45));
    }
    #ai-chat-toggle:active { transform: scale(0.96); }
    .ai-toggle-avatar {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
        display: block;
        border: 2.5px solid rgba(255, 255, 255, 0.9);
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.4);
        background: #0f172a;
    }
    #ai-chat-toggle.is-hidden { display: none !important; }

    /* Float / Melayang Halus */
    @keyframes aiFloatFab {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-5px); }
    }

    /* Radar Ripple pada Indikator Online */
    .ai-toggle-pulse {
        position: absolute; top: 1px; right: 1px; width: 14px; height: 14px;
        background: #10b981; border: 2.5px solid #fff; border-radius: 50%;
        box-shadow: 0 0 8px rgba(16, 185, 129, .6);
    }
    .ai-toggle-pulse::after {
        content: "";
        position: absolute;
        inset: -2px;
        border-radius: 50%;
        border: 1.5px solid #10b981;
        animation: aiRadarRipple 2.4s cubic-bezier(0, 0.2, 0.8, 1) infinite;
        pointer-events: none;
    }
    @keyframes aiRadarRipple {
        0% { transform: scale(0.9); opacity: 0.85; }
        60%, 100% { transform: scale(2.4); opacity: 0; }
    }

    @media (prefers-reduced-motion: reduce) {
        #ai-chat-toggle,
        .ai-robot-icon,
        .ai-robot-eye,
        .ai-toggle-pulse::after {
            animation: none !important;
        }
    }

    #ai-chat-panel {
        display: none;
        width: min(390px, calc(100vw - 28px));
        height: min(520px, calc(100vh - 90px));
        max-height: calc(100dvh - 90px);
        background: #fff;
        border: 1px solid rgba(226, 232, 240, 0.9);
        border-radius: 16px;
        box-shadow: 0 20px 48px -8px rgba(15, 23, 42, .26), 0 0 0 1px rgba(15, 23, 42, .06);
        flex-direction: column;
        overflow: hidden;
        margin-bottom: 10px;
        animation: aiPanelSlideUp 0.28s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    @keyframes aiPanelSlideUp {
        from { opacity: 0; transform: translateY(12px) scale(0.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    #ai-chat-panel.open { display: flex; }
    #ai-chat-root.is-fullscreen {
        inset: 16px;
        right: 16px;
        bottom: 16px;
        left: 16px;
        top: 16px;
        width: auto;
        z-index: 1200;
    }
    #ai-chat-root.is-fullscreen #ai-chat-panel {
        width: 100%;
        height: 100%;
        max-height: none;
        margin: 0;
        border-radius: 16px;
    }
    #ai-chat-root.is-fullscreen #ai-chat-toggle { display: none !important; }

    #ai-chat-panel header {
        padding: 12px 14px;
        background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
        color: #fff;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
        gap: 10px;
        user-select: none;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }
    #ai-chat-panel header.ai-draggable { cursor: move; }
    .ai-header-info {
        display: flex;
        align-items: center;
        gap: 9px;
        flex: 1;
        min-width: 0;
    }
    .ai-header-avatar {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        border: 1.5px solid rgba(255, 255, 255, 0.45);
        box-shadow: 0 0 0 1px rgba(37, 99, 235, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        flex-shrink: 0;
        background: #0f172a;
    }
    .ai-header-avatar-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
        display: block;
    }
    .ai-avatar-online {
        position: absolute;
        bottom: -2px;
        right: -2px;
        width: 9px;
        height: 9px;
        background: #10b981;
        border: 1.5px solid #0f172a;
        border-radius: 50%;
    }
    .ai-header-titles {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }
    .ai-header-titles strong {
        font-size: 13.5px;
        font-weight: 700;
        color: #ffffff;
        letter-spacing: -0.2px;
        line-height: 1.25;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .ai-header-status {
        font-size: 10.5px;
        color: #93c5fd;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-weight: 500;
        line-height: 1.2;
    }
    .ai-status-pulse {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #34d399;
        display: inline-block;
        box-shadow: 0 0 6px #34d399;
    }

    #ai-chat-actions { display: flex; align-items: center; gap: 4px; flex-shrink: 0; }
    #ai-chat-actions button,
    #ai-chat-actions a {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.12);
        color: #fff;
        width: 28px; height: 28px; border-radius: 7px;
        display: inline-flex; align-items: center; justify-content: center;
        cursor: pointer; text-decoration: none; padding: 0; line-height: 1;
        transition: all 0.15s ease;
    }
    #ai-chat-actions button:hover,
    #ai-chat-actions a:hover {
        background: rgba(255, 255, 255, 0.22);
        border-color: rgba(255, 255, 255, 0.3);
        transform: translateY(-1px);
    }
    #ai-chat-actions svg { width: 14px; height: 14px; display: block; }

    #ai-chat-messages {
        flex: 1; overflow-y: auto; padding: 14px; background: var(--ai-bg);
        font-size: 13.5px; line-height: 1.5; -webkit-overflow-scrolling: touch;
        scroll-behavior: smooth;
    }
    #ai-chat-messages::-webkit-scrollbar { width: 5px; }
    #ai-chat-messages::-webkit-scrollbar-track { background: transparent; }
    #ai-chat-messages::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    #ai-chat-messages::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

    .ai-msg {
        margin-bottom: 12px; width: fit-content; max-width: 88%; padding: 9px 13px;
        white-space: pre-wrap; word-break: break-word; font-size: 13px;
        transition: all 0.15s ease;
    }
    .ai-msg.user {
        margin-left: auto;
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: #ffffff;
        border-radius: 14px 14px 4px 14px;
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.22);
        font-weight: 500;
    }
    .ai-msg.bot {
        margin-right: auto;
        background: #ffffff;
        border: 1px solid var(--ai-border);
        color: var(--ai-text-main);
        border-radius: 14px 14px 14px 4px;
        box-shadow: 0 1px 4px rgba(15, 23, 42, 0.04);
    }
    .ai-msg.err {
        margin-right: auto;
        background: #fff1f2;
        border: 1px solid #fecdd3;
        color: #9f1239;
        border-radius: 12px;
        padding: 9px 12px;
        font-size: 12.5px;
        box-shadow: 0 1px 3px rgba(244, 63, 94, 0.06);
    }
    .ai-msg.err::before {
        content: "⚠️ ";
        margin-right: 2px;
    }
    .ai-msg.thinking {
        margin-right: auto;
        background: #ffffff;
        border: 1px solid var(--ai-border);
        border-radius: 14px 14px 14px 4px;
        min-width: 190px;
        box-shadow: 0 1px 4px rgba(15, 23, 42, 0.04);
        padding: 10px 13px;
    }
    .ai-thinking-label {
        font-size: 11px;
        font-weight: 600;
        color: #2563eb;
        margin-bottom: 7px;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .ai-thinking-label::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #2563eb;
        animation: ai-pulse 1s ease-in-out infinite alternate;
    }
    @keyframes ai-pulse {
        from { transform: scale(0.8); opacity: 0.5; }
        to { transform: scale(1.3); opacity: 1; }
    }
    .ai-skel {
        height: 9px; border-radius: 5px; margin-bottom: 6px;
        background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
        background-size: 200% 100%;
        animation: ai-shimmer 1.2s ease-in-out infinite;
    }
    .ai-skel.short { width: 50%; }
    .ai-skel.mid { width: 75%; }
    .ai-skel.long { width: 92%; margin-bottom: 0; }
    @keyframes ai-shimmer {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    .ai-msg.bot.ai-streaming { min-width: 48px; }
    .ai-stream-status {
        font-size: 11px;
        font-weight: 600;
        color: #2563eb;
        margin-bottom: 4px;
    }
    .ai-stream-status:empty { display: none; }
    .ai-caret {
        display: inline-block;
        width: 2px;
        height: 0.95em;
        margin-left: 1px;
        vertical-align: text-bottom;
        background: #2563eb;
        animation: ai-caret-blink 1s step-end infinite;
    }
    @keyframes ai-caret-blink {
        50% { opacity: 0; }
    }


    /* Form & Input Bar */
    #ai-chat-form {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 12px;
        border-top: 1px solid var(--ai-border);
        background: #ffffff;
        flex-shrink: 0;
    }
    .ai-input-wrap {
        flex: 1;
        min-width: 0;
        position: relative;
        display: flex;
        align-items: center;
    }
    #ai-chat-input {
        width: 100%;
        border: 1.5px solid var(--ai-border);
        background: #f8fafc;
        border-radius: 20px;
        padding: 8px 14px;
        font-size: 13px;
        color: var(--ai-text-main);
        outline: none;
        transition: all 0.2s ease;
    }
    #ai-chat-input:focus {
        background: #ffffff;
        border-color: var(--ai-primary-accent);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }
    #ai-chat-input::placeholder { color: #94a3b8; }

    #ai-chat-send {
        border: 0;
        border-radius: 20px;
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: #fff;
        padding: 8px 15px;
        cursor: pointer;
        flex-shrink: 0;
        font-size: 12.5px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.28);
        transition: all 0.18s ease;
    }
    #ai-chat-send:hover:not(:disabled) {
        background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
        box-shadow: 0 4px 10px rgba(37, 99, 235, 0.38);
        transform: translateY(-1px);
    }
    #ai-chat-send:active:not(:disabled) { transform: scale(0.96); }
    #ai-chat-send:disabled {
        opacity: 0.55;
        cursor: not-allowed;
        box-shadow: none;
    }

    #ai-chat-attach {
        border: 1px solid var(--ai-border);
        border-radius: 50%;
        background: #f8fafc;
        color: #64748b;
        width: 34px;
        height: 34px;
        flex-shrink: 0;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        transition: all 0.18s ease;
    }
    #ai-chat-attach:hover:not(:disabled) {
        background: #eff6ff;
        border-color: #bfdbfe;
        color: #2563eb;
        transform: translateY(-1px);
    }
    #ai-chat-attach svg { width: 17px; height: 17px; }
    #ai-chat-attach:disabled { opacity: 0.5; cursor: not-allowed; }

    #ai-chat-preview {
        position: relative;
        align-self: flex-start;
        margin: 8px 12px 0;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        padding: 4px;
        background: #eff6ff;
        box-shadow: 0 1px 3px rgba(37, 99, 235, 0.1);
    }
    #ai-chat-preview img { display: block; max-height: 68px; max-width: 110px; border-radius: 5px; }
    #ai-chat-preview button {
        position: absolute; top: -7px; right: -7px; width: 19px; height: 19px;
        border-radius: 50%; border: 0; background: #ef4444; color: #fff;
        font-size: 13px; line-height: 19px; padding: 0; cursor: pointer;
        box-shadow: 0 1px 4px rgba(239, 68, 68, 0.4);
        display: flex; align-items: center; justify-content: center;
    }
    .ai-msg-img { display: block; max-width: 180px; max-height: 180px; border-radius: 8px; margin-bottom: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }

    /* Mobile */
    @media (max-width: 767.98px) {
        #ai-chat-root { right: 12px; bottom: 12px; }
        #ai-chat-toggle { right: 12px; bottom: 12px; width: 52px; height: 52px; }
        #ai-chat-toggle svg { width: 26px; height: 26px; }
        #ai-chat-panel {
            width: calc(100vw - 24px);
            height: min(72dvh, calc(100dvh - 80px));
            margin-bottom: 8px;
            border-radius: 14px;
        }
        #ai-chat-root.is-fullscreen { inset: 8px; }
        .ai-desktop-only { display: none !important; }
        #ai-chat-panel header.ai-draggable { cursor: default; }
    }
</style>

<div id="ai-chat-root" aria-live="polite">
    <div id="ai-chat-panel">
        <header id="ai-chat-header" class="ai-draggable">
            <div class="ai-header-info">
                <div class="ai-header-avatar">
                    <img src="{{ asset('assets/img/ai-pegasus-avatar.png') }}?v={{ @filemtime(public_path('assets/img/ai-pegasus-avatar.png')) ?: 2 }}" alt="Pegasus" class="ai-header-avatar-img">
                    <span class="ai-avatar-online"></span>
                </div>
                <div class="ai-header-titles">
                    <strong>Asisten Pegasus</strong>
                    <span class="ai-header-status">
                        <span class="ai-status-pulse"></span> Siap Membantu
                    </span>
                </div>
            </div>
            <div id="ai-chat-actions">
                <button type="button" id="ai-chat-fullscreen" class="ai-desktop-only" title="Layar penuh" aria-label="Layar penuh">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 0 0-2 2v3M16 3h3a2 2 0 0 1 2 2v3M8 21H5a2 2 0 0 1-2-2v-3M16 21h3a2 2 0 0 0 2-2v-3"/></svg>
                </button>
                <a id="ai-chat-newtab" class="ai-desktop-only" href="{{ $aiPageUrl }}" target="_blank" rel="noopener" title="Buka di tab baru" aria-label="Buka di tab baru">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                </a>
                <button type="button" id="ai-chat-close" title="Tutup" aria-label="Tutup">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
        </header>
        <div id="ai-chat-messages">
            <div class="ai-msg bot">{{ $aiWelcome }}</div>
        </div>
        <div id="ai-chat-preview" style="display:none"></div>
        <form id="ai-chat-form">
            <button id="ai-chat-attach" type="button" title="Lampirkan gambar (atau tempel screenshot)" aria-label="Lampirkan gambar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
            </button>
            <input id="ai-chat-file" type="file" accept="image/jpeg,image/png,image/webp" hidden>
            <div class="ai-input-wrap">
                <input id="ai-chat-input" type="text" autocomplete="off" placeholder="Tanyakan sesuatu… (Ctrl+V tempel gambar)" maxlength="2000">
            </div>
            <button id="ai-chat-send" type="submit">
                <span>Kirim</span>
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
            </button>
        </form>
    </div>
    <button type="button" id="ai-chat-toggle" title="Asisten Pegasus" aria-label="Buka Asisten Pegasus">
        <img src="{{ asset('assets/img/ai-pegasus-avatar.png') }}?v={{ @filemtime(public_path('assets/img/ai-pegasus-avatar.png')) ?: 2 }}" alt="Asisten Pegasus" class="ai-toggle-avatar">
        <span class="ai-toggle-pulse"></span>
    </button>
</div>

<script src="{{ asset('Custom_js/Shared/ai-chat-image.js') }}"></script>
<script src="{{ asset('Custom_js/Shared/ai-chat-stream.js') }}"></script>
<script>
(function () {
    var root = document.getElementById('ai-chat-root');
    var panel = document.getElementById('ai-chat-panel');
    var header = document.getElementById('ai-chat-header');
    var toggle = document.getElementById('ai-chat-toggle');
    var closeBtn = document.getElementById('ai-chat-close');
    var fsBtn = document.getElementById('ai-chat-fullscreen');
    var form = document.getElementById('ai-chat-form');
    var input = document.getElementById('ai-chat-input');
    var sendBtn = document.getElementById('ai-chat-send');
    var messages = document.getElementById('ai-chat-messages');
    // Conversation lives in the login session (server side); this tab only
    // remembers whether the panel was open so it reopens on the next menu.
    var OPEN_KEY = 'ai_chat_open';
    var drag = null;

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
        // After layout, so a panel that was hidden has a real height.
        requestAnimationFrame(function () { messages.scrollTop = messages.scrollHeight; });
    }

    function isDesktop() {
        return window.matchMedia('(min-width: 768px)').matches;
    }

    function rememberOpen(open) {
        try { sessionStorage.setItem(OPEN_KEY, open ? '1' : '0'); } catch (e) {}
    }

    function openChat() {
        panel.classList.add('open');
        toggle.classList.add('is-hidden');
        rememberOpen(true);
        scrollToLatest();
        input.focus();
    }

    function closeChat() {
        rememberOpen(false);
        panel.classList.remove('open');
        root.classList.remove('is-fullscreen');
        toggle.classList.remove('is-hidden');
        root.style.left = '';
        root.style.top = '';
        root.style.right = '';
        root.style.bottom = '';
    }

    function toggleFullscreen() {
        if (!isDesktop()) return;
        root.classList.toggle('is-fullscreen');
        root.style.left = '';
        root.style.top = '';
        root.style.right = '';
        root.style.bottom = '';
    }

    function appendMsg(text, cls, imageUrl) {
        var el = document.createElement('div');
        el.className = 'ai-msg ' + cls;
        if (imageUrl) {
            AiChatImage.fillBubble(el, text, imageUrl);
        } else {
            el.textContent = text;
        }
        messages.appendChild(el);
        messages.scrollTop = messages.scrollHeight;
        return el;
    }

    // Drag whole floating root by header (desktop/tablet only)
    header.addEventListener('pointerdown', function (e) {
        if (!isDesktop() || root.classList.contains('is-fullscreen')) return;
        if (e.target.closest('#ai-chat-actions')) return;
        var rect = root.getBoundingClientRect();
        drag = {
            startX: e.clientX,
            startY: e.clientY,
            dx: e.clientX - rect.left,
            dy: e.clientY - rect.top,
            w: rect.width,
            h: rect.height,
            active: false
        };
        header.setPointerCapture(e.pointerId);
    });
    header.addEventListener('pointermove', function (e) {
        if (!drag) return;
        if (!drag.active) {
            if (Math.hypot(e.clientX - drag.startX, e.clientY - drag.startY) < 4) return;
            drag.active = true;
            root.style.right = 'auto';
            root.style.bottom = 'auto';
        }
        var x = Math.min(Math.max(8, e.clientX - drag.dx), window.innerWidth - drag.w - 8);
        var y = Math.min(Math.max(8, e.clientY - drag.dy), window.innerHeight - drag.h - 8);
        root.style.left = x + 'px';
        root.style.top = y + 'px';
    });
    header.addEventListener('pointerup', function () { drag = null; });
    header.addEventListener('pointercancel', function () { drag = null; });

    var attachBtn = document.getElementById('ai-chat-attach');
    var attachment = AiChatImage.attach({
        button: attachBtn,
        fileInput: document.getElementById('ai-chat-file'),
        input: input,
        preview: document.getElementById('ai-chat-preview'),
        // Panel agar Ctrl+V tetap jalan meski fokus bukan di input.
        pasteRoot: panel,
        onError: function (msg) { appendMsg(msg, 'err'); }
    });

    // Redraw this login session's chat so moving between menus keeps it.
    function loadHistory() {
        fetch('{{ url('/ai/history') }}', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
        .then(function (res) { return res.ok ? res.json() : null; })
        .then(function (data) {
            if (!data || data.status !== 1 || !Array.isArray(data.messages)) return;
            data.messages.forEach(function (m) {
                appendMsg(m.content, m.role === 'user' ? 'user' : 'bot', m.image_url || null);
            });
            scrollToLatest();
        })
        .catch(function () {});
    }

    loadHistory();
    try {
        if (sessionStorage.getItem(OPEN_KEY) === '1') openChat();
    } catch (e) {}

    toggle.addEventListener('click', openChat);
    closeBtn.addEventListener('click', closeChat);
    if (fsBtn) fsBtn.addEventListener('click', toggleFullscreen);

    AiChatStream.bindForm({
        messages: messages,
        form: form,
        input: input,
        sendBtn: sendBtn,
        attachBtn: attachBtn,
        attachment: attachment,
        url: '{{ url('/ai/chat') }}',
        getCsrf: function () {
            return (typeof token !== 'undefined' && token) ? token :
                (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
        },
        clearDraft: clearDraft,
        onScroll: scrollToLatest
    });
})();
</script>
