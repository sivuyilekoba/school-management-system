<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AnnouncementForm from '@/components/AnnouncementForm.vue';
import type {
    AnnouncementRecord,
    AudienceOption,
} from '@/components/AnnouncementForm.vue';
import PageHeader from '@/components/PageHeader.vue';
import { index, show } from '@/routes/school/announcements';

defineProps<{
    announcement: AnnouncementRecord;
    audiences: AudienceOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Announcements',
                href: index(),
            },
            {
                title: 'Edit announcement',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit ${announcement.title}`" />

    <div class="flex max-w-3xl flex-col space-y-6">
        <PageHeader
            eyebrow="Announcements"
            title="Edit announcement"
            description="Update the notice, its audience, or when it is published."
        />

        <AnnouncementForm :announcement="announcement" :audiences="audiences" />

        <p class="text-sm text-muted-foreground">
            <a
                :href="show.url(announcement.id)"
                class="font-medium underline-offset-4 hover:underline"
            >
                Back to the announcement
            </a>
        </p>
    </div>
</template>
