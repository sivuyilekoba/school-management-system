<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AttendanceController from '@/actions/App/Http/Controllers/School/AttendanceController';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Input } from '@/components/ui/input';
import { index } from '@/routes/school/attendance';
import type { DataTableColumn } from '@/types';

type AttendanceCounts = {
    present: number;
    absent: number;
    late: number;
    excused: number;
};

type ClassRow = {
    id: number;
    name: string;
    teacher: string | null;
    students_count: number;
    recorded: boolean;
    counts: AttendanceCounts;
};

const props = defineProps<{
    date: string;
    classes: ClassRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Attendance',
                href: index(),
            },
        ],
    },
});

const page = usePage();

const canRecord = computed(() =>
    page.props.auth.permissions.includes('attendance.record'),
);

const selectedDate = ref(props.date);

const columns = computed<DataTableColumn[]>(() => [
    { key: 'name', label: 'Class' },
    { key: 'teacher', label: 'Teacher' },
    { key: 'students_count', label: 'Students' },
    { key: 'attendance', label: 'Attendance' },
    {
        key: 'actions',
        label: '',
        srLabel: 'Actions',
        align: 'right' as const,
    },
]);

function changeDate(): void {
    if (!selectedDate.value) {
        return;
    }

    router.get(
        index.url(),
        { date: selectedDate.value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}
</script>

<template>
    <Head title="Attendance" />

    <div class="flex flex-col gap-6">
        <PageHeader
            eyebrow="Teaching"
            title="Attendance"
            description="Daily registers for every class."
        >
            <template #actions>
                <div class="grid w-full max-w-xs gap-2">
                    <label for="attendance-date" class="text-sm font-medium">
                        Date
                    </label>
                    <Input
                        id="attendance-date"
                        v-model="selectedDate"
                        type="date"
                        @change="changeDate"
                    />
                </div>
            </template>
        </PageHeader>

        <DataTable
            :columns="columns"
            :rows="props.classes"
            row-key="id"
            caption="Class attendance"
            empty-title="No classes yet."
            empty-description="Create a course and class before taking attendance."
        >
            <template #row="{ row }">
                <td class="px-4 py-3 font-medium">{{ row.name }}</td>
                <td class="px-4 py-3 text-muted-foreground">
                    {{ row.teacher ?? '—' }}
                </td>
                <td class="px-4 py-3 text-muted-foreground">
                    {{ row.students_count }}
                </td>

                <td class="px-4 py-3">
                    <div v-if="row.recorded" class="flex flex-wrap gap-1.5">
                        <StatusBadge
                            :label="`Present ${row.counts.present}`"
                            tone="success"
                        />
                        <StatusBadge
                            :label="`Absent ${row.counts.absent}`"
                            tone="danger"
                        />
                        <StatusBadge
                            :label="`Late ${row.counts.late}`"
                            tone="warning"
                        />
                        <StatusBadge
                            :label="`Excused ${row.counts.excused}`"
                            tone="info"
                        />
                    </div>
                    <span v-else class="text-muted-foreground">Not taken</span>
                </td>

                <td class="px-4 py-3 text-right">
                    <Link
                        :href="
                            AttendanceController.show.url(row.id, {
                                query: { date },
                            })
                        "
                        class="text-sm font-medium underline-offset-4 hover:underline"
                    >
                        {{ canRecord ? 'Take register' : 'View' }}
                    </Link>
                </td>
            </template>
        </DataTable>
    </div>
</template>
