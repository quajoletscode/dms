<?php

namespace App\Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePosSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('sales.pos') ?? false;
    }

    /**
     * Drops blank line rows before validation runs, same as
     * StorePurchaseOrderRequest, and defaults a blank discount to '0' since
     * InvoiceLineComposer::compute() reads it unconditionally.
     */
    protected function prepareForValidation(): void
    {
        $items = collect($this->array('items'))
            ->filter(fn (array $line) => filled($line['product_id'] ?? null) && filled($line['qty'] ?? null))
            ->map(fn (array $line) => [
                'product_id' => $line['product_id'],
                'qty' => $line['qty'],
                'unit_price' => $line['unit_price'] ?? '0',
                'discount' => $line['discount'] ?? '0',
            ])
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
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id', 'distinct'],
            'items.*.qty' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['required', 'numeric', 'min:0'],
            // A fully-credit POS sale with zero payments at ring-up is valid
            // business logic (RecordPosSale) — payments must be present, but
            // an empty array is allowed.
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
            'items.required' => 'Add at least one product line before submitting.',
            'items.*.product_id.distinct' => 'Each product can only appear once — combine the quantities into a single line instead.',
        ];
    }
}
