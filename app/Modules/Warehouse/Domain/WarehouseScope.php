<?php

namespace App\Modules\Warehouse\Domain;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * A Warehouse Manager only sees warehouses assigned to them (via
 * warehouse_user); Super Admin and unauthenticated/console contexts see all.
 *
 * @implements Scope<Model>
 */
class WarehouseScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if ($user === null || $user->hasRole('super_admin')) {
            return;
        }

        $builder->whereIn(
            $model->getTable().'.id',
            DB::table('warehouse_user')->select('warehouse_id')->where('user_id', $user->getKey())
        );
    }
}
