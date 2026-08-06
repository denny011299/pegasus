<?php

/**
 * Catatan perubahan (change log) aplikasi Pegasus.
 *
 * File ini ditulis & di-update manual oleh developer setiap kali ada rilis/
 * deployment ke server. Karena deployment ke production dilakukan dengan
 * upload file manual (bukan lewat git), file ini WAJIB ikut ter-upload
 * setiap kali ada rilis baru -- ini bagian dari kode aplikasi (di dalam
 * folder config/), bukan file terpisah yang gampang lupa di-copy.
 *
 * Cara menambah entri rilis baru: tambahkan array baru di PALING ATAS
 * array 'releases' (paling baru di paling atas), isi 'date', 'title',
 * dan daftar 'changes' (bullet point, bahasa Indonesia, singkat & jelas).
 *
 * Entri sebelum Agustus 2026 di-generate ulang dari histori commit git
 * (karena belum ada catatan manual sebelumnya) dan dirangkum per bulan --
 * bukan satu entri per commit. Mulai sekarang, tiap rilis ke production
 * sebaiknya punya entrinya sendiri (tidak perlu menunggu akhir bulan).
 */

return [

    'releases' => [

        [
            'date' => '2026-08-06',
            'title' => 'Fase 1 - Perbaikan bug lanjutan (Agustus 2026, berjalan)',
            'changes' => [
                'Tanda Terima sekarang wajib mengelompokkan invoice berdasarkan supplier DAN bank yang sama -- sebelumnya dua supplier berbeda yang kebetulan pakai bank yang sama bisa tergabung dalam satu Tanda Terima.',
                'Aksi pada Invoice PO tidak lagi menimpa status purchase_orders secara tidak sengaja.',
                'Konversi satuan dos/pack diperbaiki agar mengikuti urutan rantai relasi satuan yang benar (tidak lagi terbalik-balik).',
                'Perbaikan besar: produksi dengan produk yang punya relasi multi-satuan tidak lagi menghabiskan bahan baku 100x lebih banyak dari seharusnya.',
                'Berbagai crash fix dan pengerasan (hardening) tambahan hasil bug hunt fase 1 (validasi input, guard status, dsb).',
                'Modal pemisahan (separation modal) dari fase 2 diimplementasikan.',
                'Produksi yang otomatis diselesaikan sistem karena lewat batas waktu (auto-timeout) sekarang ditandai jelas di tampilan, dan proses cek overdue dijadwalkan otomatis lewat scheduler Laravel.',
            ],
        ],
        [
            'date' => '2026-08-05',
            'title' => 'Fase 1 - Perbaikan bug lanjutan',
            'changes' => [
                'Kas Gudang: efek samping ke saldo customer/Kas Armada diperketat supaya tidak salah hitung.',
                'Staff::updateStaff() diperbaiki -- sebelumnya ganti password staff tidak benar-benar tersimpan.',
                'Retur Supplies / bongkar Retur Armada diperbaiki -- hasilnya sempat bisa berbeda tergantung urutan insert baris stok.',
                'Path lama generateTandaTerima (yang sudah digantikan generateTandaTerimaInvoice) dihapus dari kode.',
                'Invoice Sales Order sekarang punya guard supaya tidak bisa dibayar melebihi tagihan (over-payment), menyamai perilaku Purchase Order.',
                'Migrasi database dirapikan ulang sampai cocok dengan skema yang benar-benar berjalan di server.',
                'Konfirmasi PM: perilaku "boleh saldo minus" pada kas & keterkaitan Kas Gudang/Armada memang disengaja, bukan bug -- diputuskan tetap seperti itu.',
            ],
        ],
        [
            'date' => '2026-08-04',
            'title' => 'Fase 1 - Perbaikan bug lanjutan',
            'changes' => [
                'Guard konfirmasi ditambahkan sebelum sistem membuat baris stok baru saat produksi "ladder-split".',
                'accPoDelivery: guard kelebihan-kirim (over-delivery) sebelumnya salah membandingkan qty terkirim dengan dirinya sendiri -- sekarang dibandingkan dengan qty yang benar.',
                'accDeleteProduction: proses pembalikan stok sekarang dibungkus transaction dan aman dari null saat lookup ladder.',
                'updateCashArmada / updateCashSales diperbaiki -- sebelumnya entri yang sudah disetujui (approved) bisa balik lagi jadi pending tanpa sengaja.',
                'deleteInvoicePO() tidak lagi crash (500) ketika menerima poi_id yang tidak valid.',
                'accStockOpname / accStockOpnameBahan sekarang punya guard status dan dibungkus transaction.',
                'Logout diperbaiki -- sebelumnya sesi tidak benar-benar ter-hapus saat logout.',
                'Modul Sales Order Delivery dan alur manual Purchase Order Delivery ditandai deprecated (dikonfirmasi tidak dipakai) beserta test-nya.',
                'Duplikasi SKU produk (MRP300P/SOHP) diperbaiki, dan sekarang dicegah terjadi lagi saat insert/update.',
            ],
        ],
        [
            'date' => '2026-08-01 s.d. 2026-08-03',
            'title' => 'Fase 1 - Awal bug hunt & program testing',
            'changes' => [
                'Optimasi query Stock Opname.',
                'Program automated testing untuk aplikasi mulai dibangun (folder cdocs/testing, database testing khusus).',
                'Sanitasi data awal & penyesuaian import kebutuhan (chore: sanitize init, import needed changes).',
            ],
        ],
        [
            'date' => 'Juli 2026',
            'title' => 'Fase 1 & 2 - External API, migrasi, dan persiapan produksi',
            'changes' => [
                'Platform External API (/api/external) diperkenalkan, termasuk fitur Payments API, untuk integrasi pihak ketiga.',
                'Modul integrasi (mockup) untuk fase 2 mulai dibuat.',
                'Migrasi database dirapikan ulang secara besar-besaran (fase 1) supaya sesuai dengan tabel yang benar-benar dipakai; migrasi warehouse & stock-transfer dari fase 2 digabungkan (schema-only).',
                'Bug akuntansi pada Kas Operasional diperbaiki.',
                'Perbaikan responsivitas halaman login dan lebar tabel dashboard di tampilan mobile.',
                'Optimasi query filter di halaman insert & detail Stock Opname.',
                'Fitur draft (menyimpan sebagai draft) untuk Stock Opname produk & bahan mulai dibuat.',
                'Penambahan pencatatan "actor" (siapa yang melakukan aksi) pada catatan/log.',
                'Perbaikan bug supplies variant dan bug Kas Operasional (30 Juli 2026).',
                'Beberapa eksperimen (gudang, assign gudang, master product) dicoba lalu di-revert karena belum siap.',
            ],
        ],
        [
            'date' => 'Juni 2026',
            'title' => 'Penyempurnaan pembelian, kas, dan produksi',
            'changes' => [
                'Revisi alur pembelian (Purchase Order).',
                'Penambahan kolom pada Kas Besar dan tabel stok bahan/produk.',
                'Perbaikan tampilan tabel peringatan stok (stock alert).',
                'Penyesuaian formula konversi produksi & BOM (Bill of Materials).',
                'Revisi lanjutan Stock Opname.',
            ],
        ],
        [
            'date' => 'Mei 2026',
            'title' => 'Kas, dashboard, dan laporan',
            'changes' => [
                'Revisi lanjutan pada berbagai jenis Kas (Kas Operasional, Kas Besar, dsb) dan Sales Order.',
                'Perbaikan tampilan dashboard, termasuk laporan aging (piutang/hutang jatuh tempo).',
                'Pemakaian pemisah ribuan (thousand separator) pada angka stok.',
                'Perbaikan loading Tanda Terima (TT).',
                'Berbagai perbaikan pada modul Report/Laporan.',
            ],
        ],
        [
            'date' => 'April 2026',
            'title' => 'Dashboard, hak akses, dan UI/UX menyeluruh',
            'changes' => [
                'Dashboard utama dibangun dan direvisi berkali-kali (ringkasan data, grafik, dsb).',
                'Modul Hak Akses (permission per role) mulai dibangun bertahap, termasuk update role.',
                'Perombakan UI/UX di hampir seluruh halaman aplikasi (kecuali Laporan).',
                'Fitur auto-approve produksi ditambahkan; alur acc (approve) pengiriman & produk bermasalah direvisi.',
                'Fitur cetak PDF Hutang (payables).',
                'Fitur Retur Supplies (pengembalian bahan ke supplier) dan laporan retur.',
                'Perbaikan responsivitas tampilan Sales Order & Purchase Order, serta fitur barcode.',
                'Optimasi query Stock Opname.',
            ],
        ],
        [
            'date' => 'Maret 2026',
            'title' => 'Kas Admin/Gudang, Dompet Virtual, dan alur approval',
            'changes' => [
                'Penyelesaian Kas Admin dan Kas Gudang.',
                'Fitur Dompet Virtual (termasuk untuk Sales) ditambahkan.',
                'Kas Armada ditambahkan dan direvisi.',
                'Fitur diskon pembelian ditambahkan.',
                'Alur approval (acc) untuk Produksi dan Produk Bermasalah ditambahkan.',
                'Perbaikan bug pada Retur PO.',
                'Fitur konfirmasi pengiriman ditambahkan; revisi Stock Opname berlanjut.',
                'Berbagai laporan (report) baru ditambahkan.',
            ],
        ],
        [
            'date' => 'Februari 2026',
            'title' => 'Armada, Hak Akses, dan Retur Pembelian',
            'changes' => [
                'Modul Armada (fleet) ditambahkan, termasuk revisi pengiriman.',
                'Modul Hak Akses (permission per role) mulai dicicil pembuatannya.',
                'Fitur Kas Besar dan Kas Operasional ditambahkan.',
                'Fitur Retur Pembelian (purchase return) dan Stock Opname ditambahkan.',
                'Fitur cetak Hutang (payables) ditambahkan.',
                'Konversi Dos pada Produksi diperbaiki; alur cancel Produksi direvisi.',
                'Pengecekan data unik untuk bahan & produk ditambahkan.',
            ],
        ],
        [
            'date' => 'Januari 2026',
            'title' => 'Produk Bermasalah, log audit, dan perbaikan login',
            'changes' => [
                'Modul Produk Bermasalah (barang rusak/retur) dibangun dari awal sampai selesai.',
                'Fitur pencatatan log/audit trail (Log PO, Log SO, Log Produk, Log Supplies) ditambahkan.',
                'Perbaikan keamanan login (hashing password) dan tampilan halaman login.',
                'Produksi multi-produk ditambahkan; konversi stok produk & bahan disempurnakan.',
                'Perbaikan bug pada Hutang (payables) dan detail Purchase Order.',
                'Filter tanggal pada log ditambahkan; tampilan tabel Staff direvisi.',
            ],
        ],
        [
            'date' => 'Desember 2025',
            'title' => 'Penyelesaian Sales Order/Purchase Order dan Stock Opname Produk',
            'changes' => [
                'Penyelesaian alur Sales Order dan revisi Purchase Order.',
                'Penyelesaian Stock Opname Produk.',
                'Fitur upload foto ditambahkan di beberapa modul.',
                'Modul Produk Bermasalah dan Declare Log mulai dibangun.',
                'Berbagai perbaikan bug JS dan bug umum lainnya.',
            ],
        ],
        [
            'date' => 'November 2025',
            'title' => 'Perbaikan satuan, BOM, dan Sales/Purchase Order',
            'changes' => [
                'Bug fixing pada satuan (unit) stok bahan mentah.',
                'Perbaikan BOM (Bill of Materials).',
                'Revisi Invoice Purchase Order.',
                'Progress dan revisi Sales Order.',
            ],
        ],
        [
            'date' => 'Oktober 2025',
            'title' => 'Bug fixing, kas kecil, dan Stock Opname',
            'changes' => [
                'Bug fixing stock alert untuk varian produk.',
                'Halaman insert Product dan ringkasan Purchase Order ditambahkan.',
                'Fitur Kas Kecil (petty cash) dan Master Kategori Kas dibangun (belakangan modul ini dinyatakan deprecated).',
                'Fitur Stock Opname mulai dibangun.',
                'Perbaikan tampilan (responsive) di berbagai halaman.',
            ],
        ],
        [
            'date' => 'September 2025',
            'title' => 'Hak Akses, BOM/Produksi, dan penerjemahan ke Bahasa Indonesia',
            'changes' => [
                'Modul Permission & Role serta CRUD Staff dibangun.',
                'Pengaturan Company & Profile ditambahkan.',
                'BOM (Bill of Materials) dan Produksi mulai dibangun.',
                'Seluruh menu aplikasi diterjemahkan ke Bahasa Indonesia.',
                'Fitur Sales Order diselesaikan; Purchase Order dan laporan bahan ditambahkan.',
                'Fitur barcode untuk product & supplies ditambahkan.',
                'Modul Produk Bermasalah mulai dirintis.',
            ],
        ],
        [
            'date' => 'Agustus 2025',
            'title' => 'Fondasi awal aplikasi Pegasus',
            'changes' => [
                'Fondasi awal aplikasi dibangun: Kategori, Satuan (Unit), dan Varian Produk.',
                'Modul Supplier dan Supplies (bahan baku) dibangun dari awal.',
                'Modul Customer dibangun dari awal.',
                'Modul Kas dan Kas Kecil awal ditambahkan.',
                'Fitur Stock Opname, Stock Alert, dan Profit & Loss awal ditambahkan.',
                'Fitur pencatatan barang masuk/keluar (Inward & Outward) dan Pay & Receive ditambahkan.',
                'Purchase Order (PO) dan Sales Order (SO) mulai dibuat.',
                'Modul User & Role awal ditambahkan.',
            ],
        ],

    ],

];
