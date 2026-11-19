<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import PageHeader from '@/components/PageHeader.vue';
import { edit } from '@/routes/appearance';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Appearance settings',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Appearance settings" />

    <div class="flex flex-col gap-6">
        <PageHeader
            eyebrow="Settings"
            title="Appearance settings"
            description="Update the appearance settings for your account"
        />
        <AppearanceTabs />
    </div>
</template>
