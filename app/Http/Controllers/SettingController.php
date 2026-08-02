<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SettingController extends Controller
{
    public function Profiles(){
        $staffId = Session::get('user') ? Session::get('user')->staff_id : null;
        $param["data"] = (new Staff())->getStaff(["staff_id" => $staffId])[0] ?? null;
        return view('Backoffice.Settings.Profiles')->with($param);
    }

    /**
     * Self-service save for the logged-in user's own Profil page — deliberately NOT the same
     * endpoint as /updateStaff (Manajemen Pengguna's admin edit-any-staff action). staff_id is
     * always forced from the session, never taken from the request, so a tampered payload can
     * never overwrite a different account; staff_username and role (staff_position) are likewise
     * always kept from the existing record rather than trusted from the client — Profil can
     * change personal info, not login identity or permissions.
     */
    function updateProfile(Request $req){
        $data = $req->all();
        $staffId = Session::get('user') ? Session::get('user')->staff_id : null;
        $current = Staff::find($staffId);
        if (!$current) {
            return response()->json([
                'status' => 0,
                'header' => 'Gagal Update',
                'message' => 'Data staff untuk akun ini tidak ditemukan.',
            ]);
        }

        $data['staff_id'] = $current->staff_id;
        $data['staff_username'] = $current->staff_username;
        $data['staff_position'] = $current->role_id;

        if (isset($req->image) && $req->image != "undefined") {
            $data['staff_image'] = (new HelperController())->insertFile($req->image, 'staff');
        }

        $result = (new Staff())->updateStaff($data);
        if ($result === -1) {
            return response()->json([
                'status' => -1,
                'header' => 'Gagal Update',
                'message' => 'Password saat ini yang dimasukkan tidak sesuai.',
            ]);
        }

        return response()->json(['status' => 1]);
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
