<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Domain\CollectionTransitions;
use App\Modules\Finance\Models\BankTransaction;
use App\Modules\Finance\Models\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Links one or more handed-over DSR collections to the bank deposit that
 * physically included their cash — the final hop of the DSR cash chain
 * (BNK-06: with_dsr -> handed_over -> deposited). No journal is posted here:
 * the GL impact of the cash reaching the bank was already recorded by
 * RecordBankDeposit ('bank.deposited'); this only records which collections
 * that deposit accounted for.
 */
final class MarkCollectionDeposited
{
    public function __construct(private readonly CollectionTransitions $transitions) {}

    /**
     * @param  list<int>  $collectionIds
     * @return list<Collection>
     */
    public function execute(BankTransaction $bankTransaction, array $collectionIds): array
    {
        Gate::authorize('bank.deposit');

        return DB::transaction(function () use ($bankTransaction, $collectionIds) {
            $collections = Collection::query()->whereIn('id', $collectionIds)->get();

            foreach ($collections as $collection) {
                $this->transitions->assertCanTransition($collection->status, 'deposited');

                $collection->update([
                    'status' => 'deposited',
                    'bank_transaction_id' => $bankTransaction->id,
                    'deposited_at' => now(),
                ]);
            }

            return array_values($collections->fresh()->all());
        });
    }
}
