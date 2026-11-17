<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import FamilyForm from '@/components/FamilyForm.vue';
import type { FamilyRecord } from '@/components/FamilyForm.vue';
import { index } from '@/routes/school/families';

type FamilyStudent = {
    id: number;
    name: string;
};

type FamilyWithStudents = FamilyRecord & {
    students: FamilyStudent[];
};

defineProps<{
    family: FamilyWithStudents;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Families',
                href: index(),
            },
            {
                title: 'Edit family',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit ${family.name}`" />

    <h1 class="sr-only">Edit family</h1>

    <div class="flex max-w-2xl flex-col space-y-8">
        <div class="flex flex-col space-y-6">
            <Heading
                variant="small"
                :title="`Edit ${family.name}`"
                description="Update the family details."
            />

            <FamilyForm :family="family" />
        </div>

        <section class="space-y-3">
            <Heading
                variant="small"
                title="Students"
                description="Students assigned to this family. Assign a family from a student record."
            />

            <ul
                v-if="family.students.length"
                class="divide-y rounded-lg border"
            >
                <li
                    v-for="student in family.students"
                    :key="student.id"
                    class="px-4 py-3 text-sm"
                >
                    {{ student.name }}
                </li>
            </ul>

            <p v-else class="text-sm text-muted-foreground">
                No students are assigned to this family yet.
            </p>
        </section>
    </div>
</template>
