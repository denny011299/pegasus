<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

class ProductionSkala extends Model
{
    protected $table = 'production_skalas';
    protected $primaryKey = 'production_skala_id';
    public $timestamps = true;
    public $incrementing = true;

    function getProductionSkala($data = [])
    {
        $data = array_merge([
            'code' => null,
            'name' => null,
        ], $data);

        $result = self::where('status', '=', 1);
        if ($data['code']) {
            $result->where('code', 'like', '%'.$data['code'].'%');
        }
        if ($data['name']) {
            $result->where('name', 'like', '%'.$data['name'].'%');
        }
        $result->orderBy('name', 'asc')->orderBy('code', 'asc')->orderBy('production_skala_id', 'asc');

        $result = $result->get();
        foreach ($result as $value) {
            $value->created_by_name = $value->created_by
                ? (Staff::find($value->created_by)->staff_name ?? '-')
                : '-';
            $value->label = trim($value->code).' | '.trim($value->name)
                .($value->combo_label ? ' ('.$value->combo_label.')' : '');
        }

        return $result;
    }

    function isDuplicateCode($code, $excludeId = null)
    {
        $query = self::where('status', 1)
            ->whereRaw('LOWER(TRIM(code)) = ?', [strtolower(trim($code))]);
        if ($excludeId) {
            $query->where('production_skala_id', '!=', $excludeId);
        }

        return $query->exists();
    }

    function insertProductionSkala($data)
    {
        if ($this->isDuplicateCode($data['code'] ?? '')) {
            return response()->json(['message' => 'Kode skala sudah digunakan'], 422);
        }

        $t = new self();
        $t->code = trim((string) ($data['code'] ?? ''));
        $t->name = trim((string) ($data['name'] ?? ''));
        $t->combo_label = trim((string) ($data['combo_label'] ?? '')) ?: null;
        $t->status = 1;
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();

        return $t->production_skala_id;
    }

    function updateProductionSkala($data)
    {
        $t = self::find($data['production_skala_id'] ?? null);
        if (! $t) {
            return null;
        }

        if ($this->isDuplicateCode($data['code'] ?? '', $t->production_skala_id)) {
            return response()->json(['message' => 'Kode skala sudah digunakan'], 422);
        }

        $t->code = trim((string) ($data['code'] ?? ''));
        $t->name = trim((string) ($data['name'] ?? ''));
        $t->combo_label = trim((string) ($data['combo_label'] ?? '')) ?: null;
        $t->updated_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();

        return $t->production_skala_id;
    }

    function deleteProductionSkala($data)
    {
        $t = self::find($data['production_skala_id'] ?? null);
        if (! $t) {
            return null;
        }
        $t->status = 0;
        $t->updated_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
    }
}
