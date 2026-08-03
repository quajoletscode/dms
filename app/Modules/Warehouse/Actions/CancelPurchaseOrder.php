<?php

namespace App\Modules\Warehouse\Actions;

use App\Modules\Warehouse\Domain\PurchaseOrderTransitions;
use App\Modules\Warehouse\Models\PurchaseOrder;

final class CancelPurchaseOrder
{
    public function __construct(private readonly PurchaseOrderTransitions $transitions) {}

    public function execute(PurchaseOrder $po): PurchaseOrder
    {
        $this->transitions->assertCanTransition($po->status, 'cancelled');

        $po->update(['status' => 'cancelled']);

        return $po->refresh();
    }
}
