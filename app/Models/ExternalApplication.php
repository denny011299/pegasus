<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class ExternalApplication extends Model
{
    protected $table = "external_applications";
    protected $primaryKey = "external_application_id";
    public $timestamps = true;
    public $incrementing = true;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_DISABLED = 'disabled';

    function getExternalApplication($data = [])
    {
        $data = array_merge([
            "external_application_id" => null,
            "application_name" => null,
            "application_status" => null,
        ], $data);

        $result = ExternalApplication::where('status', '=', 1);

        if ($data["external_application_id"]) $result->where('external_application_id', '=', $data["external_application_id"]);
        if ($data["application_name"]) $result->where('application_name', 'like', '%' . $data["application_name"] . '%');
        if ($data["application_status"]) $result->where('application_status', '=', $data["application_status"]);

        $result->orderBy('created_at', 'desc');
        $result = $result->get();

        foreach ($result as $key => $value) {
            $value->created_by_name = $value->created_by ? (Staff::find($value->created_by)->staff_name ?? '-') : '-';
            $value->status_label = $value->application_status == self::STATUS_ACTIVE ? 'Aktif' : 'Nonaktif';
            $value->total_key = ExternalApiKey::where('status', '=', 1)
                ->where('external_application_id', '=', $value->external_application_id)
                ->count();
        }

        return $result;
    }

    function insertExternalApplication($data)
    {
        $t = new ExternalApplication();
        $t->application_code = $this->generateExternalApplicationID($data["application_code"] ?? null);
        $t->application_name = $data["application_name"];
        $t->company = $data["company"] ?? null;
        $t->contact_name = $data["contact_name"] ?? null;
        $t->contact_email = $data["contact_email"] ?? null;
        $t->description = $data["description"] ?? null;
        $t->application_status = ($data["application_status"] ?? self::STATUS_ACTIVE) == self::STATUS_DISABLED
            ? self::STATUS_DISABLED
            : self::STATUS_ACTIVE;
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();

        return $t->external_application_id;
    }

    /**
     * Kode aplikasi sengaja tidak ikut diubah di sini. Kode adalah identitas
     * yang dipegang sistem eksternal, jadi tetap stabil walau nama tampilan
     * diganti berkali-kali.
     */
    function updateExternalApplication($data)
    {
        $t = ExternalApplication::find($data["external_application_id"]);
        $t->application_name = $data["application_name"];
        $t->company = $data["company"] ?? null;
        $t->contact_name = $data["contact_name"] ?? null;
        $t->contact_email = $data["contact_email"] ?? null;
        $t->description = $data["description"] ?? null;
        $t->application_status = ($data["application_status"] ?? self::STATUS_ACTIVE) == self::STATUS_DISABLED
            ? self::STATUS_DISABLED
            : self::STATUS_ACTIVE;
        $t->updated_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();

        return $t->external_application_id;
    }

    function deleteExternalApplication($data)
    {
        $t = ExternalApplication::find($data["external_application_id"]);
        $t->status = 0;
        $t->updated_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();

        // Kunci ikut dimatikan supaya tidak ada kunci yatim yang masih lolos
        // autentikasi setelah aplikasinya dihapus.
        ExternalApiKey::where('external_application_id', '=', $data["external_application_id"])
            ->update(['status' => 0]);
    }

    /** Aktifkan / nonaktifkan tanpa menghapus baris. */
    function toggleExternalApplication($data)
    {
        $t = ExternalApplication::find($data["external_application_id"]);
        $t->application_status = $t->application_status == self::STATUS_ACTIVE
            ? self::STATUS_DISABLED
            : self::STATUS_ACTIVE;
        $t->updated_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();

        return $t->application_status;
    }

    /**
     * Kode unik aplikasi. Admin boleh mengisi sendiri (dirapikan jadi slug
     * huruf besar); kalau dikosongkan, dibuatkan otomatis dari nomor urut.
     */
    function generateExternalApplicationID($requested = null)
    {
        if ($requested) {
            $code = strtoupper(Str::slug($requested, '_'));
        } else {
            $last = ExternalApplication::max('external_application_id');
            $code = 'APP' . str_pad((int) $last + 1, 4, '0', STR_PAD_LEFT);
        }

        // Kode wajib unik lintas seluruh baris, termasuk yang sudah dihapus,
        // supaya log lama tidak pernah menunjuk ke dua aplikasi berbeda.
        $base = $code;
        $counter = 1;
        while (ExternalApplication::where('application_code', '=', $code)->exists()) {
            $code = $base . '_' . $counter;
            $counter++;
        }

        return $code;
    }
}
