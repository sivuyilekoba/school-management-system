<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CourseClassForm from '@/components/CourseClassForm.vue';
import type {
    CourseClassRecord,
    StaffOption,
} from '@/components/CourseClassForm.vue';
import PageHeader from '@/components/PageHeader.vue';
import { edit as editCourse, index } from '@/routes/school/courses';

defineProps<{
    courseClass: CourseClassRecord;
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
                title: 'Edit class',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit class ${courseClass.name}`" />

    <div class="flex max-w-2xl flex-col space-y-6">
        <PageHeader
            eyebrow="Class"
            :title="`Edit class ${courseClass.name}`"
            :description="`Update this class in ${course.name}.`"
        />

        <CourseClassForm
            :course="course"
            :staff="staff"
            :course-class="courseClass"
        />

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
