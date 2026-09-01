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
     * @return array<string, mixed>
     */
    public static function adminRow(object $row, $user): array
    {
        $nominal = (int) ($row->ca_nominal ?? 0);
        $isDebit = (int) ($row->ca_aksi ?? 0) === 1;
        $debit = $isDebit ? 'Rp ' . self::money($nominal) : 'Rp 0';
        $credit = $isDebit ? 'Rp 0' : '(Rp ' . self::money($nominal) . ')';

        $action = '';
        if (self::can($user, 'view')) {
            $action .= '<a class="me-2 btn-action-icon p-2 btn_view_admin" data-id="' . (int) $row->ca_id . '" data-bs-target="#view-cash"><i class="fe fe-eye"></i></a>';
        }
        if ((int) $row->status === 1) {
            if ((int) $row->ca_type === 1) {
                if (self::can($user, 'edit')) {
                    $action .= '<a class="me-2 btn-action-icon p-2 btn_edit_admin" data-id="' . (int) $row->ca_id . '" data-bs-target="#edit-category"><i class="fe fe-edit"></i></a>';
                }
                if (self::can($user, 'delete')) {
                    $action .= '<a class="p-2 btn-action-icon btn_delete_admin" data-id="' . (int) $row->ca_id . '" href="javascript:void(0);"><i class="fe fe-trash-2"></i></a>';
                }
            } elseif ((int) $row->ca_type === 2 && self::can($user, 'others')) {
                $action .= '<a class="me-2 btn-action-icon p-2 btn_acc bg-success text-light" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Terima" cash_id="' . (int) $row->cash_id . '"><i class="fe fe-check"></i></a>';
                $action .= '<a class="me-2 btn-action-icon p-2 btn_decline bg-danger text-light" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Tolak" cash_id="' . (int) $row->cash_id . '"><i class="fe fe-x"></i></a>';
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
        $isDebit = (int) ($row->cg_aksi ?? 0) === 1;
        $debit = $isDebit ? 'Rp ' . self::money($nominal) : 'Rp 0';
        $credit = $isDebit ? 'Rp 0' : '(Rp ' . self::money($nominal) . ')';

        $action = '';
        if (self::can($user, 'view')) {
            $action .= '<a class="me-2 btn-action-icon p-2 btn_view_gudang" data-id="' . (int) $row->cg_id . '" data-bs-target="#view-cash"><i class="fe fe-eye"></i></a>';
        }
        if ((int) $row->status === 1) {
            if ((int) $row->cg_type === 1) {
                if (self::can($user, 'edit')) {
                    $action .= '<a class="me-2 btn-action-icon p-2 btn_edit_gudang" data-id="' . (int) $row->cg_id . '" data-bs-target="#edit-category"><i class="fe fe-edit"></i></a>';
                }
                if (self::can($user, 'delete')) {
                    $action .= '<a class="p-2 btn-action-icon btn_delete_gudang" data-id="' . (int) $row->cg_id . '" href="javascript:void(0);"><i class="fe fe-trash-2"></i></a>';
                }
            } elseif ((int) $row->cg_type === 2 && self::can($user, 'others')) {
                $action .= '<a class="me-2 btn-action-icon p-2 btn_acc bg-success text-light" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Terima" cash_id="' . (int) $row->cash_id . '"><i class="fe fe-check"></i></a>';
                $action .= '<a class="me-2 btn-action-icon p-2 btn_decline bg-danger text-light" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Tolak" cash_id="' . (int) $row->cash_id . '"><i class="fe fe-x"></i></a>';
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
        $isDebit = (int) ($row->cr_type ?? 0) === 1;
        $debit = $isDebit ? 'Rp ' . self::money($nominal) : 'Rp 0';
        $credit = $isDebit ? 'Rp 0' : '(Rp ' . self::money($nominal) . ')';

        $action = '';
        $hideView = (int) $row->status === 2 && (int) $row->cr_type === 1;
        if (!$hideView && self::can($user, 'view')) {
            $action .= '<a class="me-2 btn-action-icon p-2 btn_view_armada" data-id="' . (int) $row->cr_id . '" data-bs-target="#view-cash"><i class="fe fe-eye"></i></a>';
        }
        if ((int) $row->status === 1 && (int) ($row->cr_aksi ?? 0) === 2 && self::can($user, 'others')) {
            $action .= '<a class="me-2 btn-action-icon p-2 btn_acc bg-success text-light" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Terima" cash_id="' . (int) $row->cash_id . '"><i class="fe fe-check"></i></a>';
            $action .= '<a class="me-2 btn-action-icon p-2 btn_decline bg-danger text-light" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Tolak" cash_id="' . (int) $row->cash_id . '"><i class="fe fe-x"></i></a>';
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
        $isDebit = (int) ($row->cs_transaction ?? 0) === 1 && (int) ($row->cs_aksi ?? 0) === 1;
        $debit = $isDebit ? 'Rp ' . self::money($nominal) : 'Rp 0';
        $credit = $isDebit ? 'Rp 0' : '(Rp ' . self::money($nominal) . ')';

        $action = '';
        if (self::can($user, 'view')) {
            $action .= '<a class="me-2 btn-action-icon p-2 btn_view_sales" data-id="' . (int) $row->cs_id . '" data-bs-target="#view-cash"><i class="fe fe-eye"></i></a>';
        }
        if ((int) $row->status === 1 && (int) ($row->cs_aksi ?? 0) === 1 && self::can($user, 'others')) {
            $action .= '<a class="me-2 btn-action-icon p-2 btn_acc bg-success text-light" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Terima" cash_id="' . (int) $row->cash_id . '"><i class="fe fe-check"></i></a>';
            $action .= '<a class="me-2 btn-action-icon p-2 btn_decline bg-danger text-light" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Tolak" cash_id="' . (int) $row->cash_id . '"><i class="fe fe-x"></i></a>';
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
