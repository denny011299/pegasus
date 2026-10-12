<?php

namespace App\Services\AiAssistant;

use App\Services\AiAssistant\Tools\DataAnomaliesTool;

/**
 * Data lengkap halaman dokumentasi Asisten AI (Pengaturan → Dokumentasi AI).
 * Daftar tool, parameter, modul, panduan menu, dan katalog penyebab dibaca
 * langsung dari kode/config supaya dokumentasi tidak tertinggal dari sistem.
 * Teks di TOOLS (judul, kegunaan, contoh) ditulis manual untuk user.
 */
class AssistantGuide
{
    /** name => [judul, kegunaan untuk user, contoh pertanyaan, catatan hasil]. */
    private const TOOLS = [
        'find_document' => ['Cari Dokumen', 'Buka satu dokumen dari kodenya: isi header, daftar barang, dan pergerakan stok.', ['isi INV1075 apa?', 'PO0567 siapa yang ACC?'], 'Kode salah ketik → saran kode mirip (PO567 → PO0567). PO harga Rp 0 → peringatan.'],
        'search_records' => ['Cari / Filter Data', 'Daftar data per modul dengan filter, urutan, rentang tanggal, dan kata kunci.', ['5 pembelian terakhir dari DJS', 'kas besar keluar minggu ini'], 'Hasil kosong → saran barang, nama customer/pemasok/staf, atau kode dokumen mirip.'],
        'summarize_records' => ['Rekap & Total', 'Jumlah, total, rata-rata, rekap per bulan/status.', ['total pembelian bulan ini', 'jumlah pengiriman per status'], 'Tidak termasuk data dihapus/ditolak/batal kecuali diminta.'],
        'find_duplicates' => ['Cek Data Dobel', 'Mencari kemungkinan input ganda.', ['ada kas yang dobel?'], 'Disertai pola (orang, jarak waktu, nilai) dan perkiraan penyebab.'],
        'record_history' => ['Riwayat Dokumen', 'Jejak perubahan satu dokumen: siapa buat, ubah, ACC, kapan.', ['riwayat SO1075'], ''],
        'find_item' => ['Cari Barang', 'Cari produk/bahan dari nama, SKU, barcode, atau ketikan kurang tepat.', ['air aki pegasus 1 liter', 'SKU Aap1000ml'], 'Tidak persis → daftar saran SKU + kemiripan.'],
        'item_activity' => ['Jejak Barang per Modul', 'Satu barang di pengiriman, transfer, opname, pembelian, produksi, retur.', ['berapa transfer stok Aap1000ml hari ini', 'pengiriman H2SO4 bulan ini'], 'Jumlah dokumen + total qty per satuan + daftar dokumen.'],
        'dashboard_summary' => ['Angka Dashboard', 'Angka yang sama dengan halaman Dashboard (KPI, persediaan, top produk, hutang, antrian ACC).', ['ringkasan dashboard bulan ini'], 'Memanggil endpoint dashboard yang sama, angka identik.'],
        'item_stock' => ['Kartu Stok', 'Stok sekarang per gudang + rekap masuk/keluar per sumber + mutasi terakhir.', ['stok H2SO4 berapa?', 'kenapa stok jerigen 5 liter berkurang'], 'Default 30 hari terakhir.'],
        'data_anomalies' => ['Cek Kejanggalan', 'Stok minus, pending ACC lama, transfer belum diterima, selisih opname, hutang jatuh tempo, PO harga Rp 0.', ['ada yang janggal hari ini?', 'apa yang belum di-ACC?'], 'Setiap temuan membawa perkiraan penyebab dalam persen (lihat bagian Perkiraan Penyebab).'],
        'staff_activity' => ['Aktivitas Staf', 'Apa yang dikerjakan staf: aksi per menu, dokumen dibuat/di-ACC; atau peringkat staf teraktif.', ['Ika ngapain aja minggu ini', 'siapa paling aktif hari ini'], 'Membuka menu tidak dihitung kecuali diminta.'],
        'trace_document' => ['Lacak Alur Dokumen', 'Rantai dokumen dari kode mana pun: SO→invoice→surat jalan→retur, PO→TT→penerimaan→invoice, produksi→transfer.', ['PO0567 sudah sampai mana?', 'ST-PR0463-2 dari produksi mana'], 'Tahap yang belum ada ditandai; PO harga Rp 0 → peringatan.'],
        'period_report' => ['Laporan Periode', 'Rekap semua modul per periode + banding periode sebelumnya.', ['laporan bulan ini vs bulan lalu', 'rekap minggu ini'], 'Bulan dibanding bulan kalender sebelumnya, minggu dengan minggu lalu, tahun dengan tahun lalu.'],
        'partner_insight' => ['Insight Customer / Pemasok', 'Partner teratas, yang berhenti order, lead time pemasok, produk bermasalah, hutang; profil satu partner.', ['customer yang sudah lama tidak order', 'profil pemasok ASTERINDO'], 'Nama salah ketik → saran nama mirip.'],
        'reorder_suggestion' => ['Saran Beli Bahan', 'Pemakaian produksi/hari, stok cukup berapa hari, lead time, titik pesan, saran qty.', ['bahan apa yang harus dibeli?', 'jerigen 5 liter cukup berapa hari'], 'Hanya saran; cek PO berjalan sebelum order.'],
    ];

