<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/school/calendar';
import { create, edit } from '@/routes/school/calendar/years';

type SchoolYearRow = {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
    is_current: boolean;
    terms_count: number;
};

defineProps<{
    years: SchoolYearRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Calendar',
                href: index(),
            },
        ],
    },
});

const page = usePage();

const canManage = computed(() =>
    page.props.auth.permissions.includes('calendar.manage'),
);
</script>

<template>
    <Head title="Calendar" />

    <h1 class="sr-only">Calendar</h1>

    <div class="flex flex-col space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                variant="small"
                title="Calendar"
                description="School years and the terms that make them up."
            />

            <Button v-if="canManage" as-child>
                <Link :href="create()">New school year</Link>
            </Button>
        </div>

        <div class="overflow-x-auto rounded-lg border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/50">
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            School year
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Starts
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Ends
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Terms
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Status
                        </th>
                        <th
                            v-if="canManage"
                            scope="col"
                            class="px-4 py-3 text-right font-medium"
                        >
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="year in years"
                        :key="year.id"
                        class="border-b last:border-b-0"
                    >
                        <td class="px-4 py-3 font-medium">{{ year.name }}</td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ year.start_date }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ year.end_date }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ year.terms_count }}
                        </td>
                        <td class="px-4 py-3">
                            <span
                                v-if="year.is_current"
                                class="inline-flex items-center rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary"
                            >
                                Current
                            </span>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                        <td v-if="canManage" class="px-4 py-3 text-right">
                            <Link
                                :href="edit(year.id)"
                                class="text-sm font-medium underline-offset-4 hover:underline"
                            >
                                Manage
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="!years.length">
                        <td
                            :colspan="canManage ? 6 : 5"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No school years yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
