<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import SchoolYearForm from '@/components/SchoolYearForm.vue';
import { index } from '@/routes/school/calendar';
import { create } from '@/routes/school/calendar/years';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Calendar',
                href: index(),
            },
            {
                title: 'New school year',
                href: create(),
            },
        ],
    },
});
</script>

<template>
    <Head title="New school year" />

    <div class="flex max-w-2xl flex-col space-y-6">
        <PageHeader
            eyebrow="Calendar"
            title="New school year"
            description="Add a school year and mark whether it is the current one."
        />

        <SchoolYearForm />
    </div>
</template>
