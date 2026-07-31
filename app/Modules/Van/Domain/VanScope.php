<?php

namespace App\Modules\Van\Domain;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * A DSR only sees their own van. Other roles are unrestricted here (they're
 * gated by permissions/policies instead).
 *
 * @implements Scope<Model>
 */
class VanScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if ($user === null || ! $user->hasRole('dsr')) {
            return;
        }

        $builder->where($model->getTable().'.dsr_user_id', $user->getKey());
    }
}
