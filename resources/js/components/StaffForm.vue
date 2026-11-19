<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import StaffController from '@/actions/App/Http/Controllers/School/StaffController';
import FormField from '@/components/FormField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export type AccountOption = {
    id: number;
    name: string;
    email: string;
};

export type StaffRecord = {
    id: number;
    first_name: string;
    surname: string;
    preferred_name: string | null;
    job_title: string | null;
    email: string | null;
    phone: string | null;
    user_id: number | null;
};

const props = defineProps<{
    accounts: AccountOption[];
    staff?: StaffRecord | null;
}>();

const isEditing = computed(() => Boolean(props.staff));

const form = useForm({
    first_name: props.staff?.first_name ?? '',
    surname: props.staff?.surname ?? '',
    preferred_name: props.staff?.preferred_name ?? '',
    job_title: props.staff?.job_title ?? '',
    email: props.staff?.email ?? '',
    phone: props.staff?.phone ?? '',
    user_id: props.staff?.user_id ?? null,
});

const accountId = computed<string>({
    get: () => (form.user_id === null ? 'none' : String(form.user_id)),
    set: (value) => {
        form.user_id = value === 'none' ? null : Number(value);
    },
});

function submit(): void {
    if (props.staff) {
        form.put(StaffController.update.url(props.staff.id), {
            preserveScroll: true,
        });

        return;
    }

    form.post(StaffController.store.url(), { preserveScroll: true });
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <div class="grid gap-4 sm:grid-cols-2">
            <FormField
                label="First name"
                field-id="first_name"
                :error="form.errors.first_name"
                required
            >
                <Input
                    id="first_name"
                    v-model="form.first_name"
                    required
                    autocomplete="given-name"
                />
            </FormField>

            <FormField
                label="Surname"
                field-id="surname"
                :error="form.errors.surname"
                required
            >
                <Input
                    id="surname"
                    v-model="form.surname"
                    required
                    autocomplete="family-name"
                />
            </FormField>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <FormField
                label="Preferred name"
                field-id="preferred_name"
                :error="form.errors.preferred_name"
                hint="Optional. Shown instead of the first name in lists."
            >
                <Input
                    id="preferred_name"
                    v-model="form.preferred_name"
                    autocomplete="nickname"
                />
            </FormField>

            <FormField
                label="Job title"
                field-id="job_title"
                :error="form.errors.job_title"
            >
                <Input id="job_title" v-model="form.job_title" />
            </FormField>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <FormField
                label="Email address"
                field-id="email"
                :error="form.errors.email"
            >
                <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                />
            </FormField>

            <FormField
                label="Phone"
                field-id="phone"
                :error="form.errors.phone"
            >
                <Input
                    id="phone"
                    v-model="form.phone"
                    type="tel"
                    autocomplete="tel"
                />
            </FormField>
        </div>

        <FormField
            label="Linked account"
            field-id="user_id"
            :error="form.errors.user_id"
        >
            <Select v-model="accountId">
                <SelectTrigger id="user_id" class="w-full">
                    <SelectValue placeholder="No linked account" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="none">No linked account</SelectItem>
                    <SelectItem
                        v-for="account in accounts"
                        :key="account.id"
                        :value="String(account.id)"
                    >
                        {{ account.name }} ({{ account.email }})
                    </SelectItem>
                </SelectContent>
            </Select>
        </FormField>

        <div class="flex items-center gap-4">
            <Button :disabled="form.processing" data-test="save-staff">
                {{ isEditing ? 'Save changes' : 'Create staff member' }}
            </Button>
        </div>
    </form>
</template>
