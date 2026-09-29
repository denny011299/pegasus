<?php

namespace App\Support;

use App\Models\{Bom, BomDetail, LogStock, Product, ProductVariant, ProductionExecutionDocument, ProductionOutputReport,
    ProductionPlanning, ProductionPlanningItem, ProductionWorkOrder, Staff, Supplies, SuppliesStock, Unit};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use RuntimeException;

/** All writes lock the WO before its documents/items; stock moves only at final QC. */
class ProductionExecution
{
    public static function actor(): object
    {
        $user = Session::get('user');
        if (! $user || ! Staff::where('staff_id', $user->staff_id)->where('status', 1)->exists()) {
            throw new RuntimeException('Staf login tidak aktif.');
        }
        return $user;
    }

    public static function snapshot(int $staffId, bool $required = true): array
    {
        $staff = Staff::where('staff_id', $staffId)->where('status', 1)->firstOrFail();
        if ($required && ! $staff->signature_data_uri) {
            throw new RuntimeException('Lengkapi tanda tangan '.$staff->staff_name.' di Master Staf.');
        }
        return ['staff_id' => $staffId, 'name' => $staff->staff_name, 'signature' => $staff->signature_data_uri];
    }

    public static function isOps(int $warehouseId): bool
    {
        $user = self::actor();
        return StockTransferApproval::isKepalaOfWarehouse((int) $user->staff_id, $warehouseId)
            || (int) $user->role_id === -1 || (int) $user->role_id === RoleIds::DEVELOPER;
    }

    public static function isQc(int $warehouseId): bool
    {
        $user = self::actor();
        return StockTransferApproval::isQcAssignedToWarehouse($user, $warehouseId)
            || (int) $user->role_id === -1 || (int) $user->role_id === RoleIds::DEVELOPER;
    }

    public static function warehouse(): int
    {
        $user = self::actor();
        $wh = (int) Session::get('active_warehouse_id');
        if ($wh <= 0 || (! in_array((int) $user->role_id, [-1, RoleIds::DEVELOPER], true)
            && ! in_array($wh, Staff::assignedWarehouseIds($user), true))) {
            throw new RuntimeException('Pilih gudang yang ditugaskan kepada Anda.');
        }
        return $wh;
    }

    public static function workOrder(int $id, bool $lock = false): ProductionWorkOrder
    {
        $wh = self::warehouse();
        $query = ProductionWorkOrder::where('production_work_order_id', $id)->where('warehouse_id', $wh)->where('status', 1);
        if ($lock) $query->lockForUpdate();
        $wo = $query->first();
        if (! $wo) throw new RuntimeException('Work Order tidak ditemukan di gudang aktif.');
        return $wo;
    }

    private static function assertPic(ProductionWorkOrder $wo): void
    {
        $user = self::actor();
        if ((int) $wo->pic_staff_id !== (int) $user->staff_id
            && ! in_array((int) $user->role_id, [-1, RoleIds::DEVELOPER], true)) {
            throw new RuntimeException('Hanya SPV/PIC Work Order yang dapat mengonfirmasi hasil dan bahan.');
        }
    }

    private static function requestId(array $data): string
    {
        $id = trim((string) ($data['request_id'] ?? ''));
        if (! preg_match('/^[A-Za-z0-9_-]{12,64}$/', $id)) throw new RuntimeException('ID konfirmasi tidak valid. Muat ulang form.');
        return $id;
    }

    private static function qty($value, bool $allowZero = false): float
    {
        if (! is_numeric($value) || ! is_finite((float) $value) || (float) $value > 100000000
            || ($allowZero ? (float) $value < 0 : (float) $value <= 0)) {
            throw new RuntimeException('Qty wajib angka positif yang valid.');
        }
        $rounded = round((float) $value, 4);
        if (! $allowZero && $rounded <= 0) throw new RuntimeException('Qty terlalu kecil.');
        return $rounded;
    }

    private static function event(ProductionWorkOrder $wo, string $action, ?int $docId = null, array $meta = []): void
    {
        DB::table('production_execution_events')->insert([
            'production_work_order_id' => $wo->production_work_order_id, 'document_id' => $docId,
            'action' => $action, 'staff_id' => self::actor()->staff_id,
            'meta' => json_encode($meta), 'occurred_at' => now(),
        ]);
    }

