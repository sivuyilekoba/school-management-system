<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import StudentForm from '@/components/StudentForm.vue';
import type {
    AccountOption,
    FamilyOption,
    StudentRecord,
} from '@/components/StudentForm.vue';
import { index } from '@/routes/school/students';

defineProps<{
    student: StudentRecord;
    families: FamilyOption[];
    accounts: AccountOption[];
    genders: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Students',
                href: index(),
            },
            {
                title: 'Edit student',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit ${student.first_name} ${student.surname}`" />

    <h1 class="sr-only">Edit student</h1>

    <div class="flex max-w-2xl flex-col space-y-6">
        <Heading
            variant="small"
            :title="`Edit ${student.first_name} ${student.surname}`"
            description="Update the student record and account link."
        />

        <StudentForm
            :student="student"
            :families="families"
            :accounts="accounts"
            :genders="genders"
        />
    </div>
</template>