    /** Kunci jenis kejanggalan → label user. */
    private const ANOMALY_LABELS = [
        'stok_minus' => 'Stok Minus',
        'pending_lama' => 'Dokumen Pending Lama',
        'transfer_belum_diterima' => 'Transfer Belum Diterima',
        'selisih_opname' => 'Selisih Stok Opname',
        'hutang_jatuh_tempo' => 'Hutang Jatuh Tempo',
        'harga_nol' => 'PO Harga Rp 0',
    ];

    /** Sinyal data yang menggeser persentase (ringkasan dari DataAnomaliesTool/CauseEstimates). */
    private const SIGNALS = [
        'stok_minus' => ['Minus kecil (≤ 5) → "Urutan input" naik.', 'Ada 2+ log keluar dengan kode & qty sama dalam 90 hari → "Input dobel (koneksi lambat / klik ulang)" naik.'],
        'pending_lama' => ['Dokumen > 30 hari → "Sebenarnya batal tapi tidak dihapus" naik.', 'Dokumen ≤ 30 hari → "Lupa / belum sempat di-ACC" naik.'],
        'transfer_belum_diterima' => ['Semua ≤ 3 hari → "Barang masih di perjalanan" naik.', 'Ada yang > 3 hari → "Gudang tujuan lupa klik Terima" naik.'],
        'selisih_opname' => ['Rasio fisik/sistem tepat 10/12/24/100/1000 (±1%) → "Salah satuan".', 'Rasio ≥ 10 lainnya (mis. 10.000 vs 43) → "Salah hitung / salah ketik".', 'Fisik 0, sistem > 0 → "Salah pilih gudang" & "Transaksi belum diinput" naik.'],
        'hutang_jatuh_tempo' => ['Telat > 60 hari → "Sudah dibayar tapi belum ditandai lunas" naik.', 'Invoice Rp 0 → peringatan harga PO belum diisi.'],
        'harga_nol' => ['Satu pemasok ≥ 50% dari PO Rp 0 → "Harga menunggu konfirmasi pemasok" naik.'],
    ];

