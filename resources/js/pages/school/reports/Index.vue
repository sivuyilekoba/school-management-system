<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import type { AcceptableValue } from 'reka-ui';
import { ref } from 'vue';
import ReportCardController from '@/actions/App/Http/Controllers/School/ReportCardController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
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

function statusVariant(
    status: ReportStatus,
): 'default' | 'secondary' | 'outline' {
    return {
        published: 'default',
        draft: 'secondary',
        not_started: 'outline',
    }[status] as 'default' | 'secondary' | 'outline';
}

function formatPercentage(value: number | null): string {
    return value === null ? '—' : `${value}%`;
}
</script>

<template>
    <Head title="Report cards" />

    <h1 class="sr-only">Report cards</h1>

    <div class="flex flex-col space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                variant="small"
                title="Report cards"
                description="Term summaries of assessment marks and attendance."
            />

            <div v-if="schoolYears.length" class="grid w-full max-w-xs gap-2">
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
                                    schoolYear.is_current ? ' · current' : ''
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
        </div>

        <p
            v-if="!term"
            class="rounded-lg border px-4 py-8 text-center text-sm text-muted-foreground"
        >
            No terms have been added yet. Add a school year and term before
            preparing report cards.
        </p>

        <template v-else>
            <p class="text-sm text-muted-foreground">
                {{ term.school_year.name }} · {{ term.name }} ·
                {{ term.start_date }} to {{ term.end_date }}
            </p>

            <div class="overflow-x-auto rounded-lg border">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-muted/50">
                            <th
                                scope="col"
                                class="px-4 py-3 text-left font-medium"
                            >
                                Student
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 text-left font-medium"
                            >
                                Family
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 text-left font-medium"
                            >
                                Classes
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 text-left font-medium"
                            >
                                Average
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 text-left font-medium"
                            >
                                Attendance
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 text-left font-medium"
                            >
                                Status
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
                            v-for="student in students"
                            :key="student.id"
                            class="border-b last:border-b-0"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ student.name }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ student.family ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ student.classes_count }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ formatPercentage(student.average) }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ student.attendance.present }} /
                                {{ student.attendance.total }} present
                            </td>
                            <td class="px-4 py-3">
                                <Badge :variant="statusVariant(student.status)">
                                    {{ statusLabel(student.status) }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link
                                    :href="
                                        ReportCardController.show.url({
                                            student: student.id,
                                            term: term.id,
                                        })
                                    "
                                    class="text-sm font-medium underline-offset-4 hover:underline"
                                >
                                    Open
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!students.length">
                            <td
                                colspan="7"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                No students are enrolled in any classes yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>
    </div>
</template>
