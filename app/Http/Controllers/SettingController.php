<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Staff;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function Profiles(){
        $param["data"] = (new Staff())->getStaff(["staff_id"=>1]);
        return view('Backoffice.Settings.Profiles')->with($param);
    }

    public function Settings(){
        $param["data"] = (new Setting())->getSetting();
        return view('Backoffice.Settings.Settings')->with($param);
    }

    function getSetting(Request $req){
        $data = (new Setting())->getSetting(["select"=>$req->select]);
        return json_encode($data);
    }

    function insertSetting(Request $req) {
        $data = $req->all();

        if ($req->hasFile('logo')) {
            $data["logo"] = (new HelperController())->insertFile($req->file('logo'),  'logo');
        }
        if ($req->hasFile('favicon')) {
            $data["favicon"] = (new HelperController())->insertFile($req->file('favicon'),  'favicon');
        }

        foreach ($data as $key => $value) {
            $p = new Setting();
            $p->updateSetting($key, $value);
        }
    }
    
    function updateSetting(Request $req) {
        (new Setting())->updateSetting($req->setting_nama, $req->setting_value);
        return response()->json(['success' => true]);
    }
}
