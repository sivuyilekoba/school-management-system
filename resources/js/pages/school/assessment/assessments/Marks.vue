<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AssessmentController from '@/actions/App/Http/Controllers/School/AssessmentController';
import DataTable from '@/components/DataTable.vue';
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index } from '@/routes/school/assessment';
import type { DataTableColumn } from '@/types';

type StudentRow = {
    id: number;
    name: string;
    score: string;
};

const props = defineProps<{
    courseClass: {
        id: number;
        name: string;
    };
    assessment: {
        id: number;
        name: string;
        assessed_on: string;
        max_score: number;
    };
    students: StudentRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Assessment',
                href: index(),
            },
            {
                title: 'Marks',
                href: index(),
            },
        ],
    },
});

const page = usePage();

const canManage = computed(() =>
    page.props.auth.permissions.includes('assessment.manage'),
);

const form = useForm({
    records: props.students.map((student) => ({
        student_id: student.id,
        score: student.score,
    })),
});

const enteredCount = computed(
    () => form.records.filter((record) => record.score.trim() !== '').length,
);

const columns = computed<DataTableColumn[]>(() => [
    { key: 'student', label: 'Student' },
    { key: 'score', label: `Score out of ${props.assessment.max_score}` },
]);

function studentName(studentId: number): string {
    return (
        props.students.find((student) => student.id === studentId)?.name ?? '—'
    );
}

function save(): void {
    form.transform((data) => ({
        ...data,
        records: data.records.map((record) => ({
            ...record,
            score: record.score.trim() === '' ? null : record.score,
        })),
    })).put(AssessmentController.saveMarks.url(props.assessment.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="`Marks for ${assessment.name}`" />

    <div class="flex flex-col gap-6">
        <PageHeader
            eyebrow="Assessment marks"
            :title="`Marks for ${assessment.name}`"
            :description="`${courseClass.name} · ${assessment.assessed_on} · out of ${assessment.max_score}`"
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <Link
                        :href="
                            AssessmentController.classIndex.url(courseClass.id)
                        "
                    >
                        Back to class
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <p class="text-sm text-muted-foreground">
            {{ enteredCount }} of {{ students.length }} marks entered.
        </p>

        <EmptyState
            v-if="!students.length"
            title="No students are enrolled in this class."
            description="Enrol students before recording marks."
        />

        <template v-else>
            <DataTable
                :columns="columns"
                :rows="form.records"
                row-key="student_id"
                caption="Assessment marks"
                empty-title="No students are enrolled in this class."
            >
                <template #row="{ row, index: recordIndex }">
                    <td class="px-4 py-3 font-medium">
                        {{ studentName(row.student_id) }}
                    </td>

                    <td class="px-4 py-3">
                        <div class="max-w-40">
                            <Input
                                v-if="canManage"
                                v-model="row.score"
                                type="number"
                                min="0"
                                :max="assessment.max_score"
                                step="0.01"
                                :aria-label="`Score for student ${row.student_id}`"
                                placeholder="Not submitted"
                            />
                            <span v-else>{{ row.score || '—' }}</span>
                            <InputError
                                :message="
                                    form.errors[`records.${recordIndex}.score`]
                                "
                            />
                        </div>
                    </td>
                </template>
            </DataTable>

            <div v-if="canManage" class="flex items-center gap-4">
                <Button :disabled="form.processing" @click="save">
                    Save marks
                </Button>
                <InputError :message="form.errors.records" />
            </div>
        </template>
    </div>
</template>
