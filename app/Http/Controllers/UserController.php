<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // User
    public function user(){
        return view('Backoffice.User.User');
    }

    function getUser(Request $req){
        $data = (new User())->getUser();
        return response()->json($data);
    }

    function insertUser(Request $req){
        $data = $req->all();
        return (new User())->insertUser($data);
    }

    function updateUser(Request $req){
        $data = $req->all();
        return (new User())->updateUser($data);
    }

    function deleteUser(Request $req){
        $data = $req->all();
        return (new User())->deleteUser($data);
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
    public function permission(){
        return view('Backoffice.User.Permission');
    }

    function getPermission(Request $req){
        $data = (new Role())->getPermission();
        return response()->json($data);
    }

    function insertPermission(Request $req){
        $data = $req->all();
        return (new Role())->insertPermission($data);
    }

    function updatePermission(Request $req){
        $data = $req->all();
        return (new Role())->updatePermission($data);
    }

    function deletePermission(Request $req){
        $data = $req->all();
        return (new Role())->deletePermission($data);
    }

}
