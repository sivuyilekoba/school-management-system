<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { index as messages } from '@/routes/messages';
import { dashboard } from '@/routes/portal';
import {
    index as announcementList,
    show as showAnnouncement,
} from '@/routes/portal/announcements';
import { index as reports } from '@/routes/portal/reports';

type StudentRow = {
    id: number;
    name: string;
};

type AnnouncementRow = {
    id: number;
    title: string;
    audience_label: string;
    published_at: string | null;
    author: string | null;
};

defineProps<{
    students: StudentRow[];
    announcements: AnnouncementRow[];
    isParent: boolean;
    unreadMessages: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Portal',
                href: dashboard(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head title="Portal" />

    <div class="flex flex-col gap-8">
        <PageHeader
            eyebrow="Portal"
            :title="`Welcome, ${user.name}`"
            :description="
                isParent
                    ? 'Announcements, messages, and published report cards for your family.'
                    : 'Announcements, messages, and your published report cards.'
            "
        />

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl border bg-card p-5 shadow-card">
                <p class="text-sm text-muted-foreground">
                    {{ isParent ? 'Children' : 'Your student record' }}
                </p>

                <ul v-if="students.length" class="mt-3 space-y-2 text-sm">
                    <li
                        v-for="student in students"
                        :key="student.id"
                        class="font-medium"
                    >
                        {{ student.name }}
                    </li>
                </ul>

                <p v-else class="mt-3 text-sm text-muted-foreground">
                    No student record is linked to this account yet. Contact the
                    school office.
                </p>

                <Button variant="outline" size="sm" class="mt-4" as-child>
                    <Link :href="reports()">View report cards</Link>
                </Button>
            </div>

            <div class="rounded-xl border bg-card p-5 shadow-card">
                <p class="text-sm text-muted-foreground">Messages</p>
                <p class="mt-1 text-3xl font-semibold tabular-nums">
                    {{ unreadMessages }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{
                        unreadMessages === 1
                            ? 'unread message'
                            : 'unread messages'
                    }}
                </p>

                <Button variant="outline" size="sm" class="mt-4" as-child>
                    <Link :href="messages()">Open inbox</Link>
                </Button>
            </div>
        </div>

        <section class="space-y-3">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <h2 class="text-lg font-semibold">Latest announcements</h2>
                <Button variant="outline" size="sm" as-child>
                    <Link :href="announcementList()">All announcements</Link>
                </Button>
            </div>

            <div
                v-if="announcements.length"
                class="divide-y overflow-hidden rounded-xl border bg-card shadow-card"
            >
                <Link
                    v-for="announcement in announcements"
                    :key="announcement.id"
                    :href="showAnnouncement(announcement.id)"
                    class="block px-4 py-3 transition-colors hover:bg-muted/40"
                >
                    <p class="font-medium">{{ announcement.title }}</p>
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
                title="No announcements have been posted for you yet."
                description="New notices from the school will appear here."
            />
        </section>
    </div>
</template>
