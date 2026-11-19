<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import StaffForm from '@/components/StaffForm.vue';
import type { AccountOption, StaffRecord } from '@/components/StaffForm.vue';
import { index } from '@/routes/school/staff';

defineProps<{
    staff: StaffRecord;
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
                title: 'Edit staff member',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit ${staff.first_name} ${staff.surname}`" />

    <div class="flex max-w-2xl flex-col space-y-6">
        <PageHeader
            eyebrow="Staff"
            :title="`Edit ${staff.first_name} ${staff.surname}`"
            description="Update the staff record and account link."
        />

        <StaffForm :staff="staff" :accounts="accounts" />
    </div>
</template>
