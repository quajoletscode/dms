<?php

namespace App\Modules\MasterData\Policies;

use App\Models\User;
use App\Modules\MasterData\Models\Supplier;

class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('supplier.view') || $user->can('supplier.manage');
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $user->can('supplier.view') || $user->can('supplier.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('supplier.manage');
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->can('supplier.manage');
    }
}
