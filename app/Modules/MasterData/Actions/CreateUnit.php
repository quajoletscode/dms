<?php

namespace App\Modules\MasterData\Actions;

use App\Modules\MasterData\Models\Unit;

final class CreateUnit
{
    /**
     * @param  array{name: string, symbol?: string|null, base_unit_id?: int|null, conversion_factor?: string|int|float}  $data
     */
    public function execute(array $data): Unit
    {
        return Unit::query()->create([
            'name' => $data['name'],
            'symbol' => $data['symbol'] ?? null,
            'base_unit_id' => $data['base_unit_id'] ?? null,
            'conversion_factor' => $data['conversion_factor'] ?? 1,
        ]);
    }
}
