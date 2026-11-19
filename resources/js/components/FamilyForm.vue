<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import FamilyController from '@/actions/App/Http/Controllers/School/FamilyController';
import FormField from '@/components/FormField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

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
        <FormField
            label="Family name"
            field-id="name"
            :error="form.errors.name"
            required
        >
            <Input
                id="name"
                v-model="form.name"
                required
                placeholder="e.g. The Okonkwo family"
            />
        </FormField>

        <div class="grid gap-4 sm:grid-cols-2">
            <FormField
                label="Contact email"
                field-id="contact_email"
                :error="form.errors.contact_email"
            >
                <Input
                    id="contact_email"
                    v-model="form.contact_email"
                    type="email"
                    autocomplete="email"
                />
            </FormField>

            <FormField
                label="Contact phone"
                field-id="contact_phone"
                :error="form.errors.contact_phone"
            >
                <Input
                    id="contact_phone"
                    v-model="form.contact_phone"
                    type="tel"
                    autocomplete="tel"
                />
            </FormField>
        </div>

        <div class="flex items-center gap-4">
            <Button :disabled="form.processing" data-test="save-family">
                {{ isEditing ? 'Save changes' : 'Create family' }}
            </Button>
        </div>
    </form>
</template>
