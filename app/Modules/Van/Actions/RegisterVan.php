<?php

namespace App\Modules\Van\Actions;

use App\Modules\Van\Models\VanDsrHistory;
use App\Modules\Van\Models\VanStorage;
use Illuminate\Support\Facades\DB;

final class RegisterVan
{
    /**
     * @param  array{code: string, warehouse_id: int, vehicle_no?: string|null, dsr_user_id?: int|null}  $data
     */
    public function execute(array $data): VanStorage
    {
        return DB::transaction(function () use ($data) {
            $van = VanStorage::query()->create([
                'code' => $data['code'],
                'vehicle_no' => $data['vehicle_no'] ?? null,
                'warehouse_id' => $data['warehouse_id'],
                'dsr_user_id' => $data['dsr_user_id'] ?? null,
                'is_active' => true,
            ]);

            if ($van->dsr_user_id !== null) {
                VanDsrHistory::query()->create([
                    'van_storage_id' => $van->id,
                    'dsr_user_id' => $van->dsr_user_id,
                    'assigned_at' => now(),
                ]);
            }

            return $van;
        });
    }
}
