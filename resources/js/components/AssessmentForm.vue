<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AssessmentController from '@/actions/App/Http/Controllers/School/AssessmentController';
import FormField from '@/components/FormField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

export type AssessmentRecord = {
    id: number;
    name: string;
    assessed_on: string;
    max_score: number;
};

const props = defineProps<{
    courseClass: {
        id: number;
        name: string;
    };
    assessment?: AssessmentRecord | null;
    defaults?: {
        assessed_on: string;
    };
}>();

const isEditing = computed(() => Boolean(props.assessment));

const form = useForm({
    name: props.assessment?.name ?? '',
    assessed_on:
        props.assessment?.assessed_on ?? props.defaults?.assessed_on ?? '',
    max_score: props.assessment?.max_score ?? 100,
});

function submit(): void {
    if (props.assessment) {
        form.put(AssessmentController.update.url(props.assessment.id), {
            preserveScroll: true,
        });

        return;
    }

    form.post(AssessmentController.store.url(props.courseClass.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <p class="text-sm text-muted-foreground">
            Assessment for
            <span class="font-medium">{{ courseClass.name }}</span
            >.
        </p>

        <FormField
            label="Name"
            field-id="name"
            :error="form.errors.name"
            required
        >
            <Input
                id="name"
                v-model="form.name"
                required
                placeholder="e.g. Term 1 test"
            />
        </FormField>

        <div class="grid gap-4 sm:grid-cols-2">
            <FormField
                label="Date"
                field-id="assessed_on"
                :error="form.errors.assessed_on"
                required
            >
                <Input
                    id="assessed_on"
                    v-model="form.assessed_on"
                    type="date"
                    required
                />
            </FormField>

            <FormField
                label="Maximum score"
                field-id="max_score"
                :error="form.errors.max_score"
                required
            >
                <Input
                    id="max_score"
                    v-model.number="form.max_score"
                    type="number"
                    min="0.01"
                    max="9999.99"
                    step="0.01"
                    required
                />
            </FormField>
        </div>

        <div class="flex items-center gap-4">
            <Button :disabled="form.processing" data-test="save-assessment">
                {{ isEditing ? 'Save changes' : 'Create assessment' }}
            </Button>
        </div>
    </form>
</template>
