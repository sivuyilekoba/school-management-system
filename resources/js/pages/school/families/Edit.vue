<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import FamilyController from '@/actions/App/Http/Controllers/School/FamilyController';
import PageHeader from '@/components/PageHeader.vue';
import FamilyForm from '@/components/FamilyForm.vue';
import type { FamilyRecord } from '@/components/FamilyForm.vue';
import LoginAccountPanel from '@/components/LoginAccountPanel.vue';
import type { LoginAccount } from '@/components/LoginAccountPanel.vue';
import { index } from '@/routes/school/families';

type FamilyStudent = {
    id: number;
    name: string;
};

type FamilyWithStudents = FamilyRecord & {
    students: FamilyStudent[];
    login: LoginAccount;
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

    <div class="flex max-w-2xl flex-col space-y-8">
        <div class="flex flex-col space-y-6">
            <PageHeader
                eyebrow="Families"
                :title="`Edit ${family.name}`"
                description="Update the family details."
            />

            <FamilyForm :family="family" />
        </div>

        <LoginAccountPanel
            :action="FamilyController.storeLogin.url(family.id)"
            :account="family.login"
            :default-email="family.contact_email"
        />

        <section class="space-y-3">
            <div class="space-y-1">
                <h2 class="text-lg font-semibold">Students</h2>
                <p class="text-sm text-muted-foreground">
                    Students assigned to this family. Assign a family from a
                    student record.
                </p>
            </div>

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
