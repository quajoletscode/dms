<?php

namespace App\Modules\Van\Actions;

use App\Modules\Van\Models\VanDsrHistory;
use App\Modules\Van\Models\VanStorage;
use Illuminate\Support\Facades\DB;

final class RegisterVan
{
    /**
     * @param  array<string, mixed>  $data  code, warehouse_id, vehicle_no?, dsr_user_id? — validated by StoreVanRequest
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
