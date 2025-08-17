<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PettyCash extends Model
{
    protected $table = "petty_cashes";
    protected $primaryKey = "pc_id";
    public $timestamps = true;
    public $incrementing = true;

    function getPettyCash($data = []){
        $data = [
            ['tanggal' => '2025-08-01', 'keterangan' => 'kh', 'masuk' => 1519800, 'keluar1' => 390000, 'keluar2' => 0, 'saldo' => 1519800],
            ['tanggal' => '2025-08-01', 'keterangan' => 'pr', 'masuk' => 1498650, 'keluar1' => 435000, 'keluar2' => 0, 'saldo' => 1488650],
            ['tanggal' => '2025-08-01', 'keterangan' => 'HD', 'masuk' => 5957100, 'keluar1' => 0, 'keluar2' => 0, 'saldo' => 5957100],
            ['tanggal' => '2025-08-01', 'keterangan' => 'ME', 'masuk' => 0, 'keluar1' => 1401000, 'keluar2' => 0, 'saldo' => -1401000],
            ['tanggal' => '2025-08-01', 'keterangan' => 'SOPIR', 'masuk' => 0, 'keluar1' => 85000, 'keluar2' => 0, 'saldo' => -85000],
            ['tanggal' => '2025-08-01', 'keterangan' => 'NR', 'masuk' => 0, 'keluar1' => 2150000, 'keluar2' => 0, 'saldo' => -2150000],
            ['tanggal' => '2025-08-02', 'keterangan' => 'KH', 'masuk' => 88100, 'keluar1' => 320000, 'keluar2' => 0, 'saldo' => 88100],
            ['tanggal' => '2025-08-02', 'keterangan' => 'PR', 'masuk' => 0, 'keluar1' => 324000, 'keluar2' => 0, 'saldo' => -324000],
            ['tanggal' => '2025-08-02', 'keterangan' => 'HD', 'masuk' => 0, 'keluar1' => 283000, 'keluar2' => 0, 'saldo' => -283000],
            ['tanggal' => '2025-08-02', 'keterangan' => 'PABRIK 2/8', 'masuk' => 0, 'keluar1' => 0, 'keluar2' => 11050000, 'saldo' => -11050000],
            ['tanggal' => '2025-08-02', 'keterangan' => 'SOPIR', 'masuk' => 0, 'keluar1' => 4770000, 'keluar2' => 0, 'saldo' => -4770000],
            ['tanggal' => '2025-08-02', 'keterangan' => 'NR', 'masuk' => 0, 'keluar1' => 7000000, 'keluar2' => 0, 'saldo' => -7000000],
            ['tanggal' => '2025-08-02', 'keterangan' => 'SKY', 'masuk' => 0, 'keluar1' => 10500000, 'keluar2' => 0, 'saldo' => -10500000],
            ['tanggal' => '2025-08-02', 'keterangan' => '', 'masuk' => 26295200, 'keluar1' => 337000, 'keluar2' => 0, 'saldo' => 26295200],
            ['tanggal' => '2025-08-02', 'keterangan' => 'CH 29/7', 'masuk' => 0, 'keluar1' => 0, 'keluar2' => 3650000, 'saldo' => -3650000],
            ['tanggal' => '2025-08-02', 'keterangan' => 'CH 30/7', 'masuk' => 0, 'keluar1' => 0, 'keluar2' => 8980000, 'saldo' => -8980000],
            ['tanggal' => '2025-08-02', 'keterangan' => 'CH 31/7', 'masuk' => 0, 'keluar1' => 0, 'keluar2' => 4950000, 'saldo' => -4950000],
        ];
        return $data;
    }
}
