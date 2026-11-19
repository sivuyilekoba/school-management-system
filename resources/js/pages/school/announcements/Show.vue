<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AnnouncementController from '@/actions/App/Http/Controllers/School/AnnouncementController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { edit, index } from '@/routes/school/announcements';

type Announcement = {
    id: number;
    title: string;
    body: string;
    audience: string;
    audience_label: string;
    status: string;
    published_at: string | null;
    expires_at: string | null;
    author: string | null;
    created_at: string | null;
};

const props = defineProps<{
    announcement: Announcement;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Announcements',
                href: index(),
            },
            {
                title: 'Announcement',
                href: index(),
            },
        ],
    },
});

const page = usePage();

const canManage = computed(() =>
    page.props.auth.permissions.includes('announcements.manage'),
);

const statusVariants: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    draft: 'secondary',
    scheduled: 'outline',
    published: 'default',
    expired: 'destructive',
};

const statusLabel = computed(
    () =>
        props.announcement.status.charAt(0).toUpperCase() +
        props.announcement.status.slice(1),
);

function publish(): void {
    router.put(
        AnnouncementController.publish.url(props.announcement.id),
        {},
        { preserveScroll: true },
    );
}

function unpublish(): void {
    if (!confirm('Return this announcement to draft?')) {
        return;
    }

    router.delete(AnnouncementController.unpublish.url(props.announcement.id), {
        preserveScroll: true,
    });
}

function remove(): void {
    if (!confirm(`Delete "${props.announcement.title}"?`)) {
        return;
    }

    router.delete(AnnouncementController.destroy.url(props.announcement.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="announcement.title" />

    <h1 class="sr-only">Announcement</h1>

    <div class="flex max-w-3xl flex-col space-y-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    <Badge
                        :variant="
                            statusVariants[announcement.status] ?? 'secondary'
                        "
                    >
                        {{ statusLabel }}
                    </Badge>
                    <Badge variant="outline">
                        {{ announcement.audience_label }}
                    </Badge>
                </div>

                <Heading variant="small" :title="announcement.title" />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Button variant="outline" as-child>
                    <Link :href="index()">Back to announcements</Link>
                </Button>

                <template v-if="canManage">
                    <Button variant="outline" as-child>
                        <Link :href="edit(announcement.id)">Edit</Link>
                    </Button>

                    <Button
                        v-if="announcement.status === 'draft'"
                        @click="publish"
                    >
                        Publish
                    </Button>
                    <Button v-else variant="outline" @click="unpublish">
                        Unpublish
                    </Button>

                    <Button variant="destructive" @click="remove">
                        Delete
                    </Button>
                </template>
            </div>
        </div>

        <div class="rounded-lg border p-6 text-sm whitespace-pre-line">
            {{ announcement.body }}
        </div>

        <dl class="grid gap-4 text-sm sm:grid-cols-4">
            <div>
                <dt class="text-muted-foreground">Author</dt>
                <dd class="mt-1 font-medium">
                    {{ announcement.author ?? 'Unknown' }}
                </dd>
            </div>
            <div>
                <dt class="text-muted-foreground">Published</dt>
                <dd class="mt-1 font-medium">
                    {{ announcement.published_at ?? 'Not published' }}
                </dd>
            </div>
            <div>
                <dt class="text-muted-foreground">Expires</dt>
                <dd class="mt-1 font-medium">
                    {{ announcement.expires_at ?? 'No expiry' }}
                </dd>
            </div>
            <div>
                <dt class="text-muted-foreground">Created</dt>
                <dd class="mt-1 font-medium">
                    {{ announcement.created_at ?? '—' }}
                </dd>
            </div>
        </dl>
    </div>
</template>
