<?php

namespace App\Modules\MasterData\Actions;

use App\Modules\MasterData\Models\Product;
use App\Support\Money;

final class CreateProduct
{
    /**
     * @param  array{sku: string, name: string, unit_id: int, barcode?: string|null, category_id?: int|null, cost_price?: string|int|float, wholesale_price?: string|int|float, retail_price?: string|int|float, van_price?: string|int|float, tax_rate?: string|int|float, reorder_level?: string|int|float, track_expiry?: bool}  $data
     */
    public function execute(array $data): Product
    {
        return Product::query()->create([
            'sku' => $data['sku'],
            'barcode' => $data['barcode'] ?? null,
            'name' => $data['name'],
            'category_id' => $data['category_id'] ?? null,
            'unit_id' => $data['unit_id'],
            'cost_price' => Money::fromMajor($data['cost_price'] ?? 0)->minorUnits,
            'wholesale_price' => Money::fromMajor($data['wholesale_price'] ?? 0)->minorUnits,
            'retail_price' => Money::fromMajor($data['retail_price'] ?? 0)->minorUnits,
            'van_price' => Money::fromMajor($data['van_price'] ?? 0)->minorUnits,
            'tax_rate' => $data['tax_rate'] ?? 0,
            'reorder_level' => $data['reorder_level'] ?? 0,
            'track_expiry' => $data['track_expiry'] ?? false,
            'is_active' => true,
        ]);
    }
}
