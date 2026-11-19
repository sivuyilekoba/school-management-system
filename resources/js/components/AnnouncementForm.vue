<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AnnouncementController from '@/actions/App/Http/Controllers/School/AnnouncementController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export type AudienceOption = {
    value: string;
    label: string;
};

export type AnnouncementRecord = {
    id: number;
    title: string;
    body: string;
    audience: string;
    published_at: string | null;
    expires_at: string | null;
};

const props = defineProps<{
    audiences: AudienceOption[];
    announcement?: AnnouncementRecord | null;
    defaults?: {
        published_at: string | null;
    };
}>();

const isEditing = computed(() => Boolean(props.announcement));

const form = useForm({
    title: props.announcement?.title ?? '',
    body: props.announcement?.body ?? '',
    audience: props.announcement?.audience ?? 'everyone',
    published_at:
        props.announcement?.published_at ?? props.defaults?.published_at ?? '',
    expires_at: props.announcement?.expires_at ?? '',
});

function submit(): void {
    if (props.announcement) {
        form.put(AnnouncementController.update.url(props.announcement.id), {
            preserveScroll: true,
        });

        return;
    }

    form.post(AnnouncementController.store.url(), { preserveScroll: true });
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <div class="grid gap-2">
            <Label for="title">Title</Label>
            <Input
                id="title"
                v-model="form.title"
                required
                placeholder="e.g. Sports day moved to Friday"
            />
            <InputError :message="form.errors.title" />
        </div>

        <div class="grid gap-2">
            <Label for="body">Message</Label>
            <textarea
                id="body"
                v-model="form.body"
                rows="8"
                required
                maxlength="10000"
                class="block w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                placeholder="Write the announcement for your audience."
            />
            <InputError :message="form.errors.body" />
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="grid gap-2">
                <Label for="audience">Audience</Label>
                <Select v-model="form.audience">
                    <SelectTrigger id="audience" class="w-full">
                        <SelectValue placeholder="Select an audience" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="audience in audiences"
                            :key="audience.value"
                            :value="audience.value"
                        >
                            {{ audience.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="form.errors.audience" />
            </div>

            <div class="grid gap-2">
                <Label for="published_at">Publish at</Label>
                <Input
                    id="published_at"
                    v-model="form.published_at"
                    type="datetime-local"
                />
                <p class="text-xs text-muted-foreground">
                    Leave empty to keep this as a draft.
                </p>
                <InputError :message="form.errors.published_at" />
            </div>

            <div class="grid gap-2">
                <Label for="expires_at">Expires at</Label>
                <Input
                    id="expires_at"
                    v-model="form.expires_at"
                    type="datetime-local"
                />
                <p class="text-xs text-muted-foreground">
                    Optional. The announcement stays visible after expiring.
                </p>
                <InputError :message="form.errors.expires_at" />
            </div>
        </div>

        <div class="flex items-center gap-4">
            <Button :disabled="form.processing" data-test="save-announcement">
                {{ isEditing ? 'Save changes' : 'Create announcement' }}
            </Button>
        </div>
    </form>
</template>
