<?php

namespace App\Modules\Sales\Policies;

use App\Models\User;
use App\Modules\Sales\Models\Invoice;

/**
 * No manager-oversight permission exists yet — invoice.create/invoice.payment.record
 * holders (warehouse managers) already see everything by virtue of those broad
 * permissions; a cashier/DSR (sales.pos/van.sale only) is limited to invoices
 * they personally created.
 */
class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('invoice.create')
            || $user->can('invoice.payment.record')
            || $user->can('sales.pos')
            || $user->can('van.sale');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $invoice->created_by === $user->id
            || $user->can('invoice.create')
            || $user->can('invoice.payment.record');
    }
}
