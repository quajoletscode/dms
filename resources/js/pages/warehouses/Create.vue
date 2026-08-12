<script lang="ts" setup>
import { Link, useForm } from '@inertiajs/vue3';
import { Loader2Icon, SaveIcon } from '@lucide/vue';
import Button from '@/components/ui/Button.vue';
import SelectList from '@/components/ui/inputs/SelectList.vue';
import TextInput from '@/components/ui/inputs/TextInput.vue';
import ObjectPageHeader from '@/components/ui/ObjectPageHeader.vue';
import ObjectPageSection from '@/components/ui/ObjectPageSection.vue';
import { index, store } from '@/routes/warehouses';

const props = defineProps<{
    managers: Array<{ id: number; name: string }>;
}>();

const form = useForm({
    code: '',
    name: '',
    location: '',
    manager_id: '' as string | number,
});

const submit = () => {
    form.post(store().url);
};
</script>

<template>
    <div class="max-w-2xl space-y-6 py-4">
        <ObjectPageHeader title="New Warehouse" :back-href="index().url" />

        <form @submit.prevent="submit">
            <ObjectPageSection id="details" title="Details">
                <div class="space-y-4">
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
                </div>

                <template #footer>
                    <Link :href="index().url" class="button ghost">Cancel</Link>
                    <Button type="submit" class="flex items-center gap-2" :disabled="form.processing">
                        <Loader2Icon :size="18" class="animate-spin" v-if="form.processing" />
                        <SaveIcon :size="18" v-else />
                        Save
                    </Button>
                </template>
            </ObjectPageSection>
        </form>
    </div>
</template>
