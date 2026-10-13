<?php

/*
 * Feature flag fitur sprint yang belum rilis ke live.
 * Kode live = main; beda perilaku cukup lewat .env (live: false, dev/staging: true).
 * Pakai: Feature::on('x'), @feature('x') di Blade, middleware 'feature:x', window.PG_FEATURES.x di JS.
 * Skema DB tetap additive (kolom baru ber-default), jadi flag off tidak merusak data.
 */
return [
    // Master Bahan: jenis Trading + relasi produk. Off = semua bahan baru "supply", jenis lama tidak ditimpa.
    'supplies_trading' => (bool) env('FEATURE_SUPPLIES_TRADING', false),

    // Production Planning / Work Order (sprint 3). Off = menu disembunyikan, route 404.
    'production_planning' => (bool) env('FEATURE_PRODUCTION_PLANNING', false),
];
