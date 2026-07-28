<?php

namespace App\Models;

use App\ExternalApi\ApiKeyManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;

class ExternalApiKey extends Model
{
    protected $table = "external_api_keys";
    protected $primaryKey = "external_api_key_id";
    public $timestamps = true;
    public $incrementing = true;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_REVOKED = 'revoked';

    /** Status turunan, tidak pernah disimpan di database. */
    public const STATUS_EXPIRED = 'expired';

    function getExternalApiKey($data = [])
    {
        $data = array_merge([
            "external_application_id" => null,
            "external_api_key_id" => null,
        ], $data);

        $result = ExternalApiKey::where('status', '=', 1);

        if ($data["external_application_id"]) $result->where('external_application_id', '=', $data["external_application_id"]);
        if ($data["external_api_key_id"]) $result->where('external_api_key_id', '=', $data["external_api_key_id"]);

        $result->orderBy('created_at', 'desc');
        $result = $result->get();

        foreach ($result as $key => $value) {
            $value->created_by_name = $value->created_by ? (Staff::find($value->created_by)->staff_name ?? '-') : '-';
            $value->masked_key = $this->maskedKey($value);
            $value->effective_status = $this->effectiveStatus($value);
            $value->effective_status_label = self::statusLabel($value->effective_status);
            $value->never_expire = $value->expires_at === null;
        }

        return $result;
    }

    /**
     * Membuat kunci baru. Kunci polos hanya dikembalikan di sini — sesudah ini
     * tidak ada jalan untuk memunculkannya kembali dari database.
     *
     * @return array{external_api_key_id:int, plain_key:string}
     */
    function insertExternalApiKey($data)
    {
        $manager = app(ApiKeyManager::class);
        $environment = $manager->normalizeEnvironment($data["environment"] ?? null);
        $generated = $manager->generate($environment);

        $t = new ExternalApiKey();
        $t->external_application_id = $data["external_application_id"];
        $t->key_name = $data["key_name"];
        $t->environment = $environment;
        $t->key_prefix = $generated['prefix'];
        $t->key_hash = $generated['hash'];
        $t->key_last_four = $generated['last_four'];
        $t->key_status = self::STATUS_ACTIVE;
        $t->expires_at = $this->resolveExpiry($data);
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();

        return [
            "external_api_key_id" => $t->external_api_key_id,
            "plain_key" => $generated['plain'],
        ];
    }

    /**
     * Hanya metadata yang bisa diperbarui. Kunci itu sendiri tidak pernah
     * diganti lewat update — kunci yang bocor harus dicabut lalu dibuat baru.
     */
    function updateExternalApiKey($data)
    {
        $t = ExternalApiKey::find($data["external_api_key_id"]);
        $t->key_name = $data["key_name"];
        $t->expires_at = $this->resolveExpiry($data);
        $t->save();

        return $t->external_api_key_id;
    }

    function revokeExternalApiKey($data)
    {
        $t = ExternalApiKey::find($data["external_api_key_id"]);
        $t->key_status = self::STATUS_REVOKED;
        $t->revoked_at = now();
        $t->revoked_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();

        return $t->external_api_key_id;
    }

    function deleteExternalApiKey($data)
    {
        $t = ExternalApiKey::find($data["external_api_key_id"]);
        $t->status = 0;
        $t->save();
    }

    /**
     * Kedaluwarsa dihitung, tidak disimpan — kunci yang lewat tanggalnya
     * langsung gagal autentikasi tanpa perlu job penjadwal apa pun.
     */
    function effectiveStatus($key): string
    {
        if ($key->key_status == self::STATUS_REVOKED) {
            return self::STATUS_REVOKED;
        }

        if ($key->expires_at && Carbon::parse($key->expires_at)->isPast()) {
            return self::STATUS_EXPIRED;
        }

        return self::STATUS_ACTIVE;
    }

    /** Satu-satunya bentuk kunci yang boleh tampil sesudah dialog pembuatan. */
    function maskedKey($key): string
    {
        return str_repeat('*', 16) . $key->key_last_four;
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_REVOKED => 'Dicabut',
            self::STATUS_EXPIRED => 'Kedaluwarsa',
            default => 'Aktif',
        };
    }

    public static function statusBadgeClass(string $status): string
    {
        return match ($status) {
            self::STATUS_REVOKED => 'badge-soft-danger',
            self::STATUS_EXPIRED => 'badge-soft-warning',
            default => 'badge-soft-success',
        };
    }

    /** `never_expire` bernilai benar mengalahkan tanggal apa pun yang terkirim. */
    private function resolveExpiry($data)
    {
        $never = filter_var($data["never_expire"] ?? false, FILTER_VALIDATE_BOOLEAN);
        if ($never) {
            return null;
        }

        $expiresAt = $data["expires_at"] ?? null;
        if (!$expiresAt) {
            return null;
        }

        return Carbon::parse($expiresAt);
    }
}
