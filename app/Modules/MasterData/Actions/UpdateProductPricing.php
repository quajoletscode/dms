<?php

namespace App\Modules\MasterData\Actions;

use App\Modules\MasterData\Models\Product;
use App\Support\Money;

final class UpdateProductPricing
{
    /**
     * @param  array{cost_price?: string|int|float, wholesale_price?: string|int|float, retail_price?: string|int|float, van_price?: string|int|float, tax_rate?: string|int|float}  $data
     */
    public function execute(Product $product, array $data): Product
    {
        $updates = [];

        foreach (['cost_price', 'wholesale_price', 'retail_price', 'van_price'] as $field) {
            if (array_key_exists($field, $data)) {
                $updates[$field] = Money::fromMajor($data[$field])->minorUnits;
            }
        }

        if (array_key_exists('tax_rate', $data)) {
            $updates['tax_rate'] = $data['tax_rate'];
        }

        $product->update($updates);

        return $product->refresh();
    }
}
