<script lang="ts" setup>
import { Link, useForm } from '@inertiajs/vue3';
import { Loader2Icon, SaveIcon } from '@lucide/vue';
import Button from '@/components/ui/Button.vue';
import SelectList from '@/components/ui/inputs/SelectList.vue';
import TextInput from '@/components/ui/inputs/TextInput.vue';
import ObjectPageHeader from '@/components/ui/ObjectPageHeader.vue';
import ObjectPageSection from '@/components/ui/ObjectPageSection.vue';
import { index, store } from '@/routes/vans';

const props = defineProps<{
    warehouses: Array<{ id: number; name: string }>;
    dsrs: Array<{ id: number; name: string }>;
}>();

const form = useForm({
    code: '',
    vehicle_no: '',
    warehouse_id: '' as string | number,
    dsr_user_id: '' as string | number,
});

const submit = () => {
    form.post(store().url);
};
</script>

<template>
    <div class="max-w-2xl space-y-6 py-4">
        <ObjectPageHeader title="New Van Storage" :back-href="index().url" />

        <form @submit.prevent="submit">
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
                        label="Vehicle No."
                        v-model="form.vehicle_no"
                        :error="form.errors.vehicle_no"
                        :disabled="form.processing"
                    />
                    <SelectList
                        label="Parent Warehouse"
                        required
                        v-model="form.warehouse_id"
                        :options="
                            props.warehouses.map((w) => ({
                                label: w.name,
                                value: w.id,
                            }))
                        "
                        :error="form.errors.warehouse_id"
                        :disabled="form.processing"
                    />
                    <SelectList
                        label="DSR"
                        v-model="form.dsr_user_id"
                        :options="
                            props.dsrs.map((d) => ({
                                label: d.name,
                                value: d.id,
                            }))
                        "
                        placeholder="Unassigned"
                        :error="form.errors.dsr_user_id"
                        :disabled="form.processing"
                    />
                </div>

                <template #footer>
                    <Link :href="index().url" class="button ghost">Cancel</Link>
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
</template>
