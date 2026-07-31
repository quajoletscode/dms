<?php

namespace App\Modules\Warehouse\Console;

use App\Modules\Warehouse\Models\StockBalance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebuildStockBalancesCommand extends Command
{
    protected $signature = 'stock:rebuild-balances';

    protected $description = 'Recompute stock_balances from the stock_ledger aggregate (drift correction)';

    public function handle(): int
    {
        DB::transaction(function (): void {
            StockBalance::query()->delete();

            DB::table('stock_ledger')
                ->selectRaw('location_type, location_id, product_id, COALESCE(batch_id, 0) as batch_id, SUM(qty_in - qty_out) as qty_on_hand')
                ->groupBy('location_type', 'location_id', 'product_id', 'batch_id')
                ->get()
                ->each(function (object $row): void {
                    StockBalance::query()->create([
                        'location_type' => $row->location_type,
                        'location_id' => $row->location_id,
                        'product_id' => $row->product_id,
                        'batch_id' => $row->batch_id,
                        'qty_on_hand' => $row->qty_on_hand,
                        'updated_at' => now(),
                    ]);
                });
        });

        $this->info('Stock balances rebuilt from the ledger.');

        return self::SUCCESS;
    }
}
