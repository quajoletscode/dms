<?php

namespace App\Modules\Warehouse\Actions;

use App\Modules\Warehouse\Domain\PurchaseOrderTransitions;
use App\Modules\Warehouse\Models\PurchaseOrder;

final class SubmitPurchaseOrder
{
    public function __construct(private readonly PurchaseOrderTransitions $transitions) {}

    public function execute(PurchaseOrder $po): PurchaseOrder
    {
        $this->transitions->assertCanTransition($po->status, 'submitted');

        $po->update(['status' => 'submitted']);

        return $po->refresh();
    }
}