    /**
     * Pallet di Hasil WO: dari `qty_per_pallet` master varian, atau infer dari
     * satuan Pallet/Palete/Palet di product_unit + product_relations.
     *
     * @return array{qty:int, base_unit_id:int, base_unit_name:string, pallet_unit_id:?int}
     */
    public static function resolvePalletMeta(?ProductVariant $variant, ?Product $product, int $targetUnitId): array
    {
        $empty = ['qty' => 0, 'base_unit_id' => 0, 'base_unit_name' => '', 'pallet_unit_id' => null];
        if (! $variant || ! $product || $targetUnitId <= 0) {
            return $empty;
        }

        $variantId = (int) $variant->product_variant_id;
        $baseUnitId = (int) $product->unit_id;
        $baseUnit = $baseUnitId > 0 ? Unit::find($baseUnitId) : null;
        $baseName = $baseUnit
            ? (string) ($baseUnit->unit_short_name ?: $baseUnit->unit_name)
            : '';

        $fromVariant = (int) ($variant->qty_per_pallet ?? 0);
        if ($fromVariant > 0 && $baseUnitId > 0) {
            return [
                'qty' => $fromVariant,
                'base_unit_id' => $baseUnitId,
                'base_unit_name' => $baseName,
                'pallet_unit_id' => self::findPalletUnitId($product),
            ];
        }

        $palletUnitId = self::findPalletUnitId($product);
        if ($palletUnitId === null) {
            return $empty;
        }

        foreach ([$baseUnitId, $targetUnitId] as $toUnit) {
            if ($toUnit <= 0) {
                continue;
            }
            if (! ProductUnitStock::canConvertUnits($palletUnitId, $toUnit, $variantId)) {
                continue;
            }
            $n = (int) round(ProductUnitStock::convertQty(1, $palletUnitId, $toUnit, $variantId));
            if ($n <= 0) {
                continue;
            }
            $u = Unit::find($toUnit);

            return [
                'qty' => $n,
                'base_unit_id' => $toUnit,
                'base_unit_name' => $u
                    ? (string) ($u->unit_short_name ?: $u->unit_name)
                    : $baseName,
                'pallet_unit_id' => $palletUnitId,
            ];
        }

        return $empty;
    }

