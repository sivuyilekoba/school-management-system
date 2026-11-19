<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import FormField from '@/components/FormField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

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
            <FormField
                label="Email address"
                field-id="login-email"
                :error="form.errors.email"
                required
            >
                <Input
                    id="login-email"
                    v-model="form.email"
                    type="email"
                    required
                    autocomplete="email"
                />
            </FormField>

            <FormField
                label="Password"
                field-id="login-password"
                :error="form.errors.password"
                required
            >
                <Input
                    id="login-password"
                    v-model="form.password"
                    type="password"
                    required
                    autocomplete="new-password"
                />
            </FormField>

            <FormField
                label="Confirm password"
                field-id="login-password-confirmation"
                required
            >
                <Input
                    id="login-password-confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    required
                    autocomplete="new-password"
                />
            </FormField>

            <Button :disabled="form.processing" data-test="create-login">
                Create login
            </Button>
        </form>
    </section>
</template>
