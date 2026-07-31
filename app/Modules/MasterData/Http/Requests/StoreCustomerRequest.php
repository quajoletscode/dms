<?php

namespace App\Modules\MasterData\Http\Requests;

use App\Modules\MasterData\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Customer::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:customers,code'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:wholesale,retailer'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'price_category' => ['nullable', 'string', 'max:100'],
        ];
    }
}
