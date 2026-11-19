<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import TermForm from '@/components/TermForm.vue';
import type { SchoolYearSummary } from '@/components/TermForm.vue';
import { index } from '@/routes/school/calendar';
import { edit as editYear } from '@/routes/school/calendar/years';

defineProps<{
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
                title: 'New term',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="`New term for ${schoolYear.name}`" />

    <div class="flex max-w-2xl flex-col space-y-6">
        <PageHeader
            eyebrow="Term"
            :title="`New term for ${schoolYear.name}`"
            description="Add a term to this school year."
        />

        <TermForm :school-year="schoolYear" />

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
