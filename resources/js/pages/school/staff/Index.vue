<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { create, edit, index } from '@/routes/school/staff';

type StaffRow = {
    id: number;
    name: string;
    job_title: string | null;
    email: string | null;
    phone: string | null;
    account: string | null;
};

type StaffPaginator = {
    data: StaffRow[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
};

const props = defineProps<{
    staff: StaffPaginator;
    search: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Staff',
                href: index(),
            },
        ],
    },
});

const page = usePage();

const canManage = computed(() =>
    page.props.auth.permissions.includes('people.staff.manage'),
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
    <Head title="Staff" />

    <h1 class="sr-only">Staff</h1>

    <div class="flex flex-col space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                variant="small"
                title="Staff"
                description="Staff records for the school."
            />

            <Button v-if="canManage" as-child>
                <Link :href="create()">New staff member</Link>
            </Button>
        </div>

        <form class="max-w-md" @submit.prevent="submitSearch">
            <div class="flex gap-2">
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Search by name, job title or email"
                    aria-label="Search staff"
                />
                <Button type="submit" variant="outline">Search</Button>
            </div>
        </form>

        <div class="overflow-x-auto rounded-lg border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/50">
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Staff member
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Job title
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Phone
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Account
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
                        v-for="member in staff.data"
                        :key="member.id"
                        class="border-b last:border-b-0"
                    >
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ member.name }}</p>
                            <p
                                v-if="member.email"
                                class="text-muted-foreground"
                            >
                                {{ member.email }}
                            </p>
                        </td>
                        <td class="px-4 py-3">
                            <span v-if="member.job_title">
                                {{ member.job_title }}
                            </span>
                            <span v-else class="text-muted-foreground">
                                Not set
                            </span>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ member.phone ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <span v-if="member.account">
                                {{ member.account }}
                            </span>
                            <span v-else class="text-muted-foreground">
                                Not linked
                            </span>
                        </td>
                        <td v-if="canManage" class="px-4 py-3 text-right">
                            <Link
                                :href="edit(member.id)"
                                class="text-sm font-medium underline-offset-4 hover:underline"
                            >
                                Edit
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="!staff.data.length">
                        <td
                            :colspan="canManage ? 5 : 4"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            {{
                                search
                                    ? 'No staff members match your search.'
                                    : 'No staff members yet.'
                            }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="staff.last_page > 1"
            class="flex flex-wrap items-center justify-between gap-3"
        >
            <p class="text-sm text-muted-foreground">
                Page {{ staff.current_page }} of {{ staff.last_page }} ·
                {{ staff.total }} staff members
            </p>
            <div class="flex gap-2">
                <Button
                    v-if="staff.prev_page_url"
                    variant="outline"
                    size="sm"
                    as-child
                >
                    <Link :href="staff.prev_page_url">Previous</Link>
                </Button>
                <Button
                    v-if="staff.next_page_url"
                    variant="outline"
                    size="sm"
                    as-child
                >
                    <Link :href="staff.next_page_url">Next</Link>
                </Button>
            </div>
        </div>
    </div>
</template>
