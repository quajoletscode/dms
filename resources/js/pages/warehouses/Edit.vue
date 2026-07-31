<script lang="ts" setup>
import { Link, useForm } from '@inertiajs/vue3';
import { Loader2Icon, SaveIcon } from '@lucide/vue';
import Button from '@/components/ui/Button.vue';
import CheckToggler from '@/components/ui/inputs/CheckToggler.vue';
import SelectList from '@/components/ui/inputs/SelectList.vue';
import TextInput from '@/components/ui/inputs/TextInput.vue';
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

const submit = () => {
    form.put(update(props.warehouse.id).url);
};
</script>

<template>
    <div class="max-w-xl space-y-6 py-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Edit Warehouse</h1>
        </div>

        <form class="space-y-4 rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800" @submit.prevent="submit">
            <TextInput label="Code" required v-model="form.code" :error="form.errors.code" :disabled="form.processing" />
            <TextInput label="Name" required v-model="form.name" :error="form.errors.name" :disabled="form.processing" />
            <TextInput label="Location" v-model="form.location" :error="form.errors.location" :disabled="form.processing" />
            <SelectList
                label="Manager"
                v-model="form.manager_id"
                :options="props.managers.map((m) => ({ label: m.name, value: m.id }))"
                placeholder="No manager assigned"
                :error="form.errors.manager_id"
                :disabled="form.processing"
            />
            <CheckToggler v-model="form.is_active" label="Active" />

            <div class="flex justify-end gap-3">
                <Link :href="index().url" class="button ghost">Cancel</Link>
                <Button type="submit" class="flex items-center gap-2" :disabled="form.processing">
                    <Loader2Icon :size="18" class="animate-spin" v-if="form.processing" />
                    <SaveIcon :size="18" v-else />
                    Save
                </Button>
            </div>
        </form>
    </div>
</template>
