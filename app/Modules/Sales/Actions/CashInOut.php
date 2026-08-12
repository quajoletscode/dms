<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Sales\Domain\Exceptions\TillSessionClosedException;
use App\Modules\Sales\Models\TillCashMovement;
use App\Modules\Sales\Models\TillSession;
use App\Support\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

final class CashInOut
{
    public function execute(TillSession $tillSession, string $type, string $amount, string $reason): TillCashMovement
    {
        Gate::authorize('sales.pos');

        if ($tillSession->status !== 'open') {
            throw new TillSessionClosedException("Till session #{$tillSession->id} is closed; cannot record a cash movement against it.");
        }

        return TillCashMovement::query()->create([
            'till_session_id' => $tillSession->id,
            'type' => $type,
            'amount' => Money::fromMajor($amount)->minorUnits,
            'reason' => $reason,
            'created_by' => Auth::id(),
            'created_at' => now(),
        ]);
    }
}
