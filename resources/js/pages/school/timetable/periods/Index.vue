<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/school/timetable';
import { create, edit } from '@/routes/school/timetable/periods';
import type { DataTableColumn } from '@/types';

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

const columns: DataTableColumn[] = [
    { key: 'name', label: 'Period' },
    { key: 'start_time', label: 'Starts' },
    { key: 'end_time', label: 'Ends' },
    { key: 'entries_count', label: 'Lessons' },
    {
        key: 'actions',
        label: '',
        srLabel: 'Actions',
        align: 'right',
    },
];
</script>

<template>
    <Head title="Timetable periods" />

    <div class="flex flex-col gap-6">
        <PageHeader
            eyebrow="Timetable"
            title="Timetable periods"
            description="The periods that make up each teaching day."
        >
            <template #actions>
                <Button as-child>
                    <Link :href="create()">New period</Link>
                </Button>
            </template>
        </PageHeader>

        <DataTable
            :columns="columns"
            :rows="periods"
            row-key="id"
            caption="Timetable periods"
            empty-title="No periods yet."
            empty-description="Add the periods that make up each teaching day."
        >
            <template #row="{ row }">
                <td class="px-4 py-3 font-medium">{{ row.name }}</td>
                <td class="px-4 py-3 text-muted-foreground">
                    {{ row.start_time }}
                </td>
                <td class="px-4 py-3 text-muted-foreground">
                    {{ row.end_time }}
                </td>
                <td class="px-4 py-3 text-muted-foreground">
                    {{ row.entries_count }}
                </td>
                <td class="px-4 py-3 text-right">
                    <Link
                        :href="edit(row.id)"
                        class="text-sm font-medium underline-offset-4 hover:underline"
                    >
                        Edit
                    </Link>
                </td>
            </template>
        </DataTable>

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
