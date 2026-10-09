# Pegasus Management — Project Map (AI Assistant)

Updated: 2026-10-04

## Stack
- Laravel 12, PHP 8.2, Blade + jQuery/DataTables (Kanakku), session auth (`Session::get('user')`)
- No Jobs/Events. No Laravel policies. Access via `check.access:Module|ability` + `RoleAccess`
- Soft delete = `status = 0` (or -1/3 depending on module). Super admin: `role_id === -1`

## Key file locations
- Routes: `routes/web.php`
- Controllers: `app/Http/Controllers/{Product,Stock,Production,Customer,Supplier,Report,User,Setting,AiChat}Controller.php`
- Models: `app/Models/*` (no $fillable, no relationships)
- Views: `resources/views/Backoffice/**`, layout `resources/views/layout/mainlayout.blade.php`
- Page JS: `public/Custom_js/Backoffice/**`
- Conventions: `.claude/skills/pegasus-conventions/SKILL.md`
- AI setup: `docs/AI_ASSISTANT.md`

## Modules (permission name → area)
| Module | Area | Main tables |
|--------|------|-------------|
| Kategori / Satuan / Variasi | Master | categories, units, variants |
| Daftar Produk / Stok Produk | Produk | products, product_variants, product_stocks |
| Daftar Bahan Mentah / Stok Bahan Mentah | Bahan | supplies, supplies_variants, supplies_stocks |
| Armada | Customer/armada | customers (treat same as customer) |
| Pemasok | Supplier | suppliers |
| Produk Bermasalah | Inventory | product_issues* |
| Peringatan Stok Produk/Bahan | Inventory | stock_alerts |
| Stok Opname Produk/Bahan | Inventory | stock_opnames*, stock_opname_bahans* (versi terbaru/lines) |
| Pengiriman | Sales Order + delivery | sales_orders, sales_delivery_orders* |
| Pembelian | Purchase Order | purchase_orders*, invoices, tts (delivery PO mati) |
| Tanda Terima PO | TT | purchase_order_tts |
| Resep Bahan Mentah | BOM | boms, bom_details (1 aktif/produk) |
| Produksi | Production | productions* |
| Kas | Kas besar | cashes, petty_cashes |
| Kas Operasional * | Petty per role | cash_admins/gudangs/armadas/sales |
| Kategori Kas / Bank Account | Master cash | cash_categories, banks |
| Hutang | Pantau hutang | purchase_orders.pembayaran |
| Pengelolaan Bahan Mentah | Mutasi lain | manage_stocks |
| Stock Transfer | Transfer antar gudang | stock_transfers*, kode ST… — docs gabungan: transfer-stok-dan-produk-bermasalah.md |
| Produk Bermasalah | Inventory | product_issues*, kode PI… — docs gabungan: transfer-stok-dan-produk-bermasalah.md |
| Gudang / Tipe Gudang | Master lokasi | warehouses, warehouse_types |
| Retur Produk | Return | return_supplies* / customer_*_returns |
| Laporan * | Reports | semua laporan aktif (incl. Laporan Stock Transfer) |
| Pengguna / Peran | Users | staffs, roles (1 role/orang) |

## Status cheat sheet (confirmed)
- **Produksi:** 1=Pending, 2=Berhasil, 3=Tolak, 4=Menunggu batal
- **SO:** 1=antrean, 2=ACC, 3=tolak/hapus (sengaja sama agar hapus tetap tampil)
- **PO status:** 1=Created, 2=ACC, 0=hapus, -1=tolak; **pembayaran:** 1=belum, 3=proses/TT, 2=lunas
- **Stock Opname:** is_draft + 1=Menunggu, 2=Disetujui, 3=Ditolak, 0=hapus; freeze → 1 orang input
- **Kas operasional:** 1=pending, 2=accept, 3=decline, 0=hapus; unggah bukti wajib
- **Product Issues:** 1=pending, 2=ACC (stok turun), 3=decline/hapus
- **Stock Transfer:** 0=hapus, 1=Pending, 2=Kirim, 3=Batal, 4=Terkirim, 5=Batal Kirim

## AI assistant
- Docs: `storage/ai_docs/*.md` (no `[UNCERTAIN]` left for indexing)
- Index: `php artisan ai:index-docs` → `ai_doc_chunks`
- Chat: `POST /ai/chat`, widget in `components/ai-chat-widget.blade.php`
- Services: `app/Services/AiAssistant/*` (Vocabulary, AnswerSanitizer, DocRetriever, tools)
- Config: `config/ai_assistant.php` (whitelist + RAG), `config/ai_vocabulary.php` (UI labels/modules)
- Tools: `find_document`, `search_records`, `summarize_records`, `find_duplicates`, `record_history`
- Connection: `ai_readonly` (SELECT-only)
- Tests: `tests/Unit/AiAssistantToolGuardTest.php`, `tests/Feature/AiAssistantReadonlyToolsTest.php`, `tests/Unit/TextToolCallParserTest.php`
