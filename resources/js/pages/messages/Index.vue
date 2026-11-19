<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
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

    <h1 class="sr-only">Messages</h1>

    <div class="flex flex-col space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                variant="small"
                title="Messages"
                description="Direct conversations with staff, students, and families."
            />

            <Button as-child>
                <Link :href="create()">New message</Link>
            </Button>
        </div>

        <p
            v-if="unreadMessages"
            class="rounded-lg border bg-muted/40 px-4 py-3 text-sm"
        >
            You have {{ unreadMessages }} unread
            {{ unreadMessages === 1 ? 'message' : 'messages' }}.
        </p>

        <div v-if="conversations.length" class="divide-y rounded-lg border">
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
                        <Badge v-if="conversation.unread" variant="default">
                            {{ conversation.unread }} new
                        </Badge>
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

        <p
            v-else
            class="rounded-lg border px-4 py-8 text-center text-sm text-muted-foreground"
        >
            No messages yet. Start a conversation with the New message button.
        </p>
    </div>
</template>
