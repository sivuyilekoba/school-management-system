<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import TimetableEntryController from '@/actions/App/Http/Controllers/School/TimetableEntryController';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index } from '@/routes/school/timetable';
import { index as periodsIndex } from '@/routes/school/timetable/periods';

type ClassOption = {
    id: number;
    name: string;
    teacher: string | null;
};

type PeriodRow = {
    id: number;
    name: string;
    start_time: string;
    end_time: string;
};

type EntryRow = {
    id: number;
    day_of_week: number;
    timetable_period_id: number;
    room: string | null;
};

type DayRow = {
    value: number;
    label: string;
};

const props = defineProps<{
    classes: ClassOption[];
    selectedClass: ClassOption | null;
    periods: PeriodRow[];
    entries: EntryRow[];
    days: DayRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Timetable',
                href: index(),
            },
        ],
    },
});

const page = usePage();

const canManage = computed(() =>
    page.props.auth.permissions.includes('timetable.manage'),
);

const selectedClassId = computed<string>({
    get: () =>
        props.selectedClass === null ? '' : String(props.selectedClass.id),
    set: (value) => {
        router.get(
            index.url(),
            { class: Number(value) },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    },
});

const entriesBySlot = computed(() => {
    const map = new Map<string, EntryRow>();

    for (const entry of props.entries) {
        map.set(`${entry.day_of_week}-${entry.timetable_period_id}`, entry);
    }

    return map;
});

function entryFor(day: number, periodId: number): EntryRow | null {
    return entriesBySlot.value.get(`${day}-${periodId}`) ?? null;
}

function addLessonUrl(day: number, periodId: number): string {
    if (!props.selectedClass) {
        return '';
    }

    return TimetableEntryController.create.url(props.selectedClass.id, {
        query: { day, period: periodId },
    });
}

function editLessonUrl(day: number, periodId: number): string {
    const entry = entryFor(day, periodId);

    return entry ? TimetableEntryController.edit.url(entry.id) : '';
}
</script>

<template>
    <Head title="Timetable" />

    <div class="flex flex-col gap-6">
        <PageHeader
            eyebrow="Teaching"
            title="Timetable"
            description="Weekly lessons for each class."
        >
            <template #actions>
                <Button v-if="canManage" variant="outline" as-child>
                    <Link :href="periodsIndex()">Manage periods</Link>
                </Button>

                <Button
                    v-if="canManage && selectedClass"
                    as-child
                    data-test="add-lesson"
                >
                    <Link
                        :href="
                            TimetableEntryController.create.url(
                                selectedClass.id,
                            )
                        "
                    >
                        Add lesson
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <EmptyState
            v-if="!classes.length"
            title="No classes yet."
            description="Create a course and class before building a timetable."
        />

        <template v-else-if="selectedClass">
            <div class="flex flex-wrap items-end gap-4">
                <div class="grid w-full max-w-sm gap-2">
                    <span class="text-sm font-medium">Class</span>
                    <Select v-model="selectedClassId">
                        <SelectTrigger aria-label="Select class">
                            <SelectValue placeholder="Select a class" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="classOption in classes"
                                :key="classOption.id"
                                :value="String(classOption.id)"
                            >
                                {{ classOption.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <p
                    v-if="selectedClass.teacher"
                    class="pb-2 text-sm text-muted-foreground"
                >
                    Teacher: {{ selectedClass.teacher }}
                </p>
            </div>

            <EmptyState
                v-if="!periods.length"
                title="No periods yet."
                :description="
                    canManage
                        ? 'Add periods before building this timetable.'
                        : 'The school has not defined timetable periods yet.'
                "
            >
                <Button v-if="canManage" variant="outline" as-child>
                    <Link :href="periodsIndex()">Manage periods</Link>
                </Button>
            </EmptyState>

            <div
                v-else
                class="overflow-hidden rounded-xl border bg-card shadow-card"
            >
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[42rem] table-fixed text-sm">
                        <caption class="sr-only">
                            Weekly timetable
                        </caption>
                        <thead>
                            <tr class="border-b bg-muted/40">
                                <th
                                    scope="col"
                                    class="w-32 px-4 py-3 text-left font-medium"
                                >
                                    Period
                                </th>
                                <th
                                    v-for="day in days"
                                    :key="day.value"
                                    scope="col"
                                    class="px-4 py-3 text-left font-medium"
                                >
                                    {{ day.label }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="period in periods"
                                :key="period.id"
                                class="border-b transition-colors last:border-b-0 hover:bg-muted/40"
                            >
                                <th scope="row" class="px-4 py-3 text-left">
                                    <p class="font-medium">{{ period.name }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ period.start_time }}–{{
                                            period.end_time
                                        }}
                                    </p>
                                </th>
                                <td
                                    v-for="day in days"
                                    :key="day.value"
                                    class="border-l px-3 py-2 align-top"
                                >
                                    <div
                                        v-if="entryFor(day.value, period.id)"
                                        class="space-y-1"
                                    >
                                        <p class="font-medium">
                                            {{
                                                entryFor(day.value, period.id)
                                                    ?.room ?? 'No room'
                                            }}
                                        </p>
                                        <Link
                                            v-if="canManage"
                                            :href="
                                                editLessonUrl(
                                                    day.value,
                                                    period.id,
                                                )
                                            "
                                            class="text-xs font-medium underline-offset-4 hover:underline"
                                        >
                                            Edit
                                        </Link>
                                    </div>
                                    <Link
                                        v-else-if="canManage"
                                        :href="
                                            addLessonUrl(day.value, period.id)
                                        "
                                        class="text-xs font-medium text-muted-foreground underline-offset-4 hover:underline"
                                    >
                                        Add
                                    </Link>
                                    <span
                                        v-else
                                        class="text-xs text-muted-foreground"
                                    >
                                        —
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </div>
</template>
