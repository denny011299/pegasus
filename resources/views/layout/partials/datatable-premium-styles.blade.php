{{-- Shared DataTable / card-table glass styles (mainlayout header + fullscreen). --}}
/* ============================================================
   GLOBAL DATATABLE LOADING (PREMIUM SPINNER & OVERLAY)
   ============================================================ */
div.dataTables_wrapper div.dataTables_processing {
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100% !important;
    height: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    border: 0 !important;
    background: rgba(255, 255, 255, 0.65) !important;
    backdrop-filter: blur(3px) !important;
    -webkit-backdrop-filter: blur(3px) !important;
    box-shadow: none !important;
    z-index: 50 !important;
    transform: none !important;
}
div.dataTables_wrapper div.dataTables_processing:not([style*="display: none"]) {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.dt-premium-spinner {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    background: rgba(255, 255, 255, 0.95);
    padding: 20px 32px;
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(37, 99, 235, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.9);
}

.dt-spinner-circle {
    width: 40px;
    height: 40px;
    border: 4px solid #e0e7ff;
    border-top: 4px solid #3b82f6;
    border-right: 4px solid #3b82f6;
    border-radius: 50%;
    animation: dt-spin 0.8s ease-in-out infinite;
}

.dt-spinner-text {
    font-size: 14px;
    font-weight: 600;
    color: #334155;
    letter-spacing: 0.5px;
    animation: dt-pulse 1.5s ease-in-out infinite;
}

@keyframes dt-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@keyframes dt-pulse {
    0% { opacity: 0.6; }
    50% { opacity: 1; }
    100% { opacity: 0.6; }
}

/* ============================================================
   GLASSMORPHISM DATATABLE — PREMIUM DESIGN
   ============================================================ */

/* Page background - clean gray */
.page-wrapper {
    background: #f1f5f9 !important;
    min-height: 100vh;
}
.content.container-fluid {
    padding: 24px 28px;
}

/* Glass Card Container */
.card-table {
    background: rgba(255, 255, 255, 0.55) !important;
    backdrop-filter: blur(20px) !important;
    -webkit-backdrop-filter: blur(20px) !important;
    border: 1px solid rgba(255, 255, 255, 0.7) !important;
    border-radius: 18px !important;
    box-shadow:
        0 8px 32px rgba(37, 99, 235, 0.08),
        0 2px 8px rgba(0, 0, 0, 0.04),
        inset 0 1px 0 rgba(255,255,255,0.9) !important;
    overflow: hidden;
}

/* Base Table Reset */
table.dataTable {
    border-collapse: collapse !important;
    width: 100% !important;
    margin: 0 !important;
    border-bottom: none !important;
}

/* =============================================
   DATATABLE — PREMIUM REDESIGN
   ============================================= */

/* 1. Base Reset */
table.dataTable {
    border-collapse: collapse !important;
    width: 100% !important;
    margin: 0 !important;
    border-bottom: none !important;
}

/* 2. Wrapper */
.dataTables_wrapper {
    font-size: 13px;
    color: #475569;
    width: 100%;
    position: relative;
}
.dataTables_wrapper::after {
    content: "";
    display: table;
    clear: both;
}

/* 3. Top Bar (Search & Length) */
.dataTables_filter {
    float: left;
    padding: 16px 20px;
}
.dataTables_length {
    float: left;
    padding: 16px 20px;
}
.dataTables_length select {
    border: 1px solid #e2e8f0 !important;
    border-radius: 8px !important;
    padding: 5px 10px !important;
    font-size: 13px !important;
    color: #334155 !important;
    background: #ffffff !important;
    outline: none !important;
    cursor: pointer;
    box-shadow: 0 1px 2px rgba(0,0,0,0.02);
}

/* Search box — clean pill */
.dataTables_filter label {
    display: flex !important;
    align-items: center !important;
    position: relative !important;
    margin-bottom: 0 !important;
}
.dataTables_filter label::before {
    content: '\f002';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    position: absolute;
    left: 14px;
    color: #94a3b8;
    font-size: 13px;
    z-index: 1;
    pointer-events: none;
}
.dataTables_filter label > span:first-child,
.dataTables_filter label > *:not(input) {
    display: none !important;
}
.dataTables_filter input[type="search"] {
    border: 1px solid #e2e8f0 !important;
    border-radius: 30px !important;
    background: #f8fafc !important;
    padding: 9px 18px 9px 40px !important;
    font-size: 13px !important;
    width: 260px !important;
    color: #1e293b !important;
    outline: none !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.02) !important;
}
.dataTables_filter input[type="search"]:focus {
    background: #ffffff !important;
    border-color: #3b82f6 !important;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.12) !important;
    width: 320px !important;
}

