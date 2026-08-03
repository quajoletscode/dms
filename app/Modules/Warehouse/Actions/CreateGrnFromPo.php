<?php

namespace App\Modules\Warehouse\Actions;

use App\Modules\MasterData\Actions\CreateBatch;
use App\Modules\MasterData\Models\Batch;
use App\Modules\Warehouse\Domain\Exceptions\OverReceiptNotAllowedException;
use App\Modules\Warehouse\Models\Grn;
use App\Modules\Warehouse\Models\GrnItem;
use App\Modules\Warehouse\Models\PurchaseOrder;
use App\Modules\Warehouse\Models\PurchaseOrderItem;
use App\Support\DocumentNumberGenerator;
use App\Support\Money;
use App\Support\Quantity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * GRN-01/GRN-02: create a GRN (draft) against an approved PO. Receiving
 * beyond the PO's remaining quantity requires grn.override (GRN-04's
 * "discrepancies... flagged" is enforced here as a hard block by default).
 */
final class CreateGrnFromPo
{
    public function __construct(
        private readonly DocumentNumberGenerator $numbers,
        private readonly CreateBatch $createBatch,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $items  po_item_id, qty_received, unit_cost, batch_no?, expiry_date?, allow_expired?
     */
    public function execute(PurchaseOrder $po, array $items, ?string $invoiceRef = null, ?string $clientUuid = null): Grn
    {
        return DB::transaction(function () use ($po, $items, $invoiceRef, $clientUuid) {
            $grn = Grn::query()->create([
                'no' => $this->numbers->next('grn', 'warehouse', $po->warehouse_id),
                'po_id' => $po->id,
                'supplier_id' => $po->supplier_id,
                'warehouse_id' => $po->warehouse_id,
                'invoice_ref' => $invoiceRef,
                'status' => 'draft',
                'client_uuid' => $clientUuid,
            ]);

            foreach ($items as $line) {
                $poItem = PurchaseOrderItem::query()->whereKey($line['po_item_id'])->firstOrFail();
                $requestedQty = Quantity::fromString($line['qty_received']);
                $remaining = $poItem->qty_ordered->subtract($poItem->qty_received);

                if ($requestedQty->greaterThan($remaining) && ! (Auth::user()?->can('grn.override') ?? false)) {
                    throw new OverReceiptNotAllowedException(
                        "Receiving {$requestedQty} of product #{$poItem->product_id} exceeds the remaining PO quantity ({$remaining}); grn.override permission required."
                    );
                }

                GrnItem::query()->create([
                    'grn_id' => $grn->id,
                    'po_item_id' => $poItem->id,
                    'product_id' => $poItem->product_id,
                    'batch_id' => $this->resolveBatchId($poItem->product_id, $line),
                    'qty_received' => $requestedQty,
                    'unit_cost' => Money::fromMajor((string) $line['unit_cost'])->minorUnits,
                ]);
            }

            return $grn->load('items');
        });
    }

    /**
     * @param  array<string, mixed>  $line
     */
    private function resolveBatchId(int $productId, array $line): ?int
    {
        if (isset($line['batch_id'])) {
            return (int) $line['batch_id'];
        }

        if (! isset($line['batch_no'])) {
            return null;
        }

        $existing = Batch::query()
            ->where('product_id', $productId)
            ->where('batch_no', $line['batch_no'])
            ->first();

        if ($existing !== null) {
            return $existing->id;
        }

        return $this->createBatch->execute([
            'product_id' => $productId,
            'batch_no' => $line['batch_no'],
            'expiry_date' => $line['expiry_date'] ?? null,
            'allow_expired' => $line['allow_expired'] ?? false,
        ])->id;
    }
}
