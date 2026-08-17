@extends('layout.mainlayout')
@section('content')
<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content container-fluid d-flex flex-column justify-content-center align-items-center" style="min-height: 70vh;">
        <div class="text-center">
            <h1 class="display-1 fw-bold text-danger mb-2"><i class="fa fa-ban"></i> 403</h1>
            <h3 class="text-uppercase fw-bold mb-3 text-dark">Akses Ditolak</h3>
            <p class="text-muted mb-4 fs-5">
                Maaf, akun Anda tidak memiliki izin untuk mengakses modul ini.
                <br>
                Silakan hubungi Administrator atau kembali ke halaman utama.
            </p>
            <a href="{{ url('/') }}" class="btn btn-primary btn-lg rounded-pill px-4 py-2 shadow-sm">
                <i class="fa fa-arrow-left me-2"></i> Kembali ke Dashboard
            </a>
        </div>
    </div>
</div>
<!-- /Page Wrapper -->
@endsection
