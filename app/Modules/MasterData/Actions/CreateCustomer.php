<?php

namespace App\Modules\MasterData\Actions;

use App\Modules\MasterData\Models\Customer;
use App\Support\Money;

final class CreateCustomer
{
    /**
     * @param  array<string, mixed>  $data  code, name, type?, credit_limit?, price_category?, coa_account_id?, rims_tenant_code? — validated by StoreCustomerRequest
     */
    public function execute(array $data): Customer
    {
        return Customer::query()->create([
            'code' => $data['code'],
            'name' => $data['name'],
            'type' => $data['type'] ?? 'wholesale',
            'credit_limit' => Money::fromMajor((string) ($data['credit_limit'] ?? 0))->minorUnits,
            'price_category' => $data['price_category'] ?? null,
            'coa_account_id' => $data['coa_account_id'] ?? null,
            'rims_tenant_code' => $data['rims_tenant_code'] ?? null,
            'driver_vehicle_profiles' => $data['driver_vehicle_profiles'] ?? [],
            'is_active' => true,
        ]);
    }
}
