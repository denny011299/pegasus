<?php

namespace App\Services\AiAssistant;

use App\Services\AiAssistant\Tools\DataAnomaliesTool;

/**
 * Dokumentasi Asisten AI.
 * - sections(): untuk USER (Pengaturan → Dokumentasi AI): kemampuan + contoh, FAQ, menu yang bisa diakses AI.
 * - developerMarkdown(): untuk DEVELOPER (`php artisan ai:dev-docs` → docs/AI_ASSISTANT_TOOLS.md):
 *   nama tool, parameter, bobot penyebab, sinyal. Jangan tampilkan ini di web.
 * Daftar tool/menu/penyebab dibaca dari kode/config supaya tidak tertinggal dari sistem.
 */
class AssistantGuide
{
    /** tool => [judul, kegunaan, contoh pertanyaan, catatan untuk user]. */
    private const TOOLS = [
        'find_document' => ['Cari Dokumen', 'Buka satu dokumen dari kodenya: isi, daftar barang, dan pergerakan stoknya.', ['isi INV1075 apa?', 'PO0567 siapa yang ACC?'], 'Kode salah ketik tetap dibantu, mis. PO567 → ditawarkan PO0567.'],
        'search_records' => ['Cari / Filter Data', 'Tampilkan daftar data per menu dengan filter tanggal, status, nama, atau kata kunci.', ['5 pembelian terakhir dari DJS', 'kas besar keluar minggu ini'], 'Kalau kosong, AI menawarkan nama/kode yang mirip.'],
        'summarize_records' => ['Rekap & Total', 'Jumlah, total nilai, rata-rata, rekap per bulan atau per status.', ['total pembelian bulan ini', 'jumlah pengiriman per status'], 'Data dihapus/ditolak/batal tidak dihitung kecuali diminta.'],
        'find_duplicates' => ['Cek Data Dobel', 'Mencari kemungkinan input ganda.', ['ada kas yang dobel?'], 'Disertai pola (orang, jarak waktu, nilai) dan perkiraan penyebab.'],
        'record_history' => ['Riwayat Dokumen', 'Siapa yang membuat, mengubah, dan ACC satu dokumen, beserta waktunya.', ['riwayat SO1075'], ''],
        'find_item' => ['Cari Barang', 'Cari produk/bahan dari nama, SKU, barcode, atau ketikan yang kurang tepat.', ['air aki pegasus 1 liter', 'SKU Aap1000ml'], 'Kalau tidak persis, AI memberi pilihan barang yang mirip.'],
        'item_activity' => ['Jejak Barang per Menu', 'Satu barang di pengiriman, transfer, opname, pembelian, produksi, dan retur.', ['berapa transfer stok Aap1000ml hari ini', 'pengiriman H2SO4 bulan ini'], 'Jumlah dokumen, total qty per satuan, dan daftar dokumennya.'],
        'dashboard_summary' => ['Angka Dashboard', 'Angka yang sama dengan halaman Dashboard (penjualan, persediaan, top produk, hutang, antrian ACC).', ['ringkasan dashboard bulan ini'], 'Angkanya sama persis dengan Dashboard.'],
        'item_stock' => ['Kartu Stok', 'Stok sekarang per gudang, rekap barang masuk/keluar, dan mutasi terakhir.', ['stok H2SO4 berapa?', 'kenapa stok jerigen 5 liter berkurang'], 'Default 30 hari terakhir; sebutkan periode lain kalau perlu.'],
        'data_anomalies' => ['Cek Kejanggalan', 'Stok minus, dokumen lama belum di-ACC, transfer belum diterima, selisih opname, hutang jatuh tempo, PO harga Rp 0.', ['ada yang janggal hari ini?', 'apa yang belum di-ACC?'], 'Setiap temuan disertai perkiraan penyebab dalam persen. Lihat FAQ.'],
        'staff_activity' => ['Aktivitas Staf', 'Apa saja yang dikerjakan staf, atau siapa staf paling aktif.', ['Ika ngapain aja minggu ini', 'siapa paling aktif hari ini'], ''],
        'trace_document' => ['Lacak Alur Dokumen', 'Posisi dokumen dalam alurnya: SO → invoice → surat jalan → retur, PO → tanda terima → penerimaan → invoice, produksi → transfer.', ['PO0567 sudah sampai mana?', 'ST-PR0463-2 dari produksi mana'], 'Tahap yang belum dibuat ikut ditandai.'],
        'period_report' => ['Laporan Periode', 'Rekap semua menu per periode, dibanding periode sebelumnya.', ['laporan bulan ini vs bulan lalu', 'rekap minggu ini'], ''],
        'partner_insight' => ['Customer & Pemasok', 'Customer/pemasok teratas, yang lama tidak order, lama pengiriman pemasok, hutang; profil satu partner.', ['customer yang sudah lama tidak order', 'profil pemasok ASTERINDO'], ''],
        'reorder_suggestion' => ['Saran Beli Bahan', 'Pemakaian bahan per hari, stok cukup berapa hari, dan saran jumlah beli.', ['bahan apa yang harus dibeli?', 'jerigen 5 liter cukup berapa hari'], 'Hanya saran; cek PO yang masih berjalan sebelum order.'],
    ];

