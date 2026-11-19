<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import DataPagination from '@/components/DataPagination.vue';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import SearchForm from '@/components/SearchForm.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { create, edit, index, show } from '@/routes/school/announcements';
import type { DataTableColumn } from '@/types';

type AnnouncementRow = {
    id: number;
    title: string;
    audience: string;
    audience_label: string;
    status: string;
    published_at: string | null;
    expires_at: string | null;
    author: string | null;
};

type AnnouncementPaginator = {
    data: AnnouncementRow[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
};

const props = defineProps<{
    announcements: AnnouncementPaginator;
    search: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Announcements',
                href: index(),
            },
        ],
    },
});

const page = usePage();

const canManage = computed(() =>
    page.props.auth.permissions.includes('announcements.manage'),
);

const isSearching = computed(() => props.search.length > 0);

const columns = computed<DataTableColumn[]>(() => [
    { key: 'title', label: 'Announcement' },
    { key: 'audience', label: 'Audience' },
    { key: 'status', label: 'Status' },
    { key: 'published_at', label: 'Published' },
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

const statusTones = {
    draft: 'neutral',
    scheduled: 'info',
    published: 'success',
    expired: 'danger',
} as const;

function statusTone(
    status: string,
): 'success' | 'warning' | 'info' | 'danger' | 'neutral' {
    return statusTones[status as keyof typeof statusTones] ?? 'neutral';
}

function statusLabel(status: string): string {
    return status.charAt(0).toUpperCase() + status.slice(1);
}
</script>

<template>
    <Head title="Announcements" />

    <div class="flex flex-col gap-6">
        <PageHeader
            eyebrow="Communication"
            title="Announcements"
            description="Notices posted to staff, students, and families."
        >
            <template #actions>
                <Button v-if="canManage" as-child>
                    <Link :href="create()">New announcement</Link>
                </Button>
            </template>
        </PageHeader>

        <SearchForm
            :url="index.url()"
            :value="props.search"
            placeholder="Search announcements"
            label="Search announcements"
        />

        <DataTable
            :columns="columns"
            :rows="props.announcements.data"
            row-key="id"
            caption="Announcements"
            :empty-title="
                isSearching
                    ? 'No announcements match your search.'
                    : 'No announcements yet.'
            "
            :empty-description="
                isSearching
                    ? 'Try a different title or message.'
                    : 'Write the first announcement for your school.'
            "
        >
            <template #row="{ row }">
                <td class="px-4 py-3">
                    <Link
                        :href="show(row.id)"
                        class="font-medium underline-offset-4 hover:underline"
                    >
                        {{ row.title }}
                    </Link>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ row.author ?? 'Unknown author' }}
                    </p>
                </td>

                <td class="px-4 py-3 text-muted-foreground">
                    {{ row.audience_label }}
                </td>

                <td class="px-4 py-3">
                    <StatusBadge
                        :label="statusLabel(row.status)"
                        :tone="statusTone(row.status)"
                    />
                </td>

                <td class="px-4 py-3 text-muted-foreground">
                    {{ row.published_at ?? '—' }}
                </td>

                <td v-if="canManage" class="px-4 py-3 text-right">
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="edit(row.id)">Edit</Link>
                    </Button>
                </td>
            </template>
        </DataTable>

        <DataPagination
            :current-page="props.announcements.current_page"
            :last-page="props.announcements.last_page"
            :total="props.announcements.total"
            :prev-url="props.announcements.prev_page_url"
            :next-url="props.announcements.next_page_url"
            item-label="announcements"
        />
    </div>
</template>
