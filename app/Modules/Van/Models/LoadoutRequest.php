<?php

namespace App\Modules\Van\Models;

use App\Models\User;
use App\Modules\Warehouse\Models\Warehouse;
use App\Support\Auditable;
use Illuminate\Database\Eloquent\Builder;
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
 * @property int|null $approved_by
 * @property int|null $loaded_by
 * @property int|null $received_by
 * @property int|null $rejected_by
 * @property Carbon|null $requested_at
 * @property Carbon|null $approved_at
 * @property Carbon|null $loaded_at
 * @property Carbon|null $received_at
 * @property Carbon|null $rejected_at
 * @property string|null $rejection_reason
 * @property-read VanStorage $vanStorage van_storage_id is a required FK — always present once persisted
 * @property-read Warehouse $warehouse warehouse_id is a required FK — always present once persisted
 */
class LoadoutRequest extends Model
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
            'approved_at' => 'datetime',
            'loaded_at' => 'datetime',
            'received_at' => 'datetime',
            'rejected_at' => 'datetime',
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
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * @return HasMany<LoadoutRequestItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(LoadoutRequestItem::class);
    }

    /**
     * Loaded but never confirmed received — LoadoutStuckInTransitReportTest.
     *
     * @param  Builder<LoadoutRequest>  $query
     * @return Builder<LoadoutRequest>
     */
    public function scopeStuckInTransit(Builder $query): Builder
    {
        return $query->where('status', 'loaded');
    }
}
