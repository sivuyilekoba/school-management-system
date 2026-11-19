<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import DataPagination from '@/components/DataPagination.vue';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import SearchForm from '@/components/SearchForm.vue';
import { Button } from '@/components/ui/button';
import { create, edit, index } from '@/routes/school/courses';
import type { DataTableColumn } from '@/types';

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

const isSearching = computed(() => props.search.length > 0);

const columns = computed<DataTableColumn[]>(() => [
    { key: 'name', label: 'Course' },
    { key: 'short_name', label: 'Short name' },
    { key: 'classes_count', label: 'Classes' },
    ...(canManage.value
        ? [
              {
                  key: 'actions',
                  label: '',
                  srLabel: 'Actions',
                  align: 'right' as const,
              },
          ]
        : []),
]);
</script>

<template>
    <Head title="Courses" />

    <div class="flex flex-col gap-6">
        <PageHeader
            eyebrow="Teaching"
            title="Courses"
            description="Subjects offered by the school and their classes."
        >
            <template #actions>
                <Button v-if="canManage" as-child>
                    <Link :href="create()">New course</Link>
                </Button>
            </template>
        </PageHeader>

        <SearchForm
            :url="index.url()"
            :value="props.search"
            placeholder="Search by course name"
            label="Search courses"
        />

        <DataTable
            :columns="columns"
            :rows="props.courses.data"
            row-key="id"
            caption="Courses"
            :empty-title="
                isSearching
                    ? 'No courses match your search.'
                    : 'No courses yet.'
            "
            :empty-description="
                isSearching
                    ? 'Try a different course name.'
                    : 'Add the first course to get started.'
            "
        >
            <template #row="{ row }">
                <td class="px-4 py-3 font-medium">{{ row.name }}</td>
                <td class="px-4 py-3 text-muted-foreground">
                    {{ row.short_name ?? '—' }}
                </td>
                <td class="px-4 py-3 text-muted-foreground">
                    {{ row.classes_count }}
                </td>

                <td v-if="canManage" class="px-4 py-3 text-right">
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="edit(row.id)">Manage</Link>
                    </Button>
                </td>
            </template>
        </DataTable>

        <DataPagination
            :current-page="props.courses.current_page"
            :last-page="props.courses.last_page"
            :total="props.courses.total"
            :prev-url="props.courses.prev_page_url"
            :next-url="props.courses.next_page_url"
            item-label="courses"
        />
    </div>
</template>
