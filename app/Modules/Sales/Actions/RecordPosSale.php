<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Finance\Domain\PostingEngine;
use App\Modules\MasterData\Models\Customer;
use App\Modules\Sales\Domain\CustomerCreditLimitCheck;
use App\Modules\Sales\Domain\Exceptions\CreditLimitExceededException;
use App\Modules\Sales\Domain\Exceptions\DiscountOverrideRequiredException;
use App\Modules\Sales\Domain\Exceptions\TillSessionClosedException;
use App\Modules\Sales\Domain\InvoiceLineComposer;
use App\Modules\Sales\Models\Invoice;
use App\Modules\Sales\Models\TillSession;
use App\Support\DocumentNumberGenerator;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * POS-01/02/03: barcode/search cart → Invoice with source=pos, posted
 * immediately (no draft/confirm/fulfil lifecycle like a wholesale Sales
 * Order — a POS sale is final the moment it's rung up). Unlike
 * ConvertToInvoice, the credit-limit check nets out payments collected in
 * the same transaction: a walk-in cash sale against a credit_limit=0
 * customer never blocks as long as it's paid in full at the till.
 *
 * @phpstan-import-type ComposedLine from InvoiceLineComposer
 */
final class RecordPosSale
{
    public function __construct(
        private readonly DocumentNumberGenerator $numbers,
        private readonly InvoiceLineComposer $lineComposer,
        private readonly CustomerCreditLimitCheck $creditCheck,
        private readonly PostingEngine $postingEngine,
        private readonly RecordInvoicePayment $recordPayment,
    ) {}

    /**
     * @param  list<array{product_id:int, qty:string, unit_price:string, discount:string, service_date?:string|null, vehicle_no?:string|null, line_description?:string|null}>  $items
     * @param  list<array{method:string, amount:string, reference?:string}>  $payments
     */
    public function execute(int $tillSessionId, int $customerId, array $items, array $payments, ?string $saleDate = null): Invoice
    {
        Gate::authorize('sales.pos');

        return DB::transaction(function () use ($tillSessionId, $customerId, $items, $payments, $saleDate) {
            $tillSession = TillSession::query()->whereKey($tillSessionId)->firstOrFail();

            if ($tillSession->status !== 'open') {
                throw new TillSessionClosedException("Till session #{$tillSession->id} is closed; cannot record a sale against it.");
            }

            $customer = Customer::query()->whereKey($customerId)->firstOrFail();
            $computation = $this->lineComposer->compute($items);

            $this->assertDiscountsWithinThreshold($computation['lines']);

            $totalPayments = collect($payments)->reduce(
                fn (Money $carry, array $payment) => $carry->add(Money::fromMajor($payment['amount'])),
                Money::zero(),
            );
            $remainingAfterPayment = $computation['grandTotal']->subtract($totalPayments);

            if ($remainingAfterPayment->isPositive()
                && $this->creditCheck->wouldExceedLimit($customer, $remainingAfterPayment)
                && ! (Auth::user()?->can('sales.credit_override') ?? false)) {
                throw new CreditLimitExceededException(
                    "POS sale to {$customer->name} would leave {$remainingAfterPayment} unpaid, exceeding their credit limit ({$customer->credit_limit}); sales.credit_override permission required."
                );
            }

            $saleDateString = Carbon::parse($saleDate ?? now())->toDateString();

            $invoice = Invoice::query()->create([
                'no' => $this->numbers->next('invoice', 'warehouse', $tillSession->warehouse_id),
                'source' => 'pos',
                'customer_id' => $customer->id,
                'warehouse_id' => $tillSession->warehouse_id,
                'sales_order_id' => null,
                'proforma_invoice_id' => null,
                'due_date' => null,
                'invoice_date' => $saleDateString,
                'posting_date' => $saleDateString,
                'status' => 'unpaid',
                'created_by' => Auth::id(),
                'subtotal' => $computation['subtotal']->minorUnits,
                'tax_total' => $computation['taxTotal']->minorUnits,
                'discount_total' => $computation['discountTotal']->minorUnits,
                'grand_total' => $computation['grandTotal']->minorUnits,
            ]);

            $this->lineComposer->persist($invoice, 'warehouse', $tillSession->warehouse_id, $computation['lines']);

            $this->postingEngine->post('invoice.issued', $invoice->load('items'), date: $saleDateString);

            foreach ($payments as $payment) {
                $this->recordPayment->execute(
                    $invoice,
                    Money::fromMajor($payment['amount']),
                    $payment['method'],
                    $payment['reference'] ?? null,
                    null,
                    $tillSession->id,
                );
            }

            return $invoice->refresh()->load(['items', 'payments']);
        });
    }

    /**
     * @param  list<ComposedLine>  $lines
     */
    private function assertDiscountsWithinThreshold(array $lines): void
    {
        $thresholdPercent = (float) config('sales.pos_discount_override_threshold_percent');

        foreach ($lines as $line) {
            if ($line['discount']->isZero()) {
                continue;
            }

            $lineGross = $line['unit_price']->multiply((string) $line['qty']);

            if ($lineGross->isZero()) {
                continue;
            }

            $discountRatePercent = ($line['discount']->minorUnits / $lineGross->minorUnits) * 100;

            if ($discountRatePercent > $thresholdPercent && ! (Auth::user()?->can('pos.discount_override') ?? false)) {
                throw new DiscountOverrideRequiredException(
                    "Line discount of {$discountRatePercent}% on product #{$line['product']?->id} exceeds the {$thresholdPercent}% threshold; pos.discount_override permission required."
                );
            }
        }
    }
}