    /** Aturan perilaku jawaban (ringkasan dari system prompt). */
    private const RULES = [
        'Hanya membaca data. Tidak bisa membuat, mengubah, menghapus, ACC, atau menolak data — user diarahkan ke menu terkait.',
        'Peran utama: pemandu input. Pertanyaan "cara input X" dijawab langkah bernomor dari panduan menu, plus 1 tips pencegah salah input, lalu ditawarkan cek hasil.',
        'Data tidak ketemu persis → selalu tawarkan opsi "Apakah maksudnya…?" (barang, kode dokumen, staf, customer, pemasok, bahan).',
        'Ada peringatan (mis. harga Rp 0) → disampaikan di awal: masalah, dampak (kenapa nominal ikut 0), langkah perbaikan.',
        'Kejanggalan → fakta + daftar perkiraan penyebab dalam persen (total 100%). Alasan rinci baru dijelaskan kalau user minta detail.',
        'Penyebab selalu operasional (human error, salah gudang/satuan, input dobel karena koneksi lambat, lupa ACC/Terima). Tidak pernah menyebut bug/error sistem.',
        'Tidak menampilkan nama tabel/kolom/SQL. Alamat, telepon, email, rekening, saldo tidak tersedia.',
        'Bahasa Indonesia, singkat (±12 baris; panduan langkah boleh ±18 baris), teks biasa.',
        'Di luar cakupan aplikasi (ide fitur, opini, hal umum) → dijawab singkat dan diarahkan ke tim development.',
    ];

    public function __construct(private ToolRegistry $registry, private Vocabulary $vocab) {}

    public function sections(): array
    {
        return [
            'ringkasan' => [
                'nama' => 'Asisten Pegasus (AI Chat)',
                'tujuan' => 'Memandu staf mengisi data di website, mencari & merekap data, melacak alur dokumen, dan menjelaskan kejanggalan data dengan perkiraan penyebab operasional.',
                'akses' => 'Menu Asisten AI (hak akses "Asisten AI" view). Riwayat chat per sesi login.',
                'model' => config('ai_assistant.openrouter.model'),
                'model_gambar' => config('ai_assistant.vision.model'),
                'batas_baris_ke_model' => (int) config('ai_assistant.rows_to_model'),
                'riwayat_konteks' => (int) config('ai_assistant.context_messages').' pesan terakhir',
            ],
            'aturan_jawaban' => self::RULES,
            'kemampuan' => $this->tools(),
            'perkiraan_penyebab' => $this->causes(),
            'panduan_menu' => $this->guideDocs(),
            'modul_data' => array_values($this->vocab->moduleCatalog()),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function tools(): array
    {
        $out = [];
        foreach ($this->registry->openAiTools() as $t) {
            $fn = $t['function'];
            [$title, $use, $examples, $note] = self::TOOLS[$fn['name']] ?? [$fn['name'], $fn['description'], [], ''];
            $params = [];
            foreach ($fn['parameters']['properties'] ?? [] as $key => $p) {
                $params[] = [
                    'nama' => $key,
                    'wajib' => in_array($key, $fn['parameters']['required'] ?? [], true),
                    'keterangan' => trim(($p['description'] ?? '').(isset($p['enum']) ? ' (pilihan: '.implode(', ', $p['enum']).')' : '')),
                ];
            }
            $out[] = ['tool' => $fn['name'], 'judul' => $title, 'kegunaan' => $use, 'contoh' => $examples, 'catatan' => $note, 'parameter' => $params];
        }

        return $out;
    }

    private function causes(): array
    {
        $out = [];
        foreach (DataAnomaliesTool::causeCatalog() as $type => $causes) {
            $out[] = [
                'jenis' => self::ANOMALY_LABELS[$type] ?? $type,
                'penyebab' => $causes,
                'sinyal' => self::SIGNALS[$type] ?? [],
            ];
        }

        return $out;
    }

    /** Panduan per menu yang dipakai AI (storage/ai_docs, file "_" tidak diindeks). */
    private function guideDocs(): array
    {
        $dir = config('ai_assistant.docs_path', storage_path('ai_docs'));
        $out = [];
        foreach (glob($dir.'/*.md') ?: [] as $file) {
            $name = basename($file);
            if (str_starts_with($name, '_')) {
                continue;
            }
            $text = (string) file_get_contents($file);
            preg_match('/^#\s+(.+)$/m', $text, $h1);
            preg_match_all('/^##\s+(.+)$/m', $text, $h2);
            $out[] = [
                'file' => $name,
                'judul' => trim($h1[1] ?? pathinfo($name, PATHINFO_FILENAME)),
                'bagian' => array_map('trim', $h2[1] ?? []),
                'diindeks' => !str_contains($text, '[UNCERTAIN'),
            ];
        }

        return $out;
    }
}
