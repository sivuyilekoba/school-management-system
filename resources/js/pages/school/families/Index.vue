<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { create, edit, index } from '@/routes/school/families';

type FamilyRow = {
    id: number;
    name: string;
    contact_email: string | null;
    contact_phone: string | null;
    students_count: number;
};

type FamilyPaginator = {
    data: FamilyRow[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
};

const props = defineProps<{
    families: FamilyPaginator;
    search: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Families',
                href: index(),
            },
        ],
    },
});

const page = usePage();

const canManage = computed(() =>
    page.props.auth.permissions.includes('people.families.manage'),
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
    <Head title="Families" />

    <h1 class="sr-only">Families</h1>

    <div class="flex flex-col space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                variant="small"
                title="Families"
                description="Family groups that students belong to."
            />

            <Button v-if="canManage" as-child>
                <Link :href="create()">New family</Link>
            </Button>
        </div>

        <form class="max-w-md" @submit.prevent="submitSearch">
            <div class="flex gap-2">
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Search by name or contact email"
                    aria-label="Search families"
                />
                <Button type="submit" variant="outline">Search</Button>
            </div>
        </form>

        <div class="overflow-x-auto rounded-lg border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/50">
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Family
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Students
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Contact email
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Contact phone
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
                        v-for="family in families.data"
                        :key="family.id"
                        class="border-b last:border-b-0"
                    >
                        <td class="px-4 py-3 font-medium">{{ family.name }}</td>
                        <td class="px-4 py-3">{{ family.students_count }}</td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ family.contact_email ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ family.contact_phone ?? '—' }}
                        </td>
                        <td v-if="canManage" class="px-4 py-3 text-right">
                            <Link
                                :href="edit(family.id)"
                                class="text-sm font-medium underline-offset-4 hover:underline"
                            >
                                Edit
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="!families.data.length">
                        <td
                            :colspan="canManage ? 5 : 4"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            {{
                                search
                                    ? 'No families match your search.'
                                    : 'No families yet.'
                            }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="families.last_page > 1"
            class="flex flex-wrap items-center justify-between gap-3"
        >
            <p class="text-sm text-muted-foreground">
                Page {{ families.current_page }} of {{ families.last_page }} ·
                {{ families.total }} families
            </p>
            <div class="flex gap-2">
                <Button
                    v-if="families.prev_page_url"
                    variant="outline"
                    size="sm"
                    as-child
                >
                    <Link :href="families.prev_page_url">Previous</Link>
                </Button>
                <Button
                    v-if="families.next_page_url"
                    variant="outline"
                    size="sm"
                    as-child
                >
                    <Link :href="families.next_page_url">Next</Link>
                </Button>
            </div>
        </div>
    </div>
</template>