    /** unit_id Pallet/Palete/Palet dari product_unit master (aktif). */
    public static function findPalletUnitId(Product $product): ?int
    {
        $ids = collect(json_decode($product->product_unit ?? '[]', true) ?: [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        if ($ids === []) {
            return null;
        }

        $hit = Unit::where('status', 1)
            ->whereIn('unit_id', $ids)
            ->where(function ($q) {
                // pallet / pallete / palette / palet (master satuan)
                $q->whereRaw("LOWER(COALESCE(unit_name,'')) LIKE '%palet%'")
                    ->orWhereRaw("LOWER(COALESCE(unit_short_name,'')) LIKE '%palet%'")
                    ->orWhereRaw("LOWER(COALESCE(unit_name,'')) LIKE '%pallet%'")
                    ->orWhereRaw("LOWER(COALESCE(unit_short_name,'')) LIKE '%pallet%'");
            })
            ->orderBy('unit_id')
            ->first();

        return $hit ? (int) $hit->unit_id : null;
    }

    /** Stock columns are integers: snapshot a representable unit before saving output. */
    private static function stockQuantity(int $variant, int $unit, float $qty): array
    {
        $candidates = array_unique(array_merge(array_reverse(ProductUnitStock::orderedUnitIds($variant)), [$unit]));
        foreach ($candidates as $candidate) {
            if (! ProductUnitStock::canConvertUnits($unit, (int) $candidate, $variant)) continue;
            $converted = ProductUnitStock::convertQty($qty, $unit, (int) $candidate, $variant);
            if ($converted > 0 && $converted <= 100000000 && abs($converted - round($converted)) < 0.000001) {
                return ['stock_unit_id' => (int) $candidate, 'stock_qty' => (int) round($converted)];
            }
        }
        throw new RuntimeException('Qty hasil tidak dapat disimpan utuh pada satuan stok. Gunakan satuan terkecil sesuai master.');
    }

    public static function totals(int $woId): array
    {
        $totals = [];
        foreach (ProductionOutputReport::where('production_work_order_id', $woId)->get() as $report) {
            foreach ($report->items as $row) {
                $totals[$row['ppi_id']] = ($totals[$row['ppi_id']] ?? 0) + $row['target_qty'];
            }
        }
        return $totals;
    }

    public static function report(int $woId, array $data): array
    {
        return DB::transaction(function () use ($woId, $data) {
            $wo = self::workOrder($woId, true);
            self::assertPic($wo);
            $key = self::requestId($data);
            $existing = ProductionOutputReport::where('production_work_order_id', $woId)->where('request_id', $key)->first();
            if ($existing) return ['status' => 1, 'report_id' => $existing->id, 'duplicate' => true];
            if ($wo->production_completed_at || $wo->closed_at) throw new RuntimeException('Hasil WO sudah lengkap; tidak dapat ditambah lagi.');
            $signature = self::snapshot((int) self::actor()->staff_id);
            $items = ProductionPlanningItem::where('production_work_order_id', $woId)->where('status', 1)->lockForUpdate()->get()->keyBy('ppi_id');
            $input = $data['items'] ?? [];
            if (! is_array($input) || ! $input) throw new RuntimeException('Isi minimal satu hasil produksi.');
            ProductUnitStock::clearCache();
            $rows = [];
            foreach ($input as $row) {
                $item = $items->get((int) ($row['ppi_id'] ?? 0));
                if (! $item) throw new RuntimeException('Item bukan milik WO ini.');
                $variant = ProductVariant::where('product_variant_id', $item->product_variant_id)->where('status', 1)->firstOrFail();
                $product = Product::findOrFail($variant->product_id);
                $qty = self::qty($row['qty'] ?? null);
                $isPallet = ($row['unit_id'] ?? '') === 'pallet';
                $pallet = $isPallet
                    ? self::resolvePalletMeta($variant, $product, (int) $item->unit_id)
                    : null;
                $unitId = $isPallet ? (int) ($pallet['base_unit_id'] ?? 0) : (int) ($row['unit_id'] ?? 0);
                $perPallet = $isPallet ? (float) ($pallet['qty'] ?? 0) : null;
                if ($isPallet && $perPallet <= 0) {
                    throw new RuntimeException(
                        'Isi per pallet belum diatur: isi kolom Isi/Pallet di master varian, '
                        .'atau tambahkan satuan Pallet di product_unit + relasi ke satuan produk.'
                    );
                }
                if (! Unit::where('unit_id', $unitId)->where('status', 1)->exists()
                    || ! ProductUnitStock::canConvertUnits($unitId, (int) $item->unit_id, (int) $variant->product_variant_id)) {
                    throw new RuntimeException('Satuan hasil tidak dapat dikonversi ke satuan target WO.');
                }
                $actual = self::qty($qty * ($isPallet ? $perPallet : 1));
                $targetQty = round(ProductUnitStock::convertQty($actual, $unitId, (int) $item->unit_id, (int) $variant->product_variant_id), 4);
                if ($targetQty <= 0) throw new RuntimeException('Qty hasil terlalu kecil.');
                $stock = self::stockQuantity((int) $variant->product_variant_id, $unitId, $actual);
                $rows[] = $stock + [
                    'ppi_id' => (int) $item->ppi_id, 'product_variant_id' => (int) $variant->product_variant_id,
                    'product_id' => (int) $product->product_id, 'product_name' => $item->product_name,
                    'input_qty' => $qty, 'input_unit' => $isPallet ? 'pallet' : $unitId,
                    'unit_id' => $unitId, 'qty' => $actual, 'target_unit_id' => (int) $item->unit_id,
                    'target_qty' => $targetQty, 'qty_per_pallet' => $perPallet,
                    'pallet_number' => mb_substr(trim((string) ($row['pallet_number'] ?? '')), 0, 100),
                ];
            }
            $report = ProductionOutputReport::create([
                'production_work_order_id' => $woId, 'request_id' => $key, 'items' => $rows,
                'created_by' => self::actor()->staff_id, 'actor_snapshot' => $signature, 'confirmed_at' => now(),
            ]);
            self::event($wo, 'output_confirmed', null, ['report_id' => $report->id]);
            $totals = self::totals($woId);
            // Sync hasil ke item SPK/PP (kolom Hasil di cetak SPKP)
            foreach ($items as $item) {
                $item->actual_qty = round((float) ($totals[$item->ppi_id] ?? 0), 4);
                $item->save();
            }
            $complete = $items->isNotEmpty() && $items->every(fn ($item) => ($totals[$item->ppi_id] ?? 0) + 0.0001 >= (float) $item->qty);
            $fgId = null;
            if ($complete) {
                // Target penuh → Completed + Form Gudang. Stok/jam/Tally baru setelah Ops+QC.
                $fgId = self::issueWarehouseForm($wo, $items, $totals, $signature, $key);
            }

            return [
                'status' => 1,
                'report_id' => $report->id,
                'production_complete' => $complete,
                'wo_done' => $complete,
                'document_id' => $fgId,
            ];
        });
    }

    /**
     * Target tercapai: tandai produksi selesai + terbitkan Form Gudang (awaiting_ops).
     * Belum kredit stok / closed_at / warehouse_at / tally.
     */
    private static function issueWarehouseForm(
        ProductionWorkOrder $wo,
        $items,
        array $totals,
        array $signature,
        string $reportKey
    ): int {
        $woId = (int) $wo->production_work_order_id;
        $existing = ProductionExecutionDocument::where('production_work_order_id', $woId)
            ->where('type', 'warehouse')
            ->whereNotIn('document_status', ['cancelled'])
            ->lockForUpdate()
            ->first();
        if ($existing) {
            return (int) $existing->id;
        }

        // Agregat stock_legs per baris PP dari semua laporan output.
        $legsByPpi = [];
        foreach (ProductionOutputReport::where('production_work_order_id', $woId)->get() as $output) {
            foreach ($output->items as $entry) {
                $ppi = (int) $entry['ppi_id'];
                $unit = (int) $entry['stock_unit_id'];
                if (! isset($legsByPpi[$ppi])) {
                    $legsByPpi[$ppi] = [];
                }
                $legsByPpi[$ppi][$unit] = ($legsByPpi[$ppi][$unit] ?? 0) + (int) $entry['stock_qty'];
            }
        }

        $fgItems = [];
        foreach ($items as $item) {
            $ppi = (int) $item->ppi_id;
            $actual = round((float) ($totals[$ppi] ?? 0), 4);
            $variant = ProductVariant::find($item->product_variant_id);
            $fgItems[] = [
                'ppi_id' => $ppi,
                'product_variant_id' => (int) $item->product_variant_id,
                'product_id' => (int) ($variant->product_id ?? 0),
                'product_name' => $item->product_name,
                'unit_id' => (int) $item->unit_id,
                'unit_label' => $item->unit_label,
                'requested_qty' => $actual,
                'received_qty' => null,
                'excess_qty' => max(0, $actual - (float) $item->qty),
                'stock_legs' => $legsByPpi[$ppi] ?? [],
            ];
        }

        $doc = self::createDocument(
            $wo,
            'warehouse',
            'fg-'.$reportKey,
            $fgItems,
            ['pic' => $signature]
        );

        $wo->production_completed_at = now();
        $wo->execution_status = 'done';
        $wo->save();
        self::event($wo, 'production_completed', $doc->id, ['totals' => $totals]);

        return (int) $doc->id;
    }

    /** Setelah QC ACC Form Gudang: tutup WO + sync PP bila semua WO closed. */
    private static function closeWorkOrderAfterFg(ProductionWorkOrder $wo): void
    {
        if ($wo->closed_at) {
            return;
        }
        $wo->closed_at = now();
        if ($wo->execution_status !== 'done') {
            $wo->execution_status = 'done';
        }
        $wo->save();
        self::event($wo, 'closed', null, ['via' => 'fg_qc']);

        $pp = ProductionPlanning::whereKey($wo->production_planning_id)->lockForUpdate()->firstOrFail();
        if (! ProductionWorkOrder::where('production_planning_id', $pp->production_planning_id)
            ->where('status', 1)
            ->whereNull('closed_at')
            ->exists()) {
            $pp->pp_status = 'done';
            $pp->save();
        }
    }

    private static function createDocument(ProductionWorkOrder $wo, string $type, string $key, array $items, array $signatures): ProductionExecutionDocument
    {
        $doc = ProductionExecutionDocument::create([
            'production_work_order_id' => $wo->production_work_order_id, 'warehouse_id' => $wo->warehouse_id,
            'type' => $type, 'request_id' => $key, 'items' => $items, 'signatures' => $signatures,
            'created_by' => self::actor()->staff_id, 'confirmed_at' => now(),
            'document_status' => in_array($type, ['warehouse', 'material_issue', 'material_return'], true)
                ? 'awaiting_ops'
                : 'awaiting_qc',
        ]);
        $prefix = ['warehouse' => 'FG', 'material_issue' => 'STB', 'material_return' => 'KBB'][$type];
        $doc->number = $prefix.'-'.str_pad((string) $doc->id, 4, '0', STR_PAD_LEFT);
        $doc->save();
        self::event($wo, 'document_created', $doc->id, ['type' => $type]);
        return $doc;
    }

    public static function approve(int $documentId, string $stage, array $data): array
    {
        return DB::transaction(function () use ($documentId, $stage, $data) {
            $ref = ProductionExecutionDocument::findOrFail($documentId);
            $wo = self::workOrder((int) $ref->production_work_order_id, true);
            $doc = ProductionExecutionDocument::whereKey($documentId)->lockForUpdate()->firstOrFail();
            if (! in_array($stage, ['ops', 'qc'], true)) throw new RuntimeException('Tahap approval tidak valid.');
            if (! ($stage === 'ops' ? self::isOps((int) $wo->warehouse_id) : self::isQc((int) $wo->warehouse_id))) {
                throw new RuntimeException('Anda bukan petugas approval tahap ini di gudang aktif.');
            }
            if ($doc->document_status === 'approved' || ($stage === 'ops' && $doc->ops_approved_at)) {
                return ['status' => 1, 'duplicate' => true];
            }
            if ($doc->document_status !== 'awaiting_'.$stage) throw new RuntimeException('Approval harus berurutan: Kepala Operasional lalu QC Gudang.');
            $signatures = $doc->signatures;
            $signatures[$stage] = self::snapshot((int) self::actor()->staff_id);
            $now = now();
            if ($stage === 'ops') {
                $doc->ops_approved_by = self::actor()->staff_id;
                $doc->ops_approved_at = $now;
                $doc->document_status = 'awaiting_qc';
            } else {
                $block = PendingStockSoftBlock::messageIfAnyDomainBlocked((int) $wo->warehouse_id);
                if ($block) throw new RuntimeException($block);
                $received = $data['received'] ?? [];
                $rows = $doc->items;
                foreach ($rows as $index => &$row) {
                    // Form Gudang: qty terima default = hasil produksi (requested) jika tidak diisi.
                    if (! array_key_exists($index, $received)) {
                        // FG + STB/KBB: default qty terima = qty PIC.
                        if (in_array($doc->type, ['warehouse', 'material_issue', 'material_return'], true)) {
                            $qty = self::qty($row['requested_qty'] ?? null);
                        } else {
                            throw new RuntimeException('Isi qty terima untuk setiap baris.');
                        }
                    } else {
                        $qty = self::qty($received[$index]);
                    }
                    if (abs($qty - (float) $row['requested_qty']) > 0.0001) {
                        throw new RuntimeException('Qty terima harus sesuai qty yang dikonfirmasi PIC.');
                    }
                    $row['received_qty'] = $qty;
                    if ($doc->type === 'warehouse') {
                        ProductVariant::whereKey($row['product_variant_id'])->lockForUpdate()->firstOrFail();
                        foreach (($row['stock_legs'] ?? []) as $stockUnit => $stockQty) {
                            $add = ProductUnitStock::addQty(
                                (int) $wo->warehouse_id,
                                $row['product_id'],
                                $row['product_variant_id'],
                                (int) $stockUnit,
                                $stockQty,
                                $doc->number,
                                'Hasil '.$wo->wo_number.' / '.$doc->number,
                                false
                            );
                            if (! $add['ok']) throw new RuntimeException($add['message'] ?? 'Gagal masuk stok.');
                        }
                    } else {
                        self::moveMaterial($wo, $doc, $row, $qty);
                    }
                }
                unset($row);
                $doc->items = $rows;
                $doc->qc_approved_by = self::actor()->staff_id;
                $doc->qc_approved_at = $now;
                $doc->warehouse_at = $now;
                $doc->document_status = 'approved';
                if ($doc->type === 'warehouse') {
                    $doc->tally_number = 'TLY-'.$now->format('ymd').'-'.str_pad((string) $doc->id, 4, '0', STR_PAD_LEFT);
                }
            }
            $doc->signatures = $signatures;
            $doc->save();
            self::event($wo, $stage.'_approved', $doc->id);
            if ($stage === 'qc' && $doc->type === 'warehouse') {
                self::closeWorkOrderAfterFg($wo);
            }
            return ['status' => 1, 'document_id' => $doc->id, 'tally_number' => $doc->tally_number];
        });
    }

    public static function initializeMaterials(ProductionWorkOrder $wo): void
    {
        $doc = self::createDocument($wo, 'material_issue', 'assignment-materials', [], []);
        $doc->document_status = 'awaiting_pic';
        $doc->save();
    }

    public static function cancelMaterial(int $documentId): array
    {
        return DB::transaction(function () use ($documentId) {
            $ref = ProductionExecutionDocument::findOrFail($documentId);
            $wo = self::workOrder((int) $ref->production_work_order_id, true);
            self::assertPic($wo);
            $doc = ProductionExecutionDocument::whereKey($documentId)->lockForUpdate()->firstOrFail();
            if (!in_array($doc->type, ['material_issue','material_return'], true) || $doc->document_status === 'approved' || $wo->closed_at) throw new RuntimeException('Hanya permintaan bahan yang belum di-ACC dapat dibatalkan.');
            $doc->document_status = 'cancelled'; $doc->save();
            self::event($wo, 'material_cancelled', $doc->id);
            return ['status'=>1];
        });
    }

    public static function noMaterials(int $woId): array
    {
        return DB::transaction(function () use ($woId) {
            $wo = self::workOrder($woId, true);
            self::assertPic($wo);
            $doc = ProductionExecutionDocument::where('production_work_order_id', $woId)->where('document_status', 'awaiting_pic')->lockForUpdate()->first();
            if (!$doc) throw new RuntimeException('Draft serah terima bahan tidak ditemukan.');
            $doc->document_status = 'approved';
            $doc->notes = 'PIC menyatakan tidak ada bahan yang diminta melalui WO ini.';
            $doc->signatures = ['pic'=>self::snapshot((int) self::actor()->staff_id)];
            $doc->confirmed_at = now();
            $doc->save();
            self::event($wo, 'no_materials_confirmed', $doc->id);
            return ['status'=>1];
        });
    }

    public static function acknowledge(int $documentId, string $role): array
    {
        return DB::transaction(function () use ($documentId, $role) {
            if (! in_array($role, ['hand_pallet', 'palletized'], true)) throw new RuntimeException('Peran konfirmasi tidak valid.');
            $ref = ProductionExecutionDocument::findOrFail($documentId);
            $wo = self::workOrder((int) $ref->production_work_order_id, true);
            $doc = ProductionExecutionDocument::whereKey($documentId)->lockForUpdate()->firstOrFail();
            if ($doc->type !== 'warehouse' || $doc->document_status !== 'awaiting_qc') throw new RuntimeException('Konfirmasi penanganan dilakukan setelah ACC Kepala Operasional, sebelum ACC QC.');
            $signatures = $doc->signatures;
            if (isset($signatures[$role])) {
                if ((int) $signatures[$role]['staff_id'] === (int) self::actor()->staff_id) return ['status' => 1];
                throw new RuntimeException('Peran sudah dikonfirmasi oleh staf lain.');
            }
            $signatures[$role] = self::snapshot((int) self::actor()->staff_id) + ['confirmed_at' => now()->toDateTimeString()];
            $doc->signatures = $signatures;
            $doc->save();
            self::event($wo, $role.'_confirmed', $doc->id);
            return ['status' => 1];
        });
    }

    public static function materialBalance(int $woId): array
    {
        $balance = [];
        foreach (ProductionExecutionDocument::where('production_work_order_id', $woId)->where('document_status', 'approved')->whereIn('type', ['material_issue','material_return'])->get() as $doc) {
            foreach ($doc->items as $row) {
                $key = $row['supplies_id'].':'.$row['unit_id'];
                if (!isset($balance[$key])) $balance[$key] = ['supplies_id'=>$row['supplies_id'], 'name'=>$row['product_name'], 'unit'=>$row['unit_label'], 'qty'=>0];
                $balance[$key]['qty'] += ($doc->type === 'material_issue' ? 1 : -1) * $row['received_qty'];
            }
        }
        return array_values($balance);
    }

    /**
     * Saran bahan dari resep (BOM) item WO — agregat per supplies+unit, stok gudang, sisa vs sudah diambil.
     *
     * @return array{items: list<array>, missing_bom: list<string>}
     */
    public static function materialRecipe(int $woId): array
    {
        $wo = self::workOrder($woId);
        $whId = (int) $wo->warehouse_id;
        $planItems = ProductionPlanningItem::where('production_work_order_id', $woId)->where('status', 1)->get();
        $needs = [];
        $missingBom = [];

        foreach ($planItems as $item) {
            $pvId = (int) $item->product_variant_id;
            $bom = Bom::where('status', 1)->where('product_id', $pvId)->first();
            if (! $bom) {
                $missingBom[] = (string) ($item->product_name ?: ('SKU #'.$pvId));
                continue;
            }
            $itemInBomUnit = ProductUnitStock::convertQty(
                (float) $item->qty,
                (int) $item->unit_id,
                (int) $bom->unit_id,
                $pvId
            );
            $bomQty = (float) $bom->bom_qty;
            if ($bomQty <= 0 || $itemInBomUnit <= 0) {
                continue;
            }
            $batches = (int) floor($itemInBomUnit / $bomQty);
            if ($batches <= 0) {
                $batches = 1;
            }

            $details = BomDetail::where('bom_id', $bom->bom_id)->where('status', 1)->get();
            foreach ($details as $bd) {
                $sid = (int) $bd->supplies_id;
                $uid = (int) $bd->unit_id;
                if ($sid <= 0 || $uid <= 0) {
                    continue;
                }
                $key = $sid.':'.$uid;
                if (! isset($needs[$key])) {
                    $needs[$key] = [
                        'supplies_id' => $sid,
                        'unit_id' => $uid,
                        'recipe_qty' => 0.0,
                    ];
                }
                $needs[$key]['recipe_qty'] += ((float) $bd->bom_detail_qty) * $batches;
            }
        }

        $taken = [];
        foreach (ProductionExecutionDocument::where('production_work_order_id', $woId)
            ->where('document_status', 'approved')
            ->whereIn('type', ['material_issue', 'material_return'])
            ->get() as $doc) {
            foreach ($doc->items as $row) {
                $key = ((int) $row['supplies_id']).':'.((int) $row['unit_id']);
                $taken[$key] = ($taken[$key] ?? 0)
                    + ($doc->type === 'material_issue' ? 1 : -1) * (float) ($row['received_qty'] ?? 0);
            }
        }
        // Pending Ops/QC dihitung agar saran tidak double-request.
        foreach (ProductionExecutionDocument::where('production_work_order_id', $woId)
            ->whereIn('document_status', ['awaiting_ops', 'awaiting_qc'])
            ->where('type', 'material_issue')
            ->get() as $doc) {
            foreach ($doc->items as $row) {
                $key = ((int) $row['supplies_id']).':'.((int) $row['unit_id']);
                $taken[$key] = ($taken[$key] ?? 0) + (float) ($row['requested_qty'] ?? 0);
            }
        }

        $out = [];
        foreach ($needs as $key => $need) {
            $supply = Supplies::find($need['supplies_id']);
            $unit = Unit::find($need['unit_id']);
            if (! $supply || ! $unit) {
                continue;
            }
            $available = self::availableSuppliesQty($whId, (int) $need['supplies_id'], (int) $need['unit_id'], $supply);
            $already = (float) ($taken[$key] ?? 0);
            $suggest = max(0, round($need['recipe_qty'] - $already, 4));
            $out[] = [
                'supplies_id' => (int) $need['supplies_id'],
                'name' => (string) $supply->supplies_name,
                'unit_id' => (int) $need['unit_id'],
                'unit' => (string) ($unit->unit_short_name ?: $unit->unit_name),
                'recipe_qty' => round($need['recipe_qty'], 4),
                'taken_qty' => round($already, 4),
                'suggest_qty' => $suggest,
                'available' => round($available, 4),
                'is_trading' => Supplies::isTradingKind($supply->supplies_kind ?? null) ? 1 : 0,
            ];
        }

        usort($out, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return [
            'items' => $out,
            'missing_bom' => array_values(array_unique($missingBom)),
            'warehouse_id' => $whId,
        ];
    }

    /** Stok tersedia di gudang WO (trading → product_variant link). */
    public static function availableSuppliesQty(int $warehouseId, int $suppliesId, int $unitId, ?Supplies $supply = null): float
    {
        $supply ??= Supplies::find($suppliesId);
        if (! $supply) {
            return 0.0;
        }
        if (Supplies::isTradingKind($supply->supplies_kind ?? null)) {
            $pvId = (int) ($supply->trading_product_variant_id ?? 0);

            return $pvId > 0
                ? ProductUnitStock::totalAvailable($warehouseId, $pvId, $unitId, false, true)
                : 0.0;
        }

        return (float) SuppliesStock::withoutGlobalScope('active_warehouse')
            ->where('warehouse_id', $warehouseId)
            ->where('supplies_id', $suppliesId)
            ->where('unit_id', $unitId)
            ->where('status', 1)
            ->sum('ss_stock');
    }

    public static function requestMaterial(int $woId, string $type, array $data): array
    {
        return DB::transaction(function () use ($woId, $type, $data) {
            if (! in_array($type, ['material_issue', 'material_return'], true)) throw new RuntimeException('Jenis dokumen bahan tidak valid.');
            $wo = self::workOrder($woId, true);
            self::assertPic($wo);
            $key = self::requestId($data);
            $existing = ProductionExecutionDocument::where('production_work_order_id', $woId)->where('type', $type)->where('request_id', $key)->first();
            if ($existing) return ['status' => 1, 'document_id' => $existing->id];
            if ($wo->closed_at) throw new RuntimeException('WO sudah ditutup.');
            if ($wo->execution_status !== 'inprod' && $type === 'material_issue') {
                throw new RuntimeException('Ambil bahan hanya saat WO masih In Production.');
            }

            $softBlock = PendingStockSoftBlock::messageIfAnyDomainBlocked((int) $wo->warehouse_id);
            if ($softBlock) {
                throw new RuntimeException($softBlock);
            }

            $rows = [];
            foreach (($data['items'] ?? []) as $row) {
                $supply = Supplies::where('supplies_id', $row['supplies_id'] ?? 0)->where('status', 1)->firstOrFail();
                $unit = Unit::where('unit_id', $row['unit_id'] ?? 0)->where('status', 1)->firstOrFail();
                $qty = self::qty($row['qty'] ?? null);
                if (abs($qty - round($qty)) > 0.000001) throw new RuntimeException('Stok bahan menggunakan jumlah utuh. Pilih satuan lebih kecil untuk pecahan.');

                if ($type === 'material_issue') {
                    $available = self::availableSuppliesQty((int) $wo->warehouse_id, (int) $supply->supplies_id, (int) $unit->unit_id, $supply);
                    if ($available + 0.0001 < $qty) {
                        throw new RuntimeException(
                            'Stok tidak cukup: '.$supply->supplies_name
                            .' (butuh '.$qty.' '.$unit->unit_short_name.', tersedia '.$available.').'
                        );
                    }
                } else {
                    if (! Supplies::isTradingKind($supply->supplies_kind ?? null)
                        && ! SuppliesStock::withoutGlobalScopes()
                            ->where('warehouse_id', $wo->warehouse_id)
                            ->where('supplies_id', $supply->supplies_id)
                            ->where('unit_id', $unit->unit_id)
                            ->where('status', 1)
                            ->exists()) {
                        throw new RuntimeException('Satuan bahan tidak tersedia pada gudang ini.');
                    }
                }

                $rows[] = ['supplies_id' => (int) $supply->supplies_id, 'product_name' => $supply->supplies_name,
                    'unit_id' => (int) $unit->unit_id, 'unit_label' => $unit->unit_short_name ?: $unit->unit_name,
                    'requested_qty' => $qty, 'received_qty' => null];
            }
            if (! $rows) throw new RuntimeException('Isi minimal satu bahan.');
            $signature = self::snapshot((int) self::actor()->staff_id);
            $doc = $type === 'material_issue' ? ProductionExecutionDocument::where('production_work_order_id', $woId)->where('document_status', 'awaiting_pic')->lockForUpdate()->first() : null;
            if ($doc) {
                $doc->request_id = $key; $doc->items = $rows; $doc->signatures = ['pic'=>$signature];
                $doc->document_status = 'awaiting_ops'; $doc->confirmed_at = now(); $doc->created_by = self::actor()->staff_id; $doc->save();
                self::event($wo, 'materials_requested', $doc->id);
            } else {
                $doc = self::createDocument($wo, $type, $key, $rows, ['pic' => $signature]);
            }
            return ['status' => 1, 'document_id' => $doc->id];
        });
    }

    private static function moveMaterial(ProductionWorkOrder $wo, ProductionExecutionDocument $doc, array $row, float $qty): void
    {
        if ($doc->type === 'material_return') {
            $balance = 0.0;
            foreach (ProductionExecutionDocument::where('production_work_order_id', $wo->production_work_order_id)->where('document_status', 'approved')->whereIn('type', ['material_issue', 'material_return'])->get() as $previous) {
                foreach ($previous->items as $entry) {
                    if ($entry['supplies_id'] === $row['supplies_id'] && $entry['unit_id'] === $row['unit_id']) {
                        $balance += ($previous->type === 'material_issue' ? 1 : -1) * $entry['received_qty'];
                    }
                }
            }
            // Include all same-unit lines in this return so duplicate rows cannot over-return.
            $requested = collect($doc->items)->filter(fn ($r) => $r['supplies_id'] === $row['supplies_id'] && $r['unit_id'] === $row['unit_id'])->sum('requested_qty');
            if ($requested > $balance + 0.0001) throw new RuntimeException('Pengembalian melebihi bahan yang diterima oleh PIC.');
            $supply = Supplies::find($row['supplies_id']);
            if ($supply && Supplies::isTradingKind($supply->supplies_kind ?? null)) {
                $pvId = (int) ($supply->trading_product_variant_id ?? 0);
                $pv = $pvId > 0 ? ProductVariant::find($pvId) : null;
                if (! $pv) {
                    throw new RuntimeException('Varian produk Trading tidak ditemukan.');
                }
                $add = ProductUnitStock::addQty(
                    (int) $wo->warehouse_id,
                    (int) $pv->product_id,
                    $pvId,
                    (int) $row['unit_id'],
                    $qty,
                    $doc->number,
                    'Sisa bahan Trading '.$wo->wo_number,
                    true
                );
                if (! ($add['ok'] ?? false)) {
                    throw new RuntimeException($add['message'] ?? 'Gagal mengembalikan stok Trading.');
                }

                return;
            }
            SuppliesUnitStock::addQty((int) $wo->warehouse_id, $row['supplies_id'], $row['unit_id'], $qty, $doc->number, 'Sisa bahan '.$wo->wo_number, false);
            return;
        }

        $supply = Supplies::find($row['supplies_id']);
        if ($supply && Supplies::isTradingKind($supply->supplies_kind ?? null)) {
            $pvId = (int) ($supply->trading_product_variant_id ?? 0);
            if ($pvId <= 0) {
                throw new RuntimeException('Bahan Trading belum terhubung ke produk.');
            }
            $deduct = ProductUnitStock::deductQty(
                (int) $wo->warehouse_id,
                $pvId,
                (int) $row['unit_id'],
                $qty,
                $doc->number,
                'Serah terima bahan Trading '.$wo->wo_number,
                false,
                true,
                'Serah terima bahan'
            );
            if (! ($deduct['ok'] ?? false)) {
                throw new RuntimeException($deduct['message'] ?? 'Stok Trading tidak mencukupi.');
            }

            return;
        }

        $stocks = SuppliesStock::withoutGlobalScope('active_warehouse')->where('warehouse_id', $wo->warehouse_id)
            ->where('supplies_id', $row['supplies_id'])->where('unit_id', $row['unit_id'])->where('status', 1)->lockForUpdate()->get();
        if ((float) $stocks->sum('ss_stock') + 0.0001 < $qty) throw new RuntimeException('Stok bahan pada satuan terpilih tidak mencukupi.');
        $left = $qty;
        foreach ($stocks as $stock) {
            $take = min($left, (float) $stock->ss_stock);
            $stock->ss_stock = round($stock->ss_stock - $take, 4);
            $stock->save();
            $left -= $take;
            if ($left <= 0.0001) break;
        }
        (new LogStock())->insertLog(['log_date' => now(), 'log_kode' => $doc->number, 'log_type' => 2,
            'log_category' => 2, 'log_item_id' => $row['supplies_id'], 'log_jumlah' => $qty,
            'unit_id' => $row['unit_id'], 'warehouse_id' => $wo->warehouse_id,
            'log_saldo' => (float) $stocks->sum('ss_stock'), 'log_notes' => 'Serah terima bahan '.$wo->wo_number]);
    }

    public static function close(int $woId, array $data = []): array
    {
        return DB::transaction(function () use ($woId, $data) {
            $wo = self::workOrder($woId, true);
            if ($wo->closed_at) {
                return ['status' => 1];
            }
            // Tutup WO hanya setelah Form Gudang di-ACC QC (stok sudah masuk).
            if (! $wo->production_completed_at) {
                throw new RuntimeException('Isi hasil produksi sampai target tercapai agar Form Gudang terbit.');
            }
            $fg = ProductionExecutionDocument::where('production_work_order_id', $woId)
                ->where('type', 'warehouse')
                ->where('document_status', 'approved')
                ->exists();
            if (! $fg) {
                throw new RuntimeException('Form Gudang harus di-ACC Kepala Ops lalu Staf QC terlebih dahulu.');
            }
            self::closeWorkOrderAfterFg($wo);

            return ['status' => 1];
        });
    }
}
