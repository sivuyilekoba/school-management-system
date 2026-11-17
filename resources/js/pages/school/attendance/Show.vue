<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AttendanceController from '@/actions/App/Http/Controllers/School/AttendanceController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
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

    <h1 class="sr-only">Attendance register</h1>

    <div class="flex flex-col space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                variant="small"
                :title="`Attendance for ${courseClass.name}`"
                :description="
                    courseClass.teacher
                        ? `Teacher: ${courseClass.teacher}`
                        : 'No teacher assigned'
                "
            />

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
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <Badge variant="secondary">
                Present {{ counts.present ?? 0 }}
            </Badge>
            <Badge variant="destructive">
                Absent {{ counts.absent ?? 0 }}
            </Badge>
            <Badge variant="outline">Late {{ counts.late ?? 0 }}</Badge>
            <Badge variant="outline">Excused {{ counts.excused ?? 0 }}</Badge>
            <span v-if="!recorded" class="text-sm text-muted-foreground">
                This register has not been saved yet.
            </span>
        </div>

        <p
            v-if="!students.length"
            class="rounded-lg border px-4 py-8 text-center text-sm text-muted-foreground"
        >
            No students are enrolled in this class.
        </p>

        <template v-else>
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
                                Status
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 text-left font-medium"
                            >
                                Note
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(record, recordIndex) in form.records"
                            :key="record.student_id"
                            class="border-b last:border-b-0"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{
                                    students.find(
                                        (student) =>
                                            student.id === record.student_id,
                                    )?.name
                                }}
                            </td>
                            <td class="px-4 py-3">
                                <Select
                                    v-if="canRecord"
                                    v-model="record.status"
                                >
                                    <SelectTrigger
                                        :aria-label="`Status for student ${record.student_id}`"
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
                                <span v-else>
                                    {{
                                        statuses.find(
                                            (status) =>
                                                status.value === record.status,
                                        )?.label ?? record.status
                                    }}
                                </span>
                                <InputError
                                    :message="
                                        form.errors[
                                            `records.${recordIndex}.status`
                                        ]
                                    "
                                />
                            </td>
                            <td class="px-4 py-3">
                                <Input
                                    v-if="canRecord"
                                    v-model="record.note"
                                    type="text"
                                    maxlength="255"
                                    :aria-label="`Note for student ${record.student_id}`"
                                    placeholder="Optional note"
                                />
                                <span v-else>
                                    {{ record.note || '—' }}
                                </span>
                                <InputError
                                    :message="
                                        form.errors[
                                            `records.${recordIndex}.note`
                                        ]
                                    "
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

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
