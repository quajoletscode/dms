<?php

namespace App\Modules\Warehouse\Models;

use App\Models\User;
use App\Modules\Van\Models\VanStorage;
use App\Modules\Warehouse\Domain\WarehouseScope;
use App\Support\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $location
 * @property int|null $manager_id
 * @property bool $is_active
 */
class Warehouse extends Model
{
    use Auditable;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new WarehouseScope);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'warehouse_user');
    }

    /**
     * @return HasMany<VanStorage, $this>
     */
    public function vans(): HasMany
    {
        return $this->hasMany(VanStorage::class);
    }
}
