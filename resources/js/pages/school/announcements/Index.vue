<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { create, edit, index, show } from '@/routes/school/announcements';

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

const search = ref(props.search);

const statusVariants: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    draft: 'secondary',
    scheduled: 'outline',
    published: 'default',
    expired: 'destructive',
};

function statusLabel(status: string): string {
    return status.charAt(0).toUpperCase() + status.slice(1);
}

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
        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                variant="small"
                title="Announcements"
                description="Notices posted to staff, students, and families."
            />

            <Button v-if="canManage" as-child>
                <Link :href="create()">New announcement</Link>
            </Button>
        </div>

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

        <div class="overflow-x-auto rounded-lg border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/50">
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Announcement
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Audience
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Status
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Published
                        </th>
                        <th
                            v-if="canManage"
                            scope="col"
                            class="px-4 py-3 text-right font-medium"
                        >
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="announcement in announcements.data"
                        :key="announcement.id"
                        class="border-b last:border-b-0"
                    >
                        <td class="px-4 py-3">
                            <Link
                                :href="show(announcement.id)"
                                class="font-medium underline-offset-4 hover:underline"
                            >
                                {{ announcement.title }}
                            </Link>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ announcement.author ?? 'Unknown author' }}
                            </p>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ announcement.audience_label }}
                        </td>
                        <td class="px-4 py-3">
                            <Badge
                                :variant="
                                    statusVariants[announcement.status] ??
                                    'secondary'
                                "
                            >
                                {{ statusLabel(announcement.status) }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ announcement.published_at ?? '—' }}
                        </td>
                        <td v-if="canManage" class="px-4 py-3 text-right">
                            <Link
                                :href="edit(announcement.id)"
                                class="text-sm font-medium underline-offset-4 hover:underline"
                            >
                                Edit
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="!announcements.data.length">
                        <td
                            :colspan="canManage ? 5 : 4"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            {{
                                search
                                    ? 'No announcements match your search.'
                                    : 'No announcements yet.'
                            }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

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
