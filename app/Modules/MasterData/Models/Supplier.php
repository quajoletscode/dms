<?php

namespace App\Modules\MasterData\Models;

use App\Casts\MoneyCast;
use App\Modules\Finance\Models\ChartOfAccount;
use App\Support\Auditable;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $contact
 * @property string|null $payment_terms
 * @property Money $opening_balance
 * @property int|null $coa_account_id
 * @property bool $is_active
 */
class Supplier extends Model
{
    use Auditable;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opening_balance' => MoneyCast::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ChartOfAccount, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'coa_account_id');
    }
}
