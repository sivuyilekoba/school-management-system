<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { create, edit, index } from '@/routes/school/students';

type StudentRow = {
    id: number;
    name: string;
    gender: string;
    date_of_birth: string | null;
    email: string | null;
    family: string | null;
    account: string | null;
};

type StudentPaginator = {
    data: StudentRow[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
};

const props = defineProps<{
    students: StudentPaginator;
    search: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Students',
                href: index(),
            },
        ],
    },
});

const page = usePage();

const canManage = computed(() =>
    page.props.auth.permissions.includes('people.students.manage'),
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
    <Head title="Students" />

    <h1 class="sr-only">Students</h1>

    <div class="flex flex-col space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                variant="small"
                title="Students"
                description="Student records for the school."
            />

            <Button v-if="canManage" as-child>
                <Link :href="create()">New student</Link>
            </Button>
        </div>

        <form class="max-w-md" @submit.prevent="submitSearch">
            <div class="flex gap-2">
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Search by name or email"
                    aria-label="Search students"
                />
                <Button type="submit" variant="outline">Search</Button>
            </div>
        </form>

        <div class="overflow-x-auto rounded-lg border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/50">
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Student
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Family
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Date of birth
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
                        v-for="student in students.data"
                        :key="student.id"
                        class="border-b last:border-b-0"
                    >
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ student.name }}</p>
                            <p
                                v-if="student.email"
                                class="text-muted-foreground"
                            >
                                {{ student.email }}
                            </p>
                        </td>
                        <td class="px-4 py-3">
                            <span v-if="student.family">{{
                                student.family
                            }}</span>
                            <span v-else class="text-muted-foreground">
                                No family
                            </span>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ student.date_of_birth ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <span v-if="student.account">
                                {{ student.account }}
                            </span>
                            <span v-else class="text-muted-foreground">
                                Not linked
                            </span>
                        </td>
                        <td v-if="canManage" class="px-4 py-3 text-right">
                            <Link
                                :href="edit(student.id)"
                                class="text-sm font-medium underline-offset-4 hover:underline"
                            >
                                Edit
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="!students.data.length">
                        <td
                            :colspan="canManage ? 5 : 4"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            {{
                                search
                                    ? 'No students match your search.'
                                    : 'No students yet.'
                            }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="students.last_page > 1"
            class="flex flex-wrap items-center justify-between gap-3"
        >
            <p class="text-sm text-muted-foreground">
                Page {{ students.current_page }} of {{ students.last_page }} ·
                {{ students.total }} students
            </p>
            <div class="flex gap-2">
                <Button
                    v-if="students.prev_page_url"
                    variant="outline"
                    size="sm"
                    as-child
                >
                    <Link :href="students.prev_page_url">Previous</Link>
                </Button>
                <Button
                    v-if="students.next_page_url"
                    variant="outline"
                    size="sm"
                    as-child
                >
                    <Link :href="students.next_page_url">Next</Link>
                </Button>
            </div>
        </div>
    </div>
</template>
