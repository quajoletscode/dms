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
 * @property string $type
 * @property Money $credit_limit
 * @property string|null $price_category
 * @property int|null $coa_account_id
 * @property string|null $rims_tenant_code
 * @property bool $is_active
 */
class Customer extends Model
{
    use Auditable;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credit_limit' => MoneyCast::class,
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
