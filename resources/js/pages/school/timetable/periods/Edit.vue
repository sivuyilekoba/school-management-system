<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
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

    <h1 class="sr-only">Edit timetable period</h1>

    <div class="flex max-w-2xl flex-col space-y-6">
        <Heading
            variant="small"
            :title="`Edit ${period.name}`"
            description="Update the period name and times."
        />

        <TimetablePeriodForm :period="period" />
    </div>
</template>
