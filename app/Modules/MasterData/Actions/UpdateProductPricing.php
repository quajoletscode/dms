<?php

namespace App\Modules\MasterData\Actions;

use App\Modules\MasterData\Models\Product;
use App\Support\Money;

final class UpdateProductPricing
{
    /**
     * @param  array<string, mixed>  $data  cost_price?, wholesale_price?, retail_price?, van_price?, tax_rate? — validated by UpdateProductRequest
     */
    public function execute(Product $product, array $data): Product
    {
        $updates = [];

        foreach (['cost_price', 'wholesale_price', 'retail_price', 'van_price'] as $field) {
            if (array_key_exists($field, $data)) {
                $updates[$field] = Money::fromMajor((string) $data[$field])->minorUnits;
            }
        }

        if (array_key_exists('tax_rate', $data)) {
            $updates['tax_rate'] = $data['tax_rate'];
        }

        $product->update($updates);

        return $product->refresh();
    }
}
