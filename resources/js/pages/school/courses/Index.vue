<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { create, edit, index } from '@/routes/school/courses';

type CourseRow = {
    id: number;
    name: string;
    short_name: string | null;
    classes_count: number;
};

type CoursePaginator = {
    data: CourseRow[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
};

const props = defineProps<{
    courses: CoursePaginator;
    search: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Courses',
                href: index(),
            },
        ],
    },
});

const page = usePage();

const canManage = computed(() =>
    page.props.auth.permissions.includes('curriculum.manage'),
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
    <Head title="Courses" />

    <h1 class="sr-only">Courses</h1>

    <div class="flex flex-col space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                variant="small"
                title="Courses"
                description="Subjects offered by the school and their classes."
            />

            <Button v-if="canManage" as-child>
                <Link :href="create()">New course</Link>
            </Button>
        </div>

        <form class="max-w-md" @submit.prevent="submitSearch">
            <div class="flex gap-2">
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Search by course name"
                    aria-label="Search courses"
                />
                <Button type="submit" variant="outline">Search</Button>
            </div>
        </form>

        <div class="overflow-x-auto rounded-lg border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/50">
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Course
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Short name
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Classes
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
                        v-for="course in courses.data"
                        :key="course.id"
                        class="border-b last:border-b-0"
                    >
                        <td class="px-4 py-3 font-medium">{{ course.name }}</td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ course.short_name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ course.classes_count }}
                        </td>
                        <td v-if="canManage" class="px-4 py-3 text-right">
                            <Link
                                :href="edit(course.id)"
                                class="text-sm font-medium underline-offset-4 hover:underline"
                            >
                                Manage
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="!courses.data.length">
                        <td
                            :colspan="canManage ? 4 : 3"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            {{
                                search
                                    ? 'No courses match your search.'
                                    : 'No courses yet.'
                            }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="courses.last_page > 1"
            class="flex flex-wrap items-center justify-between gap-3"
        >
            <p class="text-sm text-muted-foreground">
                Page {{ courses.current_page }} of {{ courses.last_page }} ·
                {{ courses.total }} courses
            </p>
            <div class="flex gap-2">
                <Button
                    v-if="courses.prev_page_url"
                    variant="outline"
                    size="sm"
                    as-child
                >
                    <Link :href="courses.prev_page_url">Previous</Link>
                </Button>
                <Button
                    v-if="courses.next_page_url"
                    variant="outline"
                    size="sm"
                    as-child
                >
                    <Link :href="courses.next_page_url">Next</Link>
                </Button>
            </div>
        </div>
    </div>
</template>
