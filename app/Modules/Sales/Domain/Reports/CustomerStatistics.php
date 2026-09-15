<?php

namespace App\Modules\Sales\Domain\Reports;

use App\Modules\MasterData\Models\Customer;
use App\Modules\Sales\Domain\CustomerCreditLimitCheck;
use App\Modules\Sales\Models\Invoice;
use App\Modules\Sales\Models\SalesOrder;
use App\Support\Money;

/**
 * The Business-Central-style "Customer Statistics" figures shown on a
 * customer's detail page: balance, credit limit, outstanding orders/
 * invoices, payments received, overdue amount, and lifetime sales. Balance
 * is delegated to CustomerCreditLimitCheck so it stays derived live from the
 * ledger, never a cached column — exactly like the credit-limit check itself.
 *
 * BC fields with no equivalent here (Balance As Vendor, Refunds, Invoiced
 * Prepayment) are intentionally omitted — there's no vendor/prepayment
 * concept on a Customer in this app.
 */
final class CustomerStatistics
{
    public function __construct(private readonly CustomerCreditLimitCheck $creditCheck) {}

    /**
     * @return array{balance: Money, creditLimit: Money, outstandingOrders: Money, outstandingInvoices: Money, overdueAmount: Money, totalSales: Money, totalPayments: Money}
     */
    public function for(Customer $customer): array
    {
        $invoices = Invoice::query()
            ->where('customer_id', $customer->id)
            ->with('payments')
            ->get();

        $outstandingInvoices = Money::zero();
        $overdueAmount = Money::zero();
        $totalSales = Money::zero();
        $totalPayments = Money::zero();
        $today = now()->toDateString();

        foreach ($invoices as $invoice) {
            $totalSales = $totalSales->add($invoice->grand_total);

            $paid = $invoice->payments->reduce(
                fn (Money $carry, $payment) => $carry->add($payment->amount),
                Money::zero(),
            );
            $totalPayments = $totalPayments->add($paid);

            if ($invoice->status === 'paid') {
                continue;
            }

            $remaining = $invoice->grand_total->subtract($paid);

            if ($remaining->isNegative()) {
                continue;
            }

            $outstandingInvoices = $outstandingInvoices->add($remaining);

            if ($invoice->due_date !== null && $invoice->due_date->toDateString() < $today) {
                $overdueAmount = $overdueAmount->add($remaining);
            }
        }

        $outstandingOrders = SalesOrder::query()
            ->where('customer_id', $customer->id)
            ->whereNotIn('status', ['invoiced', 'cancelled'])
            ->get()
            ->reduce(fn (Money $carry, SalesOrder $order) => $carry->add($order->grand_total), Money::zero());

        return [
            'balance' => $this->creditCheck->outstandingBalance($customer),
            'creditLimit' => $customer->credit_limit,
            'outstandingOrders' => $outstandingOrders,
            'outstandingInvoices' => $outstandingInvoices,
            'overdueAmount' => $overdueAmount,
            'totalSales' => $totalSales,
            'totalPayments' => $totalPayments,
        ];
    }
}
