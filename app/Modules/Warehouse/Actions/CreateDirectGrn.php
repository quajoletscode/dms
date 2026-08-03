<?php

namespace App\Modules\Warehouse\Actions;

use App\Modules\MasterData\Actions\CreateBatch;
use App\Modules\MasterData\Models\Batch;
use App\Modules\Warehouse\Models\Grn;
use App\Modules\Warehouse\Models\GrnItem;
use App\Support\DocumentNumberGenerator;
use App\Support\Money;
use App\Support\Quantity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * GRN-01: a direct receipt without a PO — requires grn.direct.create.
 */
final class CreateDirectGrn
{
    public function __construct(
        private readonly DocumentNumberGenerator $numbers,
        private readonly CreateBatch $createBatch,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $items  product_id, qty_received, unit_cost, batch_no?, expiry_date?, allow_expired?
     */
    public function execute(int $supplierId, int $warehouseId, array $items, ?string $invoiceRef = null, ?string $clientUuid = null): Grn
    {
        Gate::authorize('grn.direct.create');

        return DB::transaction(function () use ($supplierId, $warehouseId, $items, $invoiceRef, $clientUuid) {
            $grn = Grn::query()->create([
                'no' => $this->numbers->next('grn', 'warehouse', $warehouseId),
                'po_id' => null,
                'supplier_id' => $supplierId,
                'warehouse_id' => $warehouseId,
                'invoice_ref' => $invoiceRef,
                'status' => 'draft',
                'client_uuid' => $clientUuid,
            ]);

            foreach ($items as $line) {
                GrnItem::query()->create([
                    'grn_id' => $grn->id,
                    'po_item_id' => null,
                    'product_id' => $line['product_id'],
                    'batch_id' => $this->resolveBatchId((int) $line['product_id'], $line),
                    'qty_received' => Quantity::fromString($line['qty_received']),
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
