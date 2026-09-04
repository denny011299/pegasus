<?php

namespace App\Http\Controllers;

use App\Models\DashboardChangeLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DashboardActivityController extends Controller
{
    /**
     * Tutup baris 'open' terakhir milik staf yang sedang login, ditembak oleh
     * navigator.sendBeacon() dari layout/mainlayout.blade.php saat tab/halaman
     * benar-benar ditinggalkan (pagehide, atau visibilitychange -> hidden sebagai
     * fallback mobile Safari yang tidak selalu memanggil pagehide).
     *
     * Ini sinyal AKTIF, beda dari estimasi pasif di LogDashboardActivity::logOpen()
     * (yang baru menutup baris saat user membuka menu LAIN). Tanpa ini, tab yang
     * ditutup tanpa navigasi lagi akan nyangkut "Sedang dibuka" selamanya di
     * ReportController::dashboardChangeLogCounts()/laporan aktivitas.
     *
     * Tidak pakai $maxSeconds (null) -- beda dari estimasi pasif, sinyal ini nyata
     * (browser benar-benar menutup halaman), jadi durasi berapa pun tetap valid,
     * bukan tebakan yang perlu dibatasi.
     *
     * sendBeacon tidak menunggu response, jadi isi balasan tidak penting -- yang
     * penting endpoint ini ringan & selalu 204 walau tidak ada sesi 'open' yang cocok
     * (mis. beacon terkirim dua kali, atau baris sudah ditutup oleh navigasi lain).
     */
    public function closeSession(Request $request): Response
    {
        $staffId = (int) (session('user')->staff_id ?? 0);
        if ($staffId > 0) {
            // Token per-pageview (window.__dashboardActivityToken, ditanam oleh
            // LogDashboardActivity::logOpen()) -- wajib dipakai kalau ada, supaya beacon
            // menutup baris 'open' yang BENAR miliknya sendiri, bukan baris lain yang keburu
            // dibuat navigasi berikutnya (race condition, lihat catatan di logOpen()).
            // Fallback ke closeOpenSession() lama hanya untuk halaman yang sempat di-cache
            // sebelum fix ini (token belum ada di HTML-nya).
            $token = trim((string) $request->input('token', ''));
            if ($token !== '') {
                DashboardChangeLog::closeOpenSessionByToken($staffId, $token, now());
            } else {
                // Fallback ini juga di-scope ke marker sesi (session data, bukan session ID --
                // lihat LogDashboardActivity::sessionMarker()) supaya dua sesi login staf yang
                // sama (2 device/browser) tidak saling menutup baris 'open' satu sama lain.
                // Kalau markernya belum pernah ada (mis. baris lama pra-fix), jangan generate
                // baru di sini -- beacon TIDAK bikin baris baru, cuma menutup yang sudah ada.
                $marker = $request->session()->get('_dashboard_activity_sid');
                if (is_string($marker) && $marker !== '') {
                    DashboardChangeLog::closeOpenSession($staffId, now(), null, $marker);
                }
            }
        }

        return response()->noContent();
    }
}
