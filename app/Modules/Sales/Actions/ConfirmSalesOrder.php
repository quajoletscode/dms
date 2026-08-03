<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Sales\Domain\SalesOrderTransitions;
use App\Modules\Sales\Models\SalesOrder;

final class ConfirmSalesOrder
{
    public function __construct(private readonly SalesOrderTransitions $transitions) {}

    public function execute(SalesOrder $salesOrder): SalesOrder
    {
        $this->transitions->assertCanTransition($salesOrder->status, 'confirmed');

        $salesOrder->update(['status' => 'confirmed']);

        return $salesOrder->refresh();
    }
}
