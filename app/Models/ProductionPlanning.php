<?php

namespace App\Models;

use App\Support\DataTableParams;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class ProductionPlanning extends Model
{
    protected $table = 'production_plannings';
    protected $primaryKey = 'production_planning_id';
    public $timestamps = true;
    public $incrementing = true;

    /**
     * Gudang aktif session — wajib untuk list/create PP multi-gudang.
     */
    public static function activeWarehouseId(): ?int
    {
        $id = (int) (Session::get('active_warehouse_id') ?? 0);

        return $id > 0 ? $id : null;
    }

    public static function generatePpNumber(?string $dateYmd = null, ?int $warehouseId = null): string
    {
        $date = $dateYmd ? Carbon::parse($dateYmd) : Carbon::today();
        $prefix = 'PP-'.$date->format('ymd').'-';
        $whId = $warehouseId ?: self::activeWarehouseId();
        $q = self::where('pp_number', 'like', $prefix.'%');
        if ($whId) {
            $q->where('warehouse_id', $whId);
        }
        $last = $q->orderByDesc('production_planning_id')->value('pp_number');
        $seq = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return $prefix.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Auto-draft dari shortage doc (1 doc = 1 PP). Idempotent.
     * Dari External API pengiriman: selalu ke gudang utama pertama (bukan session).
     */
    public static function createDraftFromShortage(ShipmentShortageDocument $doc): ?self
    {
        $existing = self::where('shipment_shortage_document_id', $doc->id)
            ->where('status', 1)
            ->first();
        if ($existing) {
            return $existing;
        }

        $itemsPayload = self::mapShortageItems($doc->items ?? []);
        if ($itemsPayload === []) {
            return null;
        }

        // API/cron tanpa session → gudang utama pertama.
        $whId = Warehouse::firstMainId();
        if (! $whId) {
            return null;
        }

        $pp = new self();
        $pp->pp_number = self::generatePpNumber(null, $whId);
        $pp->pp_date = Carbon::today()->toDateString();
        $pp->warehouse_id = $whId;
        $pp->pp_status = 'draft';
        $pp->shipment_shortage_document_id = $doc->id;
        $pp->so_id = $doc->so_id;
        $pp->notes = 'Auto dari Form Kekurangan '.$doc->doc_number;
        $pp->status = 1;
        $pp->created_by = $doc->created_by;
        $pp->save();

        foreach ($itemsPayload as $row) {
            $item = new ProductionPlanningItem();
            $item->production_planning_id = $pp->production_planning_id;
            $item->product_variant_id = $row['product_variant_id'];
            $item->sku = $row['sku'];
            $item->product_name = $row['product_name'];
            $item->qty = $row['qty'];
            $item->unit_id = $row['unit_id'];
            $item->unit_label = $row['unit_label'];
            $item->status = 1;
            $item->save();
        }

        return $pp;
    }

    /**
     * @param  array<int, mixed>  $shortageItems
     * @return array<int, array{product_variant_id:?int,sku:string,product_name:string,qty:float,unit_id:?int,unit_label:string}>
     */
    public static function mapShortageItems(array $shortageItems): array
    {
        $skus = [];
        $unitIds = [];
        foreach ($shortageItems as $row) {
            if (! is_array($row)) {
                continue;
            }
            $sku = trim((string) ($row['sku'] ?? ''));
            if ($sku !== '') {
                $skus[$sku] = true;
            }
            if (isset($row['unit_id']) && $row['unit_id'] !== '' && $row['unit_id'] !== null) {
                $unitIds[(int) $row['unit_id']] = true;
            }
        }

        $namesBySku = collect();
        if ($skus !== []) {
            $namesBySku = ProductVariant::query()
                ->join('products as pr', 'pr.product_id', '=', 'product_variants.product_id')
                ->whereIn('product_variants.product_variant_sku', array_keys($skus))
                ->get([
                    'product_variants.product_variant_id',
                    'product_variants.product_variant_sku',
                    'pr.product_name',
                    'product_variants.product_variant_name',
                ])
                ->mapWithKeys(function ($v) {
                    $label = trim(($v->product_name ?? '').' '.($v->product_variant_name ?? ''));

                    return [(string) $v->product_variant_sku => [
                        'product_variant_id' => (int) $v->product_variant_id,
                        'product' => $label !== '' ? $label : (string) $v->product_variant_sku,
                    ]];
                });
        }

        $unitsByRef = collect();
        $unitsById = collect();
        if ($unitIds !== []) {
            $unitIdList = array_keys($unitIds);
            $unitRows = Unit::query()
                ->where(function ($q) use ($unitIdList) {
                    $q->whereIn('ref_unit_id', $unitIdList)->orWhereIn('unit_id', $unitIdList);
                })
                ->get(['unit_id', 'ref_unit_id', 'unit_name', 'unit_short_name']);
            foreach ($unitRows as $u) {
                $label = trim((string) ($u->unit_short_name ?: $u->unit_name)) ?: '—';
                $unitsById->put((int) $u->unit_id, ['id' => (int) $u->unit_id, 'label' => $label]);
                if ($u->ref_unit_id !== null && $u->ref_unit_id !== '') {
                    $unitsByRef->put((int) $u->ref_unit_id, ['id' => (int) $u->unit_id, 'label' => $label]);
                }
            }
        }

        $out = [];
        foreach ($shortageItems as $row) {
            if (! is_array($row)) {
                continue;
            }
            $shortage = (float) ($row['shortage'] ?? 0);
            if ($shortage <= 0) {
                continue;
            }
            $sku = trim((string) ($row['sku'] ?? ''));
            $meta = $sku !== '' ? $namesBySku->get($sku) : null;
            $unitKey = isset($row['unit_id']) ? (int) $row['unit_id'] : null;
            $unitMeta = null;
            if ($unitKey !== null) {
                $unitMeta = $unitsByRef->get($unitKey) ?: $unitsById->get($unitKey);
            }
            $out[] = [
                'product_variant_id' => $meta['product_variant_id'] ?? null,
                'sku' => $sku !== '' ? $sku : '—',
                'product_name' => $meta['product'] ?? ($sku !== '' ? $sku : '—'),
                'qty' => $shortage,
                'unit_id' => $unitMeta['id'] ?? $unitKey,
                'unit_label' => $unitMeta['label'] ?? '—',
            ];
        }

        return $out;
    }

    function insertDraft(array $data)
    {
        $whId = self::activeWarehouseId();
        if (! $whId) {
            return ['status' => -1, 'message' => 'Pilih gudang aktif sebelum membuat planning'];
        }

        $items = $data['items'] ?? [];
        if (! is_array($items) || $items === []) {
            return ['status' => -1, 'message' => 'Tambahkan minimal 1 produk'];
        }

        $date = $data['pp_date'] ?? null;
        try {
            $ppDate = $date
                ? Carbon::parse($date)->toDateString()
                : Carbon::today()->toDateString();
        } catch (\Throwable $e) {
            $ppDate = Carbon::today()->toDateString();
        }

        $pp = new self();
        $pp->pp_number = self::generatePpNumber($ppDate, $whId);
        $pp->pp_date = $ppDate;
        $pp->warehouse_id = $whId;
        $pp->pp_status = 'draft';
        $shortageDocId = ! empty($data['shipment_shortage_document_id'])
            ? (int) $data['shipment_shortage_document_id']
            : null;
        if ($shortageDocId) {
            $dup = self::where('shipment_shortage_document_id', $shortageDocId)
                ->where('status', 1)
                ->exists();
            if ($dup) {
                return ['status' => -1, 'message' => 'Dokumen kekurangan ini sudah punya draft PP'];
            }
        }
        $pp->shipment_shortage_document_id = $shortageDocId;
        $pp->so_id = ! empty($data['so_id']) ? (int) $data['so_id'] : null;
        $pp->notes = trim((string) ($data['notes'] ?? '')) ?: null;
        $pp->status = 1;
        $pp->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $pp->save();

        foreach ($items as $row) {
            if (! is_array($row)) {
                continue;
            }
            $qty = (float) ($row['qty'] ?? 0);
            if ($qty <= 0) {
                continue;
            }
            $item = new ProductionPlanningItem();
            $item->production_planning_id = $pp->production_planning_id;
            $item->product_variant_id = ! empty($row['product_variant_id']) ? (int) $row['product_variant_id'] : null;
            $item->sku = trim((string) ($row['sku'] ?? '')) ?: null;
            $item->product_name = trim((string) ($row['product_name'] ?? $row['product'] ?? '')) ?: '—';
            $item->qty = $qty;
            $item->unit_id = ! empty($row['unit_id']) ? (int) $row['unit_id'] : null;
            $item->unit_label = trim((string) ($row['unit_label'] ?? $row['unit'] ?? '')) ?: '—';
            $item->status = 1;
            $item->save();
        }

        return ['status' => 1, 'production_planning_id' => $pp->production_planning_id, 'pp_number' => $pp->pp_number];
    }

    /** Draft → Released to Production (ACC saja, tanpa PIC/Skala/Armada). */
    function approveDraft(array $data)
    {
        return $this->lockedExecutionTransition($data, 'approveDraftLocked');
    }

    private function lockedExecutionTransition(array $data, string $method): array
    {
        try {
            return DB::transaction(function () use ($data, $method) {
                $wh = \App\Support\ProductionExecution::warehouse();
                if (! \App\Support\ProductionExecution::isOps($wh)) {
                    throw new \RuntimeException('Hanya Kepala Operasional gudang aktif atau Developer yang dapat release dan membagikan WO.');
                }
                // Serialize number allocation and status transitions for this warehouse.
                Warehouse::whereKey($wh)->lockForUpdate()->firstOrFail();
                self::whereKey($data['production_planning_id'] ?? 0)->where('warehouse_id', $wh)->lockForUpdate()->firstOrFail();
                return $this->$method($data);
            });
        } catch (\Illuminate\Database\QueryException $e) {
            report($e);
            return ['status'=>-1, 'message'=>'Transaksi gagal disimpan. Muat ulang planning dan coba lagi.'];
        } catch (\RuntimeException $e) {
            return ['status' => -1, 'message' => $e->getMessage()];
        }
    }

    private function approveDraftLocked(array $data)
    {
        $pp = $this->findInActiveWarehouse($data['production_planning_id'] ?? null);
        if (! $pp) {
            return ['status' => -1, 'message' => 'Planning tidak ditemukan di gudang aktif'];
        }
        if ($pp->pp_status !== 'draft') {
            return ['status' => -1, 'message' => 'Hanya draft yang bisa di-release'];
        }

        $hasItems = ProductionPlanningItem::where('production_planning_id', $pp->production_planning_id)
            ->where('status', 1)
            ->exists();
        if (! $hasItems) {
            return ['status' => -1, 'message' => 'Planning tidak punya item'];
        }

        $incoming = collect($data['items'] ?? [])->keyBy('ppi_id');
        foreach (ProductionPlanningItem::where('production_planning_id', $pp->production_planning_id)->where('status', 1)->get() as $item) {
            $row = $incoming->get($item->ppi_id, []);
            $skala = (int) ($row['production_skala_id'] ?? 0);
            $armada = (int) ($row['armada_customer_id'] ?? 0);
            $pic = (int) ($row['pic_staff_id'] ?? 0);
            if (! ProductionSkala::whereKey($skala)->where('status', 1)->exists()) throw new \RuntimeException('Pilih skala master untuk semua barang sebelum release.');
            if ($pic <= 0 || ! Staff::whereKey($pic)->where('status', 1)->exists()) {
                throw new \RuntimeException('Pilih PIC (Supervisor) untuk semua barang sebelum release.');
            }
            if ($armada && ! Customer::whereKey($armada)->where('status', 1)->exists()) throw new \RuntimeException('Armada tidak valid.');
            $item->production_skala_id = $skala;
            $item->armada_customer_id = $armada ?: null;
            $item->pic_staff_id = $pic;
            $item->save();
        }
        $pp->approval_snapshot = json_encode(\App\Support\ProductionExecution::snapshot((int) Session::get('user')->staff_id));
        $pp->spkp_number = 'SPKP-'.now()->format('ymd').'-'.str_pad((string) $pp->production_planning_id, 4, '0', STR_PAD_LEFT);
        $pp->pp_status = 'released';
        $pp->approved_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $pp->approved_at = now();
        $pp->updated_by = $pp->approved_by;
        $pp->save();

        return ['status' => 1, 'pp_number' => $pp->pp_number, 'pp_status' => 'released'];
    }

    /**
     * Soft-delete PP manual (+ items + WO aktif). PP dari Form Kekurangan pengiriman dilarang.
     */
    function deletePlanning(array $data): array
    {
        $pp = $this->findInActiveWarehouse($data['production_planning_id'] ?? null);
        if (! $pp) {
            return ['status' => -1, 'message' => 'Planning tidak ditemukan di gudang aktif'];
        }
        if ($pp->shipment_shortage_document_id) {
            return [
                'status' => -1,
                'message' => 'Planning dari Form Kekurangan Pengiriman tidak dapat dihapus.',
            ];
        }
        if ($pp->pp_status === 'done') {
            return ['status' => -1, 'message' => 'Planning yang sudah Completed tidak dapat dihapus.'];
        }
        $reason = trim((string) ($data['delete_reason'] ?? $data['notes'] ?? ''));
        if ($reason === '') {
            return ['status' => -1, 'message' => 'Isi catatan/alasan penghapusan.'];
        }
        if (ProductionWorkOrder::where('production_planning_id', $pp->production_planning_id)
            ->where('status', 1)
            ->whereNotNull('closed_at')
            ->exists()) {
            return [
                'status' => -1,
                'message' => 'Ada Work Order yang sudah selesai (stok masuk). Planning tidak dapat dihapus.',
            ];
        }

        $actor = Session::get('user') ? Session::get('user')->staff_id : null;
        $pp->notes = $reason;
        $pp->status = 0;
        $pp->updated_by = $actor;
        $pp->save();

        ProductionPlanningItem::where('production_planning_id', $pp->production_planning_id)
            ->where('status', 1)
            ->update(['status' => 0]);

        ProductionWorkOrder::where('production_planning_id', $pp->production_planning_id)
            ->where('status', 1)
            ->update(['status' => 0]);

        return ['status' => 1, 'pp_number' => $pp->pp_number];
    }

    /**
     * Released → In Production: isi PIC/Skala/Armada, buat 1 Work Order per PIC (SPV).
     * Print FORM-OPS-09 lewat /printProductionWorkOrder/{id}.
     */
    function assignWorkOrder(array $data)
    {
        return $this->lockedExecutionTransition($data, 'assignWorkOrderLocked');
    }

    private function assignWorkOrderLocked(array $data)
    {
        $pp = $this->findInActiveWarehouse($data['production_planning_id'] ?? null);
        if (! $pp) {
            return ['status' => -1, 'message' => 'Planning tidak ditemukan di gudang aktif'];
        }
        if ($pp->pp_status !== 'released') {
            return ['status' => -1, 'message' => 'Hanya status Released yang bisa masuk Work Order'];
        }

        // Items opsional: kosong = pakai Skala/PIC/Armada yang sudah disimpan saat Release.
        $itemInputs = $data['items'] ?? [];
        $useDbAssignment = ! is_array($itemInputs) || $itemInputs === [];

        $byId = [];
        if (! $useDbAssignment) {
            foreach ($itemInputs as $row) {
                if (! is_array($row) || empty($row['ppi_id'])) {
                    continue;
                }
                $byId[(int) $row['ppi_id']] = $row;
            }
        }

        $dbItems = ProductionPlanningItem::where('production_planning_id', $pp->production_planning_id)
            ->where('status', 1)
            ->get();
        if ($dbItems->isEmpty()) {
            return ['status' => -1, 'message' => 'Planning tidak punya item'];
        }

        $prepared = [];
        foreach ($dbItems as $item) {
            if ($useDbAssignment) {
                $skalaId = (int) ($item->production_skala_id ?? 0);
                $picId = (int) ($item->pic_staff_id ?? 0);
                $armadaId = (int) ($item->armada_customer_id ?? 0);
            } else {
                $in = $byId[(int) $item->ppi_id] ?? null;
                if (! $in) {
                    return ['status' => -1, 'message' => 'Data Work Order tidak lengkap untuk semua item'];
                }
                $skalaId = (int) ($in['production_skala_id'] ?? 0);
                $picId = (int) ($in['pic_staff_id'] ?? 0);
                $armadaId = (int) ($in['armada_customer_id'] ?? 0);
            }
            if ($skalaId <= 0 || $picId <= 0) {
                return ['status' => -1, 'message' => 'Setiap item wajib PIC dan Skala (isi saat Release)'];
            }
            $skalaOk = ProductionSkala::where('production_skala_id', $skalaId)->where('status', 1)->exists();
            if (! $skalaOk) {
                return ['status' => -1, 'message' => 'Master Skala tidak valid'];
            }
            $pic = Staff::where('staff_id', $picId)->where('status', 1)->first();
            $picOk = $pic && in_array((int) $pp->warehouse_id, Staff::assignedWarehouseIds($pic), true);
            if (! $picOk) {
                return ['status' => -1, 'message' => 'PIC tidak valid'];
            }
            if ($armadaId && ! Customer::where('customer_id', $armadaId)->where('status', 1)->exists()) {
                return ['status' => -1, 'message' => 'Armada tidak aktif atau tidak ditemukan'];
            }
            // Lini = header dokumen (SPKP/PP), bukan input user.
            $line = trim((string) ($pp->spkp_number ?: $pp->pp_number));
            if ($line === '') {
                return ['status' => -1, 'message' => 'Nomor PP/SPKP kosong — tidak bisa buat WO'];
            }
            $prepared[] = [
                'item' => $item,
                'skala_id' => $skalaId,
                'pic_id' => $picId,
                'armada_id' => $armadaId ?: null,
                'line' => mb_substr($line, 0, 100),
            ];
        }

        $staffId = Session::get('user') ? Session::get('user')->staff_id : null;
        $whId = (int) $pp->warehouse_id;
        $woDate = Carbon::today()->toDateString();
        $workOrdersOut = [];

        DB::transaction(function () use ($pp, $prepared, $staffId, $whId, $woDate, &$workOrdersOut) {
            if (ProductionWorkOrder::where('production_planning_id', $pp->production_planning_id)->where('status', 1)->exists()) {
                throw new \RuntimeException('WO sudah diterbitkan. Muat ulang planning.');
            }

            $byPic = [];
            foreach ($prepared as $row) {
                $byPic[$row['pic_id']][] = $row;
            }

            foreach ($byPic as $picId => $rows) {
                $wo = new ProductionWorkOrder();
                $wo->production_planning_id = $pp->production_planning_id;
                $wo->warehouse_id = $whId ?: null;
                $wo->pic_staff_id = (int) $picId;
                $wo->production_line = $rows[0]['line'];
                $wo->wo_number = ProductionWorkOrder::generateWoNumber($woDate, $whId ?: null);
                $wo->wo_date = $woDate;
                $wo->status = 1;
                $wo->created_by = $staffId;
                $wo->save();

                \App\Support\ProductionExecution::initializeMaterials($wo);

                foreach ($rows as $row) {
                    /** @var ProductionPlanningItem $item */
                    $item = $row['item'];
                    $item->production_skala_id = $row['skala_id'];
                    $item->pic_staff_id = $row['pic_id'];
                    $item->armada_customer_id = $row['armada_id'];
                    $item->production_work_order_id = $wo->production_work_order_id;
                    $item->save();
                }

                $picName = Staff::find($picId)->staff_name ?? '—';
                $workOrdersOut[] = [
                    'production_work_order_id' => (int) $wo->production_work_order_id,
                    'wo_number' => $wo->wo_number,
                    'pic_staff_id' => (int) $picId,
                    'pic_name' => $picName,
                    'print_url' => url('/printProductionWorkOrder/'.$wo->production_work_order_id),
                ];
            }

            // Simpan WO = langsung In Production (satu lembar WO per PIC)
            $pp->pp_status = 'inprod';
            $pp->updated_by = $staffId;
            $pp->save();
        });

        return [
            'status' => 1,
            'pp_number' => $pp->pp_number,
            'pp_status' => 'inprod',
            'work_orders' => $workOrdersOut,
        ];
    }

    function getDetail($id)
    {
        $pp = $this->findInActiveWarehouse($id);
        if (! $pp) {
            return null;
        }

        return $this->presentPlanning($pp, true);
    }

    /** @return self|null */
    private function findInActiveWarehouse($id): ?self
    {
        $whId = self::activeWarehouseId();
        if (! $whId || ! $id) {
            return null;
        }

        return self::where('production_planning_id', $id)
            ->where('warehouse_id', $whId)
            ->where('status', 1)
            ->first();
    }

    /**
     * Self-heal: inprod + semua WO aktif sudah production_completed_at → done.
     * Agar Histori / kartu Selesai tidak kosong untuk data lama yang macet di inprod.
     */
    public static function healDoneFromCompletedWorkOrders(int $whId): void
    {
        if ($whId <= 0) {
            return;
        }
        $ids = self::where('warehouse_id', $whId)
            ->where('status', 1)
            ->where('pp_status', 'inprod')
            ->whereExists(function ($q) {
                $q->selectRaw('1')
                    ->from('production_work_orders as wo')
                    ->whereColumn('wo.production_planning_id', 'production_plannings.production_planning_id')
                    ->where('wo.status', 1);
            })
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')
                    ->from('production_work_orders as wo')
                    ->whereColumn('wo.production_planning_id', 'production_plannings.production_planning_id')
                    ->where('wo.status', 1)
                    ->whereNull('wo.production_completed_at');
            })
            ->pluck('production_planning_id');
        if ($ids->isEmpty()) {
            return;
        }
        self::whereIn('production_planning_id', $ids->all())->update(['pp_status' => 'done']);
    }

    function paginateList(array $data): array
    {
        $dt = DataTableParams::from($data);
        $whId = self::activeWarehouseId();
        if (! $whId) {
            return [
                'draw' => $dt['draw'],
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'counts' => [
                    'draft' => 0,
                    'released' => 0,
                    'work_order' => 0,
                    'inprod' => 0,
                    'done' => 0,
                    'wo_active' => 0,
                ],
                'tabs' => ['planning' => 0, 'job' => 0, 'histori' => 0, 'bahan' => 0],
                'stamp' => '0',
            ];
        }

        $allowed = ['draft', 'released', 'work_order', 'inprod', 'done'];

        // Self-heal: inprod yang semua WO sudah production-complete → done (Histori/Selesai).
        self::healDoneFromCompletedWorkOrders($whId);

        // Scope KPI = tanggal/produk/PIC (status dropdown tidak mengecilkan kartu ringkasan).
        $scope = self::where('status', 1)->where('warehouse_id', $whId);
        $dateFrom = null;
        $dateTo = null;
        $stageHint = trim((string) ($data['stage'] ?? ''));
        // Histori label "Tanggal Selesai" → filter tgl selesai WO, bukan pp_date.
        $filterByCompletion = $stageHint === 'histori';
        if (! empty($data['date_from'])) {
            try {
                $dateFrom = Carbon::parse($data['date_from'])->toDateString();
                if (! $filterByCompletion) {
                    $scope->where('pp_date', '>=', $dateFrom);
                }
            } catch (\Throwable $e) {
            }
        }
        if (! empty($data['date_to'])) {
            try {
                $dateTo = Carbon::parse($data['date_to'])->toDateString();
                if (! $filterByCompletion) {
                    $scope->where('pp_date', '<=', $dateTo);
                }
            } catch (\Throwable $e) {
            }
        }
        if ($filterByCompletion && ($dateFrom || $dateTo)) {
            $scope->whereRaw(
                '(SELECT DATE(MAX(COALESCE(wo.production_completed_at, wo.closed_at)))
                  FROM production_work_orders wo
                  WHERE wo.production_planning_id = production_plannings.production_planning_id
                    AND wo.status = 1) IS NOT NULL'
            );
            if ($dateFrom) {
                $scope->whereRaw(
                    '(SELECT DATE(MAX(COALESCE(wo.production_completed_at, wo.closed_at)))
                      FROM production_work_orders wo
                      WHERE wo.production_planning_id = production_plannings.production_planning_id
                        AND wo.status = 1) >= ?',
                    [$dateFrom]
                );
            }
            if ($dateTo) {
                $scope->whereRaw(
                    '(SELECT DATE(MAX(COALESCE(wo.production_completed_at, wo.closed_at)))
                      FROM production_work_orders wo
                      WHERE wo.production_planning_id = production_plannings.production_planning_id
                        AND wo.status = 1) <= ?',
                    [$dateTo]
                );
            }
        }
        if (! empty($data['product_variant_id'])) {
            $vid = (int) $data['product_variant_id'];
            $scope->whereExists(function ($q) use ($vid) {
                $q->selectRaw('1')
                    ->from('production_planning_items as ppi')
                    ->whereColumn('ppi.production_planning_id', 'production_plannings.production_planning_id')
                    ->where('ppi.status', 1)
                    ->where('ppi.product_variant_id', $vid);
            });
        }
        if (! empty($data['pic_staff_id'])) {
            $sid = (int) $data['pic_staff_id'];
            $scope->whereExists(function ($q) use ($sid) {
                $q->selectRaw('1')
                    ->from('production_planning_items as ppi')
                    ->whereColumn('ppi.production_planning_id', 'production_plannings.production_planning_id')
                    ->where('ppi.status', 1)
                    ->where('ppi.pic_staff_id', $sid);
            });
        }

        // Tabel: scope + stage/status.
        $base = clone $scope;
        $stage = trim((string) ($data['stage'] ?? ''));
        $stageMap = [
            'planning' => ['draft', 'released', 'inprod'],
            'job' => ['work_order', 'inprod'],
            'histori' => ['done'],
        ];
        $stageStatuses = $stageMap[$stage] ?? null;

        $ppStatus = $data['pp_status'] ?? $data['status'] ?? null;
        $ppStatuses = $data['pp_statuses'] ?? null;
        if (is_string($ppStatuses) && $ppStatuses !== '') {
            $ppStatuses = array_values(array_filter(array_map('trim', explode(',', $ppStatuses))));
        }
        if (is_array($ppStatuses) && $ppStatuses !== []) {
            $filteredStatuses = array_values(array_intersect($ppStatuses, $allowed));
            if ($stageStatuses !== null) {
                $filteredStatuses = array_values(array_intersect($filteredStatuses, $stageStatuses));
            }
            if ($filteredStatuses !== []) {
                $base->whereIn('pp_status', $filteredStatuses);
            } elseif ($stageStatuses !== null) {
                $base->whereIn('pp_status', $stageStatuses);
            }
        } elseif ($ppStatus && in_array($ppStatus, $allowed, true)) {
            if ($stageStatuses === null || in_array($ppStatus, $stageStatuses, true)) {
                $base->where('pp_status', $ppStatus);
            } else {
                $base->whereRaw('1 = 0');
            }
        } elseif ($stageStatuses !== null) {
            $base->whereIn('pp_status', $stageStatuses);
        }

        $recordsTotal = self::where('status', 1)->where('warehouse_id', $whId)->count();
        $filtered = clone $base;
        $search = $dt['search'] ?? '';
        if ($search !== '') {
            $like = '%'.$search.'%';
            $filtered->where(function ($q) use ($like) {
                $q->where('pp_number', 'like', $like)
                    ->orWhere('notes', 'like', $like)
                    ->orWhereExists(function ($sq) use ($like) {
                        $sq->selectRaw('1')
                            ->from('production_planning_items as ppi')
                            ->whereColumn('ppi.production_planning_id', 'production_plannings.production_planning_id')
                            ->where('ppi.status', 1)
                            ->where(function ($w) use ($like) {
                                $w->where('ppi.product_name', 'like', $like)
                                    ->orWhere('ppi.sku', 'like', $like);
                            });
                    });
            });
        }
        $recordsFiltered = (clone $filtered)->count('production_planning_id');

        $rows = (clone $filtered)
            ->orderByDesc('pp_date')
            ->orderByDesc('production_planning_id')
            ->skip($dt['start'])
            ->take($dt['length'] > 0 ? $dt['length'] : 25)
            ->get();

        $presented = $this->presentPlanningList($rows);

        if (! empty($data['skip_metrics'])) {
            return [
                'draw' => $dt['draw'],
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $presented,
            ];
        }

        // KPI cards ikut filter tanggal/produk/PIC (bukan total gudang).
        $countRows = (clone $scope)
            ->selectRaw('pp_status, COUNT(*) as c')
            ->groupBy('pp_status')
            ->pluck('c', 'pp_status');
        $counts = [
            'draft' => (int) ($countRows['draft'] ?? 0),
            'released' => (int) ($countRows['released'] ?? 0),
            'work_order' => (int) ($countRows['work_order'] ?? 0),
            'inprod' => (int) ($countRows['inprod'] ?? 0),
            'done' => (int) ($countRows['done'] ?? 0),
        ];

        $woQ = ProductionWorkOrder::where('warehouse_id', $whId)
            ->where('status', 1)
            ->where('execution_status', 'inprod');
        if ($dateFrom) {
            $woQ->where('wo_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $woQ->where('wo_date', '<=', $dateTo);
        }
        if (! empty($data['pic_staff_id'])) {
            $woQ->where('pic_staff_id', (int) $data['pic_staff_id']);
        }
        if (! empty($data['product_variant_id'])) {
            $vid = (int) $data['product_variant_id'];
            $woQ->whereExists(function ($q) use ($vid) {
                $q->selectRaw('1')
                    ->from('production_planning_items as ppi')
                    ->whereColumn('ppi.production_work_order_id', 'production_work_orders.production_work_order_id')
                    ->where('ppi.status', 1)
                    ->where('ppi.product_variant_id', $vid);
            });
        }
        $counts['wo_active'] = (int) $woQ->count();

        $live = self::liveMetrics($whId, [
            'draft' => 0, 'released' => 0, 'work_order' => 0, 'inprod' => 0, 'done' => 0,
        ]);
        $counts['bahan_pending'] = (int) ($live['counts']['bahan_pending'] ?? 0);

        return [
            'draw' => $dt['draw'],
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $presented,
            'counts' => $counts,
            'tabs' => [
                'planning' => $counts['draft'] + $counts['released'] + $counts['inprod'],
                'job' => $counts['wo_active'],
                'bahan' => $counts['bahan_pending'],
                'histori' => $counts['done'],
            ],
            'stamp' => $live['stamp'],
        ];
    }

    /**
     * 4 kartu stage bawah — 1 round-trip, ikut filter tanggal/produk/PIC Planning.
     *
     * @param  array<string, mixed>  $data
     * @return array{cards: array<string, array{total:int, data: array<int, array<string, mixed>>}>}
     */
    public static function stageMiniCards(array $data = []): array
    {
        $whId = self::activeWarehouseId();
        $statuses = ['released', 'work_order', 'inprod', 'done'];
        $empty = ['cards' => array_fill_keys($statuses, ['total' => 0, 'data' => []])];
        if (! $whId) {
            return $empty;
        }

        self::healDoneFromCompletedWorkOrders($whId);

        $scope = self::where('status', 1)->where('warehouse_id', $whId);
        if (! empty($data['date_from'])) {
            try {
                $scope->where('pp_date', '>=', Carbon::parse($data['date_from'])->toDateString());
            } catch (\Throwable $e) {
            }
        }
        if (! empty($data['date_to'])) {
            try {
                $scope->where('pp_date', '<=', Carbon::parse($data['date_to'])->toDateString());
            } catch (\Throwable $e) {
            }
        }
        if (! empty($data['product_variant_id'])) {
            $vid = (int) $data['product_variant_id'];
            $scope->whereExists(function ($q) use ($vid) {
                $q->selectRaw('1')
                    ->from('production_planning_items as ppi')
                    ->whereColumn('ppi.production_planning_id', 'production_plannings.production_planning_id')
                    ->where('ppi.status', 1)
                    ->where('ppi.product_variant_id', $vid);
            });
        }
        if (! empty($data['pic_staff_id'])) {
            $sid = (int) $data['pic_staff_id'];
            $scope->whereExists(function ($q) use ($sid) {
                $q->selectRaw('1')
                    ->from('production_planning_items as ppi')
                    ->whereColumn('ppi.production_planning_id', 'production_plannings.production_planning_id')
                    ->where('ppi.status', 1)
                    ->where('ppi.pic_staff_id', $sid);
            });
        }

        $totals = (clone $scope)
            ->whereIn('pp_status', $statuses)
            ->selectRaw('pp_status, COUNT(*) as c')
            ->groupBy('pp_status')
            ->pluck('c', 'pp_status');

        $idsByStatus = [];
        foreach ($statuses as $st) {
            $idsByStatus[$st] = (clone $scope)
                ->where('pp_status', $st)
                ->orderByDesc('pp_date')
                ->orderByDesc('production_planning_id')
                ->limit(4)
                ->pluck('production_planning_id')
                ->all();
        }
        $merged = [];
        foreach ($idsByStatus as $ids) {
            foreach ($ids as $id) {
                $merged[] = $id;
            }
        }
        $allIds = array_values(array_unique($merged));
        $rowsById = $allIds === []
            ? collect()
            : self::whereIn('production_planning_id', $allIds)->get()->keyBy('production_planning_id');

        $presenter = new self;
        $cards = [];
        foreach ($statuses as $st) {
            $rows = collect($idsByStatus[$st])
                ->map(fn ($id) => $rowsById->get($id))
                ->filter();
            $cards[$st] = [
                'total' => (int) ($totals[$st] ?? 0),
                'data' => $presenter->presentPlanningList($rows),
            ];
        }

        return ['cards' => $cards];
    }

    /**
     * Ringkasan ringan untuk polling realtime (KPI + badge tab + stamp perubahan).
     *
     * @return array{counts: array<string,int>, tabs: array<string,int>, stamp: string}
     */
    public static function liveSummary(): array
    {
        $whId = self::activeWarehouseId();
        if (! $whId) {
            return [
                'counts' => [
                    'draft' => 0,
                    'released' => 0,
                    'work_order' => 0,
                    'inprod' => 0,
                    'done' => 0,
                    'wo_active' => 0,
                ],
                'tabs' => ['planning' => 0, 'job' => 0, 'histori' => 0, 'bahan' => 0],
                'stamp' => '0',
            ];
        }

        $countRows = self::where('status', 1)
            ->where('warehouse_id', $whId)
            ->selectRaw('pp_status, COUNT(*) as c')
            ->groupBy('pp_status')
            ->pluck('c', 'pp_status');
        $counts = [
            'draft' => (int) ($countRows['draft'] ?? 0),
            'released' => (int) ($countRows['released'] ?? 0),
            'work_order' => (int) ($countRows['work_order'] ?? 0),
            'inprod' => (int) ($countRows['inprod'] ?? 0),
            'done' => (int) ($countRows['done'] ?? 0),
        ];

        return self::liveMetrics($whId, $counts);
    }

    /**
     * @param  array{draft:int,released:int,work_order:int,inprod:int,done:int}  $counts
     * @return array{counts: array<string,int>, tabs: array<string,int>, stamp: string}
     */
    private static function liveMetrics(int $whId, array $counts): array
    {
        // Satu round-trip: WO aktif + bahan pending + stamp perubahan.
        $meta = DB::selectOne(
            'SELECT
                (SELECT COUNT(*) FROM production_work_orders
                    WHERE warehouse_id = ? AND status = 1 AND execution_status = ?) AS wo_active,
                (SELECT COUNT(*) FROM production_execution_documents
                    WHERE warehouse_id = ?
                      AND type IN (?, ?)
                      AND document_status IN (?, ?)) AS bahan_pending,
                (SELECT MAX(production_planning_id) FROM production_plannings
                    WHERE warehouse_id = ? AND status = 1) AS pp_max_id,
                (SELECT MAX(updated_at) FROM production_plannings
                    WHERE warehouse_id = ? AND status = 1) AS pp_max_at,
                (SELECT MAX(production_work_order_id) FROM production_work_orders
                    WHERE warehouse_id = ? AND status = 1) AS wo_max_id,
                (SELECT MAX(updated_at) FROM production_work_orders
                    WHERE warehouse_id = ? AND status = 1) AS wo_max_at',
            [
                $whId, 'inprod',
                $whId, 'material_issue', 'material_return', 'awaiting_ops', 'awaiting_qc',
                $whId,
                $whId,
                $whId,
                $whId,
            ]
        );

        $woActive = (int) ($meta->wo_active ?? 0);
        $bahanPending = (int) ($meta->bahan_pending ?? 0);
        $counts['wo_active'] = $woActive;
        $counts['bahan_pending'] = $bahanPending;
        $tabs = [
            'planning' => $counts['draft'] + $counts['released'] + $counts['inprod'],
            'job' => $woActive,
            'bahan' => $bahanPending,
            'histori' => $counts['done'],
        ];
        $stamp = implode('|', [
            (string) ((int) ($meta->pp_max_id ?? 0)),
            (string) ($meta->pp_max_at ?? ''),
            (string) ((int) ($meta->wo_max_id ?? 0)),
            (string) ($meta->wo_max_at ?? ''),
            (string) $tabs['planning'],
            (string) $tabs['job'],
            (string) $tabs['bahan'],
            (string) $tabs['histori'],
        ]);

        return ['counts' => $counts, 'tabs' => $tabs, 'stamp' => $stamp];
    }

    /**
     * List: batch load items + PIC (hindari N+1 presentPlanning).
     *
     * @param  \Illuminate\Support\Collection<int, self>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function presentPlanningList($rows): array
    {
        if ($rows->isEmpty()) {
            return [];
        }

        $ids = $rows->pluck('production_planning_id')->all();
        $items = ProductionPlanningItem::whereIn('production_planning_id', $ids)
            ->where('status', 1)
            ->orderBy('ppi_id')
            ->get()
            ->groupBy('production_planning_id');

        $picIds = $items->flatten(1)->pluck('pic_staff_id')->filter()->unique()->all();
        $staffs = $picIds
            ? Staff::whereIn('staff_id', $picIds)->get(['staff_id', 'staff_name'])->keyBy('staff_id')
            : collect();

        $creatorIds = $rows->pluck('created_by')->filter()->unique()->all();
        $creators = $creatorIds
            ? Staff::whereIn('staff_id', $creatorIds)->get(['staff_id', 'staff_name'])->keyBy('staff_id')
            : collect();

        $woByPp = ProductionWorkOrder::whereIn('production_planning_id', $ids)
            ->where('status', 1)
            ->orderBy('production_work_order_id')
            ->get()
            ->groupBy('production_planning_id');
        $woPicIds = $woByPp->flatten(1)->pluck('pic_staff_id')->filter()->unique()->all();
        $woStaffs = $woPicIds
            ? Staff::whereIn('staff_id', $woPicIds)->get(['staff_id', 'staff_name'])->keyBy('staff_id')
            : collect();

        $out = [];
        foreach ($rows as $pp) {
            $ppItems = $items->get($pp->production_planning_id) ?? collect();
            $qtySum = 0.0;
            $qtyByUnit = [];
            $firstPic = null;
            foreach ($ppItems as $i => $it) {
                $qtySum += (float) $it->qty;
                $u = $it->unit_label ?: '—';
                $qtyByUnit[$u] = ($qtyByUnit[$u] ?? 0.0) + (float) $it->qty;
                if ($i === 0 && $it->pic_staff_id) {
                    $firstPic = $staffs->get($it->pic_staff_id)->staff_name ?? null;
                }
            }
            $itemCount = $ppItems->count();
            $first = $ppItems->first();
            $productLabel = $itemCount > 1
                ? $itemCount.' Item'
                : ($first->product_name ?? '—');
            $skuLabel = $itemCount > 1
                ? $itemCount.' item'
                : ($first->sku ?? '—');
            $unitKeys = array_keys($qtyByUnit);
            $unitLabel = count($unitKeys) === 1 ? $unitKeys[0] : (count($unitKeys) > 1 ? 'multi' : '—');
            $qtyLines = [];
            foreach ($qtyByUnit as $u => $q) {
                $qtyLines[] = ['qty' => $q, 'unit' => $u];
            }

            $workOrders = [];
            $woDone = 0;
            foreach ($woByPp->get($pp->production_planning_id) ?? [] as $wo) {
                $isClosed = ! empty($wo->closed_at);
                if ($isClosed) {
                    $woDone++;
                }
                $workOrders[] = [
                    'production_work_order_id' => (int) $wo->production_work_order_id,
                    'wo_number' => $wo->wo_number,
                    'pic_name' => $woStaffs->get($wo->pic_staff_id)->staff_name ?? '—',
                    'execution_status' => $wo->execution_status ?: 'inprod',
                    'is_closed' => $isClosed,
                    'closed_at' => $wo->closed_at
                        ? Carbon::parse($wo->closed_at)->format('d M Y H:i')
                        : null,
                    'print_url' => url('/printProductionWorkOrder/'.$wo->production_work_order_id),
                ];
            }
            $woTotal = count($workOrders);

            $out[] = [
                'production_planning_id' => (int) $pp->production_planning_id,
                'warehouse_id' => $pp->warehouse_id ? (int) $pp->warehouse_id : null,
                'spkp_number' => $pp->spkp_number,
            'approval_snapshot' => json_decode($pp->approval_snapshot ?? 'null', true),
            'code' => $pp->pp_number,
                'pp_number' => $pp->pp_number,
                'date' => Carbon::parse($pp->pp_date)->format('d M Y'),
                'date_iso' => Carbon::parse($pp->pp_date)->format('Y-m-d'),
                'product' => $productLabel,
                'sku' => $skuLabel,
                'qty' => $qtySum,
                'unit' => $unitLabel,
                'qty_by_unit' => $qtyLines,
                'status' => $pp->pp_status,
                'pp_status' => $pp->pp_status,
                'wo_total' => $woTotal,
                'wo_done' => $woDone,
                'created_by' => $pp->created_by
                    ? ($creators->get($pp->created_by)->staff_name ?? '-')
                    : '-',
                'notes' => $pp->notes,
                'so_id' => $pp->so_id ? (int) $pp->so_id : null,
                'shipment_shortage_document_id' => $pp->shipment_shortage_document_id
                    ? (int) $pp->shipment_shortage_document_id
                    : null,
                // Hapus hanya PP manual (bukan dari Form Kekurangan / pengiriman)
                'from_shipment' => (bool) $pp->shipment_shortage_document_id,
                'can_delete' => ! $pp->shipment_shortage_document_id && $pp->pp_status !== 'done',
                'item_count' => $itemCount,
                'pic_name' => $firstPic,
                'approved_at' => $pp->approved_at,
                'work_orders' => $workOrders,
            ];
        }

        return $out;
    }

    function presentPlanning(self $pp, bool $withItems): array
    {
        $items = ProductionPlanningItem::where('production_planning_id', $pp->production_planning_id)
            ->where('status', 1)
            ->orderBy('ppi_id')
            ->get();

        $qtySum = 0.0;
        $productLabel = '—';
        $skuLabel = '—';
        $unitLabel = '—';
        $units = [];
        $itemRows = [];

        $skalaIds = $items->pluck('production_skala_id')->filter()->unique()->all();
        $picIds = $items->pluck('pic_staff_id')->filter()->unique()->all();
        $armadaIds = $items->pluck('armada_customer_id')->filter()->unique()->all();

        $skalas = $skalaIds
            ? ProductionSkala::whereIn('production_skala_id', $skalaIds)->get()->keyBy('production_skala_id')
            : collect();
        $staffs = $picIds
            ? Staff::whereIn('staff_id', $picIds)->get(['staff_id', 'staff_name'])->keyBy('staff_id')
            : collect();
        $armadas = $armadaIds
            ? Customer::whereIn('customer_id', $armadaIds)->get(['customer_id', 'customer_notes', 'customer_name'])->keyBy('customer_id')
            : collect();

        $qtyByUnit = [];
        foreach ($items as $i => $it) {
            $qtySum += (float) $it->qty;
            if ($i === 0) {
                $productLabel = $it->product_name;
                $skuLabel = $it->sku ?: '—';
            }
            $u = $it->unit_label ?: '—';
            $units[$u] = true;
            if (! isset($qtyByUnit[$u])) {
                $qtyByUnit[$u] = 0.0;
            }
            $qtyByUnit[$u] += (float) $it->qty;

            $skala = $skalas->get($it->production_skala_id);
            $pic = $staffs->get($it->pic_staff_id);
            $armada = $armadas->get($it->armada_customer_id);

            $itemRows[] = [
                'ppi_id' => (int) $it->ppi_id,
                'product_variant_id' => $it->product_variant_id ? (int) $it->product_variant_id : null,
                'sku' => $it->sku,
                'product' => $it->product_name,
                'product_name' => $it->product_name,
                'qty' => (float) $it->qty,
                'actual_qty' => $it->actual_qty !== null ? (float) $it->actual_qty : null,
                'unit_id' => $it->unit_id ? (int) $it->unit_id : null,
                'unit' => $u,
                'unit_label' => $u,
                'production_skala_id' => $it->production_skala_id ? (int) $it->production_skala_id : null,
                'skala_code' => $skala->code ?? null,
                'skala_label' => $skala
                    ? trim($skala->code.' — '.$skala->name.($skala->combo_label ? ' ('.$skala->combo_label.')' : ''))
                    : null,
                'pic_staff_id' => $it->pic_staff_id ? (int) $it->pic_staff_id : null,
                'pic_name' => $pic->staff_name ?? null,
                'armada_customer_id' => $it->armada_customer_id ? (int) $it->armada_customer_id : null,
                'armada_name' => $armada
                    ? trim((string) ($armada->customer_notes ?: $armada->customer_name))
                    : null,
            ];
        }

        if ($items->count() > 1) {
            $skuLabel = $items->count().' item';
            $productLabel = $items->count().' Item';
        }
        $unitKeys = array_keys($units);
        $unitLabel = count($unitKeys) === 1 ? $unitKeys[0] : (count($unitKeys) > 1 ? 'multi' : '—');

        $qtyLines = [];
        foreach ($qtyByUnit as $u => $q) {
            $qtyLines[] = ['qty' => $q, 'unit' => $u];
        }

        $createdBy = $pp->created_by
            ? (Staff::find($pp->created_by)->staff_name ?? '-')
            : '-';

        $out = [
            'production_planning_id' => (int) $pp->production_planning_id,
            'warehouse_id' => $pp->warehouse_id ? (int) $pp->warehouse_id : null,
            'spkp_number' => $pp->spkp_number,
            'approval_snapshot' => json_decode($pp->approval_snapshot ?? 'null', true),
            'code' => $pp->pp_number,
            'pp_number' => $pp->pp_number,
            'date' => Carbon::parse($pp->pp_date)->format('d M Y'),
            'date_iso' => Carbon::parse($pp->pp_date)->format('Y-m-d'),
            'product' => $productLabel,
            'sku' => $skuLabel,
            'qty' => $qtySum,
            'unit' => $unitLabel,
            'qty_by_unit' => $qtyLines,
            'status' => $pp->pp_status,
            'pp_status' => $pp->pp_status,
            'created_by' => $createdBy,
            'notes' => $pp->notes,
            'so_id' => $pp->so_id ? (int) $pp->so_id : null,
            'shipment_shortage_document_id' => $pp->shipment_shortage_document_id
                ? (int) $pp->shipment_shortage_document_id
                : null,
            'from_shipment' => (bool) $pp->shipment_shortage_document_id,
            'can_delete' => ! $pp->shipment_shortage_document_id && $pp->pp_status !== 'done',
            'item_count' => $items->count(),
            'pic_name' => $itemRows[0]['pic_name'] ?? null,
            'approved_at' => $pp->approved_at,
        ];

        // Release / assign WO: Kepala Ops gudang aktif atau Developer (sama gate backend)
        $canOps = false;
        try {
            $whForOps = (int) ($pp->warehouse_id ?: 0);
            $canOps = $whForOps > 0 && \App\Support\ProductionExecution::isOps($whForOps);
        } catch (\Throwable $e) {
            $canOps = false;
        }
        $out['can_release'] = $canOps && $pp->pp_status === 'draft';
        $out['can_assign_wo'] = $canOps && $pp->pp_status === 'released';

        if ($withItems) {
            $out['items'] = $itemRows;
            $workOrders = $this->presentWorkOrders((int) $pp->production_planning_id);
            $out['work_orders'] = $workOrders;
            $out['wo_total'] = count($workOrders);
            $out['wo_done'] = count(array_filter($workOrders, static fn ($w) => ! empty($w['is_closed'])));
        }

        return $out;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function presentWorkOrders(int $ppId): array
    {
        $wos = ProductionWorkOrder::where('production_planning_id', $ppId)
            ->where('status', 1)
            ->orderBy('production_work_order_id')
            ->get([
                'production_work_order_id',
                'wo_number',
                'wo_date',
                'pic_staff_id',
                'execution_status',
                'closed_at',
                'production_completed_at',
            ]);
        if ($wos->isEmpty()) {
            return [];
        }

        $picIds = $wos->pluck('pic_staff_id')->filter()->unique()->all();
        $staffs = $picIds
            ? Staff::whereIn('staff_id', $picIds)->get(['staff_id', 'staff_name'])->keyBy('staff_id')
            : collect();

        $out = [];
        foreach ($wos as $wo) {
            $isClosed = ! empty($wo->closed_at);
            $out[] = [
                'production_work_order_id' => (int) $wo->production_work_order_id,
                'wo_number' => $wo->wo_number,
                'wo_date' => Carbon::parse($wo->wo_date)->format('d M Y'),
                'pic_staff_id' => (int) $wo->pic_staff_id,
                'pic_name' => $staffs->get($wo->pic_staff_id)->staff_name ?? '—',
                // closed_at = benar-benar selesai (PP auto-done saat semua closed)
                'execution_status' => $isClosed
                    ? 'done'
                    : ($wo->execution_status ?: 'inprod'),
                'is_closed' => $isClosed,
                'closed_at' => $wo->closed_at
                    ? Carbon::parse($wo->closed_at)->format('d M Y H:i')
                    : null,
                'production_completed_at' => $wo->production_completed_at
                    ? Carbon::parse($wo->production_completed_at)->format('d M Y H:i')
                    : null,
                'print_url' => url('/printProductionWorkOrder/'.$wo->production_work_order_id),
            ];
        }

        return $out;
    }
}
