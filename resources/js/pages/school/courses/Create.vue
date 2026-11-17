<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CourseForm from '@/components/CourseForm.vue';
import Heading from '@/components/Heading.vue';
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

    <h1 class="sr-only">New course</h1>

    <div class="flex max-w-2xl flex-col space-y-6">
        <Heading
            variant="small"
            title="New course"
            description="Create a subject-level course that classes can be offered under."
        />

        <CourseForm />
    </div>
</template>
