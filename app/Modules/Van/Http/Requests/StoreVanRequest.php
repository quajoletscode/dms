<?php

namespace App\Modules\Van\Http\Requests;

use App\Modules\Van\Models\VanStorage;
use Illuminate\Foundation\Http\FormRequest;

class StoreVanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', VanStorage::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:van_storages,code'],
            'vehicle_no' => ['nullable', 'string', 'max:50'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'dsr_user_id' => ['nullable', 'integer', 'exists:users,id', 'unique:van_storages,dsr_user_id'],
        ];
    }
}
