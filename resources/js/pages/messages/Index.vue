<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { create, index, show } from '@/routes/messages';

type Conversation = {
    participant: {
        id: number;
        name: string;
        email: string;
    };
    latest: {
        body: string;
        sent_at: string | null;
        outgoing: boolean;
    };
    unread: number;
};

defineProps<{
    conversations: Conversation[];
    unreadMessages: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Messages',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Messages" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6">
        <PageHeader
            eyebrow="Inbox"
            title="Messages"
            description="Direct conversations with staff, students, and families."
        >
            <template #actions>
                <Button as-child>
                    <Link :href="create()">New message</Link>
                </Button>
            </template>
        </PageHeader>

        <p
            v-if="unreadMessages"
            class="rounded-xl border bg-muted/40 px-4 py-3 text-sm"
        >
            You have {{ unreadMessages }} unread
            {{ unreadMessages === 1 ? 'message' : 'messages' }}.
        </p>

        <div
            v-if="conversations.length"
            class="divide-y overflow-hidden rounded-xl border bg-card shadow-card"
        >
            <Link
                v-for="conversation in conversations"
                :key="conversation.participant.id"
                :href="show(conversation.participant.id)"
                class="flex items-start justify-between gap-4 px-4 py-4 transition-colors hover:bg-muted/40"
            >
                <div class="min-w-0 space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-medium">
                            {{ conversation.participant.name }}
                        </span>
                        <StatusBadge
                            v-if="conversation.unread"
                            :label="`${conversation.unread} new`"
                            tone="info"
                        />
                    </div>
                    <p class="truncate text-sm text-muted-foreground">
                        <template v-if="conversation.latest.outgoing">
                            You:
                        </template>
                        {{ conversation.latest.body }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ conversation.participant.email }}
                    </p>
                </div>

                <span class="shrink-0 text-xs text-muted-foreground">
                    {{ conversation.latest.sent_at ?? '' }}
                </span>
            </Link>
        </div>

        <EmptyState
            v-else
            title="No messages yet."
            description="Start a conversation with the New message button."
        />
    </div>
</template>
