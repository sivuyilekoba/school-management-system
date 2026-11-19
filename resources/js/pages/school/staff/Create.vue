<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import StaffForm from '@/components/StaffForm.vue';
import type { AccountOption } from '@/components/StaffForm.vue';
import { create, index } from '@/routes/school/staff';

defineProps<{
    accounts: AccountOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Staff',
                href: index(),
            },
            {
                title: 'New staff member',
                href: create(),
            },
        ],
    },
});
</script>

<template>
    <Head title="New staff member" />

    <div class="flex max-w-2xl flex-col space-y-6">
        <PageHeader
            eyebrow="Staff"
            title="New staff member"
            description="Add a staff record to the school."
        />

        <StaffForm :accounts="accounts" />
    </div>
</template>
