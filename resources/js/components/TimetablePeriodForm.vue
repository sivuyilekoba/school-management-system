<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import TimetablePeriodController from '@/actions/App/Http/Controllers/School/TimetablePeriodController';
import FormField from '@/components/FormField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

export type TimetablePeriodRecord = {
    id: number;
    name: string;
    start_time: string;
    end_time: string;
};

const props = defineProps<{
    period?: TimetablePeriodRecord | null;
}>();

const isEditing = computed(() => Boolean(props.period));

const form = useForm({
    name: props.period?.name ?? '',
    start_time: props.period?.start_time ?? '',
    end_time: props.period?.end_time ?? '',
});

function submit(): void {
    if (props.period) {
        form.put(TimetablePeriodController.update.url(props.period.id), {
            preserveScroll: true,
        });

        return;
    }

    form.post(TimetablePeriodController.store.url(), { preserveScroll: true });
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <FormField
            label="Period name"
            field-id="name"
            :error="form.errors.name"
            required
        >
            <Input
                id="name"
                v-model="form.name"
                required
                placeholder="e.g. Period 1"
            />
        </FormField>

        <div class="grid gap-4 sm:grid-cols-2">
            <FormField
                label="Start time"
                field-id="start_time"
                :error="form.errors.start_time"
                required
            >
                <Input
                    id="start_time"
                    v-model="form.start_time"
                    type="time"
                    required
                />
            </FormField>

            <FormField
                label="End time"
                field-id="end_time"
                :error="form.errors.end_time"
                required
            >
                <Input
                    id="end_time"
                    v-model="form.end_time"
                    type="time"
                    required
                />
            </FormField>
        </div>

        <div class="flex items-center gap-4">
            <Button :disabled="form.processing" data-test="save-period">
                {{ isEditing ? 'Save changes' : 'Create period' }}
            </Button>
        </div>
    </form>
</template>
