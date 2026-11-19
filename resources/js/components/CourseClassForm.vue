<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import CourseClassController from '@/actions/App/Http/Controllers/School/CourseClassController';
import FormField from '@/components/FormField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export type StaffOption = {
    id: number;
    name: string;
};

export type CourseClassRecord = {
    id: number;
    name: string;
    staff_id: number | null;
};

const props = defineProps<{
    course: {
        id: number;
        name: string;
    };
    staff: StaffOption[];
    courseClass?: CourseClassRecord | null;
}>();

const isEditing = computed(() => Boolean(props.courseClass));

const form = useForm({
    name: props.courseClass?.name ?? '',
    staff_id: props.courseClass?.staff_id ?? null,
});

const teacherId = computed<string>({
    get: () => (form.staff_id === null ? 'none' : String(form.staff_id)),
    set: (value) => {
        form.staff_id = value === 'none' ? null : Number(value);
    },
});

function submit(): void {
    if (props.courseClass) {
        form.put(CourseClassController.update.url(props.courseClass.id), {
            preserveScroll: true,
        });

        return;
    }

    form.post(CourseClassController.store.url(props.course.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <p class="text-sm text-muted-foreground">
            Class for <span class="font-medium">{{ course.name }}</span
            >.
        </p>

        <FormField
            label="Class name"
            field-id="name"
            :error="form.errors.name"
            hint="Class names must be unique within this course."
            required
        >
            <Input
                id="name"
                v-model="form.name"
                required
                placeholder="e.g. 10A"
            />
        </FormField>

        <FormField
            label="Teacher"
            field-id="staff_id"
            :error="form.errors.staff_id"
        >
            <Select v-model="teacherId">
                <SelectTrigger id="staff_id" class="w-full">
                    <SelectValue placeholder="No teacher assigned" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="none">No teacher assigned</SelectItem>
                    <SelectItem
                        v-for="member in staff"
                        :key="member.id"
                        :value="String(member.id)"
                    >
                        {{ member.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </FormField>

        <div class="flex items-center gap-4">
            <Button :disabled="form.processing" data-test="save-class">
                {{ isEditing ? 'Save changes' : 'Create class' }}
            </Button>
        </div>
    </form>
</template>