    /** Jenis kejanggalan → [pertanyaan FAQ, yang ditampilkan AI]. */
    private const ANOMALY_FAQ = [
        'stok_minus' => ['Kenapa stok barang bisa minus?', 'AI menampilkan barang dan gudang yang stoknya minus.'],
        'pending_lama' => ['Ada dokumen yang lama belum di-ACC, kenapa?', 'AI menampilkan dokumen yang masih menunggu ACC beserta umurnya.'],
        'transfer_belum_diterima' => ['Transfer stok belum diterima gudang tujuan, kenapa?', 'AI menampilkan transfer yang belum diklik Terima dan sudah berapa hari.'],
        'selisih_opname' => ['Hasil stok opname selisih jauh dengan sistem, kenapa?', 'AI menampilkan barang dengan selisih besar (fisik vs sistem).'],
        'hutang_jatuh_tempo' => ['Ada hutang lewat jatuh tempo, kenapa?', 'AI menampilkan invoice pembelian yang belum lunas dan lewat jatuh tempo.'],
        'harga_nol' => ['Kenapa total PO / invoice / hutang Rp 0?', 'AI memberi peringatan di awal jawaban: harga beli di PO masih 0, sehingga invoice dan hutang ikut Rp 0. Perbaiki harga di PO sebelum membuat Tanda Terima.'],
    ];

    /** Pertanyaan umum (tanya → jawab) untuk user. */
    private const FAQ = [
        ['Apakah harus pakai kata kunci / perintah khusus?', 'Tidak. Tanya dengan bahasa sehari-hari, mis. "PO0567 sudah sampai mana?" atau "stok H2SO4 berapa?". AI memilih sendiri data yang perlu dicek.'],
        ['Bagaimana minta panduan cara input?', 'Tanya "cara input PO" / "gimana bikin transfer stok". AI menjawab langkah bernomor sesuai menu, 1 tips agar tidak salah input, lalu bertanya "Sudah sampai mana?". Setelah selesai input, minta AI mengecek hasilnya.'],
        ['Kode atau nama yang saya ketik tidak ketemu?', 'AI tidak berhenti di "tidak ditemukan", tapi menawarkan "Apakah maksudnya…?" berisi kode dokumen, barang, staf, customer, atau pemasok yang mirip. Pilih salah satu.'],
        ['Bagaimana mengecek kejanggalan data?', 'Tanya "ada yang janggal hari ini?" atau sebut masalahnya langsung, mis. "kenapa stok jerigen minus?".'],
        ['Apa arti persentase penyebab di jawaban AI?', 'Itu perkiraan dari pola data, bukan kepastian. Totalnya 100% dan penyebabnya selalu operasional (salah input, salah pilih gudang/satuan, lupa ACC/Terima, koneksi lambat). Cek dokumen terkait untuk memastikan.'],
        ['Bisa minta penjelasan kenapa penyebabnya begitu?', 'Bisa. Balas "detailnya" atau "kenapa salah satuan?". AI baru menjelaskan alasan tiap penyebab saat diminta.'],
        ['Bisakah AI mengubah, menghapus, atau ACC data?', 'Tidak. AI hanya membaca data. AI akan mengarahkan ke menu yang tepat untuk melakukannya.'],
        ['Kenapa AI tidak menampilkan alamat, telepon, rekening, atau saldo?', 'Data tersebut sengaja dirahasiakan dari AI.'],
        ['Bisa kirim gambar / screenshot?', 'Bisa. Lampirkan gambar di chat (mis. screenshot error atau foto dokumen), lalu tulis pertanyaannya.'],
        ['Apakah chat sebelumnya tersimpan?', 'Ya, riwayat chat tersimpan per sesi login. AI mengingat beberapa pesan terakhir dalam percakapan.'],
    ];

