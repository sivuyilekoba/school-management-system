<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
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

    <div class="flex max-w-3xl flex-col gap-8">
        <PageHeader
            eyebrow="Announcement"
            :title="announcement.title"
            :description="`${announcement.audience_label} · ${announcement.published_at ?? 'Not dated'}`"
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <Link :href="index()">Back to announcements</Link>
                </Button>
            </template>
        </PageHeader>

        <div
            class="rounded-xl border bg-card p-6 text-sm whitespace-pre-line shadow-card"
        >
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
