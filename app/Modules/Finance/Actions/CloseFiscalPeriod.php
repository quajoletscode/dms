<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Models\FiscalPeriod;
use App\Support\Exceptions\IllegalTransitionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

final class CloseFiscalPeriod
{
    public function execute(FiscalPeriod $fiscalPeriod): FiscalPeriod
    {
        Gate::authorize('fiscal_period.close');

        if (! $fiscalPeriod->isOpen()) {
            throw new IllegalTransitionException("Fiscal period #{$fiscalPeriod->id} is already closed.");
        }

        $fiscalPeriod->update([
            'status' => 'closed',
            'closed_by' => Auth::id(),
            'closed_at' => now(),
        ]);

        return $fiscalPeriod->refresh();
    }
}