    /**
     * Menu website → [modul data yang dibaca AI, file panduan input, catatan].
     * Modul/panduan yang belum terpetakan otomatis ditambahkan di akhir.
     */
    private const MENUS = [
        'Dashboard' => [[], null, 'Angka KPI, persediaan, top produk, hutang, antrian ACC'],
        'Pengiriman (Sales Order)' => [['pengiriman', 'surat jalan', 'invoice penjualan'], 'pengiriman-sales-order.md', ''],
        'Pembelian (Purchase Order)' => [['pembelian', 'penerimaan pembelian'], 'pembelian-po.md', ''],
        'Tanda Terima PO' => [['tanda terima'], 'tanda-terima-po.md', ''],
        'Hutang' => [['invoice pembelian'], 'hutang.md', ''],
        'Produksi' => [['produksi'], 'produksi.md', ''],
        'Resep Bahan Mentah (BOM)' => [['resep'], 'resep-bom.md', ''],
        'Stok Produk' => [['stok produk', 'riwayat stok'], 'stok-produk.md', ''],
        'Stok Bahan Mentah' => [['stok bahan'], 'stok-bahan-mentah.md', ''],
        'Pengelolaan Bahan Mentah' => [['pengelolaan bahan'], 'pengelolaan-bahan.md', ''],
        'Peringatan Stok' => [[], 'peringatan-stok.md', 'Dibaca lewat Kartu Stok / Saran Beli Bahan'],
        'Stok Opname Produk' => [['opname produk'], 'stok-opname-produk.md', ''],
        'Stok Opname Bahan Mentah' => [['opname bahan'], 'stok-opname-bahan.md', ''],
        'Transfer Stok & Produk Bermasalah' => [['transfer stok', 'produk bermasalah'], 'transfer-stok-dan-produk-bermasalah.md', ''],
        'Retur' => [['retur ke pemasok', 'retur produk dari customer', 'retur bahan dari customer'], 'retur-produk.md', ''],
        'Kas Besar & Petty Cash' => [['kas umum', 'petty cash'], 'kas-umum.md', ''],
        'Kas Operasional' => [['kas admin', 'kas gudang', 'kas armada', 'kas sales'], 'kas-operasional.md', ''],
        'Kategori Kas' => [['kategori kas'], 'kategori-kas.md', ''],
        'Bank Account' => [['bank'], 'bank-account.md', 'Nomor rekening tidak ditampilkan'],
        'Daftar Produk' => [['produk', 'varian produk', 'konversi satuan produk'], 'daftar-produk.md', ''],
        'Daftar Bahan Mentah' => [['bahan', 'varian bahan', 'konversi satuan bahan'], 'daftar-bahan-mentah.md', ''],
        'Kategori' => [['kategori'], 'master-kategori.md', ''],
        'Satuan' => [['satuan'], 'master-satuan.md', ''],
        'Variasi' => [['variasi'], 'master-variasi.md', ''],
        'Customer' => [['customer'], null, 'Alamat, telepon, dan saldo tidak ditampilkan'],
        'Pemasok' => [['pemasok'], 'pemasok.md', 'Alamat, telepon, dan rekening tidak ditampilkan'],
        'Gudang' => [['gudang', 'tipe gudang'], 'gudang.md', ''],
        'Area' => [['area'], null, ''],
        'Armada' => [[], 'armada.md', ''],
        'Pengguna & Peran' => [['pengguna', 'peran', 'penempatan pengguna', 'aktivitas'], 'pengguna-peran.md', ''],
        'Laporan' => [[], 'laporan.md', 'Rekap lewat Laporan Periode'],
        'Profil & Pengaturan' => [[], 'profil-pengaturan.md', ''],
    ];

