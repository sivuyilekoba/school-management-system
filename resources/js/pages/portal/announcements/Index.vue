<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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

const search = ref(props.search);

function submitSearch(): void {
    router.get(
        index.url(),
        { q: search.value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}
</script>

<template>
    <Head title="Announcements" />

    <h1 class="sr-only">Announcements</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Announcements"
            description="Notices shared with students and families."
        />

        <form class="max-w-md" @submit.prevent="submitSearch">
            <div class="flex gap-2">
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Search announcements"
                    aria-label="Search announcements"
                />
                <Button type="submit" variant="outline">Search</Button>
            </div>
        </form>

        <div
            v-if="announcements.data.length"
            class="divide-y rounded-lg border"
        >
            <Link
                v-for="announcement in announcements.data"
                :key="announcement.id"
                :href="show(announcement.id)"
                class="block px-4 py-4 transition-colors hover:bg-muted/40"
            >
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-medium">{{ announcement.title }}</span>
                    <Badge variant="outline">
                        {{ announcement.audience_label }}
                    </Badge>
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ announcement.published_at ?? '' }}
                    <template v-if="announcement.author">
                        · {{ announcement.author }}
                    </template>
                </p>
            </Link>
        </div>

        <p
            v-else
            class="rounded-lg border px-4 py-8 text-center text-sm text-muted-foreground"
        >
            {{
                search
                    ? 'No announcements match your search.'
                    : 'No announcements have been posted for you yet.'
            }}
        </p>

        <div
            v-if="announcements.last_page > 1"
            class="flex flex-wrap items-center justify-between gap-3"
        >
            <p class="text-sm text-muted-foreground">
                Page {{ announcements.current_page }} of
                {{ announcements.last_page }} · {{ announcements.total }}
                announcements
            </p>
            <div class="flex gap-2">
                <Button
                    v-if="announcements.prev_page_url"
                    variant="outline"
                    size="sm"
                    as-child
                >
                    <Link :href="announcements.prev_page_url">Previous</Link>
                </Button>
                <Button
                    v-if="announcements.next_page_url"
                    variant="outline"
                    size="sm"
                    as-child
                >
                    <Link :href="announcements.next_page_url">Next</Link>
                </Button>
            </div>
        </div>
    </div>
</template>
