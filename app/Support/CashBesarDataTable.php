<?php

namespace App\Support;

use App\Models\Cash;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class CashBesarDataTable
{
    public static function paginate(array $data): array
    {
        $data = array_merge([
            'cash_id' => null,
            'cash_date' => null,
            'dates' => null,
        ], $data);

        $dt = DataTableParams::from($data);
        $user = Session::get('user');

        $base = Cash::query()
            ->where('status', '>=', 1)
            ->where('status', '<', 3);

        if ($data['cash_id']) {
            $base->where('cash_id', '=', $data['cash_id']);
        }
        if ($data['cash_date']) {
            $base->where('cash_date', '=', $data['cash_date']);
        }
        self::applyDateFilter($base, $data['dates']);

        $recordsTotal = Cash::where('status', '>=', 1)->where('status', '<', 3)->count();
        $filtered = clone $base;
        self::applySearch($filtered, $dt['search']);
        $recordsFiltered = (clone $filtered)->count('cash_id');

        $footer = self::footer(clone $filtered);

        $rows = (clone $filtered)
            ->orderBy('status', 'asc')
            ->orderBy('cash_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->skip($dt['start'])
            ->take($dt['length'])
            ->get();

        Cash::enrichCashRows($rows);

        return [
            'draw' => $dt['draw'],
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows->map(fn ($row) => CashBesarPresenter::row($row, $user))->values()->all(),
            'meta' => $footer,
        ];
    }

    private static function applyDateFilter(Builder $query, $dates): void
    {
        if (!$dates) {
            return;
        }

        if (is_array($dates) && count($dates) === 2) {
            $start = $dates[0] ?? null;
            $end = $dates[1] ?? null;
            if ($start) {
                $query->whereDate('cash_date', '>=', Carbon::parse($start)->toDateString());
            }
            if ($end) {
                $query->whereDate('cash_date', '<=', Carbon::parse($end)->toDateString());
            }

            return;
        }

        $query->whereDate('cash_date', Carbon::parse($dates)->toDateString());
    }

    private static function applySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $like = '%' . $search . '%';
        $query->where('cash_description', 'like', $like);
    }

    /** @return array{debits:int,credits1:int,credits2:int,sisa:int,setor:int} */
    private static function footer(Builder $query): array
    {
        $row = $query->where('status', 2)
            ->selectRaw('
                COALESCE(SUM(CASE WHEN cash_type = 1 THEN cash_nominal ELSE 0 END), 0) as debits,
                COALESCE(SUM(CASE WHEN cash_type = 2 THEN cash_nominal ELSE 0 END), 0) as credits1,
                COALESCE(SUM(CASE WHEN cash_type = 3 THEN cash_nominal ELSE 0 END), 0) as credits2,
                COALESCE(SUM(CASE
                    WHEN cash_type = 1 THEN cash_nominal
                    WHEN cash_type = 2 THEN -cash_nominal
                    WHEN cash_type = 3 AND cash_tujuan != 4 THEN -cash_nominal
                    ELSE 0
                END), 0) as sisa,
                COALESCE(SUM(CASE WHEN cash_type = 3 AND cash_tujuan = 4 THEN cash_nominal ELSE 0 END), 0) as setor
            ')
            ->first();

        return [
            'debits' => (int) ($row->debits ?? 0),
            'credits1' => (int) ($row->credits1 ?? 0),
            'credits2' => (int) ($row->credits2 ?? 0),
            'sisa' => (int) ($row->sisa ?? 0),
            'setor' => (int) ($row->setor ?? 0),
        ];
    }
}
