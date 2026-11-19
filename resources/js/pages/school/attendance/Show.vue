<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AttendanceController from '@/actions/App/Http/Controllers/School/AttendanceController';
import DataTable from '@/components/DataTable.vue';
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index } from '@/routes/school/attendance';
import type { DataTableColumn } from '@/types';

type StatusOption = {
    value: string;
    label: string;
};

type StudentRow = {
    id: number;
    name: string;
    status: string;
    note: string | null;
};

const props = defineProps<{
    courseClass: {
        id: number;
        name: string;
        teacher: string | null;
    };
    date: string;
    students: StudentRow[];
    statuses: StatusOption[];
    recorded: boolean;
    counts: Record<string, number>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Attendance',
                href: index(),
            },
            {
                title: 'Register',
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

const form = useForm({
    date: props.date,
    records: props.students.map((student) => ({
        student_id: student.id,
        status: student.status,
        note: student.note ?? '',
    })),
});

const counts = computed<Record<string, number>>(() => {
    const totals: Record<string, number> = {
        present: 0,
        absent: 0,
        late: 0,
        excused: 0,
    };

    for (const record of form.records) {
        totals[record.status] = (totals[record.status] ?? 0) + 1;
    }

    return totals;
});

const columns = computed<DataTableColumn[]>(() => [
    { key: 'student', label: 'Student' },
    { key: 'status', label: 'Status' },
    { key: 'note', label: 'Note' },
]);

function studentName(studentId: number): string {
    return (
        props.students.find((student) => student.id === studentId)?.name ?? '—'
    );
}

function statusLabel(status: string): string {
    return (
        props.statuses.find((option) => option.value === status)?.label ??
        status
    );
}

function changeDate(): void {
    if (!selectedDate.value) {
        return;
    }

    router.get(
        AttendanceController.show.url(props.courseClass.id),
        { date: selectedDate.value },
        { preserveScroll: true, replace: true },
    );
}

function markAllPresent(): void {
    for (const record of form.records) {
        record.status = 'present';
    }
}

function save(): void {
    form.transform((data) => ({
        ...data,
        records: data.records.map((record) => ({
            ...record,
            note: record.note.trim() === '' ? null : record.note,
        })),
    })).put(AttendanceController.save.url(props.courseClass.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="`Attendance for ${courseClass.name}`" />

    <div class="flex flex-col gap-6">
        <PageHeader
            eyebrow="Attendance register"
            :title="`Attendance for ${courseClass.name}`"
            :description="
                courseClass.teacher
                    ? `Teacher: ${courseClass.teacher}`
                    : 'No teacher assigned'
            "
        >
            <template #actions>
                <div class="grid w-full max-w-xs gap-2">
                    <label for="register-date" class="text-sm font-medium">
                        Date
                    </label>
                    <Input
                        id="register-date"
                        v-model="selectedDate"
                        type="date"
                        @change="changeDate"
                    />
                </div>
            </template>
        </PageHeader>

        <div class="flex flex-wrap items-center gap-2">
            <StatusBadge
                :label="`Present ${counts.present ?? 0}`"
                tone="success"
            />
            <StatusBadge
                :label="`Absent ${counts.absent ?? 0}`"
                tone="danger"
            />
            <StatusBadge :label="`Late ${counts.late ?? 0}`" tone="warning" />
            <StatusBadge
                :label="`Excused ${counts.excused ?? 0}`"
                tone="info"
            />
            <span v-if="!recorded" class="text-sm text-muted-foreground">
                This register has not been saved yet.
            </span>
        </div>

        <EmptyState
            v-if="!students.length"
            title="No students are enrolled in this class."
            description="Enrol students in this class before taking a register."
        />

        <template v-else>
            <DataTable
                :columns="columns"
                :rows="form.records"
                row-key="student_id"
                caption="Attendance register"
                empty-title="No students are enrolled in this class."
            >
                <template #row="{ row, index: recordIndex }">
                    <td class="px-4 py-3 font-medium">
                        {{ studentName(row.student_id) }}
                    </td>

                    <td class="px-4 py-3">
                        <Select v-if="canRecord" v-model="row.status">
                            <SelectTrigger
                                :aria-label="`Status for student ${row.student_id}`"
                                class="w-full max-w-40"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="status in statuses"
                                    :key="status.value"
                                    :value="status.value"
                                >
                                    {{ status.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <span v-else>{{ statusLabel(row.status) }}</span>
                        <InputError
                            :message="
                                form.errors[`records.${recordIndex}.status`]
                            "
                        />
                    </td>

                    <td class="px-4 py-3">
                        <Input
                            v-if="canRecord"
                            v-model="row.note"
                            type="text"
                            maxlength="255"
                            :aria-label="`Note for student ${row.student_id}`"
                            placeholder="Optional note"
                        />
                        <span v-else>{{ row.note || '—' }}</span>
                        <InputError
                            :message="
                                form.errors[`records.${recordIndex}.note`]
                            "
                        />
                    </td>
                </template>
            </DataTable>

            <div v-if="canRecord" class="flex flex-wrap items-center gap-3">
                <Button type="button" variant="outline" @click="markAllPresent">
                    Mark all present
                </Button>
                <Button :disabled="form.processing" @click="save">
                    Save register
                </Button>
            </div>
        </template>
    </div>
</template>
