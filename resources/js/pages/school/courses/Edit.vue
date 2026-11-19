<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import ClassEnrolmentController from '@/actions/App/Http/Controllers/School/ClassEnrolmentController';
import CourseForm from '@/components/CourseForm.vue';
import type { CourseRecord } from '@/components/CourseForm.vue';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import {
    create as createClass,
    edit as editClass,
} from '@/routes/school/classes';
import { index } from '@/routes/school/courses';
import type { DataTableColumn } from '@/types';

type ClassRow = {
    id: number;
    name: string;
    teacher: string | null;
    students_count: number;
};

defineProps<{
    course: CourseRecord;
    classes: ClassRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Courses',
                href: index(),
            },
            {
                title: 'Edit course',
                href: index(),
            },
        ],
    },
});

const columns: DataTableColumn[] = [
    { key: 'name', label: 'Class' },
    { key: 'teacher', label: 'Teacher' },
    { key: 'students_count', label: 'Students' },
    {
        key: 'actions',
        label: '',
        srLabel: 'Actions',
        align: 'right',
    },
];
</script>

<template>
    <Head :title="`Edit ${course.name}`" />

    <div class="flex max-w-3xl flex-col gap-8">
        <div class="flex flex-col gap-6">
            <PageHeader
                eyebrow="Course"
                :title="`Edit ${course.name}`"
                description="Update the course details."
            />

            <CourseForm :course="course" />
        </div>

        <div class="flex flex-col gap-4">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div class="space-y-1">
                    <h2 class="text-lg font-semibold">Classes</h2>
                    <p class="text-sm text-muted-foreground">
                        Teaching groups offered under this course.
                    </p>
                </div>

                <Button as-child variant="outline">
                    <Link :href="createClass(course.id)">Add class</Link>
                </Button>
            </div>

            <DataTable
                :columns="columns"
                :rows="classes"
                row-key="id"
                caption="Classes in this course"
                empty-title="No classes yet."
                empty-description="Add a class to start enrolling students."
            >
                <template #row="{ row }">
                    <td class="px-4 py-3 font-medium">{{ row.name }}</td>
                    <td class="px-4 py-3">
                        <span v-if="row.teacher">{{ row.teacher }}</span>
                        <span v-else class="text-muted-foreground">
                            No teacher
                        </span>
                    </td>
                    <td class="px-4 py-3 text-muted-foreground">
                        {{ row.students_count }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex justify-end gap-3">
                            <Link
                                :href="
                                    ClassEnrolmentController.index.url(row.id)
                                "
                                class="text-sm font-medium underline-offset-4 hover:underline"
                            >
                                Students
                            </Link>
                            <Link
                                :href="editClass(row.id)"
                                class="text-sm font-medium underline-offset-4 hover:underline"
                            >
                                Edit
                            </Link>
                        </div>
                    </td>
                </template>
            </DataTable>
        </div>
    </div>
</template>
