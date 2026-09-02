<?php

namespace App\Support;

use Carbon\Carbon;

class CashOperasionalPresenter
{
    /** @var list<string> */
    public const MODULES = ['Kas', 'Kas Operasional'];

    private static function money(int $amount): string
    {
        return number_format(abs($amount), 0, ',', '.');
    }

    private static function can($user, string $ability): bool
    {
        return RoleAccess::canAny($user, self::MODULES, $ability);
    }

    private static function statusBadge(int $status): string
    {
        if ($status === 1) {
            return '<span class="badge bg-warning" style="font-size: 12px">Sedang Diajukan</span>';
        }
        if ($status === 2) {
            return '<span class="badge bg-success" style="font-size: 12px">Diterima</span>';
        }
        if ($status === 3) {
            return '<span class="badge bg-danger" style="font-size: 12px">Ditolak</span>';
        }

        return '-';
    }

    private static function dateText($value): string
    {
        if (!$value) {
            return '-';
        }

        return Carbon::parse($value)->format('j M Y');
    }

    /**
     * GitHub #130 (item 35): `ca_nominal`/`cg_nominal` are stored RAW (whatever sign the request
     * sent — see the linked Cash row's own sign-flip at insert time in ReportController), but a
     * "saldo" (`ca_type`/`cg_type` == 1) row's Masuk/Keluar column here was always decided purely
     * by `ca_aksi`/`cg_aksi` (1=Pengajuan→Masuk, 2=Pengembalian→Keluar), ignoring the sign
     * entirely. A NEGATIVE nominal reverses what actually happened (a negative Pengembalian is
     * functionally a Pengajuan, and vice versa), so it must flip which column the row lands in —
     * only for saldo rows; "operasional" (type 2, expense) rows are untouched, out of scope here.
     */
    private static function isDebitColumn(int $aksi, int $type, int $nominal): bool
    {
        $isDebit = $aksi === 1;
        if ($type === 1 && $nominal < 0) {
            $isDebit = !$isDebit;
        }

        return $isDebit;
    }

