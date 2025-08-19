<?php

namespace App\Http\Controllers;

use App\Models\Pengaturan;
use Illuminate\Http\Request;

class HelperController extends Controller
{
        //lain lain
    public function insertFile($file, $type)
    {
        try {
            $fileName = uniqid() . '.' . $file->extension();
            $filePath = 'upload/' . $type . "/" . $fileName;

            $file->move(public_path('upload/' . $type), $fileName);
            return $filePath;
        } catch (\Throwable $th) {
            dd($th);
            return -1;
        }
    }

    function fetchPengaturan()
    {
        
        $data = Pengaturan::all();
        $param = [];
        foreach ($data as $key => $value) {
            $param[$value["pengaturan_nama"]] = $value["pengaturan_value"];
        };
        return $param;
    }
}
