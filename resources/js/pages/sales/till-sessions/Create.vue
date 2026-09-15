<script lang="ts" setup>
import { Link, useForm } from '@inertiajs/vue3';
import { Loader2Icon, SaveIcon } from '@lucide/vue';
import Button from '@/components/ui/Button.vue';
import NumberInput from '@/components/ui/inputs/NumberInput.vue';
import SelectList from '@/components/ui/inputs/SelectList.vue';
import ObjectPageHeader from '@/components/ui/ObjectPageHeader.vue';
import ObjectPageSection from '@/components/ui/ObjectPageSection.vue';
import { index, store } from '@/routes/till-sessions';

const props = defineProps<{
    warehouses: Array<{ id: number; name: string }>;
}>();

const form = useForm({
    warehouse_id: '' as string | number,
    opening_float: '' as string | number,
});

const submit = () => {
    form.post(store().url);
};
</script>

<template>
    <div class="max-w-xl space-y-6 py-4">
        <ObjectPageHeader title="Open Till Session" :back-href="index().url" />

        <form @submit.prevent="submit">
            <ObjectPageSection id="details" title="Details">
                <div class="space-y-4">
                    <SelectList
                        label="Warehouse"
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
                    <NumberInput
                        label="Opening Float"
                        required
                        v-model="form.opening_float"
                        :error="form.errors.opening_float"
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
                        Open Session
                    </Button>
                </template>
            </ObjectPageSection>
        </form>
    </div>
</template>
