<?php

namespace App\Modules\Warehouse\Http\Requests;

use App\Modules\Warehouse\Models\PurchaseOrder;
use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PurchaseOrder::class) ?? false;
    }

    /**
     * Drops blank line rows before validation runs, so a 15-row entry grid
     * with only 3 filled lines submits (and validates) just those 3.
     */
    protected function prepareForValidation(): void
    {
        $items = collect($this->array('items'))
            ->filter(fn (array $line) => filled($line['product_id'] ?? null) && filled($line['qty_ordered'] ?? null))
            ->values()
            ->all();

        $this->merge(['items' => $items]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'expected_delivery_date' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id', 'distinct'],
            'items.*.qty_ordered' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Add at least one product line before submitting.',
            'items.*.product_id.distinct' => 'Each product can only appear once — combine the quantities into a single line instead.',
        ];
    }
}
