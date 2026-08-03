<?php

namespace App\Modules\Warehouse\Policies;

use App\Models\User;
use App\Modules\Warehouse\Models\PurchaseOrder;

/**
 * Approval-specific rules (segregation of duties, po.approve) live inside
 * ApprovePurchaseOrder itself; this policy only gates viewing/creating/editing.
 */
class PurchaseOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('po.create') || $user->can('po.approve');
    }

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('po.create') || $user->can('po.approve');
    }

    public function create(User $user): bool
    {
        return $user->can('po.create');
    }

    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('po.create');
    }
}
