<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import TimetableEntryController from '@/actions/App/Http/Controllers/School/TimetableEntryController';
import PageHeader from '@/components/PageHeader.vue';
import TimetableEntryForm from '@/components/TimetableEntryForm.vue';
import type {
    DayOption,
    PeriodOption,
    TimetableEntryRecord,
} from '@/components/TimetableEntryForm.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/school/timetable';

const props = defineProps<{
    entry: TimetableEntryRecord;
    courseClass: {
        id: number;
        name: string;
    };
    periods: PeriodOption[];
    days: DayOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Timetable',
                href: index(),
            },
            {
                title: 'Edit lesson',
                href: index(),
            },
        ],
    },
});

function backUrl(): string {
    return index.url({ query: { class: props.courseClass.id } });
}

function destroy(): void {
    if (!confirm('Remove this lesson from the timetable?')) {
        return;
    }

    router.delete(TimetableEntryController.destroy.url(props.entry.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="`Edit lesson for ${courseClass.name}`" />

    <div class="flex max-w-2xl flex-col space-y-6">
        <PageHeader
            eyebrow="Timetable lesson"
            :title="`Edit lesson for ${courseClass.name}`"
            description="Update when and where this lesson takes place."
        />

        <TimetableEntryForm
            :course-class="courseClass"
            :periods="periods"
            :days="days"
            :entry="entry"
        />

        <div class="flex flex-wrap items-center gap-3">
            <Button
                type="button"
                variant="destructive"
                data-test="delete-entry"
                @click="destroy"
            >
                Remove lesson
            </Button>

            <Link
                :href="backUrl()"
                class="text-sm font-medium underline-offset-4 hover:underline"
            >
                Back to timetable
            </Link>
        </div>
    </div>
</template>
