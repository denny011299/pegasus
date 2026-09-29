<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

class ProductionWorkOrder extends Model
{
    protected $table = 'production_work_orders';
    protected $primaryKey = 'production_work_order_id';
    public $timestamps = true;
    public $incrementing = true;

    public static function generateWoNumber(?string $dateYmd = null, ?int $warehouseId = null): string
    {
        $date = $dateYmd ? Carbon::parse($dateYmd) : Carbon::today();
        $prefix = 'WO-'.$date->format('ymd').'-';
        $q = self::where('wo_number', 'like', $prefix.'%')->where('status', 1);
        if ($warehouseId) {
            $q->where('warehouse_id', $warehouseId);
        }
        $last = $q->orderByDesc('production_work_order_id')->value('wo_number');
        $seq = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return $prefix.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Payload print FORM-OPS-09 untuk satu WO (satu PIC).
     *
     * @return array<string, mixed>|null
     */
    public static function printPayload(int $pwoId): ?array
    {
        $wo = self::where('production_work_order_id', $pwoId)->where('status', 1)->first();
        if (! $wo) {
            return null;
        }

        $activeWh = (int) (Session::get('active_warehouse_id') ?? 0);
        if ($activeWh > 0 && (int) $wo->warehouse_id !== $activeWh) {
            return null;
        }

        $pp = ProductionPlanning::where('production_planning_id', $wo->production_planning_id)
            ->where('status', 1)
            ->first();
        if (! $pp) {
            return null;
        }

        $pic = Staff::find($wo->pic_staff_id);
        $items = ProductionPlanningItem::where('production_work_order_id', $pwoId)
            ->where('status', 1)
            ->orderBy('ppi_id')
            ->get();

        $skalaIds = $items->pluck('production_skala_id')->filter()->unique()->all();
        $skalas = $skalaIds
            ? ProductionSkala::whereIn('production_skala_id', $skalaIds)->get()->keyBy('production_skala_id')
            : collect();

        $totals = \App\Support\ProductionExecution::totals($pwoId);
        $rows = [];
        foreach ($items as $i => $it) {
            $skala = $skalas->get($it->production_skala_id);
            $rows[] = [
                'no' => $i + 1,
                'product_name' => $it->product_name ?: '—',
                'qty' => (float) $it->qty,
                'unit_label' => $it->unit_label ?: '',
                'skala' => $skala
                    ? trim((string) ($skala->code ?: $skala->name))
                    : '—',
                'actual' => $totals[$it->ppi_id] ?? null,
                'excess' => max(0, ($totals[$it->ppi_id] ?? 0) - $it->qty),
                'status' => isset($totals[$it->ppi_id]) ? (($totals[$it->ppi_id] + 0.0001 >= $it->qty) ? 'OK' : 'NOK') : '',
            ];
        }

        $setting = (new Setting())->getSetting([
            'select' => [
                'logo',
                'company_logo',
                'company_name',
                'company_address',
                'company_phone',
                'company_email',
                'city_name',
                'state_name',
            ],
        ]);
        $companyName = trim((string) ($setting['company_name'] ?? '')) ?: 'PEGASUS HIKARI INDONESIA';
        $companyAddress = trim((string) ($setting['company_address'] ?? '')) ?: 'Pergudangan Meiko Abadi 2, Blok A8 - 9, Jl. Industri, Buduran, Sidoarjo';
        $city = trim((string) ($setting['city_name'] ?? ''));
        $state = trim((string) ($setting['state_name'] ?? ''));
        $phone = trim((string) ($setting['company_phone'] ?? ''));
        $companyContactParts = array_values(array_filter([
            $city !== '' || $state !== '' ? trim($city.($city && $state ? ', ' : '').$state) : null,
            $phone !== '' ? 'Telp. '.$phone : null,
            'Sistem ERP Pegasus',
        ]));
        $companyContact = $companyContactParts !== []
            ? implode(' • ', $companyContactParts)
            : 'Sidoarjo, Jawa Timur • Sistem ERP Pegasus';
        $companyCity = $city !== '' ? $city : 'Sidoarjo';

        $logoBase64 = self::resolveCompanyLogoBase64(
            (string) ($setting['logo'] ?? $setting['company_logo'] ?? '')
        );

        $whId = $wo->warehouse_id ?: ($pp->warehouse_id ?? null);
        $warehouse = $whId ? Warehouse::find($whId) : null;
        $warehouseName = $warehouse && ! empty($warehouse->warehouse_name)
            ? $warehouse->warehouse_name
            : 'Gudang Pusat / Utama';

        $u = session()->get('user') ?? Session::get('user');
        $printedBy = $u ? ($u->staff_name ?? $u->name ?? 'Admin') : 'Admin';
        $printedAt = Carbon::now()->translatedFormat('d F Y H:i');

        return [
            'company_name' => $companyName,
            'company_address' => $companyAddress,
            'company_contact' => $companyContact,
            'company_city' => $companyCity,
            'logo_base64' => $logoBase64,
            'warehouse_name' => $warehouseName,
            'wo_number' => $wo->wo_number,
            'wo_date' => Carbon::parse($wo->wo_date)->format('d/m/y'),
            'wo_date_long' => Carbon::parse($wo->wo_date)->translatedFormat('d F Y'),
            'pp_number' => $pp->pp_number,
            'pic_name' => $pic->staff_name ?? '—',
            'items' => $rows,
            'printed_by' => $printedBy,
            'printed_at' => $printedAt,
        ];
    }

    /**
     * Logo PDF dari Company Setting (settings.logo / company_logo).
     * WebP dikonversi ke PNG base64 agar DomPDF dapat merender gambar dengan baik.
     */
    public static function resolveCompanyLogoBase64(?string $settingPath = null): ?string
    {
        $candidates = [];
        $path = trim(str_replace('\\', '/', (string) $settingPath));
        if ($path !== '') {
            $path = ltrim($path, '/');
            if (str_starts_with($path, 'public/')) {
                $path = substr($path, 7);
            }
            $candidates[] = public_path($path);
        }
        $candidates = array_merge($candidates, [
            public_path('assets/pegasus_logo.jpg'),
            public_path('upload/logo/pegasus_logo_clean.png'),
            public_path('upload/logo/logo.png'),
            public_path('assets/img/logo.png'),
        ]);

        foreach (array_unique($candidates) as $cand) {
            if (! is_string($cand) || $cand === '' || ! is_file($cand)) {
                continue;
            }

            $ext = strtolower(pathinfo($cand, PATHINFO_EXTENSION) ?: 'png');
            $binary = @file_get_contents($cand);
            if ($binary === false || $binary === '') {
                continue;
            }

            // DomPDF: webp → png
            if ($ext === 'webp' && function_exists('imagecreatefromwebp') && function_exists('imagepng')) {
                $img = @imagecreatefromwebp($cand);
                if ($img !== false) {
                    ob_start();
                    imagepng($img);
                    $binary = (string) ob_get_clean();
                    imagedestroy($img);
                    $ext = 'png';
                }
            }

            if ($ext === 'jpg') {
                $ext = 'jpeg';
            }
            if (! in_array($ext, ['png', 'jpeg', 'gif', 'svg+xml'], true)) {
                $info = @getimagesize($cand);
                if (! empty($info['mime']) && str_starts_with((string) $info['mime'], 'image/')) {
                    return 'data:'.$info['mime'].';base64,'.base64_encode($binary);
                }
                continue;
            }

            return 'data:image/'.$ext.';base64,'.base64_encode($binary);
        }

        return null;
    }
}
