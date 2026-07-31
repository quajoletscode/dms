<?php

namespace App\Modules\MasterData\Actions;

use App\Modules\MasterData\Models\Supplier;
use App\Support\Money;

final class CreateSupplier
{
    /**
     * @param  array{code: string, name: string, contact?: string|null, payment_terms?: string|null, opening_balance?: string|int|float, coa_account_id?: int|null}  $data
     */
    public function execute(array $data): Supplier
    {
        return Supplier::query()->create([
            'code' => $data['code'],
            'name' => $data['name'],
            'contact' => $data['contact'] ?? null,
            'payment_terms' => $data['payment_terms'] ?? null,
            'opening_balance' => Money::fromMajor($data['opening_balance'] ?? 0)->minorUnits,
            'coa_account_id' => $data['coa_account_id'] ?? null,
            'is_active' => true,
        ]);
    }
}
