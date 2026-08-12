<?php

namespace App\Modules\Van\Actions;

use App\Modules\Sales\Models\InvoicePayment;
use App\Modules\Van\Models\DsrSettlement;
use App\Modules\Van\Models\VanStorage;
use App\Modules\Warehouse\Models\StockLedgerEntry;
use App\Support\DocumentNumberGenerator;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Stock-value identity: opening + loadout - sales - loadin = closing, all
 * computed from stock_ledger for this van, so the identity holds by
 * construction — this is a regression guard on the settlement's own
 * aggregation logic (DsrSettlementIdentityInvariantTest), not an independent
 * physical count reconciliation (no separate valuation method exists yet).
 *
 * Cash identity: cash_expected = cash sales for this van/day (+ DSR
 * collections against pre-existing AR, once Phase 6 adds that flow).
 * Passing $cashCounted computes the variance and signs the settlement off in
 * the same call — there is no separate named sign-off Action in the plan;
 * CashShortOverVarianceSignoffTest exercises this optional path.
 */
final class GenerateDsrSettlement
{
    public function __construct(private readonly DocumentNumberGenerator $numbers) {}

    public function execute(int $vanStorageId, string $settlementDate, ?string $cashCounted = null): DsrSettlement
    {
        Gate::authorize('dsr.settlement.generate');

        return DB::transaction(function () use ($vanStorageId, $settlementDate, $cashCounted) {
            $van = VanStorage::query()->whereKey($vanStorageId)->firstOrFail();
            $date = Carbon::parse($settlementDate);
            $startOfDay = $date->copy()->startOfDay();
            $endOfDay = $date->copy()->endOfDay();

            $openingValue = $this->ledgerValueBefore($van->id, $startOfDay);
            $loadoutValue = $this->ledgerValueInRange($van->id, 'loadout', $startOfDay, $endOfDay, 'in');
            $salesValue = $this->ledgerValueInRange($van->id, 'invoice', $startOfDay, $endOfDay, 'out');
            $loadinValue = $this->ledgerValueInRange($van->id, 'loadin', $startOfDay, $endOfDay, 'out');
            $closingValue = $openingValue->add($loadoutValue)->subtract($salesValue)->subtract($loadinValue);

            $cashSalesValue = InvoicePayment::query()
                ->whereHas('invoice', fn ($query) => $query->where('van_storage_id', $van->id))
                ->where('method', 'cash')
                ->whereBetween('paid_at', [$startOfDay, $endOfDay])
                ->get()
                ->reduce(fn (Money $carry, InvoicePayment $payment) => $carry->add($payment->amount), Money::zero());

            $cashExpected = $cashSalesValue;

            $existing = DsrSettlement::query()
                ->where('van_storage_id', $van->id)
                ->whereDate('settlement_date', $date->toDateString())
                ->first();

            $attributes = [
                'van_storage_id' => $van->id,
                'dsr_user_id' => $van->dsr_user_id,
                'settlement_date' => $date->toDateString(),
                'opening_value' => $openingValue->minorUnits,
                'loadout_value' => $loadoutValue->minorUnits,
                'sales_value' => $salesValue->minorUnits,
                'loadin_value' => $loadinValue->minorUnits,
                'closing_value' => $closingValue->minorUnits,
                'cash_expected' => $cashExpected->minorUnits,
                'generated_by' => Auth::id(),
                'generated_at' => now(),
            ];

            if ($cashCounted !== null) {
                $counted = Money::fromMajor($cashCounted);
                $attributes['cash_counted'] = $counted->minorUnits;
                $attributes['cash_variance'] = $counted->subtract($cashExpected)->minorUnits;
                $attributes['status'] = 'signed_off';
                $attributes['signed_off_by'] = Auth::id();
                $attributes['signed_off_at'] = now();
            } elseif ($existing === null) {
                $attributes['status'] = 'generated';
            }

            if ($existing !== null) {
                $existing->update($attributes);

                return $existing->refresh();
            }

            $attributes['no'] = $this->numbers->next('dsr_settlement', 'warehouse', $van->warehouse_id);

            return DsrSettlement::query()->create($attributes);
        });
    }

    private function ledgerValueBefore(int $vanStorageId, Carbon $before): Money
    {
        return StockLedgerEntry::query()
            ->where('location_type', 'van')
            ->where('location_id', $vanStorageId)
            ->where('created_at', '<', $before)
            ->get()
            ->reduce(
                fn (Money $carry, StockLedgerEntry $entry) => $carry->add($entry->unit_cost->multiply((string) $entry->qty_in->subtract($entry->qty_out))),
                Money::zero(),
            );
    }

    private function ledgerValueInRange(int $vanStorageId, string $docType, Carbon $from, Carbon $to, string $direction): Money
    {
        return StockLedgerEntry::query()
            ->where('location_type', 'van')
            ->where('location_id', $vanStorageId)
            ->where('doc_type', $docType)
            ->whereBetween('created_at', [$from, $to])
            ->get()
            ->reduce(function (Money $carry, StockLedgerEntry $entry) use ($direction) {
                $qty = $direction === 'in' ? $entry->qty_in : $entry->qty_out;

                return $carry->add($entry->unit_cost->multiply((string) $qty));
            }, Money::zero());
    }
}
