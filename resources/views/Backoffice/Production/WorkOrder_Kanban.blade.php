@extends('layout.fullscreen')

@section('custom_css')
<link rel="stylesheet" href="{{ asset('assets/plugins/daterangepicker/daterangepicker.css') }}">
<style>
    .daterangepicker { z-index: 1060 !important; }

    /* ═══════════════════════════════════════════════════════════
       Pegasus Kanban — Aurora Glass (modern luxury)
       ═══════════════════════════════════════════════════════════ */
    :root {
        --wok-ink: #0b1220;
        --wok-ink-2: #334155;
        --wok-muted: #64748b;
        --wok-soft: #94a3b8;
        --wok-line: rgba(148, 163, 184, 0.22);
        --wok-blue: #2563eb;
        --wok-blue-2: #3b82f6;
        --wok-amber: #f59e0b;
        --wok-amber-2: #fbbf24;
        --wok-green: #10b981;
        --wok-green-2: #34d399;
        --wok-glass: rgba(255, 255, 255, 0.62);
        --wok-glass-strong: rgba(255, 255, 255, 0.86);
        --wok-glass-border: rgba(255, 255, 255, 0.75);
        --wok-ease: cubic-bezier(0.16, 1, 0.3, 1);
    }

    body.pp-fullscreen-body {
        background: #eef2f9 !important;
        font-family: "Inter", "Segoe UI Variable", "Segoe UI", -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
        color: var(--wok-ink);
        -webkit-font-smoothing: antialiased;
    }

    /* Aurora ambient blobs (glass needs color behind it) */
    .wok-aurora {
        position: fixed;
        inset: 0;
        z-index: 0;
        pointer-events: none;
        overflow: hidden;
    }
    .wok-aurora span {
        position: absolute;
        border-radius: 50%;
        filter: blur(90px);
        opacity: 0.55;
        animation: wokFloat 22s ease-in-out infinite alternate;
    }
    .wok-aurora .a1 { width: 520px; height: 520px; top: -160px; left: -120px; background: #93c5fd; }
    .wok-aurora .a2 { width: 460px; height: 460px; top: 20%; right: -140px; background: #c4b5fd; animation-delay: -6s; opacity: 0.4; }
    .wok-aurora .a3 { width: 520px; height: 520px; bottom: -220px; left: 30%; background: #a7f3d0; animation-delay: -12s; opacity: 0.45; }
    .wok-aurora .a4 { width: 300px; height: 300px; bottom: 10%; left: -80px; background: #fde68a; animation-delay: -3s; opacity: 0.3; }
    .wok-aurora::after {
        content: "";
        position: absolute;
        inset: 0;
        background-image: radial-gradient(rgba(15, 23, 42, 0.05) 1px, transparent 1px);
        background-size: 22px 22px;
        mask-image: linear-gradient(180deg, rgba(0,0,0,0.6), transparent 70%);
        -webkit-mask-image: linear-gradient(180deg, rgba(0,0,0,0.6), transparent 70%);
    }
    @keyframes wokFloat {
        0%   { transform: translate(0, 0) scale(1); }
        100% { transform: translate(60px, 40px) scale(1.12); }
    }
    @media (prefers-reduced-motion: reduce) {
        .wok-aurora span { animation: none; }
    }

    .wok-page {
        position: relative;
        z-index: 1;
        min-height: 100vh;
        padding: 26px 34px 30px;
        background: transparent !important;
    }

    /* Shared glass surface */
    .wok-glass {
        background: var(--wok-glass);
        backdrop-filter: blur(22px) saturate(160%);
        -webkit-backdrop-filter: blur(22px) saturate(160%);
        border: 1px solid var(--wok-glass-border);
        box-shadow:
            0 1px 0 rgba(255, 255, 255, 0.9) inset,
            0 20px 40px -24px rgba(15, 23, 42, 0.18),
            0 2px 6px -2px rgba(15, 23, 42, 0.05);
    }

    /* ═══ HEADER ═══ */
    .sloth-top-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 18px;
        margin-bottom: 16px;
        border-radius: 24px;
        padding: 16px 20px 16px 18px;
    }
    .sloth-project-meta {
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .sloth-project-icon {
        position: relative;
        width: 48px;
        height: 48px;
        border-radius: 15px;
        background: linear-gradient(140deg, #60a5fa 0%, #2563eb 45%, #1e3a8a 100%);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow:
            0 1px 0 rgba(255, 255, 255, 0.45) inset,
            0 10px 24px -8px rgba(37, 99, 235, 0.65);
    }
    .sloth-project-icon svg { width: 22px; height: 22px; }
    .sloth-project-icon::after {
        content: "";
        position: absolute;
        inset: 0;
        border-radius: inherit;
        background: linear-gradient(180deg, rgba(255,255,255,0.35), transparent 55%);
        pointer-events: none;
    }
    .sloth-project-title {
        font-size: 21px;
        font-weight: 800;
        color: var(--wok-ink);
        margin: 0;
        letter-spacing: -0.5px;
        line-height: 1.15;
    }
    .wok-subtitle {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 4px;
        font-size: 12px;
        font-weight: 500;
        color: var(--wok-muted);
    }
    .wok-live {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 2px 9px 2px 7px;
        border-radius: 999px;
        background: rgba(16, 185, 129, 0.1);
        color: #047857;
        font-weight: 700;
        font-size: 10.5px;
        letter-spacing: 0.6px;
        text-transform: uppercase;
    }
    .wok-live i {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: var(--wok-green);
        position: relative;
    }
    .wok-live i::after {
        content: "";
        position: absolute;
        inset: -4px;
        border-radius: 50%;
        border: 2px solid var(--wok-green);
        opacity: 0;
        animation: wokPing 1.8s var(--wok-ease) infinite;
    }
    @keyframes wokPing {
        0%   { transform: scale(0.4); opacity: 0.8; }
        100% { transform: scale(1.6); opacity: 0; }
    }

    /* Stats chips */
    .wok-stats {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 5px;
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.55);
        border: 1px solid rgba(255, 255, 255, 0.8);
    }
    .wok-stat {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 6px 14px 6px 8px;
        border-radius: 12px;
        transition: background 0.2s;
    }
    .wok-stat:hover { background: rgba(255, 255, 255, 0.85); }
    .wok-stat-ic {
        width: 30px;
        height: 30px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        color: #fff;
    }
    .wok-stat--inprod .wok-stat-ic { background: linear-gradient(140deg, var(--wok-blue-2), var(--wok-blue)); box-shadow: 0 6px 14px -6px rgba(37,99,235,.7); }
    .wok-stat--awaiting .wok-stat-ic { background: linear-gradient(140deg, var(--wok-amber-2), var(--wok-amber)); box-shadow: 0 6px 14px -6px rgba(245,158,11,.7); }
    .wok-stat--done .wok-stat-ic { background: linear-gradient(140deg, var(--wok-green-2), var(--wok-green)); box-shadow: 0 6px 14px -6px rgba(16,185,129,.7); }
    .wok-stat-num {
        font-size: 17px;
        font-weight: 800;
        line-height: 1;
        color: var(--wok-ink);
        font-variant-numeric: tabular-nums;
    }
    .wok-stat-lbl {
        font-size: 10.5px;
        font-weight: 600;
        color: var(--wok-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 3px;
        white-space: nowrap;
    }
    .wok-stat-sep {
        width: 1px;
        height: 26px;
        background: var(--wok-line);
    }

    .sloth-top-right {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    .sloth-search-box {
        position: relative;
        min-width: 240px;
    }
    .sloth-search-box input {
        height: 42px;
        padding: 0 14px 0 40px;
        font-size: 13px;
        font-weight: 500;
        border-radius: 14px;
        border: 1px solid rgba(255, 255, 255, 0.9);
        background: rgba(255, 255, 255, 0.7);
        color: var(--wok-ink);
        width: 100%;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04) inset;
        transition: all 0.25s var(--wok-ease);
    }
    .sloth-search-box input::placeholder { color: var(--wok-soft); }
    .sloth-search-box input:focus {
        outline: none;
        background: #fff;
        border-color: rgba(59, 130, 246, 0.55);
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.14);
    }
    .sloth-search-box .search-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--wok-soft);
        font-size: 15px;
        pointer-events: none;
    }

    .sloth-avatar-stack { display: flex; align-items: center; }
    .sloth-avatar-stack .stack-avatar {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        border: 2.5px solid #fff;
        margin-left: -10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 800;
        color: #fff;
        box-shadow: 0 4px 10px -4px rgba(15, 23, 42, 0.35);
        transition: transform 0.25s var(--wok-ease);
        position: relative;
    }
    .sloth-avatar-stack .stack-avatar:first-child { margin-left: 0; }
    .sloth-avatar-stack .stack-avatar:hover { transform: translateY(-3px) scale(1.06); z-index: 3; }
    .sloth-avatar-stack .av-1 { background: linear-gradient(140deg, #60a5fa, #1d4ed8); }
    .sloth-avatar-stack .av-2 { background: linear-gradient(140deg, #34d399, #047857); }
    .sloth-avatar-stack .av-3 { background: linear-gradient(140deg, #fbbf24, #d97706); }
    .sloth-avatar-stack .av-more { background: #fff; color: var(--wok-ink-2); }

    .sloth-btn-pill-primary {
        position: relative;
        height: 42px;
        padding: 0 20px;
        background: linear-gradient(140deg, #3b82f6 0%, #2563eb 50%, #1e40af 100%);
        color: #fff !important;
        border: 0;
        border-radius: 14px;
        font-size: 13px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        overflow: hidden;
        box-shadow:
            0 1px 0 rgba(255, 255, 255, 0.35) inset,
            0 10px 22px -10px rgba(37, 99, 235, 0.85);
        transition: transform 0.25s var(--wok-ease), box-shadow 0.25s var(--wok-ease);
    }
    .sloth-btn-pill-primary::before {
        content: "";
        position: absolute;
        top: 0;
        left: -120%;
        width: 60%;
        height: 100%;
        background: linear-gradient(110deg, transparent, rgba(255, 255, 255, 0.35), transparent);
        transition: left 0.6s var(--wok-ease);
    }
    .sloth-btn-pill-primary:hover {
        transform: translateY(-2px);
        box-shadow:
            0 1px 0 rgba(255, 255, 255, 0.35) inset,
            0 16px 28px -12px rgba(37, 99, 235, 0.95);
    }
    .sloth-btn-pill-primary:hover::before { left: 140%; }

    /* ═══ TOOLBAR (tabs + date) ═══ */
    .sloth-nav-tabs-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        border-radius: 20px;
        padding: 8px 10px;
        margin-bottom: 22px;
    }
    .sloth-tabs-list {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px;
        border-radius: 14px;
        background: rgba(15, 23, 42, 0.045);
    }
    .sloth-tab-link,
    button.sloth-tab-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 600;
        color: var(--wok-muted);
        padding: 8px 16px;
        margin: 0;
        border: 0;
        border-radius: 11px;
        background: transparent;
        cursor: pointer;
        transition: all 0.25s var(--wok-ease);
    }
    .sloth-tab-link:hover { color: var(--wok-ink); }
    .sloth-tab-link.active,
    .sloth-tab-link.is-active {
        color: var(--wok-blue);
        background: #fff;
        font-weight: 700;
        box-shadow:
            0 1px 0 rgba(255,255,255,1) inset,
            0 6px 16px -8px rgba(37, 99, 235, 0.45),
            0 1px 3px rgba(15, 23, 42, 0.06);
    }
    .sloth-tabs-right { display: flex; align-items: center; gap: 12px; }
    .wok-date-wrap { position: relative; min-width: 260px; }
    .wok-date-wrap .fe-calendar {
        position: absolute;
        left: 8px;
        top: 50%;
        transform: translateY(-50%);
        width: 26px;
        height: 26px;
        border-radius: 8px;
        background: linear-gradient(140deg, #60a5fa, #2563eb);
        color: #fff;
        font-size: 13px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        pointer-events: none;
        z-index: 1;
    }
    #wok_filter_date {
        height: 40px;
        padding: 6px 14px 6px 44px;
        font-size: 12.5px;
        font-weight: 700;
        color: var(--wok-ink-2);
        border: 1px solid rgba(255, 255, 255, 0.9);
        border-radius: 13px;
        background: rgba(255, 255, 255, 0.75);
        width: 100%;
        cursor: pointer;
        font-variant-numeric: tabular-nums;
        transition: all 0.25s var(--wok-ease);
    }
    #wok_filter_date:hover { background: #fff; }
    #wok_filter_date:focus {
        outline: none;
        background: #fff;
        border-color: rgba(59, 130, 246, 0.55);
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.14);
    }

    /* ═══ BOARD ═══ */
    .sloth-board-grid {
        display: flex;
        gap: 20px;
        align-items: stretch;
        overflow-x: auto;
        padding: 2px 2px 14px;
        height: calc(100vh - 232px);
        min-height: 420px;
        scroll-snap-type: x proximity;
    }
    .sloth-board-grid::-webkit-scrollbar { height: 10px; }
    .sloth-board-grid::-webkit-scrollbar-thumb {
        background: rgba(100, 116, 139, 0.35);
        border-radius: 10px;
        border: 3px solid transparent;
        background-clip: padding-box;
    }
    .sloth-board-grid::-webkit-scrollbar-track { background: transparent; }

    /* Column lane */
    .sloth-board-col {
        --col-c: #6366f1;
        --col-c2: #818cf8;
        --col-tint: rgba(99, 102, 241, 0.08);
        position: relative;
        display: flex;
        flex-direction: column;
        flex: 1 0 340px;
        min-width: 330px;
        max-width: 480px;
        border-radius: 26px;
        padding: 0;
        overflow: hidden;
        scroll-snap-align: start;
        background: rgba(255, 255, 255, 0.42);
        backdrop-filter: blur(22px) saturate(150%);
        -webkit-backdrop-filter: blur(22px) saturate(150%);
        border: 1px solid rgba(255, 255, 255, 0.7);
        box-shadow:
            0 1px 0 rgba(255, 255, 255, 0.9) inset,
            0 24px 48px -30px rgba(15, 23, 42, 0.25);
    }
    .sloth-board-col--inprod   { --col-c: #2563eb; --col-c2: #60a5fa; --col-tint: rgba(37, 99, 235, 0.08); }
    .sloth-board-col--awaiting { --col-c: #f59e0b; --col-c2: #fcd34d; --col-tint: rgba(245, 158, 11, 0.09); }
    .sloth-board-col--done     { --col-c: #10b981; --col-c2: #6ee7b7; --col-tint: rgba(16, 185, 129, 0.08); }

    .sloth-board-col::before {
        content: "";
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 140px;
        background: linear-gradient(180deg, var(--col-tint), transparent);
        pointer-events: none;
    }

    .sloth-col-header-bar {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 20px 14px;
    }
    .sloth-col-header-bar::after {
        content: "";
        position: absolute;
        left: 20px;
        right: 20px;
        bottom: 0;
        height: 3px;
        border-radius: 999px;
        background: linear-gradient(90deg, var(--col-c), var(--col-c2) 60%, transparent);
        opacity: 0.85;
    }
    .sloth-col-title-left {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
        flex: 1;
    }
    .sloth-col-dot-indicator {
        position: relative;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
        background: var(--col-c);
        box-shadow: 0 0 0 4px var(--col-tint), 0 0 12px var(--col-c);
    }
    .sloth-board-col--inprod .sloth-col-dot-indicator::after,
    .sloth-board-col--awaiting .sloth-col-dot-indicator::after {
        content: "";
        position: absolute;
        inset: -4px;
        border-radius: 50%;
        border: 2px solid var(--col-c);
        opacity: 0;
        animation: wokPing 2.2s var(--wok-ease) infinite;
    }
    .sloth-col-title-text {
        font-size: 15px;
        font-weight: 800;
        color: var(--wok-ink);
        margin: 0;
        letter-spacing: -0.3px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .sloth-col-count-num {
        margin-left: auto;
        min-width: 30px;
        height: 26px;
        padding: 0 10px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 800;
        color: #fff;
        font-variant-numeric: tabular-nums;
        background: linear-gradient(140deg, var(--col-c2), var(--col-c));
        box-shadow: 0 6px 14px -6px var(--col-c), 0 1px 0 rgba(255,255,255,.35) inset;
    }
    /* Status counters keep their colors: biru / oranye / hijau */
    .sloth-board-col--awaiting .sloth-col-count-num { background: linear-gradient(140deg, #fbbf24, #f97316); }

    /* Cards scroll body */
    .sloth-cards-container {
        position: relative;
        flex: 1;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 14px;
        padding: 16px 16px 20px;
        scrollbar-width: thin;
        scrollbar-color: rgba(100, 116, 139, 0.3) transparent;
        mask-image: linear-gradient(180deg, transparent 0, #000 12px, #000 calc(100% - 18px), transparent 100%);
        -webkit-mask-image: linear-gradient(180deg, transparent 0, #000 12px, #000 calc(100% - 18px), transparent 100%);
    }
    .sloth-cards-container::-webkit-scrollbar { width: 6px; }
    .sloth-cards-container::-webkit-scrollbar-thumb { background: rgba(100, 116, 139, 0.28); border-radius: 10px; }
    .sloth-cards-container::-webkit-scrollbar-track { background: transparent; }

    /* ═══ CARD ═══ */
    .sloth-kanban-card {
        --st-c: #2563eb;
        --st-glow: rgba(37, 99, 235, 0.22);
        position: relative;
        flex-shrink: 0;
        background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(255,255,255,0.86));
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-radius: 18px;
        padding: 16px 18px 14px;
        border: 1px solid rgba(255, 255, 255, 0.95);
        box-shadow:
            0 1px 0 #fff inset,
            0 0 0 1px rgba(15, 23, 42, 0.04),
            0 8px 20px -14px rgba(15, 23, 42, 0.28);
        transition: transform 0.35s var(--wok-ease), box-shadow 0.35s var(--wok-ease);
        cursor: pointer;
    }
    .sloth-kanban-card.wok-st-awaiting_acc { --st-c: #f59e0b; --st-glow: rgba(245, 158, 11, 0.25); }
    .sloth-kanban-card.wok-st-done         { --st-c: #10b981; --st-glow: rgba(16, 185, 129, 0.22); }
    .sloth-kanban-card:hover {
        transform: translateY(-3px);
        box-shadow:
            0 1px 0 #fff inset,
            0 0 0 1px color-mix(in srgb, var(--st-c) 28%, transparent),
            0 22px 34px -18px var(--st-glow),
            0 10px 18px -14px rgba(15, 23, 42, 0.18);
    }
    .sloth-card-menu.open { z-index: 40; }
    .sloth-kanban-card:has(.sloth-card-menu.open) { z-index: 20; }

    /* Pinned */
    .sloth-kanban-card.is-pinned {
        background: linear-gradient(165deg, #fffbeb 0%, #ffffff 55%);
        box-shadow:
            0 1px 0 #fff inset,
            0 0 0 1.5px rgba(245, 158, 11, 0.45),
            0 14px 28px -16px rgba(245, 158, 11, 0.55);
    }
    .sloth-pinned-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        flex-shrink: 0;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.4px;
        text-transform: uppercase;
        color: #b45309;
        background: linear-gradient(140deg, #fef3c7, #fde68a);
        padding: 3px 8px;
        border-radius: 999px;
    }

    /* Progress 3 segment (warna ikut status) */
    .sloth-card-progress-bar {
        display: flex;
        gap: 5px;
        margin-bottom: 14px;
    }
    .sloth-progress-seg {
        height: 5px;
        border-radius: 999px;
        flex: 1;
    }
    .seg-blue  { background: linear-gradient(90deg, #3b82f6, #2563eb); box-shadow: 0 2px 6px -1px rgba(37,99,235,.45); }
    .seg-amber { background: linear-gradient(90deg, #fbbf24, #f59e0b); box-shadow: 0 2px 6px -1px rgba(245,158,11,.45); }
    .seg-green { background: linear-gradient(90deg, #34d399, #10b981); box-shadow: 0 2px 6px -1px rgba(16,185,129,.45); }
    .seg-gray  { background: rgba(148, 163, 184, 0.22); }

    .sloth-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 12px;
    }
    .sloth-card-title-group {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 1;
        min-width: 0;
    }
    .sloth-card-check-icon {
        width: 30px;
        height: 30px;
        border-radius: 10px;
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        color: var(--st-c);
        background: color-mix(in srgb, var(--st-c) 11%, #fff);
        box-shadow: 0 0 0 1px color-mix(in srgb, var(--st-c) 18%, transparent) inset;
    }
    .sloth-card-code-title {
        font-size: 14.5px;
        font-weight: 800;
        color: var(--wok-ink);
        margin: 0;
        line-height: 1.25;
        letter-spacing: -0.2px;
        font-variant-numeric: tabular-nums;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .wok-card-sub {
        display: block;
        font-size: 11px;
        font-weight: 600;
        color: var(--wok-soft);
        margin-top: 2px;
        letter-spacing: 0.2px;
    }

    /* Menu ⋮ */
    .sloth-card-menu { position: relative; flex-shrink: 0; }
    .sloth-card-menu-btn {
        width: 30px;
        height: 30px;
        border: 0;
        border-radius: 10px;
        background: transparent;
        color: var(--wok-soft);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
    }
    .sloth-card-menu-btn:hover,
    .sloth-card-menu.open .sloth-card-menu-btn {
        background: rgba(15, 23, 42, 0.06);
        color: var(--wok-ink);
    }
    .sloth-card-menu-drop {
        display: none;
        position: absolute;
        right: 0;
        top: calc(100% + 6px);
        min-width: 168px;
        padding: 6px;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
        border: 1px solid rgba(255, 255, 255, 0.9);
        box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.05), 0 20px 40px -14px rgba(15, 23, 42, 0.3);
        z-index: 50;
        transform-origin: top right;
        animation: wokPop 0.18s var(--wok-ease);
    }
    @keyframes wokPop {
        from { opacity: 0; transform: scale(0.94) translateY(-4px); }
        to   { opacity: 1; transform: scale(1) translateY(0); }
    }
    .sloth-card-menu.open .sloth-card-menu-drop { display: block; }
    .sloth-card-menu-item {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        border: 0;
        background: transparent;
        color: var(--wok-ink-2);
        font-size: 12.5px;
        font-weight: 600;
        padding: 9px 10px;
        border-radius: 9px;
        text-decoration: none;
        cursor: pointer;
        transition: background 0.15s, color 0.15s;
    }
    .sloth-card-menu-item:hover { background: rgba(37, 99, 235, 0.08); color: var(--wok-blue); }

    /* Meta tile (SPKP / Lini) */
    .sloth-card-meta-line {
        display: grid;
        gap: 6px;
        margin-bottom: 12px;
    }
    .wok-meta {
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 8px 10px;
        border-radius: 11px;
        background: rgba(241, 245, 249, 0.75);
        font-size: 12px;
        min-width: 0;
    }
    .wok-meta i { color: var(--wok-soft); font-size: 13px; flex-shrink: 0; }
    .wok-meta-k {
        font-size: 10px;
        font-weight: 700;
        color: var(--wok-soft);
        text-transform: uppercase;
        letter-spacing: 0.6px;
        flex-shrink: 0;
    }
    .wok-meta-v {
        margin-left: auto;
        font-weight: 700;
        color: var(--wok-ink-2);
        font-variant-numeric: tabular-nums;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* Status pill */
    .sloth-pills-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px;
        margin-bottom: 14px;
    }
    .sloth-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        font-weight: 700;
        padding: 4px 10px 4px 8px;
        border-radius: 999px;
        letter-spacing: 0.1px;
    }
    .sloth-pill::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
    }
    .sloth-pill--blue   { background: rgba(37, 99, 235, 0.09);  color: #1d4ed8; }
    .sloth-pill--orange { background: rgba(245, 158, 11, 0.13); color: #b45309; }
    .sloth-pill--green  { background: rgba(16, 185, 129, 0.11); color: #047857; }

    /* Footer */
    .sloth-card-footer-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding-top: 12px;
        border-top: 1px dashed rgba(148, 163, 184, 0.3);
        font-size: 12px;
        color: var(--wok-muted);
    }
    .sloth-footer-left {
        display: flex;
        align-items: center;
        gap: 9px;
        min-width: 0;
    }
    .sloth-user-av {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        flex-shrink: 0;
        background: linear-gradient(140deg, #60a5fa, #1e3a8a);
        color: #fff;
        font-size: 10px;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 0 0 2px #fff, 0 4px 10px -4px rgba(30, 58, 138, 0.6);
    }
    .sloth-footer-right .pic-name,
    .sloth-footer-left .pic-name {
        font-size: 12px;
        font-weight: 700;
        color: var(--wok-ink-2);
        max-width: 140px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .sloth-footer-right {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-shrink: 0;
    }
    .sloth-footer-date {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 11.5px;
        font-weight: 600;
        color: var(--wok-muted);
        padding: 4px 9px;
        border-radius: 8px;
        background: rgba(241, 245, 249, 0.8);
        font-variant-numeric: tabular-nums;
    }

    .sloth-empty-box {
        text-align: center;
        color: var(--wok-soft);
        font-size: 13px;
        font-weight: 600;
        padding: 46px 16px;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.4);
        border: 1.5px dashed rgba(148, 163, 184, 0.45);
    }
    .sloth-board-loading { opacity: 0.6; pointer-events: none; transition: opacity 0.2s; }

    @media (max-width: 1100px) {
        .wok-stats { display: none; }
    }
    @media (max-width: 768px) {
        .wok-page { padding: 16px; }
        .sloth-board-col { flex-basis: 310px; min-width: 300px; }
        .sloth-board-grid { height: auto; }
    }
</style>
@endsection

@section('content')
<div class="wok-aurora" aria-hidden="true"><span class="a1"></span><span class="a2"></span><span class="a3"></span><span class="a4"></span></div>

<div class="page-wrapper wok-page">
    <div class="content container-fluid p-0">
        <!-- Header -->
        <div class="sloth-top-bar wok-glass">
            <div class="sloth-project-meta">
                <div class="sloth-project-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="3"/><path d="M8 7v7"/><path d="M12 7v10"/><path d="M16 7v4"/></svg>
                </div>
                <div>
                    <h1 class="sloth-project-title">Work Order Production</h1>
                    <div class="wok-subtitle">
                        <span class="wok-live"><i></i>Live</span>
                        <span>Board produksi realtime</span>
                    </div>
                </div>
            </div>

            <!-- Ringkasan status -->
            <div class="wok-stats">
                <div class="wok-stat wok-stat--inprod">
                    <span class="wok-stat-ic"><i class="fe fe-loader"></i></span>
                    <div><div class="wok-stat-num" data-wok-stat="inprod">0</div><div class="wok-stat-lbl">In Production</div></div>
                </div>
                <span class="wok-stat-sep"></span>
                <div class="wok-stat wok-stat--awaiting">
                    <span class="wok-stat-ic"><i class="fe fe-clock"></i></span>
                    <div><div class="wok-stat-num" data-wok-stat="awaiting_acc">0</div><div class="wok-stat-lbl">Need ACC</div></div>
                </div>
                <span class="wok-stat-sep"></span>
                <div class="wok-stat wok-stat--done">
                    <span class="wok-stat-ic"><i class="fe fe-check"></i></span>
                    <div><div class="wok-stat-num" data-wok-stat="done">0</div><div class="wok-stat-lbl">Completed</div></div>
                </div>
            </div>

            <div class="sloth-top-right">
                <div class="sloth-search-box">
                    <i class="fe fe-search search-icon"></i>
                    <input type="text" id="wok-search" placeholder="Cari WO, SPKP, PIC...">
                </div>
                <div class="sloth-avatar-stack" title="Tim Produksi"></div>
                <a class="sloth-btn-pill-primary" href="{{ route('productionPlanning', ['tab' => 'job']) }}">
                    <i class="fe fe-arrow-left"></i> Kembali ke Planning
                </a>
            </div>
        </div>

        <!-- Tab kiri = cara grup board; tanggal kanan = daterangepicker -->
        <div class="sloth-nav-tabs-bar wok-glass">
            <div class="sloth-tabs-list" role="navigation" aria-label="Grup kolom Kanban">
                <button type="button" class="sloth-tab-link wok-group-btn is-active" data-wok-group="status">
                    <i class="fe fe-layers"></i> Per Status
                </button>
                <button type="button" class="sloth-tab-link wok-group-btn" data-wok-group="pic">
                    <i class="fe fe-users"></i> Per PIC
                </button>
                <button type="button" class="sloth-tab-link wok-group-btn" data-wok-group="line">
                    <i class="fe fe-git-commit"></i> Per Lini
                </button>
            </div>

            <div class="sloth-tabs-right">
                <div class="wok-date-wrap">
                    <i class="fe fe-calendar"></i>
                    <input type="text" id="wok_filter_date" class="form-control" placeholder="DD-MM-YYYY — DD-MM-YYYY" readonly>
                </div>
            </div>
        </div>

        <!-- Kolom digenerate JS (status / PIC / lini) -->
        <div class="sloth-board-grid" id="wok-board">
            <div class="sloth-empty-box w-100">Memuat data...</div>
        </div>
    </div>
</div>
@endsection

@section('custom_js')
@include('components.pp-live-echo')
<script src="{{ asset('assets/plugins/moment/moment.min.js') }}"></script>
<script src="{{ asset('assets/plugins/daterangepicker/daterangepicker.js') }}"></script>
<script src="{{ asset('Custom_js/Backoffice/Production/WorkOrder_Kanban.js') }}?v={{ time() }}"></script>
@endsection
