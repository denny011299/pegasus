<?php

namespace App\Http\Controllers;

use App\Models\CategoryCoa;
use App\Models\Cities;
use App\Models\Coa;
use App\Models\Provinces;
use App\Models\Staff;
use App\Models\Store;
use Illuminate\Http\Request;

class AutocompleteController extends Controller
{
    public function autocompleteCity(Request $req)
    {
        $keyword = isset($req->keyword) ? $req->keyword : null;

        $p = new Cities();
        $data_city = $p->get_data_simple_city([
            "prov_id" => $req->prov_id,
            "city_name" => $keyword,
        ]);


        foreach ($data_city['data'] as $r) {
            $r->id = $r["city_id"];
            $r->text = $r["city_name"];
        };

        echo json_encode(array(
            "data" => $data_city
        ));
    }


    public function autocompleteProv(Request $req)
    {
        $keyword = isset($req->keyword) ? $req->keyword : null;

        $p = new Provinces();
        $data_city = $p->get_data([
            "prov_name" => $keyword
        ]);


        foreach ($data_city['data'] as $r) {
            $r->id = $r["prov_id"];
            $r->text = $r["prov_name"];
        };

        echo json_encode(array(
            "data" => $data_city
        ));
    }
}
