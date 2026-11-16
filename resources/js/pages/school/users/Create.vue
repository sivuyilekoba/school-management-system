<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import UserAccountForm from '@/components/UserAccountForm.vue';
import { create, index } from '@/routes/school/users';

defineProps<{
    roles: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Users',
                href: index(),
            },
            {
                title: 'New user',
                href: create(),
            },
        ],
    },
});
</script>

<template>
    <Head title="New user" />

    <h1 class="sr-only">New user</h1>

    <div class="flex max-w-2xl flex-col space-y-6">
        <Heading
            variant="small"
            title="New user"
            description="Create an account that can sign in to the school system."
        />

        <UserAccountForm :roles="roles" />
    </div>
</template>
