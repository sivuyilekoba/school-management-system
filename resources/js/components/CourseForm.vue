<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import CourseController from '@/actions/App/Http/Controllers/School/CourseController';
import FormField from '@/components/FormField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

export type CourseRecord = {
    id: number;
    name: string;
    short_name: string | null;
    description: string | null;
};

const props = defineProps<{
    course?: CourseRecord | null;
}>();

const isEditing = computed(() => Boolean(props.course));

const form = useForm({
    name: props.course?.name ?? '',
    short_name: props.course?.short_name ?? '',
    description: props.course?.description ?? '',
});

function submit(): void {
    if (props.course) {
        form.put(CourseController.update.url(props.course.id), {
            preserveScroll: true,
        });

        return;
    }

    form.post(CourseController.store.url(), { preserveScroll: true });
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <div class="grid gap-4 sm:grid-cols-[2fr_1fr]">
            <FormField
                label="Course name"
                field-id="name"
                :error="form.errors.name"
                required
            >
                <Input
                    id="name"
                    v-model="form.name"
                    required
                    placeholder="e.g. Mathematics"
                />
            </FormField>

            <FormField
                label="Short name"
                field-id="short_name"
                :error="form.errors.short_name"
            >
                <Input
                    id="short_name"
                    v-model="form.short_name"
                    placeholder="e.g. MATH"
                />
            </FormField>
        </div>

        <FormField
            label="Description"
            field-id="description"
            :error="form.errors.description"
        >
            <textarea
                id="description"
                v-model="form.description"
                rows="3"
                class="block w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                placeholder="Optional summary of this course"
            />
        </FormField>

        <div class="flex items-center gap-4">
            <Button :disabled="form.processing" data-test="save-course">
                {{ isEditing ? 'Save changes' : 'Create course' }}
            </Button>
        </div>
    </form>
</template>
