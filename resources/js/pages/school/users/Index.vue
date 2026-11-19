<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import DataPagination from '@/components/DataPagination.vue';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import SearchForm from '@/components/SearchForm.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { create, edit, index } from '@/routes/school/users';
import type { DataTableColumn } from '@/types';

type UserRow = {
    id: number;
    name: string;
    email: string;
    roles: string[];
};

type UserPaginator = {
    data: UserRow[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
};

const props = defineProps<{
    users: UserPaginator;
    search: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Users',
                href: index(),
            },
        ],
    },
});

const page = usePage();

const canManage = computed(() =>
    page.props.auth.permissions.includes('users.manage'),
);

const isSearching = computed(() => props.search.length > 0);

const columns = computed<DataTableColumn[]>(() => [
    { key: 'name', label: 'Name' },
    { key: 'email', label: 'Email' },
    { key: 'roles', label: 'Roles' },
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
    <Head title="Users" />

    <div class="flex flex-col gap-6">
        <PageHeader
            eyebrow="School"
            title="Users"
            description="Accounts that can sign in to the school system."
        >
            <template #actions>
                <Button v-if="canManage" as-child>
                    <Link :href="create()">New user</Link>
                </Button>
            </template>
        </PageHeader>

        <SearchForm
            :url="index.url()"
            :value="props.search"
            placeholder="Search by name or email"
            label="Search users"
        />

        <DataTable
            :columns="columns"
            :rows="props.users.data"
            row-key="id"
            caption="Users"
            :empty-title="
                isSearching ? 'No users match your search.' : 'No users yet.'
            "
            :empty-description="
                isSearching
                    ? 'Try a different name or email.'
                    : 'Create the first user account to get started.'
            "
        >
            <template #row="{ row }">
                <td class="px-4 py-3 font-medium">{{ row.name }}</td>
                <td class="px-4 py-3 text-muted-foreground">
                    {{ row.email }}
                </td>

                <td class="px-4 py-3">
                    <div class="flex flex-wrap gap-1">
                        <Badge
                            v-for="role in row.roles"
                            :key="role"
                            variant="secondary"
                        >
                            {{ role }}
                        </Badge>
                        <span
                            v-if="!row.roles.length"
                            class="text-muted-foreground"
                        >
                            No role
                        </span>
                    </div>
                </td>

                <td v-if="canManage" class="px-4 py-3 text-right">
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="edit(row.id)">Edit</Link>
                    </Button>
                </td>
            </template>
        </DataTable>

        <DataPagination
            :current-page="props.users.current_page"
            :last-page="props.users.last_page"
            :total="props.users.total"
            :prev-url="props.users.prev_page_url"
            :next-url="props.users.next_page_url"
            item-label="users"
        />
    </div>
</template>
