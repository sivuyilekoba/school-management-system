<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
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

    <div class="flex max-w-2xl flex-col space-y-6">
        <PageHeader
            eyebrow="Timetable periods"
            title="New timetable period"
            description="Add a period to the teaching day."
        />

        <TimetablePeriodForm />
    </div>
</template>