/* 4. Fix Scroll Gap safely (Do NOT touch table margin-top) */
.dataTables_scrollHead {
    border-bottom: 2px solid #bfdbfe !important;
    background: linear-gradient(90deg, #eff6ff 0%, #e0f2fe 100%) !important;
    border-radius: 0 !important;
}
.dataTables_scrollBody {
    border-bottom: 1px solid #e2e8f0 !important;
}
div.dataTables_scrollBody thead,
div.dataTables_scrollBody table.dataTable thead tr,
div.dataTables_scrollBody table.dataTable thead th,
div.dataTables_scrollBody table.dataTable thead td {
    height: 0 !important;
    padding: 0 !important;
    margin: 0 !important;
    border: none !important;
    font-size: 0 !important;
    line-height: 0 !important;
    visibility: hidden !important;
    background: transparent !important;
}

/* 5. Header Styling */
table.dataTable thead th {
    background: transparent !important;
    color: #1e40af !important;
    font-size: 11.5px !important;
    font-weight: 800 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.08em !important;
    border-bottom: none !important;
    border-top: none !important;
    padding: 16px 24px !important;
    vertical-align: middle !important;
    white-space: nowrap;
    text-shadow: 0 1px 1px rgba(255,255,255,0.7);
    border-radius: 0 !important;
}
table.dataTable thead th:first-child,
table.dataTable thead th:last-child {
    border-radius: 0 !important;
}
table.dataTable thead th:first-child,
table.dataTable tbody td:first-child {
    padding-left: 32px !important;
}
table.dataTable thead th:last-child,
table.dataTable tbody td:last-child {
    padding-right: 32px !important;
}

/* 6. Body Styling */
table.dataTable tbody td {
    background: #ffffff !important;
    color: #334155 !important;
    font-size: 13.5px;
    padding: 14px 24px !important;
    vertical-align: middle !important;
    border-top: none !important;
    border-bottom: 1px solid #f1f5f9 !important;
    transition: background 0.15s ease;
}
table.dataTable tbody tr:hover td {
    background: #f8fafc !important; /* Soft hover effect */
}
table.dataTable tbody tr:last-child td {
    border-bottom: none !important;
}

/* 7. Footer (Pagination & Info) */
/* To fix the "Show 10 entries" overlapping with pagination if they are in the same row,
   we just let them float normally with clear: both */
.dataTables_info {
    float: left;
    padding: 18px 20px !important;
    color: #64748b !important;
    font-size: 13px !important;
}
.dataTables_paginate {
    float: right;
    padding: 14px 20px !important;
}

/* Pagination buttons */
.dataTables_wrapper .dataTables_paginate .paginate_button {
    border-radius: 6px !important;
    padding: 6px 12px !important;
    margin: 0 2px !important;
    border: none !important;
    background: transparent !important;
    color: #475569 !important;
    font-weight: 500;
    font-size: 13px;
    transition: all 0.15s ease;
    cursor: pointer;
}
.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background: #f1f5f9 !important;
    border-color: #cbd5e1 !important;
    color: #1e293b !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button.current,
.dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
    background: #3b82f6 !important;
    color: #ffffff !important;
    border-color: #3b82f6 !important;
    box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2) !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
.dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
    color: #94a3b8 !important;
    background: #f8fafc !important;
    border-color: #e2e8f0 !important;
    box-shadow: none !important;
    cursor: not-allowed;
}
