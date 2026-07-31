<?php

namespace App\Modules\MasterData\Actions;

use App\Modules\MasterData\Models\Customer;
use App\Support\Money;

final class CreateCustomer
{
    /**
     * @param  array{code: string, name: string, type?: string, credit_limit?: string|int|float, price_category?: string|null, coa_account_id?: int|null, rims_tenant_code?: string|null}  $data
     */
    public function execute(array $data): Customer
    {
        return Customer::query()->create([
            'code' => $data['code'],
            'name' => $data['name'],
            'type' => $data['type'] ?? 'wholesale',
            'credit_limit' => Money::fromMajor($data['credit_limit'] ?? 0)->minorUnits,
            'price_category' => $data['price_category'] ?? null,
            'coa_account_id' => $data['coa_account_id'] ?? null,
            'rims_tenant_code' => $data['rims_tenant_code'] ?? null,
            'is_active' => true,
        ]);
    }
}
