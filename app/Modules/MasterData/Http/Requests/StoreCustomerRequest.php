<?php

namespace App\Modules\MasterData\Http\Requests;

use App\Modules\MasterData\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'driver_vehicle_profiles' => $this->cleanDriverVehicleProfiles(),
        ]);
    }

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
            'driver_vehicle_profiles' => ['array'],
            'driver_vehicle_profiles.*.driver_name' => ['nullable', 'string', 'max:255'],
            'driver_vehicle_profiles.*.driver_phone' => ['nullable', 'string', 'max:50'],
            'driver_vehicle_profiles.*.vehicle_no' => ['nullable', 'string', 'max:50'],
            'driver_vehicle_profiles.*.vehicle_description' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function cleanDriverVehicleProfiles(): array
    {
        return collect($this->input('driver_vehicle_profiles', []))
            ->map(fn (mixed $profile): array => is_array($profile) ? [
                'driver_name' => blank($profile['driver_name'] ?? null) ? null : (string) $profile['driver_name'],
                'driver_phone' => blank($profile['driver_phone'] ?? null) ? null : (string) $profile['driver_phone'],
                'vehicle_no' => blank($profile['vehicle_no'] ?? null) ? null : (string) $profile['vehicle_no'],
                'vehicle_description' => blank($profile['vehicle_description'] ?? null) ? null : (string) $profile['vehicle_description'],
            ] : [])
            ->filter(fn (array $profile): bool => collect($profile)->filter()->isNotEmpty())
            ->values()
            ->all();
    }
}