    /** Developer only: sinyal data yang menggeser persentase (ringkasan DataAnomaliesTool/CauseEstimates). */
    private const SIGNALS = [
        'stok_minus' => ['Minus kecil (≤ 5) → "Urutan input" naik.', 'Ada 2+ log keluar dengan kode & qty sama dalam 90 hari → "Input dobel" naik.'],
        'pending_lama' => ['Dokumen > 30 hari → "Sebenarnya batal tapi tidak dihapus" naik.', 'Dokumen ≤ 30 hari → "Lupa / belum sempat di-ACC" naik.'],
        'transfer_belum_diterima' => ['Semua ≤ 3 hari → "Barang masih di perjalanan" naik.', 'Ada yang > 3 hari → "Gudang tujuan lupa klik Terima" naik.'],
        'selisih_opname' => ['Rasio fisik/sistem tepat 10/12/24/100/1000 (±1%) → "Salah satuan".', 'Rasio ≥ 10 lainnya → "Salah hitung / salah ketik".', 'Fisik 0, sistem > 0 → "Salah pilih gudang" & "Transaksi belum diinput" naik.'],
        'hutang_jatuh_tempo' => ['Telat > 60 hari → "Sudah dibayar tapi belum ditandai lunas" naik.', 'Invoice Rp 0 → peringatan harga PO belum diisi.'],
        'harga_nol' => ['Satu pemasok ≥ 50% dari PO Rp 0 → "Harga menunggu konfirmasi pemasok" naik.'],
    ];

    public function __construct(private ToolRegistry $registry, private Vocabulary $vocab) {}

    /** Isi halaman web untuk user — tanpa nama tool, parameter, atau bobot internal. */
    public function sections(): array
    {
        return [
            'ringkasan' => [
                'nama' => 'Asisten Pegasus (AI Chat)',
                'tujuan' => 'Memandu cara input data di website, mencari dan merekap data, melacak alur dokumen, serta menjelaskan kejanggalan data.',
                'cara_pakai' => 'Klik tombol chat di pojok kanan bawah, lalu tanya dengan bahasa sehari-hari. Tidak perlu kata kunci khusus.',
                'akses' => 'Butuh hak akses "Asisten AI" di Peran & Perizinan.',
            ],
            'kemampuan' => $this->capabilities(),
            'faq' => $this->faq(),
            'menu' => $this->menus(),
        ];
    }

    /** @return list<array{tool: string, judul: string, kegunaan: string, contoh: list<string>, catatan: string}> */
    private function capabilities(): array
    {
        $out = [];
        foreach ($this->registry->names() as $name) {
            [$title, $use, $examples, $note] = self::TOOLS[$name] ?? [$name, '', [], ''];
            // 'tool' hanya untuk test cakupan; view tidak menampilkannya.
            $out[] = ['tool' => $name, 'judul' => $title, 'kegunaan' => $use, 'contoh' => $examples, 'catatan' => $note];
        }

        return $out;
    }

    /** @return list<array{tanya: string, jawab: string, contoh_jawaban?: string}> */
    private function faq(): array
    {
        $out = array_map(fn ($f) => ['tanya' => $f[0], 'jawab' => $f[1]], self::FAQ);

        foreach (DataAnomaliesTool::causeCatalog() as $type => $causes) {
            [$question, $shows] = self::ANOMALY_FAQ[$type] ?? [null, ''];
            if ($question === null) {
                continue;
            }
            $lines = array_map(fn ($c) => '• '.$c['penyebab'].' ±'.$c['bobot_dasar'].'%', $causes);
            $out[] = [
                'tanya' => $question,
                'jawab' => $shows.' Lalu perkiraan penyebab dalam persen (angka berubah mengikuti data). Balas "detailnya" untuk penjelasan tiap penyebab.',
                'contoh_jawaban' => "Kemungkinan penyebab:\n".implode("\n", $lines),
            ];
        }

        return $out;
    }

    /** @return list<array{menu: string, data: list<string>, panduan: list<string>, catatan: string}> */
    private function menus(): array
    {
        $labels = $this->vocab->moduleCatalog();
        $guides = $this->guideDocs();
        $usedModules = [];
        $usedGuides = [];
        $out = [];

        foreach (self::MENUS as $menu => [$modules, $file, $note]) {
            $modules = array_values(array_filter($modules, fn ($m) => isset($labels[$m])));
            $usedModules = array_merge($usedModules, $modules);
            if ($file !== null) {
                $usedGuides[] = $file;
            }
            $out[] = [
                'menu' => $menu,
                'data' => array_map(fn ($m) => $labels[$m], $modules),
                'panduan' => $guides[$file]['bagian'] ?? [],
                'catatan' => $note,
            ];
        }

        foreach (array_diff(array_keys($labels), $usedModules) as $m) {
            $out[] = ['menu' => $labels[$m], 'data' => [$labels[$m]], 'panduan' => [], 'catatan' => ''];
        }
        foreach (array_diff(array_keys($guides), $usedGuides) as $file) {
            $out[] = ['menu' => $guides[$file]['judul'], 'data' => [], 'panduan' => $guides[$file]['bagian'], 'catatan' => ''];
        }

        return $out;
    }

