<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CourseClassForm from '@/components/CourseClassForm.vue';
import type { StaffOption } from '@/components/CourseClassForm.vue';
import PageHeader from '@/components/PageHeader.vue';
import { edit as editCourse, index } from '@/routes/school/courses';

defineProps<{
    course: {
        id: number;
        name: string;
    };
    staff: StaffOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Courses',
                href: index(),
            },
            {
                title: 'Edit course',
                href: index(),
            },
            {
                title: 'New class',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="`New class for ${course.name}`" />

    <div class="flex max-w-2xl flex-col space-y-6">
        <PageHeader
            eyebrow="Class"
            :title="`New class for ${course.name}`"
            description="Create a teaching group and optionally assign a teacher."
        />

        <CourseClassForm :course="course" :staff="staff" />

        <div>
            <Link
                :href="editCourse(course.id)"
                class="text-sm font-medium underline-offset-4 hover:underline"
            >
                Back to course
            </Link>
        </div>
    </div>
</template>
