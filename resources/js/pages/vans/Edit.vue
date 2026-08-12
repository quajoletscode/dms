<script lang="ts" setup>
import { Link, useForm } from '@inertiajs/vue3';
import { Loader2Icon, SaveIcon } from '@lucide/vue';
import Button from '@/components/ui/Button.vue';
import DetailField from '@/components/DetailField.vue';
import SelectList from '@/components/ui/inputs/SelectList.vue';
import TextAreaInput from '@/components/ui/inputs/TextAreaInput.vue';
import ObjectPageHeader from '@/components/ui/ObjectPageHeader.vue';
import ObjectPageNav from '@/components/ui/ObjectPageNav.vue';
import ObjectPageSection from '@/components/ui/ObjectPageSection.vue';
import { index, update } from '@/routes/vans';

interface Van {
    id: number;
    code: string;
    vehicle_no: string | null;
    is_active: boolean;
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

const sections = [
    { id: 'current', label: 'Current Assignment' },
    { id: 'assignment', label: 'Reassign' },
];

const submit = () => {
    form.put(update(props.van.id).url);
};
</script>

<template>
    <div class="max-w-4xl space-y-6 py-4">
        <ObjectPageHeader
            :title="`Reassign Van ${van.code}`"
            :back-href="index().url"
            :status="{ label: van.is_active ? 'Active' : 'Inactive', variant: van.is_active ? 'success' : 'neutral' }"
        />

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[12rem_1fr]">
            <ObjectPageNav :sections="sections" />

            <form class="space-y-6" @submit.prevent="submit">
                <ObjectPageSection id="current" title="Current Assignment">
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-5">
                        <DetailField label="Vehicle No.">{{ van.vehicle_no ?? '—' }}</DetailField>
                        <DetailField label="Warehouse">{{ van.warehouse.name }}</DetailField>
                        <DetailField label="Current DSR">{{ van.dsr?.name ?? 'Unassigned' }}</DetailField>
                    </dl>
                </ObjectPageSection>

                <ObjectPageSection id="assignment" title="Reassign">
                    <div class="space-y-4">
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
                    </div>

                    <template #footer>
                        <Link :href="index().url" class="button ghost">Cancel</Link>
                        <Button type="submit" class="flex items-center gap-2" :disabled="form.processing">
                            <Loader2Icon :size="18" class="animate-spin" v-if="form.processing" />
                            <SaveIcon :size="18" v-else />
                            Reassign
                        </Button>
                    </template>
                </ObjectPageSection>
            </form>
        </div>
    </div>
</template>
