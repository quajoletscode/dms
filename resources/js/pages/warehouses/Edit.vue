<script lang="ts" setup>
import { Link, useForm } from '@inertiajs/vue3';
import { Loader2Icon, SaveIcon } from '@lucide/vue';
import Button from '@/components/ui/Button.vue';
import CheckToggler from '@/components/ui/inputs/CheckToggler.vue';
import SelectList from '@/components/ui/inputs/SelectList.vue';
import TextInput from '@/components/ui/inputs/TextInput.vue';
import ObjectPageHeader from '@/components/ui/ObjectPageHeader.vue';
import ObjectPageNav from '@/components/ui/ObjectPageNav.vue';
import ObjectPageSection from '@/components/ui/ObjectPageSection.vue';
import { index, update } from '@/routes/warehouses';

interface Warehouse {
    id: number;
    code: string;
    name: string;
    location: string | null;
    manager_id: number | null;
    is_active: boolean;
}

const props = defineProps<{
    warehouse: Warehouse;
    managers: Array<{ id: number; name: string }>;
}>();

const form = useForm({
    code: props.warehouse.code,
    name: props.warehouse.name,
    location: props.warehouse.location ?? '',
    manager_id: (props.warehouse.manager_id ?? '') as string | number,
    is_active: props.warehouse.is_active,
});

const sections = [
    { id: 'details', label: 'Details' },
    { id: 'status', label: 'Status' },
];

const submit = () => {
    form.put(update(props.warehouse.id).url);
};
</script>

<template>
    <div class="max-w-4xl space-y-6 py-4">
        <ObjectPageHeader
            :title="warehouse.name"
            :back-href="index().url"
            :status="{
                label: warehouse.is_active ? 'Active' : 'Inactive',
                variant: warehouse.is_active ? 'success' : 'neutral',
            }"
        />

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[12rem_1fr]">
            <ObjectPageNav :sections="sections" />

            <form class="space-y-6" @submit.prevent="submit">
                <ObjectPageSection id="details" title="Details">
                    <div class="space-y-4">
                        <TextInput
                            label="Code"
                            required
                            v-model="form.code"
                            :error="form.errors.code"
                            :disabled="form.processing"
                        />
                        <TextInput
                            label="Name"
                            required
                            v-model="form.name"
                            :error="form.errors.name"
                            :disabled="form.processing"
                        />
                        <TextInput
                            label="Location"
                            v-model="form.location"
                            :error="form.errors.location"
                            :disabled="form.processing"
                        />
                        <SelectList
                            label="Manager"
                            v-model="form.manager_id"
                            :options="
                                props.managers.map((m) => ({
                                    label: m.name,
                                    value: m.id,
                                }))
                            "
                            placeholder="No manager assigned"
                            :error="form.errors.manager_id"
                            :disabled="form.processing"
                        />
                    </div>
                </ObjectPageSection>

                <ObjectPageSection id="status" title="Status">
                    <CheckToggler v-model="form.is_active" label="Active" />

                    <template #footer>
                        <Link :href="index().url" class="button ghost"
                            >Cancel</Link
                        >
                        <Button
                            type="submit"
                            class="flex items-center gap-2"
                            :disabled="form.processing"
                        >
                            <Loader2Icon
                                :size="18"
                                class="animate-spin"
                                v-if="form.processing"
                            />
                            <SaveIcon :size="18" v-else />
                            Save
                        </Button>
                    </template>
                </ObjectPageSection>
            </form>
        </div>
    </div>
</template>
