<?php

namespace App\Modules\Sales\Http\Requests;

use App\Modules\Sales\Models\TillSession;
use Illuminate\Foundation\Http\FormRequest;

class OpenTillSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', TillSession::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'opening_float' => ['required', 'numeric', 'min:0'],
        ];
    }
}
