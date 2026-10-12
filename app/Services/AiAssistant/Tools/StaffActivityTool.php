<?php

namespace App\Services\AiAssistant\Tools;

use App\Services\AiAssistant\ReadOnlyDb;
use App\Services\AiAssistant\Tools\Concerns\TrackingHelpers;
use App\Services\AiAssistant\Vocabulary;

/**
 * Apa yang dikerjakan staf dalam periode: aksi di log aktivitas (tambah,
 * ubah, ACC, hapus) + jumlah dokumen yang dia buat / ACC per modul.
 * Tanpa nama staf → peringkat staf paling aktif.
 */
class StaffActivityTool implements ToolInterface
{
    use TrackingHelpers;

    /** Modul dokumen yang dihitung per staf (punya created_by & acc_by). */
    private const DOC_TABLES = [
        'Pengiriman' => 'sales_orders',
        'Pembelian' => 'purchase_orders',
        'Tanda Terima' => 'purchase_order_tts',
        'Produksi' => 'productions',
        'Transfer Stok' => 'stock_transfers',
        'Stok Opname Produk' => 'stock_opnames',
        'Stok Opname Bahan' => 'stock_opname_bahans',
        'Retur Produk Customer' => 'customer_product_returns',
        'Retur Bahan Customer' => 'customer_supply_returns',
        'Kas Besar' => 'cashes',
        'Operasional Admin' => 'cash_admins',
        'Operasional Gudang' => 'cash_gudangs',
        'Operasional Armada' => 'cash_armadas',
        'Operasional Sales' => 'cash_sales',
    ];

    /** activity_type "open" = sekadar membuka menu; bukan perubahan data. */
    private const OPEN = 'open';

    public function __construct(protected ReadOnlyDb $db, protected Vocabulary $vocab) {}

    public function name(): string
    {
        return 'staff_activity';
    }

