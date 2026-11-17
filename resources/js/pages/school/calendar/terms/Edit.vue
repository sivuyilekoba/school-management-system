<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import TermForm from '@/components/TermForm.vue';
import type { SchoolYearSummary, TermRecord } from '@/components/TermForm.vue';
import { index } from '@/routes/school/calendar';
import { edit as editYear } from '@/routes/school/calendar/years';

defineProps<{
    term: TermRecord;
    schoolYear: SchoolYearSummary;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Calendar',
                href: index(),
            },
            {
                title: 'School year',
                href: index(),
            },
            {
                title: 'Edit term',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit ${term.name}`" />

    <h1 class="sr-only">Edit term</h1>

    <div class="flex max-w-2xl flex-col space-y-6">
        <Heading
            variant="small"
            :title="`Edit ${term.name}`"
            :description="`Update this term in ${schoolYear.name}.`"
        />

        <TermForm :school-year="schoolYear" :term="term" />

        <div>
            <Link
                :href="editYear(schoolYear.id)"
                class="text-sm font-medium underline-offset-4 hover:underline"
            >
                Back to school year
            </Link>
        </div>
    </div>
</template>
