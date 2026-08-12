<?php

namespace App\Modules\Finance\Models;

use App\Casts\MoneyCast;
use App\Models\User;
use App\Support\Auditable;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $no
 * @property string|null $memo
 * @property Money $total_debit
 * @property Money $total_credit
 * @property string $status
 * @property int|null $journal_entry_id
 * @property int|null $requested_by
 * @property int|null $approved_by
 * @property int|null $rejected_by
 * @property Carbon $requested_at
 * @property Carbon|null $approved_at
 * @property Carbon|null $rejected_at
 * @property string|null $rejection_reason
 */
class ManualJournal extends Model
{
    use Auditable;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_debit' => MoneyCast::class,
            'total_credit' => MoneyCast::class,
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<JournalEntry, $this>
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
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
     * @return HasMany<ManualJournalLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(ManualJournalLine::class);
    }
}
