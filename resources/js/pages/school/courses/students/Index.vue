<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ClassEnrolmentController from '@/actions/App/Http/Controllers/School/ClassEnrolmentController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { edit as editCourse, index as courses } from '@/routes/school/courses';

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

const search = ref(props.search);

function submitSearch(): void {
    router.get(
        ClassEnrolmentController.index.url(props.courseClass.id),
        { q: search.value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

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

    <h1 class="sr-only">Class students</h1>

    <div class="flex flex-col space-y-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                variant="small"
                :title="`Students in ${courseClass.name}`"
                description="Students enrolled in this class."
            />

            <Link
                :href="editCourse(courseClass.course_id)"
                class="text-sm font-medium underline-offset-4 hover:underline"
            >
                Back to course
            </Link>
        </div>

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
                        <th
                            v-if="canEnrol"
                            scope="col"
                            class="px-4 py-3 text-right font-medium"
                        >
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="student in enrolled"
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
                        <td v-if="canEnrol" class="px-4 py-3 text-right">
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click="remove(student)"
                            >
                                Remove
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="!enrolled.length">
                        <td
                            :colspan="canEnrol ? 3 : 2"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No students enrolled yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="canEnrol" class="flex flex-col space-y-4">
            <Heading
                variant="small"
                title="Add students"
                description="Search for students who are not yet enrolled in this class."
            />

            <form class="max-w-md" @submit.prevent="submitSearch">
                <div class="flex gap-2">
                    <Input
                        v-model="search"
                        type="search"
                        placeholder="Search by name or email"
                        aria-label="Search students to enrol"
                    />
                    <Button type="submit" variant="outline">Search</Button>
                </div>
            </form>

            <div class="overflow-x-auto rounded-lg border">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-muted/50">
                            <th
                                scope="col"
                                class="px-4 py-3 text-left font-medium"
                            >
                                Student
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 text-left font-medium"
                            >
                                Family
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 text-right font-medium"
                            >
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="student in available"
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
                            <td class="px-4 py-3 text-right">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="enrol(student)"
                                >
                                    Enrol
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="!available.length">
                            <td
                                colspan="3"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                {{
                                    search
                                        ? 'No available students match your search.'
                                        : 'No students left to enrol.'
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
