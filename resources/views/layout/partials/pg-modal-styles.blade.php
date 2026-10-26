<style>
    /* ═══ SweetAlert2 di atas modal PG ═══ */
    /* pg-modal--confirm/--danger sengaja dipasang z-index:1065 (di atas modal-photo/nested
       modal lain) — lebih tinggi dari z-index default SweetAlert2 (1060), jadi popup notifikasi()
       yang muncul SETELAH modal konfirmasi/danger masih terbuka (mis. hasil ACC gagal) tampil
       tertutup DI BELAKANG modal itu. SweetAlert2 selalu harus jadi layer paling atas. */
    .swal2-container {
        z-index: 2000 !important;
    }

    /* ═══ PG Modal — Form (biru: edit / view / simpan) ═══ */
    .pg-modal--form .modal-content {
        border: none;
        border-radius: 16px;
        overflow: hidden;
    }
    .pg-modal--form .modal-header {
        background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
        border: 0;
        padding: 18px 24px;
    }
    .pg-modal--form .modal-title {
        color: #ffffff;
        font-weight: 700;
    }
    .pg-modal--form .modal-subtitle {
        color: rgba(255, 255, 255, 0.7) !important;
        font-size: 13px;
        margin-top: 4px;
        display: block;
    }
    .pg-modal--form .pg-modal-icon {
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .pg-modal--form .pg-modal-icon i {
        font-size: 18px;
        color: #fff;
    }

    /* ═══ PG Modal — Confirm (hijau: konfirmasi / ACC) ═══ */
    .pg-modal--confirm .modal-content {
        border: none;
        border-radius: 16px;
        overflow: hidden;
    }
    .pg-modal--confirm .modal-header {
        background: linear-gradient(135deg, #064e3b 0%, #059669 100%);
        border: 0;
        padding: 18px 24px;
    }
    .pg-modal--confirm .modal-title {
        color: #fff;
        font-weight: 700;
    }
    .pg-modal--confirm .modal-subtitle {
        color: rgba(255, 255, 255, 0.7) !important;
        font-size: 13px;
        margin-top: 4px;
        display: block;
    }
    .pg-modal--confirm .pg-modal-icon {
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .pg-modal--confirm .pg-modal-icon i {
        font-size: 18px;
        color: #fff;
    }
    .pg-modal--confirm .modal-body {
        font-size: 14px;
        color: #334155;
    }

    /* ═══ PG Modal — Danger (merah: hapus / peringatan) ═══ */
    .pg-modal--danger .modal-content {
        border: none;
        border-radius: 16px;
        overflow: hidden;
    }
    .pg-modal--danger .modal-header {
        background: linear-gradient(135deg, #991b1b 0%, #ef4444 100%);
        border: 0;
        padding: 18px 24px;
    }
    .pg-modal--danger .modal-title {
        color: #fff;
        font-weight: 700;
    }
    .pg-modal--danger .modal-subtitle {
        color: rgba(255, 255, 255, 0.7) !important;
        font-size: 13px;
        margin-top: 4px;
        display: block;
    }
    .pg-modal--danger .pg-modal-icon {
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .pg-modal--danger .pg-modal-icon i {
        font-size: 18px;
        color: #fff;
    }
    .pg-modal--danger .modal-body {
        font-size: 14px;
        color: #334155;
    }

    /* ═══ Footer — semua tombol kanan, setema ═══ */
    .pg-modal-footer {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        padding: 14px 24px;
    }
    .pg-btn-cancel {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 9px 20px;
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
        background: #fff;
        min-width: 130px;
        height: 42px;
        line-height: 1;
    }
    .pg-btn-cancel:hover {
        background: #f1f5f9;
        color: #475569;
        border-color: #cbd5e1;
    }
    .pg-btn-save {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 9px 20px;
        font-size: 13px;
        font-weight: 600;
        min-width: 130px;
        height: 42px;
        line-height: 1;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }
    .pg-btn-save:hover {
        color: #fff;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
    }
    .pg-btn-decline {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        background: linear-gradient(135deg, #f87171, #ef4444);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 9px 20px;
        font-size: 13px;
        font-weight: 600;
        min-width: 130px;
        height: 42px;
        line-height: 1;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.28);
    }
    .pg-btn-decline:hover {
        color: #fff;
        background: linear-gradient(135deg, #ef4444, #dc2626);
        box-shadow: 0 4px 14px rgba(220, 38, 38, 0.32);
    }
    .pg-btn-decline:disabled,
    .pg-btn-decline.disabled {
        opacity: 0.55;
        pointer-events: none;
    }
    .pg-btn-accept,
    .pg-btn-confirm {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        background: linear-gradient(135deg, #059669, #16a34a);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 9px 20px;
        font-size: 13px;
        font-weight: 600;
        min-width: 130px;
        height: 42px;
        line-height: 1;
        box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
    }
    .pg-btn-accept:hover,
    .pg-btn-confirm:hover {
        color: #fff;
        background: linear-gradient(135deg, #047857, #15803d);
    }
    .pg-btn-confirm.btn-danger,
    .pg-btn-confirm--danger {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
    }
    .pg-btn-print {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        background: #fff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        padding: 9px 20px;
        font-size: 13px;
        font-weight: 600;
        min-width: 130px;
        height: 42px;
        line-height: 1;
    }
    .pg-btn-print:hover {
        color: #1e40af;
        background: #eff6ff;
        border-color: #93c5fd;
    }
    /* Secondary Print Button in Confirm (Green) Modals -> Soft Emerald Tint */
    .pg-modal--confirm .pg-btn-print {
        background: #ecfdf5;
        color: #065f46;
        border: 1px solid #a7f3d0;
        box-shadow: 0 1px 2px rgba(5, 150, 105, 0.06);
    }
    .pg-modal--confirm .pg-btn-print:hover {
        background: #d1fae5;
        border-color: #6ee7b7;
        color: #047857;
        box-shadow: 0 2px 6px rgba(5, 150, 105, 0.16);
        transform: translateY(-1px);
    }
    .pg-btn-confirm.btn-danger:hover,
    .pg-btn-confirm--danger:hover {
        color: #fff;
        background: linear-gradient(135deg, #dc2626, #b91c1c);
    }

    /* ═══ Modal body loading (view/edit fetch) ═══ */
    .pg-modal--form.is-loading .pg-modal-loading,
    .pg-modal--confirm.is-loading .pg-modal-loading {
        display: flex;
    }
    .pg-modal--form.is-loading .pg-modal-body-content,
    .pg-modal--form.is-loading .pg-modal-footer,
    .pg-modal--confirm.is-loading .pg-modal-body-content,
    .pg-modal--confirm.is-loading .pg-modal-footer {
        visibility: hidden;
        pointer-events: none !important;
    }
    .pg-modal--form.is-loading .pg-modal-footer .btn,
    .pg-modal--form.is-loading .btn-enable-edit-transfer,
    .pg-modal--confirm.is-loading .pg-modal-footer .btn,
    .pg-modal--confirm.is-loading .btn-enable-edit-transfer {
        pointer-events: none !important;
        opacity: 0.55;
    }
    .pg-modal-loading {
        display: none;
        position: absolute;
        inset: 0;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 12px;
        background: rgba(248, 250, 252, 0.96);
        z-index: 5;
        min-height: 240px;
    }
    .pg-modal-loading .spinner-border {
        width: 2rem;
        height: 2rem;
        border-width: 0.2em;
    }

    /* ═══ Delete action icon — selalu merah (termasuk di dalam tabel modal) ═══ */
    .table tbody td a.btn-action-icon.btn_delete,
    .table tbody td a.btn-action-icon.text-danger,
    .table tbody td a.btn-action-icon.csr-remove-line,
    .table tbody td a.btn-action-icon.cpr-remove-line {
        background: #fef2f2 !important;
        border: 1px solid #fecaca !important;
        color: #dc2626 !important;
        border-radius: 8px !important;
        width: 32px !important;
        height: 32px !important;
        padding: 0 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    .table tbody td a.btn-action-icon.btn_delete:hover,
    .table tbody td a.btn-action-icon.text-danger:hover,
    .table tbody td a.btn-action-icon.csr-remove-line:hover,
    .table tbody td a.btn-action-icon.cpr-remove-line:hover {
        color: #b91c1c !important;
        background: #fee2e2 !important;
        border-color: #fca5a5 !important;
    }

    /* ═══ Edit action icon — selalu kuning/amber, sama seperti pola delete di atas.
       GitHub #117: btn_edit dipakai lewat helper global roleIconEdit() jadi ini otomatis
       menyala di semua tabel master data (Bank, BOM/Resep Bahan Mentah, Warehouse, dst),
       bukan cuma satu halaman. ═══ */
    .table tbody td a.btn-action-icon.btn_edit,
    .table tbody td a.btn-action-icon.text-warning {
        background: #fffbeb !important;
        border: 1px solid #fde68a !important;
        color: #d97706 !important;
        border-radius: 8px !important;
        width: 32px !important;
        height: 32px !important;
        padding: 0 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    .table tbody td a.btn-action-icon.btn_edit:hover,
    .table tbody td a.btn-action-icon.text-warning:hover {
        color: #b45309 !important;
        background: #fef3c7 !important;
        border-color: #fcd34d !important;
    }

    /* ═══ PG Popup Table — tabel input di dalam modal (GitHub #111) ═══
       Pola standar: kartu input di ATAS, lalu tabel daftar item yang scroll
       sendiri setinggi PG_POPUP_TABLE.MAX_VISIBLE_ROWS baris.
       Konstanta + perhitungan tinggi ada di public/Custom_js/Shared/popup-table.js —
       nilai --pg-popup-table-max-height di bawah hanya fallback sebelum JS jalan. */
    .pg-popup-table-scroll {
        --pg-popup-table-max-height: 260px;
        max-height: var(--pg-popup-table-max-height);
        overflow-y: auto;
        overflow-x: auto;
    }
    /* Header & footer total tetap terlihat saat baris di-scroll. */
    .pg-popup-table-scroll > table > thead > tr > th {
        position: sticky;
        top: 0;
        z-index: 3;
        background: #f1f5f9;
    }
    .pg-popup-table-scroll > table > tfoot > tr > td {
        position: sticky;
        bottom: 0;
        z-index: 3;
        background: #f8fafc;
    }
    .pg-popup-table-scroll::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    .pg-popup-table-scroll::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 8px;
    }
    .pg-popup-table-scroll::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Kartu input: padding disamakan dengan sel tabel (12px 16px) + jarak ke tabel. */
    .pg-popup-table-input {
        padding: 12px 16px;
        margin-bottom: 16px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
    }

    /* Baris placeholder saat daftar masih kosong. */
    .pg-popup-table-empty td {
        padding: 24px 16px !important;
        text-align: center;
        color: #94a3b8;
        font-size: 13px;
    }

    /* ═══ SweetAlert2 PG Success Modal (.pg-swal-success) ═══ */
    .swal2-popup.pg-swal-success {
        border-radius: 16px !important;
        padding: 28px 24px 24px !important;
        box-shadow: 0 20px 45px rgba(0, 0, 0, 0.15) !important;
        border: none !important;
        max-width: 440px !important;
        background: #ffffff !important;
    }
    .pg-swal-success .swal2-icon {
        border: none !important;
        background: transparent !important;
        width: 80px !important;
        height: 80px !important;
        margin: 0.5rem auto 1rem !important;
        position: relative !important;
        overflow: visible !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    .pg-swal-success .swal2-icon .swal2-success-ring,
    .pg-swal-success .swal2-icon .swal2-success-line-tip,
    .pg-swal-success .swal2-icon .swal2-success-line-long,
    .pg-swal-success .swal2-icon .swal2-success-fix,
    .pg-swal-success .swal2-icon::before,
    .pg-swal-success .swal2-icon::after {
        display: none !important;
    }
    .pg-swal-success .swal2-title {
        padding: 0 10px !important;
        font-size: 20px !important;
        color: #0f172a !important;
    }
    .pg-swal-success .swal2-html-container {
        margin: 8px 12px 18px !important;
        color: #475569 !important;
    }
    .pg-swal-success .swal2-actions {
        margin-top: 12px !important;
        gap: 10px !important;
    }
    .pg-swal-success .pg-btn-confirm {
        min-width: 120px !important;
    }

    /* SVG Icon & Draw Animation */
    .pg-swal-success-anim {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        animation: pgSuccessScale 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
    }
    .pg-swal-svg-check {
        width: 72px;
        height: 72px;
        filter: drop-shadow(0 4px 10px rgba(22, 163, 74, 0.25));
    }
    .pg-swal-circle {
        stroke-dasharray: 202;
        stroke-dashoffset: 202;
        animation: pgCircleDraw 0.5s ease-out forwards;
    }
    .pg-swal-check {
        stroke-dasharray: 48;
        stroke-dashoffset: 48;
        animation: pgCheckDraw 0.4s 0.25s ease-out forwards;
    }

    @keyframes pgCircleDraw {
        to {
            stroke-dashoffset: 0;
        }
    }
    @keyframes pgCheckDraw {
        to {
            stroke-dashoffset: 0;
        }
    }
    @keyframes pgSuccessScale {
        0% {
            transform: scale(0.6);
            opacity: 0;
        }
        50% {
            transform: scale(1.08);
            opacity: 1;
        }
        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    /* Confetti Ringan CSS-only (8-12 partikel, hilang ~1.2s) */
    .pg-swal-confetti-wrap {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        pointer-events: none;
    }
    .pg-confetti {
        position: absolute;
        width: 6px;
        height: 6px;
        border-radius: 2px;
        opacity: 0;
        animation: pgConfettiBurst 1.1s cubic-bezier(0.25, 1, 0.5, 1) forwards;
    }
    .pg-confetti.p1  { background: #16a34a; --dx: -38px; --dy: -36px; animation-delay: 0.1s; }
    .pg-confetti.p2  { background: #3b82f6; --dx:  38px; --dy: -38px; animation-delay: 0.08s; width: 7px; height: 4px; }
    .pg-confetti.p3  { background: #f59e0b; --dx: -48px; --dy:   2px; animation-delay: 0.12s; }
    .pg-confetti.p4  { background: #10b981; --dx:  48px; --dy:   6px; animation-delay: 0.15s; }
    .pg-confetti.p5  { background: #22c55e; --dx: -32px; --dy:  40px; animation-delay: 0.09s; width: 5px; height: 8px; }
    .pg-confetti.p6  { background: #6366f1; --dx:  30px; --dy:  42px; animation-delay: 0.14s; }
    .pg-confetti.p7  { background: #ec4899; --dx: -16px; --dy: -48px; animation-delay: 0.11s; }
    .pg-confetti.p8  { background: #eab308; --dx:  18px; --dy: -50px; animation-delay: 0.13s; }
    .pg-confetti.p9  { background: #14b8a6; --dx: -42px; --dy: -18px; animation-delay: 0.07s; }
    .pg-confetti.p10 { background: #84cc16; --dx:  44px; --dy: -20px; animation-delay: 0.16s; }

    @keyframes pgConfettiBurst {
        0% {
            transform: translate(0, 0) scale(1) rotate(0deg);
            opacity: 1;
        }
        70% {
            opacity: 0.9;
        }
        100% {
            transform: translate(var(--dx), var(--dy)) scale(0.3) rotate(180deg);
            opacity: 0;
        }
    }
</style>
