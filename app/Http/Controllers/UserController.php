<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class UserController extends Controller
{
    function loginUser(Request $req)
    {
        $data = (new Staff())->getStaff($req->all());
        if(count($data)>0){
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

    function deleteRole(Request $req){
        $data = $req->all();
        return (new Role())->deleteRole($data);
    }
    // permission
    public function permission($id){
        $param["data"] = Role::find($id);
        return view('Backoffice.User.Permission')->with($param);
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
        return (new Role())->updateRole($data);
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

