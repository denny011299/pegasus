<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Status per endpoint External API — satu baris per endpoint terdokumentasi
 * (App\ExternalApi\Docs\ApiEndpointDoc), diidentifikasi lewat endpoint_key
 * yang nilainya sama persis dengan ApiEndpointDoc::key(). Key ini sudah
 * dijamin unik lintas semua versi API oleh App\ExternalApi\Docs\ApiDocRegistry
 * (registry itu sendiri mengindeks seluruh dokumentasi memakai key yang sama
 * sebagai kunci array).
 *
 * Baris HANYA ada untuk endpoint yang salah satu saklarnya pernah diubah
 * dari nilai bawaan lewat halaman Status API Eksternal — endpoint yang
 * belum pernah disentuh tidak punya baris sama sekali. Nilai bawaannya
 * (is_active=true, is_public_docs_show=false) diterapkan di
 * App\ExternalApi\Support\ApiEndpointSettings, satu-satunya pemakai model
 * ini, bukan di sini.
 */
class ExternalApiEndpointStatus extends Model
{
    protected $table = "external_api_endpoints";
    protected $primaryKey = "external_api_endpoint_id";
    public $timestamps = true;
}
