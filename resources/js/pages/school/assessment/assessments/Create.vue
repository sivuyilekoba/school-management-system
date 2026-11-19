<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AssessmentForm from '@/components/AssessmentForm.vue';
import PageHeader from '@/components/PageHeader.vue';
import { index } from '@/routes/school/assessment';

defineProps<{
    courseClass: {
        id: number;
        name: string;
    };
    defaults: {
        assessed_on: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Assessment',
                href: index(),
            },
            {
                title: 'New assessment',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="`New assessment for ${courseClass.name}`" />

    <div class="flex max-w-2xl flex-col space-y-6">
        <PageHeader
            eyebrow="Assessment"
            :title="`New assessment for ${courseClass.name}`"
            description="Set a score out of a maximum so marks can be entered."
        />

        <AssessmentForm :course-class="courseClass" :defaults="defaults" />
    </div>
</template>
