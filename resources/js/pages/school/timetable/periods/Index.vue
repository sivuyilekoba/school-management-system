<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/school/timetable';
import { create, edit } from '@/routes/school/timetable/periods';

type PeriodRow = {
    id: number;
    name: string;
    start_time: string;
    end_time: string;
    entries_count: number;
};

defineProps<{
    periods: PeriodRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Timetable',
                href: index(),
            },
            {
                title: 'Periods',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Timetable periods" />

    <h1 class="sr-only">Timetable periods</h1>

    <div class="flex flex-col space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                variant="small"
                title="Timetable periods"
                description="The periods that make up each teaching day."
            />

            <Button as-child>
                <Link :href="create()">New period</Link>
            </Button>
        </div>

        <div class="overflow-x-auto rounded-lg border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/50">
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Period
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Starts
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Ends
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Lessons
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
                        v-for="period in periods"
                        :key="period.id"
                        class="border-b last:border-b-0"
                    >
                        <td class="px-4 py-3 font-medium">{{ period.name }}</td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ period.start_time }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ period.end_time }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ period.entries_count }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Link
                                :href="edit(period.id)"
                                class="text-sm font-medium underline-offset-4 hover:underline"
                            >
                                Edit
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="!periods.length">
                        <td
                            colspan="5"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No periods yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div>
            <Link
                :href="index()"
                class="text-sm font-medium underline-offset-4 hover:underline"
            >
                Back to timetable
            </Link>
        </div>
    </div>
</template>
