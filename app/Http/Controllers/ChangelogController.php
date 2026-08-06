<?php

namespace App\Http\Controllers;

class ChangelogController extends Controller
{
    /**
     * Halaman catatan perubahan (change log) aplikasi.
     *
     * Sengaja TIDAK didaftarkan di sidebar dan TIDAK dibatasi lewat
     * role_access -- ini halaman internal untuk developer, dilindungi
     * cukup dengan checkLogin (harus login) supaya hanya orang yang tahu
     * URL-nya (dan sudah punya akun staff) yang bisa membuka.
     *
     * Isinya dibaca dari config/changelog.php, yang di-maintain manual
     * oleh developer setiap rilis -- lihat komentar di file itu.
     */
    public function index()
    {
        $releases = config('changelog.releases', []);

        return view('Backoffice.System.Changelog', [
            'releases' => $releases,
        ]);
    }
}
