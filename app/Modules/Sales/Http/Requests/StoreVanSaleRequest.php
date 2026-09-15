<?php

namespace App\Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVanSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('van.sale') ?? false;
    }

    /**
     * Drops blank line rows before validation runs and applies one shared sale
     * date to every bill row.
     */
    protected function prepareForValidation(): void
    {
        $saleDate = $this->input('sale_date') ?: today()->toDateString();

        $items = collect($this->array('items'))
            ->filter(fn (array $line) => filled($line['product_id'] ?? null) && filled($line['qty'] ?? null))
            ->map(fn (array $line) => [
                'product_id' => $line['product_id'],
                'qty' => $line['qty'],
                'unit_price' => $line['unit_price'] ?? '0',
                'discount' => $line['discount'] ?? '0',
                'service_date' => $saleDate,
                'vehicle_no' => blank($line['vehicle_no'] ?? null) ? null : $line['vehicle_no'],
                'line_description' => blank($line['line_description'] ?? null) ? null : $line['line_description'],
            ])
            ->values()
            ->all();

        $this->merge(['items' => $items, 'sale_date' => $saleDate]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'van_storage_id' => ['required', 'integer', 'exists:van_storages,id'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'sale_date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.service_date' => ['required', 'date'],
            'items.*.vehicle_no' => ['nullable', 'string', 'max:50'],
            'items.*.line_description' => ['nullable', 'string', 'max:255'],
            'items.*.qty' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['required', 'numeric', 'min:0'],
            'payments' => ['present', 'array'],
            'payments.*.method' => ['required', 'string', 'in:cash,mobile_money,card,bank_transfer'],
            'payments.*.amount' => ['required', 'numeric', 'min:0.01'],
            'payments.*.reference' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Add at least one bill line before submitting.',
        ];
    }
}
