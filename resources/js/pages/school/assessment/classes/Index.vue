<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AssessmentController from '@/actions/App/Http/Controllers/School/AssessmentController';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/school/assessment';
import type { DataTableColumn } from '@/types';

type AssessmentRow = {
    id: number;
    name: string;
    assessed_on: string;
    max_score: number;
    marks_count: number;
};

const props = defineProps<{
    courseClass: {
        id: number;
        name: string;
    };
    assessments: AssessmentRow[];
    students_count: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Assessment',
                href: index(),
            },
            {
                title: 'Class assessments',
                href: index(),
            },
        ],
    },
});

const page = usePage();

const canManage = computed(() =>
    page.props.auth.permissions.includes('assessment.manage'),
);

const columns = computed<DataTableColumn[]>(() => [
    { key: 'name', label: 'Assessment' },
    { key: 'assessed_on', label: 'Date' },
    { key: 'max_score', label: 'Maximum score' },
    { key: 'marks_count', label: 'Marks' },
    {
        key: 'actions',
        label: '',
        srLabel: 'Actions',
        align: 'right' as const,
    },
]);

function remove(assessment: AssessmentRow): void {
    if (!confirm(`Remove "${assessment.name}" and all of its marks?`)) {
        return;
    }

    router.delete(AssessmentController.destroy.url(assessment.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="`Assessments for ${courseClass.name}`" />

    <div class="flex flex-col gap-6">
        <PageHeader
            eyebrow="Class assessments"
            :title="`Assessments for ${courseClass.name}`"
            description="Assessments and the marks recorded for this class."
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <Link :href="index()">Back to assessment</Link>
                </Button>

                <Button v-if="canManage" as-child>
                    <Link
                        :href="AssessmentController.create.url(courseClass.id)"
                    >
                        Add assessment
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <DataTable
            :columns="columns"
            :rows="assessments"
            row-key="id"
            caption="Assessments for this class"
            empty-title="No assessments yet."
            empty-description="Add an assessment to start recording marks."
        >
            <template #row="{ row }">
                <td class="px-4 py-3 font-medium">{{ row.name }}</td>
                <td class="px-4 py-3 text-muted-foreground">
                    {{ row.assessed_on }}
                </td>
                <td class="px-4 py-3 text-muted-foreground">
                    {{ row.max_score }}
                </td>
                <td class="px-4 py-3 text-muted-foreground">
                    {{ row.marks_count }} / {{ students_count }}
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex justify-end gap-3">
                        <Link
                            :href="AssessmentController.marks.url(row.id)"
                            class="text-sm font-medium underline-offset-4 hover:underline"
                        >
                            {{
                                canManage && !row.marks_count
                                    ? 'Enter marks'
                                    : 'Marks'
                            }}
                        </Link>
                        <template v-if="canManage">
                            <Link
                                :href="AssessmentController.edit.url(row.id)"
                                class="text-sm font-medium underline-offset-4 hover:underline"
                            >
                                Edit
                            </Link>
                            <button
                                type="button"
                                class="text-sm font-medium text-destructive underline-offset-4 hover:underline"
                                @click="remove(row)"
                            >
                                Remove
                            </button>
                        </template>
                    </div>
                </td>
            </template>
        </DataTable>
    </div>
</template>
