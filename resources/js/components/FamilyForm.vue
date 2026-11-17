<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import FamilyController from '@/actions/App/Http/Controllers/School/FamilyController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type FamilyRecord = {
    id: number;
    name: string;
    contact_email: string | null;
    contact_phone: string | null;
};

const props = defineProps<{
    family?: FamilyRecord | null;
}>();

const isEditing = computed(() => Boolean(props.family));

const form = useForm({
    name: props.family?.name ?? '',
    contact_email: props.family?.contact_email ?? '',
    contact_phone: props.family?.contact_phone ?? '',
});

function submit(): void {
    if (props.family) {
        form.put(FamilyController.update.url(props.family.id), {
            preserveScroll: true,
        });

        return;
    }

    form.post(FamilyController.store.url(), { preserveScroll: true });
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <div class="grid gap-2">
            <Label for="name">Family name</Label>
            <Input
                id="name"
                v-model="form.name"
                required
                placeholder="e.g. The Okonkwo family"
            />
            <InputError :message="form.errors.name" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="contact_email">Contact email</Label>
                <Input
                    id="contact_email"
                    v-model="form.contact_email"
                    type="email"
                    autocomplete="email"
                />
                <InputError :message="form.errors.contact_email" />
            </div>

            <div class="grid gap-2">
                <Label for="contact_phone">Contact phone</Label>
                <Input
                    id="contact_phone"
                    v-model="form.contact_phone"
                    type="tel"
                    autocomplete="tel"
                />
                <InputError :message="form.errors.contact_phone" />
            </div>
        </div>

        <div class="flex items-center gap-4">
            <Button :disabled="form.processing" data-test="save-family">
                {{ isEditing ? 'Save changes' : 'Create family' }}
            </Button>
        </div>
    </form>
</template>
