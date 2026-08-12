<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Domain\PostingEngine;
use App\Modules\Finance\Models\Collection;
use App\Modules\Van\Models\VanStorage;
use App\Support\DocumentNumberGenerator;
use App\Support\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class RecordDsrCollection
{
    public function __construct(
        private readonly DocumentNumberGenerator $numbers,
        private readonly PostingEngine $postingEngine,
    ) {}

    public function execute(int $vanStorageId, int $customerId, int $invoiceId, string $amount): Collection
    {
        Gate::authorize('collection.record');

        return DB::transaction(function () use ($vanStorageId, $customerId, $invoiceId, $amount) {
            $van = VanStorage::query()->whereKey($vanStorageId)->firstOrFail();

            $collection = Collection::query()->create([
                'no' => $this->numbers->next('collection', 'warehouse', $van->warehouse_id),
                'van_storage_id' => $van->id,
                'warehouse_id' => $van->warehouse_id,
                'dsr_user_id' => Auth::id(),
                'customer_id' => $customerId,
                'invoice_id' => $invoiceId,
                'amount' => Money::fromMajor($amount)->minorUnits,
                'status' => 'with_dsr',
                'collected_at' => now(),
            ]);

            $this->postingEngine->post('dsr_cash.collected', $collection);

            return $collection->refresh();
        });
    }
}
