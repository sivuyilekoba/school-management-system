<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import DataPagination from '@/components/DataPagination.vue';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import SearchForm from '@/components/SearchForm.vue';
import { Button } from '@/components/ui/button';
import { create, edit, index } from '@/routes/school/staff';
import type { DataTableColumn } from '@/types';

type StaffRow = {
    id: number;
    name: string;
    job_title: string | null;
    email: string | null;
    phone: string | null;
    account: string | null;
};

type StaffPaginator = {
    data: StaffRow[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
};

const props = defineProps<{
    staff: StaffPaginator;
    search: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Staff',
                href: index(),
            },
        ],
    },
});

const page = usePage();

const canManage = computed(() =>
    page.props.auth.permissions.includes('people.staff.manage'),
);

const isSearching = computed(() => props.search.length > 0);

const columns = computed<DataTableColumn[]>(() => [
    { key: 'name', label: 'Staff member' },
    { key: 'job_title', label: 'Job title' },
    { key: 'phone', label: 'Phone' },
    { key: 'account', label: 'Account' },
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
    <Head title="Staff" />

    <div class="flex flex-col gap-6">
        <PageHeader
            eyebrow="People"
            title="Staff"
            description="Staff records for the school."
        >
            <template #actions>
                <Button v-if="canManage" as-child>
                    <Link :href="create()">New staff member</Link>
                </Button>
            </template>
        </PageHeader>

        <SearchForm
            :url="index.url()"
            :value="props.search"
            placeholder="Search by name, job title or email"
            label="Search staff"
        />

        <DataTable
            :columns="columns"
            :rows="props.staff.data"
            row-key="id"
            caption="Staff"
            :empty-title="
                isSearching
                    ? 'No staff members match your search.'
                    : 'No staff members yet.'
            "
            :empty-description="
                isSearching
                    ? 'Try a different name, job title or email.'
                    : 'Add the first staff record to get started.'
            "
        >
            <template #row="{ row }">
                <td class="px-4 py-3">
                    <p class="font-medium">{{ row.name }}</p>
                    <p v-if="row.email" class="text-muted-foreground">
                        {{ row.email }}
                    </p>
                </td>

                <td class="px-4 py-3">
                    <span v-if="row.job_title">{{ row.job_title }}</span>
                    <span v-else class="text-muted-foreground">Not set</span>
                </td>

                <td class="px-4 py-3 text-muted-foreground">
                    {{ row.phone ?? '—' }}
                </td>

                <td class="px-4 py-3">
                    <span v-if="row.account">{{ row.account }}</span>
                    <span v-else class="text-muted-foreground">
                        Not linked
                    </span>
                </td>

                <td v-if="canManage" class="px-4 py-3 text-right">
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="edit(row.id)">Edit</Link>
                    </Button>
                </td>
            </template>
        </DataTable>

        <DataPagination
            :current-page="props.staff.current_page"
            :last-page="props.staff.last_page"
            :total="props.staff.total"
            :prev-url="props.staff.prev_page_url"
            :next-url="props.staff.next_page_url"
            item-label="staff members"
        />
    </div>
</template>
