<?php

namespace App\Modules\MasterData\Policies;

use App\Models\User;
use App\Modules\MasterData\Models\Product;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('product.view') || $user->can('product.manage');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->can('product.view') || $user->can('product.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('product.manage');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->can('product.manage');
    }
}
