<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AnnouncementForm from '@/components/AnnouncementForm.vue';
import type { AudienceOption } from '@/components/AnnouncementForm.vue';
import Heading from '@/components/Heading.vue';
import { create, index } from '@/routes/school/announcements';

defineProps<{
    audiences: AudienceOption[];
    defaults: {
        published_at: string | null;
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
                title: 'New announcement',
                href: create(),
            },
        ],
    },
});
</script>

<template>
    <Head title="New announcement" />

    <h1 class="sr-only">New announcement</h1>

    <div class="flex max-w-3xl flex-col space-y-6">
        <Heading
            variant="small"
            title="New announcement"
            description="Post a notice for a group of people at the school."
        />

        <AnnouncementForm :audiences="audiences" :defaults="defaults" />
    </div>
</template>
