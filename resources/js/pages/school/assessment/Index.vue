<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AssessmentController from '@/actions/App/Http/Controllers/School/AssessmentController';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import { index } from '@/routes/school/assessment';
import type { DataTableColumn } from '@/types';

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

const columns: DataTableColumn[] = [
    { key: 'name', label: 'Class' },
    { key: 'teacher', label: 'Teacher' },
    { key: 'students_count', label: 'Students' },
    { key: 'assessments_count', label: 'Assessments' },
    {
        key: 'actions',
        label: '',
        srLabel: 'Actions',
        align: 'right',
    },
];
</script>

<template>
    <Head title="Assessment" />

    <div class="flex flex-col gap-6">
        <PageHeader
            eyebrow="Teaching"
            title="Assessment"
            description="Assessments and marks for each class."
        />

        <DataTable
            :columns="columns"
            :rows="classes"
            row-key="id"
            caption="Class assessments"
            empty-title="No classes yet."
            empty-description="Create a course and class before recording assessments."
        >
            <template #row="{ row }">
                <td class="px-4 py-3 font-medium">{{ row.name }}</td>
                <td class="px-4 py-3 text-muted-foreground">
                    {{ row.teacher ?? '—' }}
                </td>
                <td class="px-4 py-3 text-muted-foreground">
                    {{ row.students_count }}
                </td>
                <td class="px-4 py-3 text-muted-foreground">
                    {{ row.assessments_count }}
                </td>
                <td class="px-4 py-3 text-right">
                    <Link
                        :href="AssessmentController.classIndex.url(row.id)"
                        class="text-sm font-medium underline-offset-4 hover:underline"
                    >
                        Manage
                    </Link>
                </td>
            </template>
        </DataTable>
    </div>
</template>
