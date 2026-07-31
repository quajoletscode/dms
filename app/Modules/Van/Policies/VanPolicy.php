<?php

namespace App\Modules\Van\Policies;

use App\Models\User;
use App\Modules\Van\Models\VanStorage;

class VanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('van.view') || $user->can('van.manage');
    }

    public function view(User $user, VanStorage $van): bool
    {
        return $user->can('van.view') || $user->can('van.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('van.manage');
    }

    public function update(User $user, VanStorage $van): bool
    {
        return $user->can('van.manage');
    }
}