    /**
     * Panduan per menu yang dipakai AI (storage/ai_docs). File "_" (catatan developer)
     * dan yang masih [UNCERTAIN] tidak diindeks, jadi tidak dihitung.
     *
     * @return array<string, array{judul: string, bagian: list<string>}>
     */
    private function guideDocs(): array
    {
        $dir = config('ai_assistant.docs_path', storage_path('ai_docs'));
        $out = [];
        foreach (glob($dir.'/*.md') ?: [] as $file) {
            $name = basename($file);
            $text = (string) file_get_contents($file);
            if (str_starts_with($name, '_') || str_contains($text, '[UNCERTAIN')) {
                continue;
            }
            preg_match('/^#\s+(.+)$/m', $text, $h1);
            preg_match_all('/^##\s+(.+)$/m', $text, $h2);
            $out[$name] = [
                'judul' => trim($h1[1] ?? pathinfo($name, PATHINFO_FILENAME)),
                'bagian' => array_map('trim', $h2[1] ?? []),
            ];
        }

        return $out;
    }

    /** Referensi developer (tool + parameter + bobot penyebab + sinyal) dalam Markdown. */
    public function developerMarkdown(): string
    {
        $md = "# Asisten AI — Referensi Developer\n\n";
        $md .= "> File ini di-generate oleh `php artisan ai:dev-docs` dari `App\\Services\\AiAssistant\\AssistantGuide`. Jangan edit manual.\n";
        $md .= "> Halaman user: Pengaturan → Dokumentasi AI (`/settings/ai-docs`).\n\n";
        $md .= "- Model teks: `".config('ai_assistant.openrouter.model')."` · Model gambar: `".config('ai_assistant.vision.model')."`\n";
        $md .= '- Baris data ke model per pencarian: '.(int) config('ai_assistant.rows_to_model').' · Konteks: '.(int) config('ai_assistant.context_messages')." pesan terakhir\n\n";

        $md .= "## Tools (semua read-only)\n\n";
        foreach ($this->registry->openAiTools() as $t) {
            $fn = $t['function'];
            $title = self::TOOLS[$fn['name']][0] ?? $fn['name'];
            $md .= "### `{$fn['name']}` — {$title}\n\n".trim((string) $fn['description'])."\n\n";
            $props = $fn['parameters']['properties'] ?? [];
            if ($props === []) {
                $md .= "_Tanpa parameter._\n\n";
                continue;
            }
            $md .= "| Parameter | Wajib | Keterangan |\n|---|---|---|\n";
            foreach ($props as $key => $p) {
                $desc = trim(($p['description'] ?? '').(isset($p['enum']) ? ' (pilihan: '.implode(', ', $p['enum']).')' : ''));
                $required = in_array($key, $fn['parameters']['required'] ?? [], true) ? 'ya' : '';
                $md .= "| `{$key}` | {$required} | ".str_replace(['|', "\n"], ['\\|', ' '], $desc)." |\n";
            }
            $md .= "\n";
        }

        $md .= "## Perkiraan penyebab (`Tools/Concerns/CauseEstimates.php`)\n\n";
        $md .= "Bobot dasar per jenis, digeser sinyal data; total selalu 100%. Penyebab hanya operasional, tidak pernah \"bug sistem\".\n\n";
        foreach (DataAnomaliesTool::causeCatalog() as $type => $causes) {
            $md .= "### `{$type}`\n\n| Penyebab | Bobot dasar | Alasan (dijelaskan bila user minta) |\n|---|---|---|\n";
            foreach ($causes as $c) {
                $md .= "| {$c['penyebab']} | {$c['bobot_dasar']}% | {$c['alasan']} |\n";
            }
            foreach (self::SIGNALS[$type] ?? [] as $s) {
                $md .= ($s === (self::SIGNALS[$type][0] ?? null) ? "\nSinyal:\n\n" : '')."- {$s}\n";
            }
            $md .= "\n";
        }

        $md .= "## Modul data (`config/ai_vocabulary.php`)\n\n";
        foreach ($this->vocab->moduleCatalog() as $key => $label) {
            $md .= "- `{$key}` — {$label}\n";
        }

        return $md;
    }
}
