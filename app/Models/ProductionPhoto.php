<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionPhoto extends Model
{
    protected $table = "production_photos";
    protected $primaryKey = "pp_id";
    public $timestamps = true;
    public $incrementing = true;

    function getPhotos($data = [])
    {
        $data = array_merge([
            "pp_id" => null,
            "pp_date" => null,
        ], $data);

        $result = self::query();

        if ($data["pp_id"]) {
            $result->where("pp_id", "=", $data["pp_id"]);
        }

        if ($data["pp_date"]) {
            $result->where("pp_date", "=", $data["pp_date"]);
        }

        $result->orderBy("created_at", "asc");

        return $result->get();
    }

    function insertPhoto($data)
    {
        $t = new self();
        $t->pp_date = $data["pp_date"];
        $t->pp_photo = $data["pp_photo"]; // base64 filename or full URL
        $t->save();

        return $t->pp_id;
    }

    function updatePhoto($data)
    {
        $t = self::find($data["pp_id"]);
        $t->pp_date = $data["pp_date"];
        $t->pp_photo = $data["pp_photo"];
        $t->save();

        return $t->pp_id;
    }

    function deletePhoto($data)
    {
        $t = self::find($data["pp_id"]);
        $t->delete();
    }
}
