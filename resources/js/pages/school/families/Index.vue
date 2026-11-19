<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import DataPagination from '@/components/DataPagination.vue';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import SearchForm from '@/components/SearchForm.vue';
import { Button } from '@/components/ui/button';
import { create, edit, index } from '@/routes/school/families';
import type { DataTableColumn } from '@/types';

type FamilyRow = {
    id: number;
    name: string;
    contact_email: string | null;
    contact_phone: string | null;
    students_count: number;
};

type FamilyPaginator = {
    data: FamilyRow[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
};

const props = defineProps<{
    families: FamilyPaginator;
    search: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Families',
                href: index(),
            },
        ],
    },
});

const page = usePage();

const canManage = computed(() =>
    page.props.auth.permissions.includes('people.families.manage'),
);

const isSearching = computed(() => props.search.length > 0);

const columns = computed<DataTableColumn[]>(() => [
    { key: 'name', label: 'Family' },
    { key: 'students_count', label: 'Students' },
    { key: 'contact_email', label: 'Contact email' },
    { key: 'contact_phone', label: 'Contact phone' },
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
    <Head title="Families" />

    <div class="flex flex-col gap-6">
        <PageHeader
            eyebrow="People"
            title="Families"
            description="Family groups that students belong to."
        >
            <template #actions>
                <Button v-if="canManage" as-child>
                    <Link :href="create()">New family</Link>
                </Button>
            </template>
        </PageHeader>

        <SearchForm
            :url="index.url()"
            :value="props.search"
            placeholder="Search by name or contact email"
            label="Search families"
        />

        <DataTable
            :columns="columns"
            :rows="props.families.data"
            row-key="id"
            caption="Families"
            :empty-title="
                isSearching
                    ? 'No families match your search.'
                    : 'No families yet.'
            "
            :empty-description="
                isSearching
                    ? 'Try a different name or contact email.'
                    : 'Add the first family to get started.'
            "
        >
            <template #row="{ row }">
                <td class="px-4 py-3 font-medium">{{ row.name }}</td>
                <td class="px-4 py-3">{{ row.students_count }}</td>
                <td class="px-4 py-3 text-muted-foreground">
                    {{ row.contact_email ?? '—' }}
                </td>
                <td class="px-4 py-3 text-muted-foreground">
                    {{ row.contact_phone ?? '—' }}
                </td>

                <td v-if="canManage" class="px-4 py-3 text-right">
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="edit(row.id)">Edit</Link>
                    </Button>
                </td>
            </template>
        </DataTable>

        <DataPagination
            :current-page="props.families.current_page"
            :last-page="props.families.last_page"
            :total="props.families.total"
            :prev-url="props.families.prev_page_url"
            :next-url="props.families.next_page_url"
            item-label="families"
        />
    </div>
</template>
