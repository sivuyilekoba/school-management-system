<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { create, edit, index } from '@/routes/school/users';

type UserRow = {
    id: number;
    name: string;
    email: string;
    roles: string[];
};

type UserPaginator = {
    data: UserRow[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
};

const props = defineProps<{
    users: UserPaginator;
    search: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Users',
                href: index(),
            },
        ],
    },
});

const page = usePage();

const canManage = computed(() =>
    page.props.auth.permissions.includes('users.manage'),
);

const search = ref(props.search);

function submitSearch(): void {
    router.get(
        index.url(),
        { q: search.value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}
</script>

<template>
    <Head title="Users" />

    <h1 class="sr-only">Users</h1>

    <div class="flex flex-col space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                variant="small"
                title="Users"
                description="Accounts that can sign in to the school system."
            />

            <Button v-if="canManage" as-child>
                <Link :href="create()">New user</Link>
            </Button>
        </div>

        <form class="max-w-md" @submit.prevent="submitSearch">
            <div class="flex gap-2">
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Search by name or email"
                    aria-label="Search users"
                />
                <Button type="submit" variant="outline">Search</Button>
            </div>
        </form>

        <div class="overflow-x-auto rounded-lg border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/50">
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Name
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Email
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Roles
                        </th>
                        <th
                            v-if="canManage"
                            scope="col"
                            class="px-4 py-3 text-right font-medium"
                        >
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="user in users.data"
                        :key="user.id"
                        class="border-b last:border-b-0"
                    >
                        <td class="px-4 py-3 font-medium">{{ user.name }}</td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ user.email }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                <Badge
                                    v-for="role in user.roles"
                                    :key="role"
                                    variant="secondary"
                                >
                                    {{ role }}
                                </Badge>
                                <span
                                    v-if="!user.roles.length"
                                    class="text-muted-foreground"
                                >
                                    No role
                                </span>
                            </div>
                        </td>
                        <td v-if="canManage" class="px-4 py-3 text-right">
                            <Link
                                :href="edit(user.id)"
                                class="text-sm font-medium underline-offset-4 hover:underline"
                            >
                                Edit
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="!users.data.length">
                        <td
                            :colspan="canManage ? 4 : 3"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            {{
                                search
                                    ? 'No users match your search.'
                                    : 'No users yet.'
                            }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="users.last_page > 1"
            class="flex flex-wrap items-center justify-between gap-3"
        >
            <p class="text-sm text-muted-foreground">
                Page {{ users.current_page }} of {{ users.last_page }} ·
                {{ users.total }} users
            </p>
            <div class="flex gap-2">
                <Button
                    v-if="users.prev_page_url"
                    variant="outline"
                    size="sm"
                    as-child
                >
                    <Link :href="users.prev_page_url">Previous</Link>
                </Button>
                <Button
                    v-if="users.next_page_url"
                    variant="outline"
                    size="sm"
                    as-child
                >
                    <Link :href="users.next_page_url">Next</Link>
                </Button>
            </div>
        </div>
    </div>
</template>
