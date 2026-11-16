<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import UserController from '@/actions/App/Http/Controllers/School/UserController';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type UserAccount = {
    id: number;
    name: string;
    email: string;
    roles: string[];
};

const props = defineProps<{
    roles: string[];
    user?: UserAccount | null;
}>();

const isEditing = computed(() => Boolean(props.user));

const form = useForm({
    name: props.user?.name ?? '',
    email: props.user?.email ?? '',
    password: '',
    password_confirmation: '',
    roles: props.user?.roles ?? [],
});

function hasRole(role: string): boolean {
    return form.roles.includes(role);
}

function toggleRole(role: string): void {
    form.roles = hasRole(role)
        ? form.roles.filter((name) => name !== role)
        : [...form.roles, role];
}

function submit(): void {
    if (props.user) {
        form.put(UserController.update.url(props.user.id), {
            preserveScroll: true,
        });

        return;
    }

    form.post(UserController.store.url(), { preserveScroll: true });
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <div class="grid gap-2">
            <Label for="name">Name</Label>
            <Input
                id="name"
                v-model="form.name"
                required
                autocomplete="name"
                placeholder="Full name"
            />
            <InputError :message="form.errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="email">Email address</Label>
            <Input
                id="email"
                v-model="form.email"
                type="email"
                required
                autocomplete="email"
                placeholder="name@school.example"
            />
            <InputError :message="form.errors.email" />
        </div>

        <template v-if="!isEditing">
            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <PasswordInput
                    id="password"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />
                <p class="text-sm text-muted-foreground">
                    At least 8 characters. Share it with the user after saving.
                </p>
                <InputError :message="form.errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm password</Label>
                <PasswordInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />
            </div>
        </template>

        <fieldset class="grid gap-2">
            <legend class="mb-2 text-sm font-medium">Roles</legend>
            <div class="grid gap-2 sm:grid-cols-2">
                <div
                    v-for="role in roles"
                    :key="role"
                    class="flex items-center gap-2"
                >
                    <Checkbox
                        :id="`role-${role}`"
                        :model-value="hasRole(role)"
                        :aria-label="`Assign the ${role} role`"
                        @update:model-value="toggleRole(role)"
                    />
                    <Label :for="`role-${role}`" class="font-normal">
                        {{ role }}
                    </Label>
                </div>
            </div>
            <InputError :message="form.errors.roles" />
        </fieldset>

        <p v-if="isEditing" class="text-sm text-muted-foreground">
            The password is unchanged. Users can reset it from the sign-in page.
        </p>

        <div class="flex items-center gap-4">
            <Button :disabled="form.processing" data-test="save-user">
                {{ isEditing ? 'Save changes' : 'Create user' }}
            </Button>
        </div>
    </form>
</template>
