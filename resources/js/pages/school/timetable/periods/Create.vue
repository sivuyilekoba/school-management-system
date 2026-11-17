<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import TimetablePeriodForm from '@/components/TimetablePeriodForm.vue';
import { index } from '@/routes/school/timetable';
import { create, index as periods } from '@/routes/school/timetable/periods';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Timetable',
                href: index(),
            },
            {
                title: 'Periods',
                href: periods(),
            },
            {
                title: 'New period',
                href: create(),
            },
        ],
    },
});
</script>

<template>
    <Head title="New timetable period" />

    <h1 class="sr-only">New timetable period</h1>

    <div class="flex max-w-2xl flex-col space-y-6">
        <Heading
            variant="small"
            title="New timetable period"
            description="Add a period to the teaching day."
        />

        <TimetablePeriodForm />
    </div>
</template>
