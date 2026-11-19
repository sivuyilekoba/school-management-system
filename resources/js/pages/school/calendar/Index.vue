<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/school/calendar';
import { create, edit } from '@/routes/school/calendar/years';
import type { DataTableColumn } from '@/types';

type SchoolYearRow = {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
    is_current: boolean;
    terms_count: number;
};

const props = defineProps<{
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

const columns = computed<DataTableColumn[]>(() => [
    { key: 'name', label: 'School year' },
    { key: 'start_date', label: 'Starts' },
    { key: 'end_date', label: 'Ends' },
    { key: 'terms_count', label: 'Terms' },
    { key: 'is_current', label: 'Status' },
    ...(canManage.value
        ? [
              {
                  key: 'actions',
                  label: '',
                  srLabel: 'Actions',
                  align: 'right' as const,
              },
          ]
        : []),
]);
</script>

<template>
    <Head title="Calendar" />

    <div class="flex flex-col gap-6">
        <PageHeader
            eyebrow="Teaching"
            title="Calendar"
            description="School years and the terms that make them up."
        >
            <template #actions>
                <Button v-if="canManage" as-child>
                    <Link :href="create()">New school year</Link>
                </Button>
            </template>
        </PageHeader>

        <DataTable
            :columns="columns"
            :rows="props.years"
            row-key="id"
            caption="School years"
            empty-title="No school years yet."
            empty-description="Add a school year before creating terms and classes."
        >
            <template #row="{ row }">
                <td class="px-4 py-3 font-medium">{{ row.name }}</td>
                <td class="px-4 py-3 text-muted-foreground">
                    {{ row.start_date }}
                </td>
                <td class="px-4 py-3 text-muted-foreground">
                    {{ row.end_date }}
                </td>
                <td class="px-4 py-3 text-muted-foreground">
                    {{ row.terms_count }}
                </td>

                <td class="px-4 py-3">
                    <StatusBadge
                        v-if="row.is_current"
                        label="Current"
                        tone="success"
                    />
                    <span v-else class="text-muted-foreground">—</span>
                </td>

                <td v-if="canManage" class="px-4 py-3 text-right">
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="edit(row.id)">Manage</Link>
                    </Button>
                </td>
            </template>
        </DataTable>
    </div>
</template>
