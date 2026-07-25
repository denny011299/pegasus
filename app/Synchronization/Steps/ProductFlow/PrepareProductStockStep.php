<?php

namespace App\Synchronization\Steps\ProductFlow;

use App\Models\ProductStock;
use App\Synchronization\SyncStepResult;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Langkah 6 — Penyiapan Stok Produk (tabel `product_stocks`).
 *
 * Tidak memanggil PMO sama sekali: `/getProducts` tidak membawa angka stok dan
 * dokumen PMO tidak mendefinisikan endpoint stok.
 *
 * Yang dibutuhkan Pegasus adalah adanya satu baris `product_stocks` untuk
 * setiap (varian × satuan yang dipakai produk), karena layar stok, stok opname,
 * dan peringatan stok membacanya. Itu persis yang dikerjakan
 * ProductStock::syncStock(): membuat baris yang belum ada dengan stok 0 dan
 * menonaktifkan baris yang satuannya tidak lagi dipakai produk.
 *
 * **Tidak ada angka stok yang diubah** — baris hanya dibuat atau dinonaktifkan.
 */
class PrepareProductStockStep extends ProductFlowStep
{
    public function handle(): SyncStepResult
    {
        return $this->run(function (SyncStepResult $result) {
            // Hanya produk yang benar-benar sudah terhubung ke PMO, supaya
            // produk asli Pegasus tidak ikut disentuh.
            $products = DB::table('products')
                ->whereNotNull('ref_product_id')
                ->orderBy('product_id')
                ->get(['product_id', 'product_name']);

            if ($products->isEmpty()) {
                $result->succeed(
                    'Belum ada produk yang terhubung ke PMO. Jalankan langkah Sinkronisasi Produk lebih dulu.'
                );

                return;
            }

            $before = DB::table('product_stocks')->count();
            $activeBefore = DB::table('product_stocks')->where('status', 1)->count();
            $stock = new ProductStock();

            foreach ($products as $product) {
                $result->processed++;

                try {
                    $stock->syncStock($product->product_id);
                    $result->updated++;
                } catch (Throwable $e) {
                    $result->failed++;
                    $result->addError(
                        'Produk #'.$product->product_id.' "'.$product->product_name.'": '.$e->getMessage()
                    );
                }
            }

            $after = DB::table('product_stocks')->count();
            $activeAfter = DB::table('product_stocks')->where('status', 1)->count();

            $result->withDetails([
                'Produk Diproses' => $result->processed,
                'Baris Stok Ditambahkan' => max(0, $after - $before),
                'Baris Stok Aktif Sebelum' => $activeBefore,
                'Baris Stok Aktif Sesudah' => $activeAfter,
                'Catatan' => 'Angka stok tidak diubah; hanya baris satuan yang dibuat atau dinonaktifkan.',
            ]);

            $result->finish('Penyiapan baris stok selesai.');
        });
    }
}
