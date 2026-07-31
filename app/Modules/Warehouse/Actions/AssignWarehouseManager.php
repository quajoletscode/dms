<?php

namespace App\Modules\Warehouse\Actions;

use App\Models\User;
use App\Modules\Warehouse\Models\Warehouse;

final class AssignWarehouseManager
{
    public function execute(Warehouse $warehouse, User $user): Warehouse
    {
        $warehouse->update(['manager_id' => $user->id]);
        $warehouse->assignedUsers()->syncWithoutDetaching([$user->id]);

        return $warehouse->refresh();
    }
}
