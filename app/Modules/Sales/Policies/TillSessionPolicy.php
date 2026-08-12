<?php

namespace App\Modules\Sales\Policies;

use App\Models\User;
use App\Modules\Sales\Models\TillSession;

/**
 * No cross-cashier oversight permission exists yet — a till session is only
 * ever visible to and operable by the user who opened it.
 */
class TillSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sales.pos');
    }

    public function view(User $user, TillSession $tillSession): bool
    {
        return $tillSession->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('sales.pos');
    }

    public function update(User $user, TillSession $tillSession): bool
    {
        return $tillSession->user_id === $user->id;
    }
}
