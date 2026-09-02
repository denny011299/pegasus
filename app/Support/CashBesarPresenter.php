<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class CashBesarPresenter
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
            return '<span class="badge bg-warning" style="font-size: 11px">Menunggu Konfirmasi</span>';
        }
        if ($status === 2) {
            return '<span class="badge bg-success" style="font-size: 11px">Diterima</span>';
        }
        if ($status === 3) {
            return '<span class="badge bg-danger" style="font-size: 11px">Ditolak</span>';
        }

        return '-';
    }

    private static function updatedAtText($createdAt, $updatedAt): string
    {
        if (!$updatedAt || !$createdAt) {
            return '-';
        }

        $created = Carbon::parse($createdAt)->format('Y-m-d H:i:s');
        $updated = Carbon::parse($updatedAt)->format('Y-m-d H:i:s');
        if ($created === $updated) {
            return '-';
        }

        $dt = Carbon::parse($updatedAt);
        $bulan = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];

        return $dt->format('d') . ' ' . $bulan[$dt->month - 1] . ' ' . $dt->format('Y, H:i');
    }

    /**
     * @return array<string, mixed>
     */
    public static function row(object $row, $user): array
    {
        $nominal = (int) ($row->cash_nominal ?? 0);
        $cashType = (int) ($row->cash_type ?? 0);

        $debit = 'Rp 0';
        $credit1 = 'Rp 0';
        $credit2 = 'Rp 0';

        if ($cashType === 1) {
            $debit = 'Rp ' . self::money($nominal);
        } elseif ($cashType === 2) {
            $credit1 = '(Rp ' . self::money($nominal) . ')';
        } elseif ($cashType === 3) {
            $credit2 = '(Rp ' . self::money($nominal) . ')';
        }

        $action = '';
        if ((int) $row->status === 1 && self::can($user, 'others')) {
            $cashId = (int) $row->cash_id;
            $cashTujuan = (int) ($row->cash_tujuan ?? 0);
            // GitHub #117/#130: bg-success/bg-danger + text-light are Bootstrap utility classes,
            // which lose to header.blade.php's global `.btn-action-icon { ... !important }` reset
            // — these rendered plain/uncolored in practice. Use the dedicated
            // .btn-action-approve/.btn-action-reject classes instead.
            $action =
                '<div class="d-flex align-items-center gap-1">' .
                '<a class="btn-action-icon btn-action-approve p-2 btn_acc" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Terima" cash_id="' . $cashId . '" cash_tujuan="' . $cashTujuan . '"><i class="fe fe-check"></i></a>' .
                '<a class="btn-action-icon btn-action-reject p-2 btn_decline" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Tolak" cash_id="' . $cashId . '" cash_tujuan="' . $cashTujuan . '"><i class="fe fe-x"></i></a></div>';
        }

        return [
            'cash_id' => (int) $row->cash_id,
            'person_id' => (int) ($row->person_id ?? 0),
            'cash_date' => $row->cash_date,
            'cash_description' => $row->cash_description ?? '',
            'cash_nominal' => $nominal,
            'cash_type' => $cashType,
            'cash_tujuan' => (int) ($row->cash_tujuan ?? 0),
            'status' => (int) ($row->status ?? 0),
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
            'created_by' => $row->created_by ?? null,
            'created_by_name' => $row->created_by_name ?? '-',
            'acc_by' => $row->acc_by ?? null,
            'acc_by_name' => $row->acc_by_name ?? '-',
            'date' => $row->cash_date
                ? Carbon::parse($row->cash_date)->format('j M Y')
                : '-',
            'debit' => $debit,
            'credit1' => $credit1,
            'credit2' => $credit2,
            'debit_text' => "<label class='text-success'>{$debit}</label>",
            'credit_text1' => "<label class='text-danger'>{$credit1}</label>",
            'credit_text2' => "<label class='text-danger'>{$credit2}</label>",
            'status_text' => self::statusBadge((int) $row->status),
            'updated_at_text' => self::updatedAtText($row->created_at ?? null, $row->updated_at ?? null),
            'action' => $action,
            'armada_operasional' => self::childRows($row->armada_operasional ?? null),
            'armada_penyerahan' => self::childRows($row->armada_penyerahan ?? null),
            'sales_operasional' => self::childRows($row->sales_operasional ?? null),
            'sales_penyerahan' => self::childRows($row->sales_penyerahan ?? null),
            'admin_operasional' => self::childRows($row->admin_operasional ?? null),
            'admin_penyerahan' => self::childRows($row->admin_penyerahan ?? null),
        ];
    }

    /**
     * @param  mixed  $rows
     * @return list<array<string, mixed>>
     */
    private static function childRows($rows): array
    {
        if (!$rows) {
            return [];
        }

        return collect($rows)->map(function ($row) {
            $data = $row instanceof Model ? $row->toArray() : (array) $row;

            if (isset($row->detail_armada)) {
                $data['detail_armada'] = collect($row->detail_armada)
                    ->map(fn ($d) => $d instanceof Model ? $d->toArray() : (array) $d)
                    ->values()
                    ->all();
            }
            if (isset($row->detail_admin)) {
                $data['detail_admin'] = collect($row->detail_admin)
                    ->map(fn ($d) => $d instanceof Model ? $d->toArray() : (array) $d)
                    ->values()
                    ->all();
            }

            return $data;
        })->values()->all();
    }
}
