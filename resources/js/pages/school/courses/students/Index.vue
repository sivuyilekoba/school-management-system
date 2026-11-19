<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ClassEnrolmentController from '@/actions/App/Http/Controllers/School/ClassEnrolmentController';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import SearchForm from '@/components/SearchForm.vue';
import { Button } from '@/components/ui/button';
import { edit as editCourse, index as courses } from '@/routes/school/courses';
import type { DataTableColumn } from '@/types';

type StudentRow = {
    id: number;
    name: string;
    email: string | null;
    family: string | null;
};

const props = defineProps<{
    courseClass: {
        id: number;
        course_id: number;
        name: string;
    };
    enrolled: StudentRow[];
    available: StudentRow[];
    search: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Courses',
                href: courses(),
            },
            {
                title: 'Edit course',
                href: courses(),
            },
            {
                title: 'Class students',
                href: courses(),
            },
        ],
    },
});

const page = usePage();

const canEnrol = computed(() =>
    page.props.auth.permissions.includes('curriculum.enrolment.manage'),
);

const enrolledColumns = computed<DataTableColumn[]>(() => [
    { key: 'name', label: 'Student' },
    { key: 'family', label: 'Family' },
    ...(canEnrol.value
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

const availableColumns = computed<DataTableColumn[]>(() => [
    { key: 'name', label: 'Student' },
    { key: 'family', label: 'Family' },
    {
        key: 'actions',
        label: '',
        srLabel: 'Actions',
        align: 'right' as const,
    },
]);

function enrol(student: StudentRow): void {
    router.post(
        ClassEnrolmentController.store.url(props.courseClass.id),
        { student_id: student.id },
        { preserveScroll: true },
    );
}

function remove(student: StudentRow): void {
    if (!confirm(`Remove ${student.name} from this class?`)) {
        return;
    }

    router.delete(
        ClassEnrolmentController.destroy.url({
            courseClass: props.courseClass.id,
            student: student.id,
        }),
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="`Students in ${courseClass.name}`" />

    <div class="flex flex-col gap-8">
        <PageHeader
            eyebrow="Class students"
            :title="`Students in ${courseClass.name}`"
            description="Students enrolled in this class."
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <Link :href="editCourse(courseClass.course_id)">
                        Back to course
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <DataTable
            :columns="enrolledColumns"
            :rows="enrolled"
            row-key="id"
            caption="Enrolled students"
            empty-title="No students enrolled yet."
            empty-description="Search below to enrol students in this class."
        >
            <template #row="{ row }">
                <td class="px-4 py-3">
                    <p class="font-medium">{{ row.name }}</p>
                    <p v-if="row.email" class="text-muted-foreground">
                        {{ row.email }}
                    </p>
                </td>
                <td class="px-4 py-3">
                    <span v-if="row.family">{{ row.family }}</span>
                    <span v-else class="text-muted-foreground">No family</span>
                </td>
                <td v-if="canEnrol" class="px-4 py-3 text-right">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="remove(row)"
                    >
                        Remove
                    </Button>
                </td>
            </template>
        </DataTable>

        <div v-if="canEnrol" class="flex flex-col gap-4">
            <div class="space-y-1">
                <h2 class="text-lg font-semibold">Add students</h2>
                <p class="text-sm text-muted-foreground">
                    Search for students who are not yet enrolled in this class.
                </p>
            </div>

            <SearchForm
                :url="ClassEnrolmentController.index.url(courseClass.id)"
                :value="props.search"
                placeholder="Search by name or email"
                label="Search students to enrol"
            />

            <DataTable
                :columns="availableColumns"
                :rows="available"
                row-key="id"
                caption="Students available to enrol"
                :empty-title="
                    props.search
                        ? 'No available students match your search.'
                        : 'No students left to enrol.'
                "
                :empty-description="
                    props.search ? 'Try a different name or email.' : undefined
                "
            >
                <template #row="{ row }">
                    <td class="px-4 py-3">
                        <p class="font-medium">{{ row.name }}</p>
                        <p v-if="row.email" class="text-muted-foreground">
                            {{ row.email }}
                        </p>
                    </td>
                    <td class="px-4 py-3">
                        <span v-if="row.family">{{ row.family }}</span>
                        <span v-else class="text-muted-foreground">
                            No family
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="enrol(row)"
                        >
                            Enrol
                        </Button>
                    </td>
                </template>
            </DataTable>
        </div>
    </div>
</template>
