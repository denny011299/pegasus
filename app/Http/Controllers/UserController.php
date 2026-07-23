<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Arr;

class UserController extends Controller
{
    function loginUser(Request $req)
    {
        $data = (new Staff())->getStaff($req->all());
        if ($data === -1) return -1;
        else if(count($data)>0){
            Session::put("user",$data[0]);
        }

        return $data;
    }
    // Staff
    public function staff(){
        return view('Backoffice.User.Staff');
    }

    public function staffDetail($id){
        $param["data"] =(new Staff())->getStaff(["staff_id"=>$id])[0];
        return view('Backoffice.User.staffDetails')->with($param);
    }

    function viewInsertStaff() {
        $param["mode"] =1; // 1 = insert, 2 = update
        $param["data"] =[];
        return view('Backoffice.User.insertStaff')->with($param);
    }

    function ViewUpdateStaff($id) {
        $param["mode"]=2; // 1 = insert, 2 = update
        $param["data"] = (new Staff())->getStaff(["staff_id"=>$id])[0];
        return view('Backoffice.User.insertStaff')->with($param);
    }

    function getStaff(Request $req){
        $data = (new Staff())->getStaff();
        return response()->json($data);
    }

    function insertStaff(Request $req){
        $data = $req->all();
        if(isset($req->image)&&$req->image!="undefined")$data["staff_image"] = (new HelperController)->insertFile($req->image, "staff");
        return (new Staff())->insertStaff($data);
    }

    function updateStaff(Request $req){
        $data = $req->all();
        if(isset($req->image)&&$req->image!="undefined")$data["staff_image"] = (new HelperController)->insertFile($req->image, "staff");
        return (new Staff())->updateStaff($data);
    }

    function deleteStaff(Request $req){
        $data = $req->all();
        return (new Staff())->deleteStaff($data);
    }

    // Role
    public function role(){
        return view('Backoffice.User.Role');
    }

    function getRole(Request $req){
        $data = (new Role())->getRole();
        return response()->json($data);
    }

    function insertRole(Request $req){
        $data = $req->all();
        return (new Role())->insertRole($data);
    }

    function updateRole(Request $req){
        $data = $req->all();
        return (new Role())->updateRole($data);
    }
    
    function updateRoleName(Request $req){
        $data = $req->all();
        return (new Role())->updateRoleName($data);
    }

    function deleteRole(Request $req){
        $data = $req->all();
        return (new Role())->deleteRole($data);
    }
    // permission
    public function permission($id){
        $param["data"] = Role::find($id);
        return view('Backoffice.User.Permission')->with($param);
    }

    public function dashboardWidgets($id)
    {
        $role = Role::findOrFail($id);
        $accessRows = json_decode($role->role_access ?? '[]', true);
        if (!is_array($accessRows)) {
            $accessRows = [];
        }
        $dashboardRow = collect($accessRows)->first(function ($row) {
            return strtolower(trim((string) Arr::get($row, 'name', ''))) === 'dashboard widgets';
        });
        $selectedWidgets = Arr::get($dashboardRow, 'akses', []);
        if (!is_array($selectedWidgets)) {
            $selectedWidgets = [];
        }

        $widgetOptions = [
            'kpi_ringkasan' => 'Ringkasan changelog & KPI',
            'approval_logs' => 'Changelog & log persetujuan',
            'delivery_chart' => 'Grafik & top produk pengiriman',
            'stock_aging' => 'Stock aging',
            'stock_alert_bahan' => 'Stock alert bahan mentah',
            'overstock_rekomendasi' => 'Overstock & rekomendasi stok produksi',
        ];

        return view('Backoffice.User.DashboardWidgets', [
            'role' => $role,
            'widgetOptions' => $widgetOptions,
            'selectedWidgets' => $selectedWidgets,
        ]);
    }

    function getPermission(Request $req){
        $data =  (new Role())->getRole();
        return json_encode($data);
    }

    function insertPermission(Request $req){
        $data = $req->all();
        return (new Role())->insertRole($data);
    }

    function updatePermission(Request $req){
        $data = $req->all();
        $updatedRoleId = (new Role())->updateRole($data);

        if (Session::has('user')) {
            $sessionUser = Session::get('user');
            if ((int) ($sessionUser->role_id ?? 0) === (int) $updatedRoleId) {
                $freshRole = Role::find((int) $updatedRoleId);
                if ($freshRole) {
                    $sessionUser->role_access = $freshRole->role_access;
                    Session::put('user', $sessionUser);
                }
            }
        }

        return $updatedRoleId;
    }

    public function updateDashboardWidgets(Request $req)
    {
        $data = $req->validate([
            'role_id' => ['required', 'integer'],
            'widgets' => ['nullable', 'array'],
            'widgets.*' => ['string'],
        ]);

        $role = Role::findOrFail((int) $data['role_id']);
        $rawAccess = json_decode($role->role_access ?? '[]', true);
        if (!is_array($rawAccess)) {
            $rawAccess = [];
        }

        $filtered = collect($rawAccess)->reject(function ($row) {
            return strtolower(trim((string) Arr::get($row, 'name', ''))) === 'dashboard widgets';
        })->values()->all();

        $selected = array_values(array_unique(array_filter($data['widgets'] ?? [], static function ($v) {
            return is_string($v) && trim($v) !== '';
        })));

        $filtered[] = [
            'name' => 'Dashboard Widgets',
            'akses' => $selected,
        ];

        $role->role_access = json_encode($filtered, JSON_UNESCAPED_UNICODE);
        $role->save();

        if (Session::has('user')) {
            $sessionUser = Session::get('user');
            if ((int) ($sessionUser->role_id ?? 0) === (int) $role->role_id) {
                $sessionUser->role_access = $role->role_access;
                Session::put('user', $sessionUser);
            }
        }

        if ($req->expectsJson() || $req->ajax()) {
            return response()->json(['status' => true]);
        }

        return redirect('/role');
    }


     // Bank
    public function bank(){
        return view('Backoffice.User.Bank');
    }

    function getBank(Request $req){
        $data = (new Bank())->getBank();
        return response()->json($data);
    }

    function insertBank(Request $req){
        $data = $req->all();
        return (new Bank())->insertBank($data);
    }

    function updateBank(Request $req){
        $data = $req->all();
        return (new Bank())->updateBank($data);
    }

    function deleteBank(Request $req){
        $data = $req->all();
        return (new Bank())->deleteBank($data);
    }

}

