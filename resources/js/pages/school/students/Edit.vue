<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import StudentController from '@/actions/App/Http/Controllers/School/StudentController';
import Heading from '@/components/Heading.vue';
import LoginAccountPanel from '@/components/LoginAccountPanel.vue';
import type { LoginAccount } from '@/components/LoginAccountPanel.vue';
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
    login: LoginAccount;
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

    <div class="flex max-w-2xl flex-col space-y-8">
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

        <LoginAccountPanel
            :action="StudentController.storeLogin.url(student.id)"
            :account="login"
            :default-email="student.email"
        />
    </div>
</template>
