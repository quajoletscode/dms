<?php

namespace App\Modules\Finance\Models;

use App\Casts\MoneyCast;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $manual_journal_id
 * @property int $account_id
 * @property Money $debit
 * @property Money $credit
 * @property string|null $partner_type
 * @property int|null $partner_id
 * @property string|null $memo
 * @property-read ChartOfAccount $account account_id is a required FK — always present once persisted
 */
class ManualJournalLine extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'debit' => MoneyCast::class,
            'credit' => MoneyCast::class,
        ];
    }

    /**
     * @return BelongsTo<ManualJournal, $this>
     */
    public function manualJournal(): BelongsTo
    {
        return $this->belongsTo(ManualJournal::class);
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
