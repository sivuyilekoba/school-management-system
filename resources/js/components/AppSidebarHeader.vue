<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { MessageCircle } from '@lucide/vue';
import { computed } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import GlobalSearch from '@/components/GlobalSearch.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { index as messages } from '@/routes/messages';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();

const unreadMessages = computed(() => page.props.auth.unreadMessages);

const canMessage = computed(
    () =>
        page.props.auth.roles.includes('Student') ||
        page.props.auth.roles.includes('Parent') ||
        page.props.auth.permissions.includes('messages.view'),
);

const messagesLabel = computed(() =>
    unreadMessages.value > 0
        ? `Messages, ${unreadMessages.value} unread`
        : 'Messages',
);
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-3 border-b border-sidebar-border/70 px-4 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-6"
    >
        <div class="flex min-w-0 items-center gap-2">
            <SidebarTrigger class="-ml-1" />

            <Breadcrumbs
                v-if="breadcrumbs.length > 0"
                class="hidden min-w-0 sm:flex"
                :breadcrumbs="breadcrumbs"
            />
        </div>

        <div class="ml-auto flex shrink-0 items-center gap-1">
            <GlobalSearch />

            <Button
                v-if="canMessage"
                as-child
                variant="ghost"
                size="icon"
                class="relative text-muted-foreground"
            >
                <Link :href="messages()" :aria-label="messagesLabel">
                    <MessageCircle aria-hidden="true" />
                    <span
                        v-if="unreadMessages > 0"
                        aria-hidden="true"
                        class="absolute top-1.5 right-2 size-2 rounded-full bg-primary ring-2 ring-background"
                    />
                </Link>
            </Button>

            <ThemeToggle />
        </div>
    </header>
</template>
