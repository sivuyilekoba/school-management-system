<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head title="Dashboard" />

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">
                Welcome back, {{ user.name }}
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                This is your school dashboard. Modules will appear here as they
                are added.
            </p>
        </div>
    </div>
</template>
