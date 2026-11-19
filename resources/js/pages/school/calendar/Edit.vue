<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import SchoolYearForm from '@/components/SchoolYearForm.vue';
import type { SchoolYearRecord } from '@/components/SchoolYearForm.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/school/calendar';
import {
    create as createTerm,
    edit as editTerm,
} from '@/routes/school/calendar/terms';
import type { DataTableColumn } from '@/types';

type TermRow = {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
};

defineProps<{
    year: SchoolYearRecord;
    terms: TermRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Calendar',
                href: index(),
            },
            {
                title: 'Edit school year',
                href: index(),
            },
        ],
    },
});

const columns: DataTableColumn[] = [
    { key: 'name', label: 'Term' },
    { key: 'start_date', label: 'Starts' },
    { key: 'end_date', label: 'Ends' },
    {
        key: 'actions',
        label: '',
        srLabel: 'Actions',
        align: 'right',
    },
];
</script>

<template>
    <Head :title="`Edit ${year.name}`" />

    <div class="flex max-w-3xl flex-col gap-8">
        <div class="flex flex-col gap-6">
            <PageHeader
                eyebrow="School year"
                :title="`Edit ${year.name}`"
                description="Update the school year details."
            />

            <SchoolYearForm :year="year" />
        </div>

        <div class="flex flex-col gap-4">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div class="space-y-1">
                    <h2 class="text-lg font-semibold">Terms</h2>
                    <p class="text-sm text-muted-foreground">
                        Terms must fall within the school year dates.
                    </p>
                </div>

                <Button as-child variant="outline">
                    <Link :href="createTerm(year.id)">Add term</Link>
                </Button>
            </div>

            <DataTable
                :columns="columns"
                :rows="terms"
                row-key="id"
                caption="Terms in this school year"
                empty-title="No terms yet."
                empty-description="Add a term to build out the school calendar."
            >
                <template #row="{ row }">
                    <td class="px-4 py-3 font-medium">{{ row.name }}</td>
                    <td class="px-4 py-3 text-muted-foreground">
                        {{ row.start_date }}
                    </td>
                    <td class="px-4 py-3 text-muted-foreground">
                        {{ row.end_date }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <Link
                            :href="editTerm(row.id)"
                            class="text-sm font-medium underline-offset-4 hover:underline"
                        >
                            Edit
                        </Link>
                    </td>
                </template>
            </DataTable>
        </div>
    </div>
</template>
