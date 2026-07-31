<?php

namespace App\Modules\Warehouse\Policies;

use App\Models\User;
use App\Modules\Warehouse\Models\Warehouse;

/**
 * Row-level scoping (which warehouses a Warehouse Manager sees at all) is
 * handled by WarehouseScope; this policy only gates the action itself.
 */
class WarehousePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('warehouse.view') || $user->can('warehouse.manage');
    }

    public function view(User $user, Warehouse $warehouse): bool
    {
        return $user->can('warehouse.view') || $user->can('warehouse.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('warehouse.manage');
    }

    public function update(User $user, Warehouse $warehouse): bool
    {
        return $user->can('warehouse.manage');
    }
}
