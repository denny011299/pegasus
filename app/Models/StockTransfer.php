<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

class StockTransfer extends Model
{
    protected $table = 'stock_transfers';

    protected $primaryKey = 'st_id';

    public $timestamps = true;

    public $incrementing = true;

    protected $fillable = [
        'transfer_code',
        'transfer_date',
        'sender_id',
        'receiver_id',
        'from_warehouse_id',
        'to_warehouse_id',
        'note',
        'accept_note',
        'source_type',
        'source_id',
        'disposition',
        'status',
        'created_by',
        'acc_by',
    ];

    public function details()
    {
        return $this->hasMany(StockTransferDetail::class, 'st_id', 'st_id')
            ->where('status', 1);
    }

    public function generateCode(): string
    {
        $id = (int) (self::max('st_id') ?? 0) + 1;

        return 'ST' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    }

    public function createHeader(array $data): int
    {
        $t = new self();
        $t->transfer_code = $this->generateCode();
        $t->transfer_date = $data['transfer_date'];
        $t->sender_id = $data['sender_id'];
        $t->receiver_id = $data['receiver_id'] ?? null;
        $t->from_warehouse_id = $data['from_warehouse_id'];
        $t->to_warehouse_id = $data['to_warehouse_id'];
        $t->note = $data['note'] ?? null;
        $t->source_type = $data['source_type'] ?? null;
        $t->source_id = $data['source_id'] ?? null;
        $t->disposition = $data['disposition'] ?? null;
        $t->status = 1;
        $t->created_by = Session::get('user')->staff_id ?? null;
        $t->save();

        return (int) $t->st_id;
    }
}
