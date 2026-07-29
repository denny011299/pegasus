<?php

namespace App\ExternalApi\Support;

use Illuminate\Support\Facades\DB;

/**
 * Penyimpan setelan sederhana untuk platform API Eksternal.
 *
 * CATATAN PENTING soal tabel `settings`:
 * tabel di database yang berjalan memakai kolom `setting_key`, sedangkan
 * App\Models\Setting (dan migrasi create_settings_table) menulis
 * `setting_name` — kolom yang tidak ada di sana. Model itu karena itu tidak
 * bisa dipakai apa adanya; saat ini memang tidak ada kode lain yang
 * memanggilnya, jadi selisih tersebut belum pernah ketahuan.
 *
 * Kelas ini sengaja bekerja langsung di atas skema yang benar-benar ada,
 * bukan lewat model tadi, supaya fitur ini tidak ikut terseret masalah itu.
 * Kalau nanti model Setting dirapikan, isi kelas ini tinggal diarahkan ke
 * sana tanpa mengubah pemanggilnya.
 */
class SettingStore
{
    private const TABLE = 'settings';
    private const COLUMN = 'setting_key';

    public function get(string $key, ?string $default = null): ?string
    {
        $row = DB::table(self::TABLE)->where(self::COLUMN, '=', $key)->first();

        return $row ? (string) $row->setting_value : $default;
    }

    public function put(string $key, string $value): void
    {
        $exists = DB::table(self::TABLE)->where(self::COLUMN, '=', $key)->exists();

        if ($exists) {
            DB::table(self::TABLE)
                ->where(self::COLUMN, '=', $key)
                ->update([
                    'setting_value' => $value,
                    'updated_at' => now(),
                ]);

            return;
        }

        DB::table(self::TABLE)->insert([
            self::COLUMN => $key,
            'setting_value' => $value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function getBool(string $key, bool $default): bool
    {
        $value = $this->get($key);

        return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function putBool(string $key, bool $value): void
    {
        $this->put($key, $value ? '1' : '0');
    }
}
