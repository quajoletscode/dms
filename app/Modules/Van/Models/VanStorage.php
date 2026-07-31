<?php

namespace App\Modules\Van\Models;

use App\Models\User;
use App\Modules\Van\Domain\VanScope;
use App\Modules\Warehouse\Models\Warehouse;
use App\Support\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string|null $vehicle_no
 * @property int $warehouse_id
 * @property int|null $dsr_user_id
 * @property bool $is_active
 */
class VanStorage extends Model
{
    use Auditable;

    protected $table = 'van_storages';

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
        static::addGlobalScope(new VanScope);
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function dsr(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dsr_user_id');
    }

    /**
     * @return HasMany<VanDsrHistory, $this>
     */
    public function history(): HasMany
    {
        return $this->hasMany(VanDsrHistory::class, 'van_storage_id');
    }
}
