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
</style>
