<script lang="ts" setup>
import { PlusIcon, Trash2Icon } from '@lucide/vue';
import Button from '@/components/ui/Button.vue';
import TextInput from '@/components/ui/inputs/TextInput.vue';

export interface DriverVehicleProfile {
    driver_name: string;
    driver_phone: string;
    vehicle_no: string;
    vehicle_description: string;
}

const props = withDefaults(
    defineProps<{
        disabled?: boolean;
        errors?: Partial<Record<string, string>>;
    }>(),
    {
        disabled: false,
        errors: () => ({}),
    },
);

const profiles = defineModel<DriverVehicleProfile[]>({ required: true });

const blankProfile = (): DriverVehicleProfile => ({
    driver_name: '',
    driver_phone: '',
    vehicle_no: '',
    vehicle_description: '',
});

const addProfile = () => {
    profiles.value.push(blankProfile());
};

const removeProfile = (index: number) => {
    profiles.value.splice(index, 1);

    if (profiles.value.length === 0) {
        addProfile();
    }
};

const fieldError = (index: number, field: keyof DriverVehicleProfile) => {
    return props.errors[`driver_vehicle_profiles.${index}.${field}`];
};

defineExpose({ addProfile });
</script>

<template>
    <section
        class="space-y-4 rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/40"
    >
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2
                    class="text-sm font-semibold text-slate-900 dark:text-slate-100"
                >
                    Drivers / Vehicles
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Saved against this debtor for quick selection on receipts.
                </p>
            </div>

            <Button
                type="button"
                variant="ghost"
                class="h-8 px-2 py-1 text-xs"
                :disabled="props.disabled"
                title="Add driver or vehicle"
                @click="addProfile"
            >
                <PlusIcon :size="15" />
                Add Line
            </Button>
        </div>

        <div class="space-y-3">
            <div
                v-for="(profile, index) in profiles"
                :key="index"
                class="grid grid-cols-1 items-start gap-3 rounded-md border border-slate-200 bg-white p-3 md:grid-cols-[1.1fr_0.9fr_1fr_1.2fr_auto] dark:border-slate-700 dark:bg-slate-800"
            >
                <TextInput
                    label="Driver"
                    v-model="profile.driver_name"
                    :disabled="props.disabled"
                    :error="fieldError(index, 'driver_name')"
                    placeholder="Driver name"
                />
                <TextInput
                    label="Phone"
                    v-model="profile.driver_phone"
                    :disabled="props.disabled"
                    :error="fieldError(index, 'driver_phone')"
                    placeholder="Phone number"
                />
                <TextInput
                    label="Vehicle"
                    v-model="profile.vehicle_no"
                    :disabled="props.disabled"
                    :error="fieldError(index, 'vehicle_no')"
                    placeholder="Vehicle number"
                />
                <TextInput
                    label="Vehicle Description"
                    v-model="profile.vehicle_description"
                    :disabled="props.disabled"
                    :error="fieldError(index, 'vehicle_description')"
                    placeholder="Optional"
                />
                <Button
                    type="button"
                    variant="ghost"
                    class="mt-6 h-9 px-2 py-1"
                    :disabled="props.disabled"
                    title="Remove line"
                    @click="removeProfile(index)"
                >
                    <Trash2Icon :size="15" />
                </Button>
            </div>
        </div>
    </section>
</template>
