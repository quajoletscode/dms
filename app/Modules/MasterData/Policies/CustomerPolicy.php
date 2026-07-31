<?php

namespace App\Modules\MasterData\Policies;

use App\Models\User;
use App\Modules\MasterData\Models\Customer;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('customer.view') || $user->can('customer.manage');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->can('customer.view') || $user->can('customer.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('customer.manage');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->can('customer.manage');
    }
}
