<?php

namespace App\Services\AiAssistant\Tools\Concerns;

/**
 * Perkiraan penyebab kejanggalan data dalam persen. Bobot dasar per jenis
 * kejanggalan = pola operasional yang umum di gudang/admin; sinyal dari data
 * (rasio selisih, umur dokumen, input dobel) menggeser bobotnya.
 * Sengaja hanya penyebab operasional — bukan vonis, bukan "bug sistem".
 */
trait CauseEstimates
{
    /** Jenis => penyebab => [bobot dasar, alasan singkat untuk dijelaskan bila diminta]. */
    private const CAUSES = [
        'stok_minus' => [
            'Urutan input (barang keluar dicatat/di-ACC sebelum barang masuk)' => [35, 'Stok sementara minus karena dokumen masuk (PO/produksi/transfer) belum di-ACC saat pengiriman diproses.'],
            'Salah pilih gudang' => [25, 'Barang dikeluarkan dari gudang yang tidak menyimpan stoknya.'],
            'Salah satuan / qty (human error)' => [20, 'Qty atau satuan keluar lebih besar dari yang seharusnya.'],
            'Input dobel (koneksi lambat / klik ulang)' => [10, 'Tombol simpan/ACC ditekan lebih dari sekali saat jaringan lambat.'],
            'Hasil opname belum disesuaikan' => [10, 'Selisih fisik belum dimasukkan lewat stok opname.'],
        ],
        'pending_lama' => [
            'Lupa / belum sempat di-ACC' => [45, 'Approver belum membuka menu terkait atau tidak tahu ada dokumen menunggu.'],
            'Sebenarnya batal tapi tidak dihapus' => [25, 'Dokumen dibiarkan pending setelah transaksinya tidak jadi.'],
            'Menunggu kelengkapan (barang / dokumen fisik)' => [20, 'ACC ditahan sampai barang datang atau berkas lengkap.'],
            'Notifikasi tidak terbaca / koneksi lambat' => [10, 'Pengaju mengira sudah terkirim, padahal belum sampai ke approver.'],
        ],
        'transfer_belum_diterima' => [
            'Gudang tujuan lupa klik Terima' => [45, 'Barang sudah sampai tapi penerimaan belum dicatat di sistem.'],
            'Barang masih di perjalanan' => [20, 'Pengiriman fisik memang belum tiba.'],
            'Salah pilih gudang tujuan' => [20, 'Transfer diarahkan ke gudang yang tidak menerima barangnya.'],
            'Koneksi lambat saat proses Terima' => [15, 'Penerimaan sudah diklik tapi gagal tersimpan karena jaringan.'],
        ],
        'selisih_opname' => [
            'Salah hitung / salah ketik qty (human error)' => [35, 'Angka fisik keliru saat dihitung atau diketik.'],
            'Transaksi belum diinput sebelum opname' => [25, 'Barang keluar/masuk fisik belum dicatat sehingga stok sistem berbeda.'],
            'Salah satuan' => [15, 'Fisik dihitung dalam satuan lain (mis. ml vs liter, pcs vs dus).'],
            'Salah pilih gudang / barang tertukar' => [15, 'Barang dihitung di gudang atau varian yang salah.'],
            'Susut / rusak / hilang' => [10, 'Selisih fisik nyata yang belum dicatat sebagai barang bermasalah.'],
        ],
        'hutang_jatuh_tempo' => [
            'Memang belum dibayar' => [40, 'Pembayaran tertunda karena jadwal kas.'],
            'Sudah dibayar tapi belum ditandai lunas' => [35, 'Pembayaran sudah dilakukan, status PO di sistem belum diperbarui.'],
            'Tanggal jatuh tempo salah input' => [15, 'Tempo invoice diisi lebih pendek dari kesepakatan dengan pemasok.'],
            'Menunggu tukar faktur / dokumen' => [10, 'Pembayaran menunggu faktur asli atau tanda terima.'],
        ],
    ];

    /**
     * @param  array<string, int>  $boost  penyebab => tambahan bobot dari sinyal data
     * @return array{perkiraan_penyebab: list<array{Penyebab: string, Peluang: string}>, alasan_bila_diminta: array<string, string>}
     */
    protected function estimateCauses(string $type, array $boost = []): array
    {
        $weights = [];
        $reasons = [];
        foreach (self::CAUSES[$type] ?? [] as $cause => [$base, $reason]) {
            $weights[$cause] = max(0, $base + ($boost[$cause] ?? 0));
            $reasons[$cause] = $reason;
        }
        arsort($weights);
        $pct = $this->toPercent($weights);

        $list = [];
        foreach ($pct as $cause => $p) {
            if ($p > 0) {
                $list[] = ['Penyebab' => $cause, 'Peluang' => $p.'%'];
            }
        }

        return ['perkiraan_penyebab' => $list, 'alasan_bila_diminta' => array_intersect_key($reasons, array_filter($pct))];
    }

    /**
     * Sinyal per baris opname. Rasio = faktor konversi (10/100/1000, 12/24 per dus) → salah satuan;
     * rasio besar lain (mis. fisik 10.000 vs sistem 43) → salah ketik angka.
     */
    protected function opnameRowBoost(float $physical, float $system): array
    {
        if ($system != 0.0 && $physical != 0.0) {
            $ratio = max(abs($physical / $system), abs($system / $physical));
            // Salah satuan menghasilkan rasio (hampir) persis faktor konversi; 10.000 vs 102 bukan.
            foreach ([10, 12, 24, 100, 1000] as $factor) {
                if (abs($ratio - $factor) / $factor <= 0.01) {
                    return ['Salah satuan' => 45];
                }
            }
            if ($ratio >= 10) {
                return ['Salah hitung / salah ketik qty (human error)' => 45];
            }
        }
        if ($physical == 0.0 && $system > 0) {
            return ['Salah pilih gudang / barang tertukar' => 15, 'Transaksi belum diinput sebelum opname' => 10];
        }

        return [];
    }

    /** @param array<string, int> $weights @return array<string, int> dibulatkan, total tepat 100 */
    private function toPercent(array $weights): array
    {
        $sum = array_sum($weights);
        if ($sum <= 0) {
            return array_map(fn () => 0, $weights);
        }
        $out = [];
        foreach ($weights as $k => $w) {
            $out[$k] = (int) round($w / $sum * 100);
        }
        $first = array_key_first($out);
        $out[$first] += 100 - array_sum($out);

        return $out;
    }
}
