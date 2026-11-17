<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import StaffController from '@/actions/App/Http/Controllers/School/StaffController';
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
            <div class="grid gap-2">
                <Label for="first_name">First name</Label>
                <Input
                    id="first_name"
                    v-model="form.first_name"
                    required
                    autocomplete="given-name"
                />
                <InputError :message="form.errors.first_name" />
            </div>

            <div class="grid gap-2">
                <Label for="surname">Surname</Label>
                <Input
                    id="surname"
                    v-model="form.surname"
                    required
                    autocomplete="family-name"
                />
                <InputError :message="form.errors.surname" />
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="preferred_name">Preferred name</Label>
                <Input
                    id="preferred_name"
                    v-model="form.preferred_name"
                    autocomplete="nickname"
                />
                <p class="text-sm text-muted-foreground">
                    Optional. Shown instead of the first name in lists.
                </p>
                <InputError :message="form.errors.preferred_name" />
            </div>

            <div class="grid gap-2">
                <Label for="job_title">Job title</Label>
                <Input id="job_title" v-model="form.job_title" />
                <InputError :message="form.errors.job_title" />
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                />
                <InputError :message="form.errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="phone">Phone</Label>
                <Input
                    id="phone"
                    v-model="form.phone"
                    type="tel"
                    autocomplete="tel"
                />
                <InputError :message="form.errors.phone" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="user_id">Linked account</Label>
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
            <InputError :message="form.errors.user_id" />
        </div>

        <div class="flex items-center gap-4">
            <Button :disabled="form.processing" data-test="save-staff">
                {{ isEditing ? 'Save changes' : 'Create staff member' }}
            </Button>
        </div>
    </form>
</template>
