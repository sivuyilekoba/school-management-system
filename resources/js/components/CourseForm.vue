<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import CourseController from '@/actions/App/Http/Controllers/School/CourseController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

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
            <div class="grid gap-2">
                <Label for="name">Course name</Label>
                <Input
                    id="name"
                    v-model="form.name"
                    required
                    placeholder="e.g. Mathematics"
                />
                <InputError :message="form.errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="short_name">Short name</Label>
                <Input
                    id="short_name"
                    v-model="form.short_name"
                    placeholder="e.g. MATH"
                />
                <InputError :message="form.errors.short_name" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="description">Description</Label>
            <textarea
                id="description"
                v-model="form.description"
                rows="3"
                class="block w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                placeholder="Optional summary of this course"
            />
            <InputError :message="form.errors.description" />
        </div>

        <div class="flex items-center gap-4">
            <Button :disabled="form.processing" data-test="save-course">
                {{ isEditing ? 'Save changes' : 'Create course' }}
            </Button>
        </div>
    </form>
</template>
