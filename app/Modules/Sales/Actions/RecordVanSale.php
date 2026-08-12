<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Finance\Domain\PostingEngine;
use App\Modules\MasterData\Models\Customer;
use App\Modules\Sales\Domain\CustomerCreditLimitCheck;
use App\Modules\Sales\Domain\Exceptions\CreditLimitExceededException;
use App\Modules\Sales\Domain\InvoiceLineComposer;
use App\Modules\Sales\Models\Invoice;
use App\Modules\Van\Models\VanStorage;
use App\Support\DocumentNumberGenerator;
use App\Support\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * A DSR field sale, issued straight from van stock: Invoice with
 * source=van, posted immediately (final the moment it's made, same
 * rationale as RecordPosSale — no draft/confirm/fulfil lifecycle). Feeds
 * GenerateDsrSettlement's sales/cash figures for the van/day. The
 * credit-limit check nets out same-transaction payments, exactly like
 * RecordPosSale — a fully-paid field sale never trips a credit_limit=0
 * retailer's limit.
 */
final class RecordVanSale
{
    public function __construct(
        private readonly DocumentNumberGenerator $numbers,
        private readonly InvoiceLineComposer $lineComposer,
        private readonly CustomerCreditLimitCheck $creditCheck,
        private readonly PostingEngine $postingEngine,
        private readonly RecordInvoicePayment $recordPayment,
    ) {}

    /**
     * @param  list<array{product_id:int, qty:string, unit_price:string, discount:string}>  $items
     * @param  list<array{method:string, amount:string, reference?:string}>  $payments
     */
    public function execute(int $vanStorageId, int $customerId, array $items, array $payments): Invoice
    {
        Gate::authorize('van.sale');

        return DB::transaction(function () use ($vanStorageId, $customerId, $items, $payments) {
            $van = VanStorage::query()->whereKey($vanStorageId)->firstOrFail();
            $customer = Customer::query()->whereKey($customerId)->firstOrFail();
            $computation = $this->lineComposer->compute($items);

            $totalPayments = collect($payments)->reduce(
                fn (Money $carry, array $payment) => $carry->add(Money::fromMajor($payment['amount'])),
                Money::zero(),
            );
            $remainingAfterPayment = $computation['grandTotal']->subtract($totalPayments);

            if ($remainingAfterPayment->isPositive()
                && $this->creditCheck->wouldExceedLimit($customer, $remainingAfterPayment)
                && ! (Auth::user()?->can('sales.credit_override') ?? false)) {
                throw new CreditLimitExceededException(
                    "Van sale to {$customer->name} would leave {$remainingAfterPayment} unpaid, exceeding their credit limit ({$customer->credit_limit}); sales.credit_override permission required."
                );
            }

            $invoice = Invoice::query()->create([
                'no' => $this->numbers->next('invoice', 'warehouse', $van->warehouse_id),
                'source' => 'van',
                'customer_id' => $customer->id,
                'warehouse_id' => $van->warehouse_id,
                'van_storage_id' => $van->id,
                'sales_order_id' => null,
                'proforma_invoice_id' => null,
                'due_date' => null,
                'status' => 'unpaid',
                'created_by' => Auth::id(),
                'subtotal' => $computation['subtotal']->minorUnits,
                'tax_total' => $computation['taxTotal']->minorUnits,
                'discount_total' => $computation['discountTotal']->minorUnits,
                'grand_total' => $computation['grandTotal']->minorUnits,
            ]);

            $this->lineComposer->persist($invoice, 'van', $van->id, $computation['lines']);

            $this->postingEngine->post('invoice.issued', $invoice->load('items'));

            foreach ($payments as $payment) {
                $this->recordPayment->execute(
                    $invoice,
                    Money::fromMajor($payment['amount']),
                    $payment['method'],
                    $payment['reference'] ?? null,
                );
            }

            return $invoice->refresh()->load(['items', 'payments']);
        });
    }
}
