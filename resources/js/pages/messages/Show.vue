<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { create, index, store as storeMessage } from '@/routes/messages';

type MessageRow = {
    id: number;
    body: string;
    outgoing: boolean;
    sent_at: string | null;
    read: boolean;
};

const props = defineProps<{
    participant: {
        id: number;
        name: string;
        email: string;
    };
    messages: MessageRow[];
    canReply: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Messages',
                href: index(),
            },
            {
                title: 'Conversation',
                href: index(),
            },
        ],
    },
});

const form = useForm({
    recipient_id: props.participant.id,
    body: '',
});

function submit(): void {
    form.post(storeMessage.url(), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('body');
        },
    });
}
</script>

<template>
    <Head :title="`Conversation with ${participant.name}`" />

    <h1 class="sr-only">Conversation</h1>

    <div class="flex max-w-3xl flex-col space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                variant="small"
                :title="participant.name"
                :description="participant.email"
            />

            <Button variant="outline" as-child>
                <Link :href="index()">Back to messages</Link>
            </Button>
        </div>

        <div class="space-y-3">
            <div
                v-for="message in messages"
                :key="message.id"
                class="flex"
                :class="message.outgoing ? 'justify-end' : 'justify-start'"
            >
                <div
                    class="max-w-[85%] rounded-lg px-4 py-3 text-sm whitespace-pre-line"
                    :class="
                        message.outgoing
                            ? 'bg-primary text-primary-foreground'
                            : 'border bg-muted/40'
                    "
                >
                    <p>{{ message.body }}</p>
                    <p
                        class="mt-2 text-xs"
                        :class="
                            message.outgoing
                                ? 'text-primary-foreground/70'
                                : 'text-muted-foreground'
                        "
                    >
                        {{ message.sent_at ?? '' }}
                        <template v-if="message.outgoing && message.read">
                            · Read
                        </template>
                    </p>
                </div>
            </div>

            <p
                v-if="!messages.length"
                class="rounded-lg border px-4 py-8 text-center text-sm text-muted-foreground"
            >
                No messages in this conversation yet.
            </p>
        </div>

        <form
            v-if="canReply"
            class="space-y-3 rounded-lg border p-4"
            @submit.prevent="submit"
        >
            <div class="grid gap-2">
                <label for="reply" class="text-sm font-medium">Reply</label>
                <textarea
                    id="reply"
                    v-model="form.body"
                    rows="4"
                    required
                    maxlength="5000"
                    class="block w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                    placeholder="Write a reply."
                />
                <InputError :message="form.errors.body" />
            </div>

            <Button :disabled="form.processing" data-test="send-reply">
                Send reply
            </Button>
        </form>

        <p
            v-else
            class="rounded-lg border px-4 py-4 text-sm text-muted-foreground"
        >
            You can no longer reply to this conversation. Start a new message
            with someone you are allowed to contact.
        </p>

        <p class="text-sm text-muted-foreground">
            <Link
                :href="create()"
                class="font-medium underline-offset-4 hover:underline"
            >
                Start another conversation
            </Link>
        </p>
    </div>
</template>
