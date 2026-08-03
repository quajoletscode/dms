<?php

namespace App\Modules\Warehouse\Actions;

use App\Modules\Warehouse\Domain\PurchaseOrderTransitions;
use App\Modules\Warehouse\Models\PurchaseOrder;
use App\Support\Exceptions\SegregationOfDutiesException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * The user who created a PO cannot approve it (spec §3.2 approval separation).
 */
final class ApprovePurchaseOrder
{
    public function __construct(private readonly PurchaseOrderTransitions $transitions) {}

    public function execute(PurchaseOrder $po): PurchaseOrder
    {
        Gate::authorize('po.approve');

        $approverId = Auth::id();

        if ($approverId !== null && $po->created_by === $approverId) {
            throw new SegregationOfDutiesException("The creator of purchase order [{$po->no}] cannot also approve it.");
        }

        $this->transitions->assertCanTransition($po->status, 'approved');

        $po->update(['status' => 'approved', 'approved_by' => $approverId]);

        return $po->refresh();
    }
}
