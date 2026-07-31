<?php

namespace App\Modules\MasterData\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('customer')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('customers', 'code')->ignore($this->route('customer'))],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:wholesale,retailer'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'price_category' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }
}
