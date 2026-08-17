<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = "settings";
    protected $primaryKey = "setting_id";
    public $timestamps = true;
    public $incrementing = true;

    function getSetting($data=[]) {
        $data = array_merge([
            'select' => null,
        ], $data);

        $result = Setting::query();
        if ($data["select"]) $result->whereIn("setting_key",$data["select"] );
        $result = $result->get();

        $param = [];
        foreach ($result as $key => $value) {
            $param[$value["setting_key"]] = $value["setting_value"];
        };

        return $param;
    }

    function updateSetting($name, $value)
    {
        if (isset($value) && $name != '_token') {

            $p = Setting::where('setting_key', '=', $name)->first();
            if ($p) {

                $p->setting_value = $value;
                $p->save();
            } else {
                $newP = new Setting();
                $newP->setting_key = $name;
                $newP->setting_value = $value;
                $newP->save();
            }
        }
    }
}
