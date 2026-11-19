<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import DataPagination from '@/components/DataPagination.vue';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import SearchForm from '@/components/SearchForm.vue';
import { Button } from '@/components/ui/button';
import { create, edit, index } from '@/routes/school/students';
import type { DataTableColumn } from '@/types';

type StudentRow = {
    id: number;
    name: string;
    gender: string;
    date_of_birth: string | null;
    email: string | null;
    family: string | null;
    account: string | null;
};

type StudentPaginator = {
    data: StudentRow[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
};

const props = defineProps<{
    students: StudentPaginator;
    search: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Students',
                href: index(),
            },
        ],
    },
});

const page = usePage();

const canManage = computed(() =>
    page.props.auth.permissions.includes('people.students.manage'),
);

const isSearching = computed(() => props.search.length > 0);

const columns = computed<DataTableColumn[]>(() => [
    { key: 'name', label: 'Student' },
    { key: 'family', label: 'Family' },
    { key: 'date_of_birth', label: 'Date of birth' },
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
    <Head title="Students" />

    <div class="flex flex-col gap-6">
        <PageHeader
            eyebrow="People"
            title="Students"
            description="Student records for the school."
        >
            <template #actions>
                <Button v-if="canManage" as-child>
                    <Link :href="create()">New student</Link>
                </Button>
            </template>
        </PageHeader>

        <SearchForm
            :url="index.url()"
            :value="props.search"
            placeholder="Search by name or email"
            label="Search students"
        />

        <DataTable
            :columns="columns"
            :rows="props.students.data"
            row-key="id"
            caption="Students"
            :empty-title="
                isSearching
                    ? 'No students match your search.'
                    : 'No students yet.'
            "
            :empty-description="
                isSearching
                    ? 'Try a different name or email.'
                    : 'Add the first student record to get started.'
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
                    <span v-if="row.family">{{ row.family }}</span>
                    <span v-else class="text-muted-foreground">
                        No family
                    </span>
                </td>

                <td class="px-4 py-3 text-muted-foreground">
                    {{ row.date_of_birth ?? '—' }}
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
            :current-page="props.students.current_page"
            :last-page="props.students.last_page"
            :total="props.students.total"
            :prev-url="props.students.prev_page_url"
            :next-url="props.students.next_page_url"
            item-label="students"
        />
    </div>
</template>
