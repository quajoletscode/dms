<?php

namespace App\Modules\MasterData\Actions;

use App\Modules\MasterData\Models\Supplier;
use App\Support\Money;

final class CreateSupplier
{
    /**
     * @param  array<string, mixed>  $data  code, name, contact?, payment_terms?, opening_balance?, coa_account_id? — validated by StoreSupplierRequest
     */
    public function execute(array $data): Supplier
    {
        return Supplier::query()->create([
            'code' => $data['code'],
            'name' => $data['name'],
            'contact' => $data['contact'] ?? null,
            'payment_terms' => $data['payment_terms'] ?? null,
            'opening_balance' => Money::fromMajor((string) ($data['opening_balance'] ?? 0))->minorUnits,
            'coa_account_id' => $data['coa_account_id'] ?? null,
            'is_active' => true,
        ]);
    }
}