    /**
     * @return array<string, mixed>
     */
    public static function adminRow(object $row, $user): array
    {
        $nominal = (int) ($row->ca_nominal ?? 0);
        $isDebit = self::isDebitColumn((int) ($row->ca_aksi ?? 0), (int) ($row->ca_type ?? 0), $nominal);
        $debit = $isDebit ? 'Rp ' . self::money($nominal) : 'Rp 0';
        $credit = $isDebit ? 'Rp 0' : '(Rp ' . self::money($nominal) . ')';

        $action = '';
        if (self::can($user, 'view')) {
            $action .= '<a class="me-2 btn-action-icon btn-action-view p-2 btn_view_admin" data-id="' . (int) $row->ca_id . '" data-bs-target="#view-cash"><i class="fe fe-eye"></i></a>';
        }
        if ((int) $row->status === 1) {
            if ((int) $row->ca_type === 1) {
                if (self::can($user, 'edit')) {
                    $action .= '<a class="me-2 btn-action-icon btn-action-edit p-2 btn_edit_admin" data-id="' . (int) $row->ca_id . '" data-bs-target="#edit-category"><i class="fe fe-edit"></i></a>';
                }
                if (self::can($user, 'delete')) {
                    $action .= '<a class="p-2 btn-action-icon btn-action-delete btn_delete_admin" data-id="' . (int) $row->ca_id . '" href="javascript:void(0);"><i class="fe fe-trash-2"></i></a>';
                }
            } elseif ((int) $row->ca_type === 2 && self::can($user, 'others')) {
                // GitHub #117/#130: bg-success/bg-danger + text-light are Bootstrap utility
                // classes, which lose to header.blade.php's global `.btn-action-icon { ...
                // !important }` reset — these rendered plain/uncolored in practice. Use the
                // dedicated .btn-action-approve/.btn-action-reject classes instead (same rollout
                // that already fixed this for other tables' row-level ACC/Tolak icons).
                $action .= '<a class="me-2 btn-action-icon btn-action-approve p-2 btn_acc" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Terima" cash_id="' . (int) $row->cash_id . '"><i class="fe fe-check"></i></a>';
                $action .= '<a class="me-2 btn-action-icon btn-action-reject p-2 btn_decline" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Tolak" cash_id="' . (int) $row->cash_id . '"><i class="fe fe-x"></i></a>';
            }
        }
        if ($action === '') {
            $action = '<span class="text-muted small">—</span>';
        }

        return self::baseRow($row, [
            'ca_id' => (int) $row->ca_id,
            'cash_id' => (int) ($row->cash_id ?? 0),
            'ca_date' => $row->ca_date,
            'ca_notes' => $row->ca_notes ?? '',
            'ca_type' => (int) ($row->ca_type ?? 0),
            'ca_aksi' => (int) ($row->ca_aksi ?? 0),
            'ca_nominal' => $nominal,
            'date' => self::dateText($row->ca_date),
            'status_text' => self::statusBadge((int) $row->status),
            'debit' => $debit,
            'credit' => $credit,
            'debit_text' => "<label class='text-success'>{$debit}</label>",
            'credit_text' => "<label class='text-danger'>{$credit}</label>",
            'action' => $action,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function gudangRow(object $row, $user): array
    {
        $nominal = (int) ($row->cg_nominal ?? 0);
        $isDebit = self::isDebitColumn((int) ($row->cg_aksi ?? 0), (int) ($row->cg_type ?? 0), $nominal);
        $debit = $isDebit ? 'Rp ' . self::money($nominal) : 'Rp 0';
        $credit = $isDebit ? 'Rp 0' : '(Rp ' . self::money($nominal) . ')';

        $action = '';
        if (self::can($user, 'view')) {
            $action .= '<a class="me-2 btn-action-icon btn-action-view p-2 btn_view_gudang" data-id="' . (int) $row->cg_id . '" data-bs-target="#view-cash"><i class="fe fe-eye"></i></a>';
        }
        if ((int) $row->status === 1) {
            if ((int) $row->cg_type === 1) {
                if (self::can($user, 'edit')) {
                    $action .= '<a class="me-2 btn-action-icon btn-action-edit p-2 btn_edit_gudang" data-id="' . (int) $row->cg_id . '" data-bs-target="#edit-category"><i class="fe fe-edit"></i></a>';
                }
                if (self::can($user, 'delete')) {
                    $action .= '<a class="p-2 btn-action-icon btn-action-delete btn_delete_gudang" data-id="' . (int) $row->cg_id . '" href="javascript:void(0);"><i class="fe fe-trash-2"></i></a>';
                }
            } elseif ((int) $row->cg_type === 2 && self::can($user, 'others')) {
                // GitHub #117/#130: see the same note in adminRow() above.
                $action .= '<a class="me-2 btn-action-icon btn-action-approve p-2 btn_acc" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Terima" cash_id="' . (int) $row->cash_id . '"><i class="fe fe-check"></i></a>';
                $action .= '<a class="me-2 btn-action-icon btn-action-reject p-2 btn_decline" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Tolak" cash_id="' . (int) $row->cash_id . '"><i class="fe fe-x"></i></a>';
            }
        }
        if ($action === '') {
            $action = '<span class="text-muted small">—</span>';
        }

        return self::baseRow($row, [
            'cg_id' => (int) $row->cg_id,
            'cash_id' => (int) ($row->cash_id ?? 0),
            'cg_date' => $row->cg_date,
            'cg_notes' => $row->cg_notes ?? '',
            'cg_type' => (int) ($row->cg_type ?? 0),
            'cg_aksi' => (int) ($row->cg_aksi ?? 0),
            'cg_nominal' => $nominal,
            'date' => self::dateText($row->cg_date),
            'status_text' => self::statusBadge((int) $row->status),
            'debit' => $debit,
            'credit' => $credit,
            'debit_text' => "<label class='text-success'>{$debit}</label>",
            'credit_text' => "<label class='text-danger'>{$credit}</label>",
            'action' => $action,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function armadaRow(object $row, $user): array
    {
        $nominal = (int) ($row->cr_nominal ?? 0);
        // GitHub #130 (item 36): `cr_type` carries real meaning for "operasional" rows (1 =
        // Setoran/Masuk, else Keluar) AND for rows CashGudang::acceptCashGudang() cross-creates
        // directly (cr_type explicitly 1, cr_aksi left at its 0 default) — both must keep using
        // `cr_type` exactly as before. Only the "saldo" / Pengembalian Dana Langsung branch
        // (`cr_aksi` == 1) never sets `cr_type` at all (model defaults it to 3), so ITS Masuk/Keluar
        // column must come from the nominal's own sign instead: a normal (positive) pengembalian
        // reduces the wallet (Keluar), a negative one reverses that and is functionally an addition
        // (Masuk).
        $isDebit = (int) ($row->cr_aksi ?? 0) === 1
            ? $nominal < 0
            : (int) ($row->cr_type ?? 0) === 1;
        $debit = $isDebit ? 'Rp ' . self::money($nominal) : 'Rp 0';
        $credit = $isDebit ? 'Rp 0' : '(Rp ' . self::money($nominal) . ')';

        $action = '';
        $hideView = (int) $row->status === 2 && (int) $row->cr_type === 1;
        if (!$hideView && self::can($user, 'view')) {
            $action .= '<a class="me-2 btn-action-icon btn-action-view p-2 btn_view_armada" data-id="' . (int) $row->cr_id . '" data-bs-target="#view-cash"><i class="fe fe-eye"></i></a>';
        }
        if ((int) $row->status === 1 && (int) ($row->cr_aksi ?? 0) === 2 && self::can($user, 'others')) {
            // GitHub #117/#130: see the same note in adminRow() above.
            $action .= '<a class="me-2 btn-action-icon btn-action-approve p-2 btn_acc" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Terima" cash_id="' . (int) $row->cash_id . '"><i class="fe fe-check"></i></a>';
            $action .= '<a class="me-2 btn-action-icon btn-action-reject p-2 btn_decline" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Tolak" cash_id="' . (int) $row->cash_id . '"><i class="fe fe-x"></i></a>';
        }
        if ($action === '' && !$hideView) {
            $action = '<span class="text-muted small">—</span>';
        }

        return self::baseRow($row, [
            'cr_id' => (int) $row->cr_id,
            'cash_id' => (int) ($row->cash_id ?? 0),
            'cr_date' => $row->cr_date,
            'cr_notes' => $row->cr_notes ?? '',
            'cr_type' => (int) ($row->cr_type ?? 0),
            'cr_aksi' => (int) ($row->cr_aksi ?? 0),
            'cr_nominal' => $nominal,
            'customer_notes' => $row->customer_notes ?? '',
            'customer_saldo' => (int) ($row->customer_saldo ?? 0),
            'total_all' => (int) ($row->total_all ?? 0),
            'date' => self::dateText($row->cr_date),
            'status_text' => self::statusBadge((int) $row->status),
            'debit' => $debit,
            'credit' => $credit,
            'debit_text' => "<label class='text-success'>{$debit}</label>",
            'credit_text' => "<label class='text-danger'>{$credit}</label>",
            'action' => $action,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function salesRow(object $row, $user): array
    {
        $nominal = (int) ($row->cs_nominal ?? 0);
        // GitHub #130 (item 38): only the "Pengembalian" saldo action (`cs_aksi` == 3) needs the
        // sign-based flip — a negative pengembalian is functionally an addition, so it belongs in
        // Masuk instead of Keluar. "Pemasukan" (aksi 1), "Setor ke Bank" (aksi 2, and per item 39
        // now guarded against ever being negative) and "operasional" rows (aksi left at its 0
        // default) all keep their original logic untouched.
        $isDebit = (int) ($row->cs_aksi ?? 0) === 3
            ? $nominal < 0
            : ((int) ($row->cs_transaction ?? 0) === 1 && (int) ($row->cs_aksi ?? 0) === 1);
        $debit = $isDebit ? 'Rp ' . self::money($nominal) : 'Rp 0';
        $credit = $isDebit ? 'Rp 0' : '(Rp ' . self::money($nominal) . ')';

        $action = '';
        if (self::can($user, 'view')) {
            $action .= '<a class="me-2 btn-action-icon btn-action-view p-2 btn_view_sales" data-id="' . (int) $row->cs_id . '" data-bs-target="#view-cash"><i class="fe fe-eye"></i></a>';
        }
        if ((int) $row->status === 1 && (int) ($row->cs_aksi ?? 0) === 1 && self::can($user, 'others')) {
            // GitHub #117/#130: see the same note in adminRow() above.
            $action .= '<a class="me-2 btn-action-icon btn-action-approve p-2 btn_acc" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Terima" cash_id="' . (int) $row->cash_id . '"><i class="fe fe-check"></i></a>';
            $action .= '<a class="me-2 btn-action-icon btn-action-reject p-2 btn_decline" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Tolak" cash_id="' . (int) $row->cash_id . '"><i class="fe fe-x"></i></a>';
        }
        if ($action === '') {
            $action = '<span class="text-muted small">—</span>';
        }

        return self::baseRow($row, [
            'cs_id' => (int) $row->cs_id,
            'cash_id' => (int) ($row->cash_id ?? 0),
            'cs_date' => $row->cs_date,
            'cs_notes' => $row->cs_notes ?? '',
            'cs_type' => (int) ($row->cs_type ?? 0),
            'cs_transaction' => (int) ($row->cs_transaction ?? 0),
            'cs_aksi' => (int) ($row->cs_aksi ?? 0),
            'cs_nominal' => $nominal,
            'staff_saldo' => (int) ($row->staff_saldo ?? 0),
            'total_all' => (int) ($row->total_all ?? 0),
            'bank_id' => (int) ($row->bank_id ?? 0),
            'bank_kode' => $row->bank_kode ?? null,
            'date' => self::dateText($row->cs_date),
            'status_text' => self::statusBadge((int) $row->status),
            'debit' => $debit,
            'credit' => $credit,
            'debit_text' => "<label class='text-success'>{$debit}</label>",
            'credit_text' => "<label class='text-danger'>{$credit}</label>",
            'action' => $action,
        ]);
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    private static function baseRow(object $row, array $fields): array
    {
        $detail = $row->detail ?? null;

        return array_merge($fields, [
            'status' => (int) ($row->status ?? 0),
            'staff_id' => (int) ($row->staff_id ?? 0),
            'staff_name' => $row->staff_name ?? '-',
            'created_by' => $row->created_by ?? null,
            'created_by_name' => $row->created_by_name ?? '-',
            'acc_by' => $row->acc_by ?? null,
            'acc_by_name' => $row->acc_by_name ?? '-',
            'detail' => $detail ? collect($detail)->values()->all() : [],
        ]);
    }
}
