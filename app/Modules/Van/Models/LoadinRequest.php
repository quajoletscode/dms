<?php

namespace App\Modules\Van\Models;

use App\Models\User;
use App\Modules\Warehouse\Models\Warehouse;
use App\Support\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $no
 * @property int $van_storage_id
 * @property int $warehouse_id
 * @property string $status
 * @property int|null $requested_by
 * @property int|null $accepted_by
 * @property Carbon|null $requested_at
 * @property Carbon|null $accepted_at
 * @property-read VanStorage $vanStorage van_storage_id is a required FK — always present once persisted
 * @property-read Warehouse $warehouse warehouse_id is a required FK — always present once persisted
 */
class LoadinRequest extends Model
{
    use Auditable;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<VanStorage, $this>
     */
    public function vanStorage(): BelongsTo
    {
        return $this->belongsTo(VanStorage::class);
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
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function accepter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    /**
     * @return HasMany<LoadinRequestItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(LoadinRequestItem::class);
    }
}
