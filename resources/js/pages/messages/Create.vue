<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { create, index, store as storeMessage } from '@/routes/messages';

type Recipient = {
    id: number;
    name: string;
    email: string;
    role: string | null;
};

const props = defineProps<{
    recipients: Recipient[];
    preselected: number | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Messages',
                href: index(),
            },
            {
                title: 'New message',
                href: create(),
            },
        ],
    },
});

const form = useForm({
    recipient_id: props.preselected,
    body: '',
});

const recipientId = computed<string>({
    get: () => (form.recipient_id === null ? '' : String(form.recipient_id)),
    set: (value) => {
        form.recipient_id = value === '' ? null : Number(value);
    },
});

function submit(): void {
    form.post(storeMessage.url(), { preserveScroll: true });
}
</script>

<template>
    <Head title="New message" />

    <div class="mx-auto flex max-w-2xl flex-col gap-6">
        <PageHeader
            eyebrow="Messages"
            title="New message"
            description="Send a direct message to a member of staff, a student, or a family."
        />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="recipient">To</Label>
                <Select v-model="recipientId">
                    <SelectTrigger id="recipient" class="w-full">
                        <SelectValue placeholder="Choose a recipient" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="recipient in recipients"
                            :key="recipient.id"
                            :value="String(recipient.id)"
                        >
                            {{ recipient.name }} ({{ recipient.email }})
                            <template v-if="recipient.role">
                                · {{ recipient.role }}
                            </template>
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="form.errors.recipient_id" />
            </div>

            <div class="grid gap-2">
                <Label for="body">Message</Label>
                <textarea
                    id="body"
                    v-model="form.body"
                    rows="8"
                    required
                    maxlength="5000"
                    class="block w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                    placeholder="Write your message."
                />
                <InputError :message="form.errors.body" />
            </div>

            <div class="flex items-center gap-3">
                <Button :disabled="form.processing" data-test="send-message">
                    Send message
                </Button>
                <Button variant="outline" as-child>
                    <a :href="index.url()">Cancel</a>
                </Button>
            </div>
        </form>
    </div>
</template>