    public function description(): string
    {
        return 'What a staff member did in a period: actions from the activity log (tambah/ubah/ACC/hapus per menu) and how many documents they created or approved per module. Without staff → ranking of the most active staff. Default today. Read-only.';
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'staff' => ['type' => 'string', 'description' => 'Nama / username staf (kosong = semua staf)'],
                'date_from' => ['type' => 'string', 'description' => 'YYYY-MM-DD, default hari ini'],
                'date_to' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                'include_open_menu' => ['type' => 'boolean', 'description' => 'Ikutkan aktivitas "membuka menu". Default false.'],
                'limit' => ['type' => 'integer', 'description' => 'Aktivitas terakhir yang ditampilkan, default 15, max 50'],
            ],
        ];
    }

    public function execute(array $args): array
    {
        [$from, $to] = $this->period($args, 1);
        $withOpen = !empty($args['include_open_menu']);
        $limit = max(1, min((int) ($args['limit'] ?? 15), 50));
        $name = trim((string) ($args['staff'] ?? ''));

        if ($name === '') {
            return $this->ranking($from, $to, $withOpen, $limit);
        }

        $staff = $this->resolveStaff($name);
        if (isset($staff['pesan'])) {
            return $staff;
        }
        $staffId = $staff['id'];

        $logs = fn () => $this->whereDay($this->db->table('dashboard_change_logs'), 'created_at', $from, $to)
            ->where('created_by', $staffId)
            ->when(!$withOpen, fn ($q) => $q->where('activity_type', '!=', self::OPEN));

        $perMenu = $logs()->selectRaw('module_label, COUNT(*) as n')->groupBy('module_label')->orderByDesc('n')->limit(15)->get()
            ->map(fn ($r) => $r->module_label.': '.$r->n.'x')->all();
        $recent = $logs()->orderByDesc('created_at')->limit($limit)
            ->get(['created_at', 'module_label', 'what_changed', 'reference'])
            ->map(fn ($r) => array_filter([
                'Waktu' => $r->created_at,
                'Menu' => $r->module_label,
                'Aksi' => $r->what_changed,
                'Referensi' => $r->reference,
            ]))->all();

        return [
            'staf' => $staff['nama'],
            'periode' => $this->periodLabel($from, $to),
            'jumlah_aktivitas' => $logs()->count(),
            'aktivitas_per_menu' => $perMenu ?: ['Tidak ada aktivitas tercatat.'],
            'dokumen' => $this->documentCounts($staffId, $from, $to),
            'aktivitas_terakhir' => $recent,
            'catatan' => $withOpen ? null : 'Aktivitas "membuka menu" tidak ikut dihitung.',
        ];
    }

    private function ranking(string $from, string $to, bool $withOpen, int $limit): array
    {
        $rows = $this->whereDay($this->db->table('dashboard_change_logs'), 'created_at', $from, $to)
            ->when(!$withOpen, fn ($q) => $q->where('activity_type', '!=', self::OPEN))
            ->selectRaw('created_by, COUNT(*) as n, MAX(created_at) as terakhir')
            ->groupBy('created_by')
            ->orderByDesc('n')
            ->limit($limit)
            ->get();

        return [
            'periode' => $this->periodLabel($from, $to),
            'staf_teraktif' => $rows->map(fn ($r) => [
                'Staf' => $this->nameOf('staff', $r->created_by),
                'Aktivitas' => (int) $r->n,
                'Terakhir' => $r->terakhir,
            ])->all() ?: 'Tidak ada aktivitas tercatat.',
        ];
    }

    /** Dokumen yang dibuat / di-ACC staf per modul, berdasarkan tanggal dokumen. */
    private function documentCounts(int $staffId, string $from, string $to): array
    {
        $out = [];
        foreach (self::DOC_TABLES as $label => $table) {
            $dateCol = $this->vocab->dateField($table);
            if ($dateCol === null) {
                continue;
            }
            $allowed = $this->db->allowedColumns($table);
            $base = fn () => $this->whereDay($this->db->table($table), $dateCol, $from, $to);
            // productions menyimpan pembuat di production_created_by, bukan created_by.
            $creatorCol = collect(['created_by', 'production_created_by'])->first(fn ($c) => in_array($c, $allowed, true));
            $created = $creatorCol ? $base()->where($creatorCol, $staffId)->count() : 0;
            $approved = in_array('acc_by', $allowed, true) ? $base()->where('acc_by', $staffId)->count() : 0;
            if ($created + $approved > 0) {
                $out[] = ['Modul' => $label, 'Dibuat' => $created, 'Di-ACC' => $approved];
            }
        }

        return $out ?: ['Tidak ada dokumen yang dibuat/di-ACC pada periode ini.'];
    }

    /** @return array{id: int, nama: string}|array{pesan: string, pilihan?: list<string>} */
    private function resolveStaff(string $name): array
    {
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $name).'%';
        $rows = $this->db->table('staffs')
            ->where(fn ($q) => $q->where('staff_name', 'like', $like)->orWhere('staff_username', 'like', $like)->orWhere('staff_code', $name))
            ->limit(10)
            ->get(['staff_id', 'staff_name', 'status']);

        $exact = $rows->first(fn ($r) => strcasecmp(trim((string) $r->staff_name), $name) === 0);
        if ($exact) {
            return ['id' => (int) $exact->staff_id, 'nama' => (string) $exact->staff_name];
        }
        // Utamakan kata utuh: "ika" → "admin 2 (IKA)", bukan "Dika".
        $word = $rows->filter(fn ($r) => preg_match('/\b'.preg_quote($name, '/').'\b/i', (string) $r->staff_name))->values();
        if ($word->count() === 1) {
            return ['id' => (int) $word[0]->staff_id, 'nama' => (string) $word[0]->staff_name];
        }
        if ($rows->count() === 1) {
            return ['id' => (int) $rows[0]->staff_id, 'nama' => (string) $rows[0]->staff_name];
        }
        if ($rows->isEmpty()) {
            return ['pesan' => 'Staf "'.$name.'" tidak ditemukan. Minta user mengecek nama/username.'];
        }

        return [
            'pesan' => 'Ada beberapa staf cocok dengan "'.$name.'". Tanyakan ke user yang mana.',
            'pilihan' => $rows->pluck('staff_name')->map(fn ($n) => (string) $n)->all(),
        ];
    }
}
