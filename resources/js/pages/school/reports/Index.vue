<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import type { AcceptableValue } from 'reka-ui';
import { ref } from 'vue';
import ReportCardController from '@/actions/App/Http/Controllers/School/ReportCardController';
import DataTable from '@/components/DataTable.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index } from '@/routes/school/reports';
import type { DataTableColumn } from '@/types';

type TermOption = {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
};

type SchoolYearOption = {
    id: number;
    name: string;
    start_date: string;
    is_current: boolean;
    terms: TermOption[];
};

type AttendanceCounts = {
    present: number;
    absent: number;
    late: number;
    excused: number;
    total: number;
};

type ReportStatus = 'published' | 'draft' | 'not_started';

type StudentRow = {
    id: number;
    name: string;
    family: string | null;
    classes_count: number;
    average: number | null;
    attendance: AttendanceCounts;
    status: ReportStatus;
};

const props = defineProps<{
    schoolYears: SchoolYearOption[];
    term:
        | (TermOption & {
              school_year: {
                  id: number;
                  name: string;
              };
          })
        | null;
    students: StudentRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Report cards',
                href: index(),
            },
        ],
    },
});

const selectedTermId = ref(props.term ? String(props.term.id) : '');

const columns: DataTableColumn[] = [
    { key: 'name', label: 'Student' },
    { key: 'family', label: 'Family' },
    { key: 'classes_count', label: 'Classes' },
    { key: 'average', label: 'Average' },
    { key: 'attendance', label: 'Attendance' },
    { key: 'status', label: 'Status' },
    {
        key: 'actions',
        label: '',
        srLabel: 'Actions',
        align: 'right',
    },
];

function changeTerm(value: AcceptableValue): void {
    if (typeof value !== 'string' && typeof value !== 'number') {
        return;
    }

    const termId = String(value);
    selectedTermId.value = termId;

    if (!termId) {
        return;
    }

    router.get(
        index.url(),
        { term: termId },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function statusLabel(status: ReportStatus): string {
    return {
        published: 'Published',
        draft: 'Draft',
        not_started: 'Not started',
    }[status];
}

function statusTone(status: ReportStatus): 'success' | 'warning' | 'neutral' {
    return {
        published: 'success',
        draft: 'warning',
        not_started: 'neutral',
    }[status] as 'success' | 'warning' | 'neutral';
}

function formatPercentage(value: number | null): string {
    return value === null ? '—' : `${value}%`;
}
</script>

<template>
    <Head title="Report cards" />

    <div class="flex flex-col gap-6">
        <PageHeader
            eyebrow="Reports"
            title="Report cards"
            description="Term summaries of assessment marks and attendance."
        >
            <template #actions>
                <div
                    v-if="schoolYears.length"
                    class="grid w-full max-w-xs gap-2"
                >
                    <Label for="report-term">Term</Label>
                    <Select
                        :model-value="selectedTermId"
                        @update:model-value="changeTerm"
                    >
                        <SelectTrigger id="report-term">
                            <SelectValue placeholder="Choose a term" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup
                                v-for="schoolYear in schoolYears"
                                :key="schoolYear.id"
                            >
                                <SelectLabel>
                                    {{ schoolYear.name
                                    }}{{
                                        schoolYear.is_current
                                            ? ' · current'
                                            : ''
                                    }}
                                </SelectLabel>
                                <SelectItem
                                    v-for="option in schoolYear.terms"
                                    :key="option.id"
                                    :value="String(option.id)"
                                >
                                    {{ option.name }}
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                </div>
            </template>
        </PageHeader>

        <EmptyState
            v-if="!term"
            title="No terms have been added yet."
            description="Add a school year and term before preparing report cards."
        />

        <template v-else>
            <p class="text-sm text-muted-foreground">
                {{ term.school_year.name }} · {{ term.name }} ·
                {{ term.start_date }} to {{ term.end_date }}
            </p>

            <DataTable
                :columns="columns"
                :rows="students"
                row-key="id"
                caption="Report cards for this term"
                empty-title="No students are enrolled in any classes yet."
                empty-description="Enrol students in classes to prepare report cards."
            >
                <template #row="{ row }">
                    <td class="px-4 py-3 font-medium">{{ row.name }}</td>
                    <td class="px-4 py-3 text-muted-foreground">
                        {{ row.family ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-muted-foreground">
                        {{ row.classes_count }}
                    </td>
                    <td class="px-4 py-3 text-muted-foreground">
                        {{ formatPercentage(row.average) }}
                    </td>
                    <td class="px-4 py-3 text-muted-foreground">
                        {{ row.attendance.present }} /
                        {{ row.attendance.total }} present
                    </td>
                    <td class="px-4 py-3">
                        <StatusBadge
                            :label="statusLabel(row.status)"
                            :tone="statusTone(row.status)"
                        />
                    </td>
                    <td class="px-4 py-3 text-right">
                        <Link
                            :href="
                                ReportCardController.show.url({
                                    student: row.id,
                                    term: term.id,
                                })
                            "
                            class="text-sm font-medium underline-offset-4 hover:underline"
                        >
                            Open
                        </Link>
                    </td>
                </template>
            </DataTable>
        </template>
    </div>
</template>
