<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import StudentForm from '@/components/StudentForm.vue';
import type { AccountOption, FamilyOption } from '@/components/StudentForm.vue';
import { create, index } from '@/routes/school/students';

defineProps<{
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
                title: 'New student',
                href: create(),
            },
        ],
    },
});
</script>

<template>
    <Head title="New student" />

    <div class="flex max-w-2xl flex-col space-y-6">
        <PageHeader
            eyebrow="Students"
            title="New student"
            description="Add a student record to the school."
        />

        <StudentForm
            :families="families"
            :accounts="accounts"
            :genders="genders"
        />
    </div>
</template>
