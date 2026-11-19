<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import TimetableEntryController from '@/actions/App/Http/Controllers/School/TimetableEntryController';
import FormField from '@/components/FormField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export type DayOption = {
    value: number;
    label: string;
};

export type PeriodOption = {
    id: number;
    name: string;
    start_time: string;
    end_time: string;
};

export type TimetableEntryRecord = {
    id: number;
    day_of_week: number;
    timetable_period_id: number;
    room: string | null;
};

const props = defineProps<{
    courseClass: {
        id: number;
        name: string;
    };
    periods: PeriodOption[];
    days: DayOption[];
    entry?: TimetableEntryRecord | null;
    defaults?: {
        day_of_week: number | null;
        timetable_period_id: number | null;
    };
}>();

const isEditing = computed(() => Boolean(props.entry));

const form = useForm({
    day_of_week: props.entry?.day_of_week ?? props.defaults?.day_of_week ?? '',
    timetable_period_id:
        props.entry?.timetable_period_id ??
        props.defaults?.timetable_period_id ??
        '',
    room: props.entry?.room ?? '',
});

const dayValue = computed<string>({
    get: () => (form.day_of_week === '' ? '' : String(form.day_of_week)),
    set: (value) => {
        form.day_of_week = value === '' ? '' : Number(value);
    },
});

const periodValue = computed<string>({
    get: () =>
        form.timetable_period_id === '' ? '' : String(form.timetable_period_id),
    set: (value) => {
        form.timetable_period_id = value === '' ? '' : Number(value);
    },
});

function submit(): void {
    if (props.entry) {
        form.put(TimetableEntryController.update.url(props.entry.id), {
            preserveScroll: true,
        });

        return;
    }

    form.post(TimetableEntryController.store.url(props.courseClass.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <p class="text-sm text-muted-foreground">
            Lesson for <span class="font-medium">{{ courseClass.name }}</span
            >.
        </p>

        <div class="grid gap-4 sm:grid-cols-2">
            <FormField
                label="Day"
                field-id="day_of_week"
                :error="form.errors.day_of_week"
                required
            >
                <Select v-model="dayValue">
                    <SelectTrigger id="day_of_week" class="w-full">
                        <SelectValue placeholder="Select a day" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="day in days"
                            :key="day.value"
                            :value="String(day.value)"
                        >
                            {{ day.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </FormField>

            <FormField
                label="Period"
                field-id="timetable_period_id"
                :error="form.errors.timetable_period_id"
                required
            >
                <Select v-model="periodValue">
                    <SelectTrigger id="timetable_period_id" class="w-full">
                        <SelectValue placeholder="Select a period" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="period in periods"
                            :key="period.id"
                            :value="String(period.id)"
                        >
                            {{ period.name }} ({{ period.start_time }}–{{
                                period.end_time
                            }})
                        </SelectItem>
                    </SelectContent>
                </Select>
            </FormField>
        </div>

        <FormField label="Room" field-id="room" :error="form.errors.room">
            <Input id="room" v-model="form.room" placeholder="e.g. Room 12" />
        </FormField>

        <div class="flex items-center gap-4">
            <Button :disabled="form.processing" data-test="save-entry">
                {{ isEditing ? 'Save changes' : 'Add lesson' }}
            </Button>
        </div>
    </form>
</template>
