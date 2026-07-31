<?php

namespace App\Modules\MasterData\Actions;

use App\Modules\MasterData\Models\Product;
use App\Support\Money;

final class CreateProduct
{
    /**
     * @param  array<string, mixed>  $data  sku, name, unit_id, barcode?, category_id?, cost_price?, wholesale_price?, retail_price?, van_price?, tax_rate?, reorder_level?, track_expiry? — validated by StoreProductRequest
     */
    public function execute(array $data): Product
    {
        return Product::query()->create([
            'sku' => $data['sku'],
            'barcode' => $data['barcode'] ?? null,
            'name' => $data['name'],
            'category_id' => $data['category_id'] ?? null,
            'unit_id' => $data['unit_id'],
            'cost_price' => Money::fromMajor((string) ($data['cost_price'] ?? 0))->minorUnits,
            'wholesale_price' => Money::fromMajor((string) ($data['wholesale_price'] ?? 0))->minorUnits,
            'retail_price' => Money::fromMajor((string) ($data['retail_price'] ?? 0))->minorUnits,
            'van_price' => Money::fromMajor((string) ($data['van_price'] ?? 0))->minorUnits,
            'tax_rate' => $data['tax_rate'] ?? 0,
            'reorder_level' => $data['reorder_level'] ?? 0,
            'track_expiry' => $data['track_expiry'] ?? false,
            'is_active' => true,
        ]);
    }
}
