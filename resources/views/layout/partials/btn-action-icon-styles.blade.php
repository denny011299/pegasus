{{-- Shared action icon styles (mainlayout header + fullscreen layout). --}}
.btn-action-icon {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 34px !important;
    height: 34px !important;
    min-width: 34px !important;
    max-width: 34px !important;
    padding: 0 !important;
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 8px !important;
    font-size: 14px !important;
    line-height: 1 !important;
    color: #64748b !important;
    transition: all 0.18s ease !important;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04) !important;
    margin: 0 2px;
    cursor: pointer;
    box-sizing: border-box !important;
}
.btn-action-icon:hover {
    color: #2563eb !important;
    background: #eff6ff !important;
    border-color: #bfdbfe !important;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.15) !important;
    transform: translateY(-1px) !important;
}
/* DELETE — always red */
.btn-action-icon.btn_delete,
.btn-action-icon.text-danger {
    color: #ef4444 !important;
}
.btn-action-icon.btn_delete:hover,
.btn-action-icon.text-danger:hover,
.btn-action-icon.btn-danger-soft:hover {
    color: #dc2626 !important;
    background: #fef2f2 !important;
    border-color: #fca5a5 !important;
    box-shadow: 0 2px 8px rgba(220, 38, 38, 0.15) !important;
    transform: translateY(-1px) !important;
}
/* STATUS NON-AKTIF — orange/warning */
.btn-action-icon.text-warning {
    color: #f59e0b !important;
}
.btn-action-icon.text-warning:hover {
    color: #d97706 !important;
    background: #fffbeb !important;
    border-color: #fcd34d !important;
    box-shadow: 0 2px 8px rgba(217, 119, 6, 0.15) !important;
    transform: translateY(-1px) !important;
}
/* STATUS AKTIFKAN — green */
.btn-action-icon.text-success {
    color: #22c55e !important;
}
.btn-action-icon.text-success:hover {
    color: #16a34a !important;
    background: #f0fdf4 !important;
    border-color: #86efac !important;
    box-shadow: 0 2px 8px rgba(22, 163, 74, 0.15) !important;
    transform: translateY(-1px) !important;
}
/* ACC / TERIMA (approve) */
.btn-action-icon.btn-action-approve {
    background: #ecfdf5 !important;
    border: 1px solid #a7f3d0 !important;
    color: #059669 !important;
    font-weight: 700 !important;
}
.btn-action-icon.btn-action-approve:hover {
    background: #d1fae5 !important;
    border-color: #6ee7b7 !important;
    color: #047857 !important;
    box-shadow: 0 2px 8px rgba(5, 150, 105, 0.15) !important;
    transform: translateY(-1px) !important;
}
/* TOLAK (reject) */
.btn-action-icon.btn-action-reject {
    background: #fef2f2 !important;
    border: 1px solid #fecaca !important;
    color: #dc2626 !important;
    font-weight: 700 !important;
}
.btn-action-icon.btn-action-reject:hover {
    background: #fee2e2 !important;
    border-color: #fca5a5 !important;
    color: #b91c1c !important;
    box-shadow: 0 2px 8px rgba(220, 38, 38, 0.15) !important;
    transform: translateY(-1px) !important;
}
/* VIEW */
.btn-action-icon.btn-action-view {
    background: #eff6ff !important;
    border: 1px solid #bfdbfe !important;
    color: #2563eb !important;
    font-weight: 700 !important;
}
.btn-action-icon.btn-action-view:hover {
    background: #dbeafe !important;
    border-color: #93c5fd !important;
    color: #1d4ed8 !important;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.15) !important;
    transform: translateY(-1px) !important;
}
/* EDIT */
.btn-action-icon.btn-action-edit {
    background: #fffbeb !important;
    border: 1px solid #fde68a !important;
    color: #d97706 !important;
    font-weight: 700 !important;
}
.btn-action-icon.btn-action-edit:hover {
    background: #fef3c7 !important;
    border-color: #fcd34d !important;
    color: #b45309 !important;
    box-shadow: 0 2px 8px rgba(217, 119, 6, 0.15) !important;
    transform: translateY(-1px) !important;
}
/* DELETE (permanent tint) */
.btn-action-icon.btn-action-delete {
    background: #fef2f2 !important;
    border: 1px solid #fecaca !important;
    color: #dc2626 !important;
    font-weight: 700 !important;
}
.btn-action-icon.btn-action-delete:hover {
    background: #fee2e2 !important;
    border-color: #fca5a5 !important;
    color: #b91c1c !important;
    box-shadow: 0 2px 8px rgba(220, 38, 38, 0.15) !important;
    transform: translateY(-1px) !important;
}
