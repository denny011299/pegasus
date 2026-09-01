<?php

namespace App\Support;

use App\Models\Bank;
use App\Models\CashAdmin;
use App\Models\CashAdminDetail;
use App\Models\CashArmada;
use App\Models\CashArmadaDetail;
use App\Models\CashGudang;
use App\Models\CashGudangDetail;
use App\Models\CashSales;
use App\Models\CashSalesDetail;
use App\Models\Customer;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class CashOperasionalDataTable
{
    public static function admin(array $data): array
    {
        $data = array_merge([
            'staff_id' => null,
            'cash_id' => null,
            'ca_id' => null,
            'dates' => null,
        ], $data);

        $dt = DataTableParams::from($data);
        $user = Session::get('user');

        $base = CashAdmin::query()->where('status', '>=', 1);
        self::applyStaffFilter($base, 'staff_id', $data['staff_id']);
        if ($data['cash_id']) {
            $base->where('cash_id', '=', $data['cash_id']);
        }
        if ($data['ca_id']) {
            $base->where('ca_id', '=', $data['ca_id']);
        }
        self::applyDateFilter($base, 'ca_date', $data['dates']);

        $recordsTotal = CashAdmin::where('status', '>=', 1)->count();
        $filtered = clone $base;
        self::applyAdminSearch($filtered, $dt['search']);
        $recordsFiltered = (clone $filtered)->count('ca_id');

        $footer = self::footerAdmin(clone $filtered);
        $sisa_kas = self::sisaKasAdmin();

        $rows = (clone $filtered)
            ->orderBy('status', 'asc')
            ->orderBy('ca_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->skip($dt['start'])
            ->take($dt['length'])
            ->get();

        self::enrichAdminRows($rows);

        return [
            'draw' => $dt['draw'],
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows->map(fn ($row) => CashOperasionalPresenter::adminRow($row, $user))->values()->all(),
            'meta' => array_merge($footer, ['sisa_kas' => $sisa_kas]),
        ];
    }

    public static function gudang(array $data): array
    {
        $data = array_merge([
            'staff_id' => null,
            'cg_id' => null,
            'dates' => null,
        ], $data);

        $dt = DataTableParams::from($data);
        $user = Session::get('user');

        $base = CashGudang::query()->where('status', '>=', 1);
        self::applyStaffFilter($base, 'staff_id', $data['staff_id']);
        if ($data['cg_id']) {
            $base->where('cg_id', '=', $data['cg_id']);
        }
        self::applyDateFilter($base, 'cg_date', $data['dates']);

        $recordsTotal = CashGudang::where('status', '>=', 1)->count();
        $filtered = clone $base;
        self::applyGudangSearch($filtered, $dt['search']);
        $recordsFiltered = (clone $filtered)->count('cg_id');

        $footer = self::footerGudang(clone $filtered);
        $sisa_kas = self::sisaKasGudang();

        $rows = (clone $filtered)
            ->orderBy('status', 'asc')
            ->orderBy('cg_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->skip($dt['start'])
            ->take($dt['length'])
            ->get();

        self::enrichGudangRows($rows);

        return [
            'draw' => $dt['draw'],
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows->map(fn ($row) => CashOperasionalPresenter::gudangRow($row, $user))->values()->all(),
            'meta' => array_merge($footer, ['sisa_kas' => $sisa_kas]),
        ];
    }

    public static function armada(array $data): array
    {
        $data = array_merge([
            'customer_id' => null,
            'cash_id' => null,
            'cr_id' => null,
            'dates' => null,
        ], $data);

        $dt = DataTableParams::from($data);
        $user = Session::get('user');

        $base = CashArmada::query()->where('status', '>=', 1);
        if ($data['customer_id']) {
            $base->where('customer_id', '=', $data['customer_id']);
        }
        if ($data['cash_id']) {
            $base->where('cash_id', '=', $data['cash_id']);
        }
        if ($data['cr_id']) {
            $base->where('cr_id', '=', $data['cr_id']);
        }
        self::applyDateFilter($base, 'cr_date', $data['dates']);

        $recordsTotal = CashArmada::where('status', '>=', 1)->count();
        $filtered = clone $base;
        self::applyArmadaSearch($filtered, $dt['search']);
        $recordsFiltered = (clone $filtered)->count('cr_id');

        $footer = self::footerArmada(clone $filtered);
        $sisa_kas = self::sisaKasArmada();
        $customer_saldo = $data['customer_id']
            ? (int) (Customer::where('customer_id', $data['customer_id'])->value('customer_saldo') ?? 0)
            : 0;
        $total_all = (int) Customer::where('status', 1)->sum('customer_saldo');

        $rows = (clone $filtered)
            ->orderBy('status', 'asc')
            ->orderBy('cr_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->skip($dt['start'])
            ->take($dt['length'])
            ->get();

        self::enrichArmadaRows($rows, $total_all);

        return [
            'draw' => $dt['draw'],
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows->map(fn ($row) => CashOperasionalPresenter::armadaRow($row, $user))->values()->all(),
            'meta' => array_merge($footer, [
                'sisa_kas' => $sisa_kas,
                'customer_saldo' => $customer_saldo,
            ]),
        ];
    }

    public static function sales(array $data): array
    {
        $data = array_merge([
            'staff_id' => null,
            'cash_id' => null,
            'cs_id' => null,
            'dates' => null,
        ], $data);

        $dt = DataTableParams::from($data);
        $user = Session::get('user');

        $base = CashSales::query()->where('status', '>=', 1);
        self::applyStaffFilter($base, 'staff_id', $data['staff_id']);
        if ($data['cash_id']) {
            $base->where('cash_id', '=', $data['cash_id']);
        }
        if ($data['cs_id']) {
            $base->where('cs_id', '=', $data['cs_id']);
        }
        self::applyDateFilter($base, 'cs_date', $data['dates']);

        $recordsTotal = CashSales::where('status', '>=', 1)->count();
        $filtered = clone $base;
        self::applySalesSearch($filtered, $dt['search']);
        $recordsFiltered = (clone $filtered)->count('cs_id');

        $footer = self::footerSales(clone $filtered);
        $sisa_kas = self::sisaKasSales();
        $staff_saldo = $data['staff_id']
            ? (int) (Staff::where('staff_id', $data['staff_id'])->value('staff_saldo') ?? 0)
            : 0;
        $total_all = (int) Staff::where('status', 1)->sum('staff_saldo');

        $rows = (clone $filtered)
            ->orderBy('status', 'asc')
            ->orderBy('cs_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->skip($dt['start'])
            ->take($dt['length'])
            ->get();

        self::enrichSalesRows($rows, $total_all);

        return [
            'draw' => $dt['draw'],
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows->map(fn ($row) => CashOperasionalPresenter::salesRow($row, $user))->values()->all(),
            'meta' => array_merge($footer, [
                'sisa_kas' => $sisa_kas,
                'staff_saldo' => $staff_saldo,
            ]),
        ];
    }

    private static function applyStaffFilter(Builder $query, string $column, $staffId): void
    {
        if ($staffId) {
            $query->where($column, '=', $staffId);
        }
    }

    private static function applyDateFilter(Builder $query, string $column, $dates): void
    {
        if (!$dates) {
            return;
        }

        if (is_array($dates)) {
            $start = $dates[0] ?? null;
            $end = $dates[1] ?? null;
            if ($start) {
                $query->whereDate($column, '>=', Carbon::parse($start)->toDateString());
            }
            if ($end) {
                $query->whereDate($column, '<=', Carbon::parse($end)->toDateString());
            }

            return;
        }

        $query->whereDate($column, Carbon::parse($dates)->toDateString());
    }

    private static function applyAdminSearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $like = '%' . $search . '%';
        $query->where(function ($q) use ($like) {
            $q->where('ca_notes', 'like', $like)
                ->orWhereExists(function ($sq) use ($like) {
                    $sq->select(DB::raw(1))
                        ->from('staffs')
                        ->whereColumn('staffs.staff_id', 'cash_admins.staff_id')
                        ->where('staffs.staff_name', 'like', $like);
                });
        });
    }

    private static function applyGudangSearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $like = '%' . $search . '%';
        $query->where(function ($q) use ($like) {
            $q->where('cg_notes', 'like', $like)
                ->orWhereExists(function ($sq) use ($like) {
                    $sq->select(DB::raw(1))
                        ->from('staffs')
                        ->whereColumn('staffs.staff_id', 'cash_gudangs.staff_id')
                        ->where('staffs.staff_name', 'like', $like);
                });
        });
    }

    private static function applyArmadaSearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $like = '%' . $search . '%';
        $query->where(function ($q) use ($like) {
            $q->where('cr_notes', 'like', $like)
                ->orWhereExists(function ($sq) use ($like) {
                    $sq->select(DB::raw(1))
                        ->from('customers')
                        ->whereColumn('customers.customer_id', 'cash_armadas.customer_id')
                        ->where('customers.customer_notes', 'like', $like);
                });
        });
    }

    private static function applySalesSearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $like = '%' . $search . '%';
        $query->where(function ($q) use ($like) {
            $q->where('cs_notes', 'like', $like)
                ->orWhereExists(function ($sq) use ($like) {
                    $sq->select(DB::raw(1))
                        ->from('staffs')
                        ->whereColumn('staffs.staff_id', 'cash_sales.staff_id')
                        ->where('staffs.staff_name', 'like', $like);
                });
        });
    }

    /** @return array{debits:int,credits:int} */
    private static function footerAdmin(Builder $query): array
    {
        $row = $query->where('status', 2)
            ->selectRaw('COALESCE(SUM(CASE WHEN ca_aksi = 1 THEN ca_nominal ELSE 0 END), 0) as debits, COALESCE(SUM(CASE WHEN ca_aksi <> 1 THEN ca_nominal ELSE 0 END), 0) as credits')
            ->first();

        return [
            'debits' => (int) ($row->debits ?? 0),
            'credits' => (int) ($row->credits ?? 0),
        ];
    }

    /** @return array{debits:int,credits:int} */
    private static function footerGudang(Builder $query): array
    {
        $row = $query->where('status', 2)
            ->selectRaw('COALESCE(SUM(CASE WHEN cg_aksi = 1 THEN cg_nominal ELSE 0 END), 0) as debits, COALESCE(SUM(CASE WHEN cg_aksi <> 1 THEN cg_nominal ELSE 0 END), 0) as credits')
            ->first();

        return [
            'debits' => (int) ($row->debits ?? 0),
            'credits' => (int) ($row->credits ?? 0),
        ];
    }

    /** @return array{debits:int,credits:int} */
    private static function footerArmada(Builder $query): array
    {
        $row = $query->where('status', 2)
            ->selectRaw('COALESCE(SUM(CASE WHEN cr_type = 1 THEN cr_nominal ELSE 0 END), 0) as debits, COALESCE(SUM(CASE WHEN cr_type >= 2 THEN cr_nominal ELSE 0 END), 0) as credits')
            ->first();

        return [
            'debits' => (int) ($row->debits ?? 0),
            'credits' => (int) ($row->credits ?? 0),
        ];
    }

    /** @return array{debits:int,credits:int} */
    private static function footerSales(Builder $query): array
    {
        $row = $query->where('status', 2)
            ->selectRaw('COALESCE(SUM(CASE WHEN cs_transaction = 1 AND cs_aksi = 1 THEN cs_nominal ELSE 0 END), 0) as debits, COALESCE(SUM(CASE WHEN NOT (cs_transaction = 1 AND cs_aksi = 1) THEN cs_nominal ELSE 0 END), 0) as credits')
            ->first();

        return [
            'debits' => (int) ($row->debits ?? 0),
            'credits' => (int) ($row->credits ?? 0),
        ];
    }

    private static function sisaKasAdmin(): int
    {
        return (int) (CashAdmin::where('status', 2)
            ->selectRaw('COALESCE(SUM(CASE
                WHEN ca_type = 1 AND ca_aksi = 1 THEN ca_nominal
                WHEN ca_type = 1 AND ca_aksi = 2 THEN -ca_nominal
                WHEN ca_type = 2 THEN -ca_nominal
                ELSE 0
            END), 0) as sisa_kas')
            ->value('sisa_kas') ?? 0);
    }

    private static function sisaKasGudang(): int
    {
        return (int) (CashGudang::where('status', 2)
            ->selectRaw('COALESCE(SUM(CASE
                WHEN cg_type = 1 AND cg_aksi = 1 THEN cg_nominal
                WHEN cg_type = 1 AND cg_aksi = 2 THEN -cg_nominal
                WHEN cg_type = 2 THEN -cg_nominal
                ELSE 0
            END), 0) as sisa_kas')
            ->value('sisa_kas') ?? 0);
    }

    private static function sisaKasArmada(): int
    {
        return (int) (CashArmada::where('status', 2)
            ->selectRaw('COALESCE(SUM(CASE
                WHEN cr_type = 1 AND cr_nominal < 0 THEN -cr_nominal
                WHEN cr_type = 1 THEN cr_nominal
                WHEN cr_type >= 2 AND cr_nominal < 0 THEN cr_nominal
                WHEN cr_type >= 2 THEN -cr_nominal
                ELSE 0
            END), 0) as sisa_kas')
            ->value('sisa_kas') ?? 0);
    }

    private static function sisaKasSales(): int
    {
        return (int) (CashSales::where('status', 2)
            ->selectRaw('COALESCE(SUM(CASE
                WHEN cs_transaction = 1 THEN cs_nominal
                WHEN cs_transaction >= 2 THEN -cs_nominal
                ELSE 0
            END), 0) as sisa_kas')
            ->value('sisa_kas') ?? 0);
    }

    private static function enrichAdminRows($rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        $staffNames = BatchLookup::staffNames(
            $rows->flatMap(fn ($row) => [$row->staff_id, $row->created_by, $row->acc_by])
        );

        $caIds = $rows->pluck('ca_id')->all();
        $detailsByCaId = $caIds === []
            ? collect()
            : CashAdminDetail::where('status', 1)
                ->whereIn('ca_id', $caIds)
                ->orderBy('created_at', 'desc')
                ->get()
                ->groupBy('ca_id');

        foreach ($rows as $value) {
            $value->staff_name = $staffNames->get((int) $value->staff_id) ?? '-';
            $value->created_by_name = $value->created_by
                ? ($staffNames->get((int) $value->created_by) ?? '-')
                : '-';
            $value->acc_by_name = $value->acc_by
                ? ($staffNames->get((int) $value->acc_by) ?? '-')
                : '-';

            $detail = $detailsByCaId->get($value->ca_id);
            if ($detail && $detail->count() > 0) {
                $value->detail = $detail->values();
            }
        }
    }

    private static function enrichGudangRows($rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        $staffNames = BatchLookup::staffNames(
            $rows->flatMap(fn ($row) => [$row->staff_id, $row->created_by, $row->acc_by])
        );

        $cgIds = $rows->pluck('cg_id')->all();
        $detailsByCgId = collect();
        if ($cgIds !== []) {
            $details = CashGudangDetail::where('status', 1)
                ->whereIn('cg_id', $cgIds)
                ->orderBy('created_at', 'desc')
                ->get();

            $customerNotes = BatchLookup::customers($details->pluck('customer_id'), ['customer_id', 'customer_notes'])
                ->mapWithKeys(fn ($row) => [(int) $row->customer_id => $row->customer_notes]);

            foreach ($details as $detailRow) {
                $detailRow->customer_notes = $customerNotes->get((int) $detailRow->customer_id) ?? '';
            }

            $detailsByCgId = $details->groupBy('cg_id');
        }

        foreach ($rows as $value) {
            $value->staff_name = $staffNames->get((int) $value->staff_id) ?? '-';
            $value->created_by_name = $value->created_by
                ? ($staffNames->get((int) $value->created_by) ?? '-')
                : '-';
            $value->acc_by_name = $value->acc_by
                ? ($staffNames->get((int) $value->acc_by) ?? '-')
                : '-';

            $detail = $detailsByCgId->get($value->cg_id);
            if ($detail && $detail->count() > 0) {
                $value->detail = $detail->values();
            }
        }
    }

    private static function enrichArmadaRows($rows, int $totalAll): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        $customers = BatchLookup::customers($rows->pluck('customer_id'));
        $staffNames = BatchLookup::staffNames(
            $rows->flatMap(fn ($row) => [$row->created_by, $row->acc_by])
        );

        $crIds = $rows->pluck('cr_id')->all();
        $detailsByCrId = $crIds === []
            ? collect()
            : CashArmadaDetail::where('status', 1)
                ->whereIn('cr_id', $crIds)
                ->orderBy('created_at', 'desc')
                ->get()
                ->groupBy('cr_id');

        foreach ($rows as $value) {
            $customer = $customers->get((int) $value->customer_id);
            $value->customer_notes = $customer?->customer_notes ?? '';
            $value->customer_saldo = $customer?->customer_saldo ?? 0;
            $value->created_by_name = $value->created_by
                ? ($staffNames->get((int) $value->created_by) ?? '-')
                : '-';
            $value->acc_by_name = $value->acc_by
                ? ($staffNames->get((int) $value->acc_by) ?? '-')
                : '-';
            $value->total_all = $totalAll;

            $detail = $detailsByCrId->get($value->cr_id);
            if ($detail && $detail->count() > 0) {
                $value->detail = $detail->values();
            }
        }
    }

    private static function enrichSalesRows($rows, int $totalAll): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        $staffRows = BatchLookup::staffRows(
            $rows->flatMap(fn ($row) => [$row->staff_id, $row->created_by, $row->acc_by])
        );

        $bankIds = $rows->pluck('bank_id')
            ->filter(fn ($id) => (int) $id !== 0)
            ->unique()
            ->values()
            ->all();

        $bankCodes = $bankIds === []
            ? collect()
            : Bank::whereIn('bank_id', $bankIds)->pluck('bank_kode', 'bank_id');

        $csIds = $rows->pluck('cs_id')->all();
        $detailsByCsId = $csIds === []
            ? collect()
            : CashSalesDetail::where('status', 1)
                ->whereIn('cs_id', $csIds)
                ->orderBy('created_at', 'desc')
                ->get()
                ->groupBy('cs_id');

        foreach ($rows as $value) {
            $sales = $staffRows->get((int) $value->staff_id);
            $value->staff_name = $sales?->staff_name ?? '-';
            $value->staff_saldo = $sales?->staff_saldo ?? 0;
            $value->created_by_name = $value->created_by
                ? ($staffRows->get((int) $value->created_by)?->staff_name ?? '-')
                : '-';
            $value->acc_by_name = $value->acc_by
                ? ($staffRows->get((int) $value->acc_by)?->staff_name ?? '-')
                : '-';
            $value->total_all = $totalAll;

            if ((int) $value->bank_id !== 0) {
                $value->bank_kode = $bankCodes->get((int) $value->bank_id);
            }

            $detail = $detailsByCsId->get($value->cs_id);
            if ($detail && $detail->count() > 0) {
                $value->detail = $detail->values();
            }
        }
    }
}
