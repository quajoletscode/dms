<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Sales\Domain\Exceptions\TillSessionAlreadyOpenException;
use App\Modules\Sales\Models\TillSession;
use App\Support\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

final class OpenTillSession
{
    public function execute(int $warehouseId, string $openingFloat): TillSession
    {
        Gate::authorize('sales.pos');

        $userId = Auth::id();

        if (TillSession::query()->where('user_id', $userId)->where('status', 'open')->exists()) {
            throw new TillSessionAlreadyOpenException("User #{$userId} already has an open till session.");
        }

        return TillSession::query()->create([
            'warehouse_id' => $warehouseId,
            'user_id' => $userId,
            'opening_float' => Money::fromMajor($openingFloat)->minorUnits,
            'status' => 'open',
            'opened_at' => now(),
        ]);
    }
}
