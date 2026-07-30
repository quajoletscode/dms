<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { DownloadIcon, Loader2Icon, UploadIcon } from '@lucide/vue';
import { ref } from 'vue';
import Button from '@/components/ui/Button.vue';
import Modal from '@/components/ui/modal/Modal.vue';

const props = defineProps<{
    isOpen: boolean;
    uploadUrl: string;
    templateUrl: string;
    /** When provided, shows a "Download existing records" link for bulk-editing via re-upload. */
    exportUrl?: string;
    /** e.g. "Attendants" — used in the title and helper copy. */
    resourceLabel: string;
}>();

const emit = defineEmits<{ close: [] }>();

const form = useForm({ file: null as File | null });
const fileInput = ref<HTMLInputElement | null>(null);

const onFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    form.file = target.files?.[0] ?? null;
    form.clearErrors('file');
};

const close = () => {
    form.reset();
    form.clearErrors();

    if (fileInput.value) {
        fileInput.value.value = '';
    }

    emit('close');
};

const submit = () => {
    if (!form.file) {
        form.setError('file', 'Please choose a CSV file to upload.');

        return;
    }

    form.post(props.uploadUrl, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: close,
    });
};
</script>

<template>
    <Modal :title="`Bulk Import — ${resourceLabel}`" :is-open="isOpen" @close="close" size="md" :static="true">
        <div class="space-y-4">
            <p class="text-sm text-slate-600 dark:text-slate-400">
                Upload a CSV to create or update {{ resourceLabel.toLowerCase() }} in bulk. Rows with an
                <strong>ID</strong> column matching an existing record update it; rows without one create a new
                record. If any row fails validation, nothing is saved.
            </p>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">
                <a :href="templateUrl" download
                  class="inline-flex items-center gap-2 text-sm font-medium text-primary-light hover:underline">
                    <DownloadIcon :size="15" />
                    Download CSV template
                </a>

                <a v-if="exportUrl" :href="exportUrl" download
                  class="inline-flex items-center gap-2 text-sm font-medium text-primary-light hover:underline">
                    <DownloadIcon :size="15" />
                    Download existing {{ resourceLabel.toLowerCase() }}
                </a>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">CSV File</label>
                <input ref="fileInput" type="file" accept=".csv,text/csv" @change="onFileChange"
                  class="block w-full rounded-md border border-slate-300 text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200 dark:border-slate-700 dark:text-slate-300 dark:file:bg-slate-800 dark:hover:file:bg-slate-700 dark:file:text-slate-200" />
                <p v-if="form.errors.file" class="mt-1 text-xs text-red-500">{{ form.errors.file }}</p>
            </div>
        </div>

        <template #footer>
            <Button variant="ghost" type="button" @click="close" :disabled="form.processing">Cancel</Button>
            <Button type="button" :disabled="form.processing" @click="submit">
                <Loader2Icon v-if="form.processing" :size="16" class="animate-spin" />
                <UploadIcon v-else :size="16" />
                Upload
            </Button>
        </template>
    </Modal>
</template>
