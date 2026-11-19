<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import DataPagination from '@/components/DataPagination.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import SearchForm from '@/components/SearchForm.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { index, show } from '@/routes/portal/announcements';

type AnnouncementRow = {
    id: number;
    title: string;
    audience: string;
    audience_label: string;
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
</script>

<template>
    <Head title="Announcements" />

    <div class="flex flex-col gap-6">
        <PageHeader
            eyebrow="Portal"
            title="Announcements"
            description="Notices shared with students and families."
        />

        <SearchForm
            :url="index.url()"
            :value="props.search"
            placeholder="Search announcements"
            label="Search announcements"
        />

        <div
            v-if="announcements.data.length"
            class="divide-y overflow-hidden rounded-xl border bg-card shadow-card"
        >
            <Link
                v-for="announcement in announcements.data"
                :key="announcement.id"
                :href="show(announcement.id)"
                class="block px-4 py-4 transition-colors hover:bg-muted/40"
            >
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-medium">{{ announcement.title }}</span>
                    <StatusBadge
                        :label="announcement.audience_label"
                        tone="neutral"
                    />
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ announcement.published_at ?? '' }}
                    <template v-if="announcement.author">
                        · {{ announcement.author }}
                    </template>
                </p>
            </Link>
        </div>

        <EmptyState
            v-else
            :title="
                props.search
                    ? 'No announcements match your search.'
                    : 'No announcements have been posted for you yet.'
            "
            :description="
                props.search
                    ? 'Try a different search term.'
                    : 'New notices from the school will appear here.'
            "
        />

        <DataPagination
            :current-page="announcements.current_page"
            :last-page="announcements.last_page"
            :total="announcements.total"
            :prev-url="announcements.prev_page_url"
            :next-url="announcements.next_page_url"
            item-label="announcements"
        />
    </div>
</template>
