<script lang="ts" setup>
import { Link, useForm } from '@inertiajs/vue3';
import { Loader2Icon, SaveIcon } from '@lucide/vue';
import Button from '@/components/ui/Button.vue';
import SelectList from '@/components/ui/inputs/SelectList.vue';
import TextAreaInput from '@/components/ui/inputs/TextAreaInput.vue';
import { index, update } from '@/routes/vans';

interface Van {
    id: number;
    code: string;
    vehicle_no: string | null;
    warehouse: { id: number; name: string };
    dsr: { id: number; name: string } | null;
}

const props = defineProps<{
    van: Van;
    dsrs: Array<{ id: number; name: string }>;
}>();

const form = useForm({
    dsr_user_id: (props.van.dsr?.id ?? '') as string | number,
    handover_note: '',
});

const submit = () => {
    form.put(update(props.van.id).url);
};
</script>

<template>
    <div class="max-w-xl space-y-6 py-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Reassign Van {{ van.code }}</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Currently: {{ van.dsr?.name ?? 'Unassigned' }} · {{ van.warehouse.name }}
            </p>
        </div>

        <form class="space-y-4 rounded-xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800" @submit.prevent="submit">
            <SelectList
                label="New DSR"
                required
                v-model="form.dsr_user_id"
                :options="props.dsrs.map((d) => ({ label: d.name, value: d.id }))"
                :error="form.errors.dsr_user_id"
                :disabled="form.processing"
            />
            <TextAreaInput
                label="Handover Note"
                helper-text="Required if the van currently has stock on board (e.g. stock count confirmation)."
                v-model="form.handover_note"
                :error="form.errors.handover_note"
                :disabled="form.processing"
            />

            <div class="flex justify-end gap-3">
                <Link :href="index().url" class="button ghost">Cancel</Link>
                <Button type="submit" class="flex items-center gap-2" :disabled="form.processing">
                    <Loader2Icon :size="18" class="animate-spin" v-if="form.processing" />
                    <SaveIcon :size="18" v-else />
                    Reassign
                </Button>
            </div>
        </form>
    </div>
</template>
