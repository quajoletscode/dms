<?php

namespace App\Modules\Sales\Models;

use App\Casts\MoneyCast;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $till_session_id
 * @property string $type
 * @property Money $amount
 * @property string $reason
 * @property int|null $created_by
 * @property-read TillSession $tillSession till_session_id is a required FK — always present once persisted
 */
class TillCashMovement extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => MoneyCast::class,
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<TillSession, $this>
     */
    public function tillSession(): BelongsTo
    {
        return $this->belongsTo(TillSession::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
