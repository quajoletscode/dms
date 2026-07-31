<?php

namespace App\Modules\Van\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $van_storage_id
 * @property int $dsr_user_id
 * @property Carbon $assigned_at
 * @property Carbon|null $unassigned_at
 * @property string|null $handover_note
 */
class VanDsrHistory extends Model
{
    protected $table = 'van_dsr_history';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'unassigned_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<VanStorage, $this>
     */
    public function van(): BelongsTo
    {
        return $this->belongsTo(VanStorage::class, 'van_storage_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function dsr(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dsr_user_id');
    }
}
