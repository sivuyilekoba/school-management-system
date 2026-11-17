<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AttendanceController from '@/actions/App/Http/Controllers/School/AttendanceController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { index } from '@/routes/school/attendance';

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

    <h1 class="sr-only">Attendance</h1>

    <div class="flex flex-col space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                variant="small"
                title="Attendance"
                description="Daily registers for every class."
            />

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
        </div>

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
                            Attendance
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
                        <td class="px-4 py-3">
                            <div
                                v-if="classRow.recorded"
                                class="flex flex-wrap gap-2"
                            >
                                <Badge variant="secondary">
                                    Present {{ classRow.counts.present }}
                                </Badge>
                                <Badge variant="destructive">
                                    Absent {{ classRow.counts.absent }}
                                </Badge>
                                <Badge variant="outline">
                                    Late {{ classRow.counts.late }}
                                </Badge>
                                <Badge variant="outline">
                                    Excused {{ classRow.counts.excused }}
                                </Badge>
                            </div>
                            <span v-else class="text-muted-foreground">
                                Not taken
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Link
                                :href="
                                    AttendanceController.show.url(classRow.id, {
                                        query: { date },
                                    })
                                "
                                class="text-sm font-medium underline-offset-4 hover:underline"
                            >
                                {{ canRecord ? 'Take register' : 'View' }}
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="!classes.length">
                        <td
                            colspan="5"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No classes yet. Create a course and class before
                            taking attendance.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
