<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AssessmentController from '@/actions/App/Http/Controllers/School/AssessmentController';
import Heading from '@/components/Heading.vue';
import { index } from '@/routes/school/assessment';

type ClassRow = {
    id: number;
    name: string;
    teacher: string | null;
    students_count: number;
    assessments_count: number;
};

defineProps<{
    classes: ClassRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Assessment',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Assessment" />

    <h1 class="sr-only">Assessment</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Assessment"
            description="Assessments and marks for each class."
        />

        <div class="overflow-x-auto rounded-lg border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/50">
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Class
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Teacher
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Students
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Assessments
                        </th>
                        <th
                            scope="col"
                            class="px-4 py-3 text-right font-medium"
                        >
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="classRow in classes"
                        :key="classRow.id"
                        class="border-b last:border-b-0"
                    >
                        <td class="px-4 py-3 font-medium">
                            {{ classRow.name }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ classRow.teacher ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ classRow.students_count }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ classRow.assessments_count }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Link
                                :href="
                                    AssessmentController.classIndex.url(
                                        classRow.id,
                                    )
                                "
                                class="text-sm font-medium underline-offset-4 hover:underline"
                            >
                                Manage
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="!classes.length">
                        <td
                            colspan="5"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No classes yet. Create a course and class before
                            recording assessments.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
