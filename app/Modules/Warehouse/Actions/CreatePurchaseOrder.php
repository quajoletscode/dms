<?php

namespace App\Modules\Warehouse\Actions;

use App\Modules\Warehouse\Models\PurchaseOrder;
use App\Modules\Warehouse\Models\PurchaseOrderItem;
use App\Support\DocumentNumberGenerator;
use App\Support\Money;
use App\Support\Quantity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class CreatePurchaseOrder
{
    public function __construct(private readonly DocumentNumberGenerator $numbers) {}

    /**
     * @param  array<int, array<string, mixed>>  $items  product_id, qty_ordered, unit_cost, discount?, tax?
     */
    public function execute(int $supplierId, int $warehouseId, array $items, ?string $expectedDeliveryDate = null): PurchaseOrder
    {
        return DB::transaction(function () use ($supplierId, $warehouseId, $items, $expectedDeliveryDate) {
            $po = PurchaseOrder::query()->create([
                'no' => $this->numbers->next('po', 'warehouse', $warehouseId),
                'supplier_id' => $supplierId,
                'warehouse_id' => $warehouseId,
                'status' => 'draft',
                'expected_delivery_date' => $expectedDeliveryDate,
                'created_by' => Auth::id(),
                'subtotal' => 0,
                'tax_total' => 0,
                'discount_total' => 0,
                'grand_total' => 0,
            ]);

            $subtotal = Money::zero();
            $taxTotal = Money::zero();
            $discountTotal = Money::zero();

            foreach ($items as $line) {
                $qty = Quantity::fromString($line['qty_ordered']);
                $unitCost = Money::fromMajor((string) $line['unit_cost']);
                $discount = Money::fromMajor((string) ($line['discount'] ?? 0));
                $tax = Money::fromMajor((string) ($line['tax'] ?? 0));

                PurchaseOrderItem::query()->create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $line['product_id'],
                    'qty_ordered' => $qty,
                    'qty_received' => '0.000',
                    'unit_cost' => $unitCost->minorUnits,
                    'discount' => $discount->minorUnits,
                    'tax' => $tax->minorUnits,
                ]);

                $subtotal = $subtotal->add($unitCost->multiply((string) $qty));
                $taxTotal = $taxTotal->add($tax);
                $discountTotal = $discountTotal->add($discount);
            }

            $grandTotal = $subtotal->add($taxTotal)->subtract($discountTotal);

            $po->update([
                'subtotal' => $subtotal->minorUnits,
                'tax_total' => $taxTotal->minorUnits,
                'discount_total' => $discountTotal->minorUnits,
                'grand_total' => $grandTotal->minorUnits,
            ]);

            return $po->load('items');
        });
    }
}
