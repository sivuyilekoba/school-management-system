<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import FamilyForm from '@/components/FamilyForm.vue';
import { create, index } from '@/routes/school/families';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Families',
                href: index(),
            },
            {
                title: 'New family',
                href: create(),
            },
        ],
    },
});
</script>

<template>
    <Head title="New family" />

    <h1 class="sr-only">New family</h1>

    <div class="flex max-w-2xl flex-col space-y-6">
        <Heading
            variant="small"
            title="New family"
            description="Create a family that students can be assigned to."
        />

        <FamilyForm />
    </div>
</template>
