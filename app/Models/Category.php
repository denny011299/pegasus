<?php

namespace App\Models;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

class Category extends Model
{
    protected $table = "categories";
    protected $primaryKey = "category_id";
    public $timestamps = true;
    public $incrementing = true;

    function getCategory($data = [])
    {

        $data = array_merge([
            "category_name"=>null
        ], $data);

        $result = category::where('status', '=', 1);
        if($data["category_name"]) $result->where('category_name','like','%'.$data["category_name"].'%');
        $result->orderBy('created_at', 'asc');
       
        $result = $result->get();
        foreach ($result as $key => $value) {
            $value->created_by_name = $value->created_by ? (Staff::find($value->created_by)->staff_name ?? '-') : '-';
        }
        return $result;
    }

    function insertCategory($data)
    {
        $t = new category();
        $t->category_name = $data["category_name"];
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
        return $t->category_id;
    }

    function updateCategory($data)
    {
        $t = category::find($data["category_id"]);
        $t->category_name = $data["category_name"];
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
        return $t->category_id;
    }

    function deleteCategory($data)
    {
        $t = category::find($data["category_id"]);
        $t->status = 0;
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
    }
}
