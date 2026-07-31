<?php

namespace App\Modules\Warehouse\Actions;

use App\Modules\Warehouse\Models\Warehouse;

final class RegisterWarehouse
{
    /**
     * @param  array{code: string, name: string, location?: string|null, manager_id?: int|null}  $data
     */
    public function execute(array $data): Warehouse
    {
        return Warehouse::query()->create([
            'code' => $data['code'],
            'name' => $data['name'],
            'location' => $data['location'] ?? null,
            'manager_id' => $data['manager_id'] ?? null,
            'is_active' => true,
        ]);
    }
}
