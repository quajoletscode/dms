<?php

namespace App\Modules\Van\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReassignVanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('van')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'dsr_user_id' => ['required', 'integer', 'exists:users,id', 'unique:van_storages,dsr_user_id'],
            'handover_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
