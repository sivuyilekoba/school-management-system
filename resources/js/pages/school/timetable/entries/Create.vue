<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import TimetableEntryForm from '@/components/TimetableEntryForm.vue';
import type {
    DayOption,
    PeriodOption,
} from '@/components/TimetableEntryForm.vue';
import { index } from '@/routes/school/timetable';

defineProps<{
    courseClass: {
        id: number;
        name: string;
    };
    periods: PeriodOption[];
    days: DayOption[];
    defaults: {
        day_of_week: number | null;
        timetable_period_id: number | null;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Timetable',
                href: index(),
            },
            {
                title: 'Add lesson',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="`Add lesson for ${courseClass.name}`" />

    <div class="flex max-w-2xl flex-col space-y-6">
        <PageHeader
            eyebrow="Timetable lesson"
            :title="`Add lesson for ${courseClass.name}`"
            description="Schedule a lesson in the weekly timetable."
        />

        <TimetableEntryForm
            :course-class="courseClass"
            :periods="periods"
            :days="days"
            :defaults="defaults"
        />
    </div>
</template>
