<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
            
        }
        return $result;
    }

    function insertCategory($data)
    {
        $t = new category();
        $t->category_name = $data["category_name"];
        $t->save();
        return $t->category_id;
    }

    function updateCategory($data)
    {
        $t = category::find($data["category_id"]);
        $t->category_name = $data["category_name"];
        $t->save();
        return $t->category_id;
    }

    function deleteCategory($data)
    {
        $t = category::find($data["category_id"]);
        $t->status = 0;
        $t->save();
    }
}
