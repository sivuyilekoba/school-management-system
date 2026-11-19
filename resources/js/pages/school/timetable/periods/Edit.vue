<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import TimetablePeriodForm from '@/components/TimetablePeriodForm.vue';
import type { TimetablePeriodRecord } from '@/components/TimetablePeriodForm.vue';
import { index } from '@/routes/school/timetable';
import { index as periods } from '@/routes/school/timetable/periods';

defineProps<{
    period: TimetablePeriodRecord;
}>();

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
                title: 'Edit period',
                href: periods(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit ${period.name}`" />

    <div class="flex max-w-2xl flex-col space-y-6">
        <PageHeader
            eyebrow="Timetable periods"
            :title="`Edit ${period.name}`"
            description="Update the period name and times."
        />

        <TimetablePeriodForm :period="period" />
    </div>
</template>
