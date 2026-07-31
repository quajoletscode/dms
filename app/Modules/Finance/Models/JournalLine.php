<?php

namespace App\Modules\Finance\Models;

use App\Casts\MoneyCast;
use App\Support\AppendOnly;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Append-only. Corrections are reversal entries only — see AppendOnly and the
 * 2026_07_31_060508 immutability-trigger migration.
 *
 * @property int $id
 * @property int $journal_entry_id
 * @property int $account_id
 * @property Money $debit
 * @property Money $credit
 */
class JournalLine extends Model
{
    use AppendOnly;

    /**
     * No updated_at column exists — this table never updates rows.
     */
    const UPDATED_AT = null;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'debit' => MoneyCast::class,
            'credit' => MoneyCast::class,
            'created_at' => 'datetime',
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
     * @return BelongsTo<ChartOfAccount, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function partner(): MorphTo
    {
        return $this->morphTo();
    }
}
