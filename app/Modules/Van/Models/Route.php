<?php

namespace App\Modules\Van\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $dsr_user_id
 * @property int $weekday
 */
class Route extends Model
{
    protected $table = 'routes';

    protected $guarded = [];

    /**
     * @return BelongsTo<User, $this>
     */
    public function dsr(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dsr_user_id');
    }

    /**
     * @return HasMany<RouteStop, $this>
     */
    public function stops(): HasMany
    {
        return $this->hasMany(RouteStop::class);
    }
}
