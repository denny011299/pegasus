<?php

namespace App\Support;

class DataTableParams
{
    /**
     * @return array{draw:int,start:int,length:int,search:string}
     */
    public static function from(array $input): array
    {
        $length = (int) ($input['length'] ?? 10);
        if ($length < 1) {
            $length = 10;
        }
        if ($length > 100) {
            $length = 100;
        }

        return [
            'draw' => (int) ($input['draw'] ?? 1),
            'start' => max(0, (int) ($input['start'] ?? 0)),
            'length' => $length,
            'search' => trim((string) data_get($input, 'search.value', '')),
        ];
    }
}
