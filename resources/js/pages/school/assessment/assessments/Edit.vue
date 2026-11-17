<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AssessmentForm from '@/components/AssessmentForm.vue';
import type { AssessmentRecord } from '@/components/AssessmentForm.vue';
import Heading from '@/components/Heading.vue';
import { index } from '@/routes/school/assessment';

defineProps<{
    courseClass: {
        id: number;
        name: string;
    };
    assessment: AssessmentRecord;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Assessment',
                href: index(),
            },
            {
                title: 'Edit assessment',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit ${assessment.name}`" />

    <h1 class="sr-only">Edit assessment</h1>

    <div class="flex max-w-2xl flex-col space-y-6">
        <Heading
            variant="small"
            :title="`Edit ${assessment.name}`"
            :description="`Assessment for ${courseClass.name}.`"
        />

        <AssessmentForm :course-class="courseClass" :assessment="assessment" />
    </div>
</template>
