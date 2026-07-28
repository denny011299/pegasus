<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ExternalApiRequestLog extends Model
{
    protected $table = "external_api_request_logs";
    protected $primaryKey = "external_api_request_log_id";
    public $timestamps = true;
    public $incrementing = true;

    function getExternalApiRequestLog($data = [])
    {
        $data = array_merge([
            "external_application_id" => null,
            "external_api_key_id" => null,
            "method" => null,
            "status_code" => null,
            "endpoint" => null,
            "start_date" => null,
            "end_date" => null,
            "limit" => 1000,
        ], $data);

        $result = ExternalApiRequestLog::query();

        if ($data["external_application_id"]) $result->where('external_application_id', '=', $data["external_application_id"]);
        if ($data["external_api_key_id"]) $result->where('external_api_key_id', '=', $data["external_api_key_id"]);
        if ($data["method"]) $result->where('method', '=', strtoupper($data["method"]));
        if ($data["endpoint"]) $result->where('endpoint', 'like', '%' . $data["endpoint"] . '%');

        // Filter kode status menerima angka pasti (404) maupun kelompok (4xx),
        // karena yang biasanya dicari admin adalah "semua yang gagal".
        if ($data["status_code"]) {
            $code = strtolower(trim((string) $data["status_code"]));
            if (str_ends_with($code, 'xx')) {
                $bucket = (int) substr($code, 0, 1) * 100;
                $result->whereBetween('status_code', [$bucket, $bucket + 99]);
            } else {
                $result->where('status_code', '=', (int) $code);
            }
        }

        if ($data["start_date"]) $result->where('requested_at', '>=', Carbon::parse($data["start_date"])->startOfDay());
        if ($data["end_date"]) $result->where('requested_at', '<=', Carbon::parse($data["end_date"])->endOfDay());

        $result->orderBy('requested_at', 'desc');

        // Tabel log dimuat penuh ke DataTables sisi klien seperti halaman lain,
        // jadi jumlah baris dibatasi agar halaman tidak berat saat log menumpuk.
        if ($data["limit"]) $result->limit((int) $data["limit"]);

        return $result->get();
    }

    /**
     * Ringkasan untuk kartu di halaman administrasi log.
     *
     * Ukuran penyimpanan sengaja hanya perkiraan dari jumlah baris dikali
     * rata-rata ukuran baris — menanyakan ukuran sebenarnya ke database berbeda
     * caranya di tiap engine dan tidak sepadan dengan manfaatnya di sini.
     *
     * @return array<string, mixed>
     */
    function getExternalApiRequestLogSummary()
    {
        $total = ExternalApiRequestLog::count();
        $oldest = ExternalApiRequestLog::min('requested_at');
        $newest = ExternalApiRequestLog::max('requested_at');
        $bytes = $total * (int) config('externalapi.logging.estimated_row_bytes', 512);

        return [
            "total" => $total,
            "estimated_bytes" => $bytes,
            "estimated_size" => $this->humanSize($bytes),
            "oldest_at" => $oldest,
            "newest_at" => $newest,
        ];
    }

    /**
     * Hapus log sampai batas waktu tertentu. `before` null berarti hapus semua.
     *
     * @return int jumlah baris yang terhapus
     */
    function deleteExternalApiRequestLog($data)
    {
        $before = $data["before"] ?? null;

        $result = ExternalApiRequestLog::query();
        if ($before) {
            $result->where('requested_at', '<', Carbon::parse($before));
        }

        return $result->delete();
    }

    private function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $size = $bytes;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, $i === 0 ? 0 : 2) . ' ' . $units[$i];
    }
}
