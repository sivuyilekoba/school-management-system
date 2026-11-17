<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
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

    <h1 class="sr-only">Edit staff member</h1>

    <div class="flex max-w-2xl flex-col space-y-6">
        <Heading
            variant="small"
            :title="`Edit ${staff.first_name} ${staff.surname}`"
            description="Update the staff record and account link."
        />

        <StaffForm :staff="staff" :accounts="accounts" />
    </div>
</template>
