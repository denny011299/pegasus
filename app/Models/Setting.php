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

    /**
     * URL publik file dari Company Setting (favicon/logo), dengan cache-bust.
     */
    public static function assetUrl(string $key, string $fallback = 'assets/pegasus_logo.jpg'): string
    {
        $settings = (new static())->getSetting(['select' => [$key, 'favicon', 'logo']]);
        $candidates = [];
        foreach ([$key, 'favicon', 'logo'] as $k) {
            $raw = trim(str_replace('\\', '/', (string) ($settings[$k] ?? '')));
            if ($raw !== '') {
                $candidates[] = ltrim($raw, '/');
            }
        }
        $candidates[] = ltrim($fallback, '/');

        foreach (array_unique($candidates) as $rel) {
            if (str_starts_with($rel, 'public/')) {
                $rel = substr($rel, 7);
            }
            $full = public_path($rel);
            if (! is_file($full)) {
                continue;
            }
            $v = @filemtime($full) ?: time();

            return asset($rel).'?v='.$v;
        }

        return asset($fallback);
    }
}
