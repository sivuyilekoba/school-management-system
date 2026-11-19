<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type LoginAccount = {
    exists: boolean;
    email: string | null;
    name: string | null;
};

const props = defineProps<{
    action: string;
    account: LoginAccount;
    defaultEmail?: string | null;
}>();

const form = useForm({
    email: props.account.email ?? props.defaultEmail ?? '',
    password: '',
    password_confirmation: '',
});

function submit(): void {
    form.post(props.action, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('password', 'password_confirmation');
        },
    });
}
</script>

<template>
    <section class="max-w-2xl space-y-4">
        <div class="space-y-1">
            <h2 class="text-lg font-semibold">Portal login</h2>
            <p class="text-sm text-muted-foreground">
                <template v-if="account.exists">
                    This record can sign in to the portal as
                    {{ account.email }}.
                </template>
                <template v-else>
                    Create a login so this record can read announcements, send
                    messages, and view published report cards.
                </template>
            </p>
        </div>

        <div v-if="account.exists" class="rounded-lg border p-4 text-sm">
            <p class="font-medium">{{ account.name }}</p>
            <p class="text-muted-foreground">{{ account.email }}</p>
        </div>

        <form v-else class="max-w-md space-y-4" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="login-email">Email address</Label>
                <Input
                    id="login-email"
                    v-model="form.email"
                    type="email"
                    required
                    autocomplete="email"
                />
                <InputError :message="form.errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="login-password">Password</Label>
                <Input
                    id="login-password"
                    v-model="form.password"
                    type="password"
                    required
                    autocomplete="new-password"
                />
                <InputError :message="form.errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="login-password-confirmation">
                    Confirm password
                </Label>
                <Input
                    id="login-password-confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    required
                    autocomplete="new-password"
                />
            </div>

            <Button :disabled="form.processing" data-test="create-login">
                Create login
            </Button>
        </form>
    </section>
</template>
