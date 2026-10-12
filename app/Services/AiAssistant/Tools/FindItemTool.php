<?php

namespace App\Services\AiAssistant\Tools;

use App\Services\AiAssistant\ReadOnlyDb;
use App\Services\AiAssistant\Tools\Concerns\ItemMatcher;
use App\Services\AiAssistant\Vocabulary;

/**
 * Cari barang (produk / bahan mentah) dari nama, SKU, barcode atau ketikan
 * yang kurang tepat. Kalau tidak ada yang cocok persis, kembalikan saran
 * barang yang mirip supaya asisten bisa bertanya "maksudnya SKU X?".
 */
class FindItemTool implements ToolInterface
{
    use ItemMatcher;

    public function __construct(protected ReadOnlyDb $db, protected Vocabulary $vocab) {}

    public function name(): string
    {
        return 'find_item';
    }

    public function description(): string
    {
        return 'Find a product or bahan mentah by name, SKU, barcode or partial/misspelled words. Returns exact matches, or similar items as suggestions when nothing matches exactly. Read-only.';
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'keyword' => ['type' => 'string', 'description' => 'Nama barang, SKU atau barcode seperti yang diketik user'],
                'jenis' => ['type' => 'string', 'enum' => ['semua', 'produk', 'bahan'], 'description' => 'produk = barang jadi, bahan = bahan mentah/kemasan. Default semua.'],
            ],
            'required' => ['keyword'],
        ];
    }

    public function execute(array $args): array
    {
        $keyword = (string) ($args['keyword'] ?? $args['item'] ?? $args['search'] ?? '');
        $jenis = in_array($args['jenis'] ?? null, ['produk', 'bahan'], true) ? $args['jenis'] : 'semua';
        $match = $this->matchItems($keyword, $jenis);
        $items = array_map(fn ($c) => $this->presentItem($c), $match['items']);

        return match ($match['status']) {
            'persis', 'cocok' => [
                'ditemukan' => true,
                'cocok_persis' => $match['status'] === 'persis',
                'total_cocok' => $match['total'],
                'barang' => $items,
            ] + ($match['total'] > count($items)
                ? ['catatan' => 'Hanya '.count($items).' dari '.$match['total'].' barang ditampilkan. Minta user memperjelas nama/SKU.']
                : []),
            'saran' => [
                'ditemukan' => false,
                'kata_kunci' => $keyword,
                'pesan' => 'Barang "'.$keyword.'" tidak ditemukan persis. Tawarkan saran berikut ke user dan tanyakan mana yang dimaksud.',
                'saran' => $items,
            ],
            default => [
                'ditemukan' => false,
                'kata_kunci' => $keyword,
                'pesan' => 'Barang "'.$keyword.'" tidak ditemukan dan tidak ada nama/SKU yang mirip. Minta user mengecek ejaan atau SKU.',
            ],
        };
    }
}
