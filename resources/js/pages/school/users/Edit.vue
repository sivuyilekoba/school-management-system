<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import UserAccountForm from '@/components/UserAccountForm.vue';
import { index } from '@/routes/school/users';

type UserAccount = {
    id: number;
    name: string;
    email: string;
    roles: string[];
};

defineProps<{
    user: UserAccount;
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
                title: 'Edit user',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit ${user.name}`" />

    <div class="flex max-w-2xl flex-col space-y-6">
        <PageHeader
            eyebrow="Users"
            :title="`Edit ${user.name}`"
            description="Update the account details and roles for this user."
        />

        <UserAccountForm :roles="roles" :user="user" />
    </div>
</template>
