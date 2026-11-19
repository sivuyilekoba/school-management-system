<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/portal/announcements';

defineProps<{
    announcement: {
        id: number;
        title: string;
        body: string;
        audience: string;
        audience_label: string;
        published_at: string | null;
        expires_at: string | null;
        author: string | null;
    };
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
</script>

<template>
    <Head :title="announcement.title" />

    <h1 class="sr-only">Announcement</h1>

    <div class="flex max-w-3xl flex-col space-y-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-3">
                <Badge variant="outline">
                    {{ announcement.audience_label }}
                </Badge>

                <Heading variant="small" :title="announcement.title" />
            </div>

            <Button variant="outline" as-child>
                <Link :href="index()">Back to announcements</Link>
            </Button>
        </div>

        <div class="rounded-lg border p-6 text-sm whitespace-pre-line">
            {{ announcement.body }}
        </div>

        <dl class="grid gap-4 text-sm sm:grid-cols-3">
            <div>
                <dt class="text-muted-foreground">Author</dt>
                <dd class="mt-1 font-medium">
                    {{ announcement.author ?? 'School office' }}
                </dd>
            </div>
            <div>
                <dt class="text-muted-foreground">Published</dt>
                <dd class="mt-1 font-medium">
                    {{ announcement.published_at ?? '—' }}
                </dd>
            </div>
            <div>
                <dt class="text-muted-foreground">Expires</dt>
                <dd class="mt-1 font-medium">
                    {{ announcement.expires_at ?? 'No expiry' }}
                </dd>
            </div>
        </dl>
    </div>
</template>
