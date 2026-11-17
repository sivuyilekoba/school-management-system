<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CourseForm from '@/components/CourseForm.vue';
import type { CourseRecord } from '@/components/CourseForm.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    create as createClass,
    edit as editClass,
} from '@/routes/school/classes';
import { index } from '@/routes/school/courses';

type ClassRow = {
    id: number;
    name: string;
    teacher: string | null;
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
</script>

<template>
    <Head :title="`Edit ${course.name}`" />

    <h1 class="sr-only">Edit course</h1>

    <div class="flex max-w-3xl flex-col space-y-8">
        <div class="flex flex-col space-y-6">
            <Heading
                variant="small"
                :title="`Edit ${course.name}`"
                description="Update the course details."
            />

            <CourseForm :course="course" />
        </div>

        <div class="flex flex-col space-y-4">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <Heading
                    variant="small"
                    title="Classes"
                    description="Teaching groups offered under this course."
                />

                <Button as-child variant="outline">
                    <Link :href="createClass(course.id)">Add class</Link>
                </Button>
            </div>

            <div class="overflow-x-auto rounded-lg border">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-muted/50">
                            <th
                                scope="col"
                                class="px-4 py-3 text-left font-medium"
                            >
                                Class
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 text-left font-medium"
                            >
                                Teacher
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
                            v-for="courseClass in classes"
                            :key="courseClass.id"
                            class="border-b last:border-b-0"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ courseClass.name }}
                            </td>
                            <td class="px-4 py-3">
                                <span v-if="courseClass.teacher">{{
                                    courseClass.teacher
                                }}</span>
                                <span v-else class="text-muted-foreground">
                                    No teacher
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link
                                    :href="editClass(courseClass.id)"
                                    class="text-sm font-medium underline-offset-4 hover:underline"
                                >
                                    Edit
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!classes.length">
                            <td
                                colspan="3"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                No classes yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
