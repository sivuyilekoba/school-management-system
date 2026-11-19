<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CourseForm from '@/components/CourseForm.vue';
import PageHeader from '@/components/PageHeader.vue';
import { create, index } from '@/routes/school/courses';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Courses',
                href: index(),
            },
            {
                title: 'New course',
                href: create(),
            },
        ],
    },
});
</script>

<template>
    <Head title="New course" />

    <div class="flex max-w-2xl flex-col space-y-6">
        <PageHeader
            eyebrow="Courses"
            title="New course"
            description="Create a subject-level course that classes can be offered under."
        />

        <CourseForm />
    </div>
</template>
