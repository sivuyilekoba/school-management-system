<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import SchoolYearForm from '@/components/SchoolYearForm.vue';
import type { SchoolYearRecord } from '@/components/SchoolYearForm.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/school/calendar';
import {
    create as createTerm,
    edit as editTerm,
} from '@/routes/school/calendar/terms';

type TermRow = {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
};

defineProps<{
    year: SchoolYearRecord;
    terms: TermRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Calendar',
                href: index(),
            },
            {
                title: 'Edit school year',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit ${year.name}`" />

    <h1 class="sr-only">Edit school year</h1>

    <div class="flex max-w-3xl flex-col space-y-8">
        <div class="flex flex-col space-y-6">
            <Heading
                variant="small"
                :title="`Edit ${year.name}`"
                description="Update the school year details."
            />

            <SchoolYearForm :year="year" />
        </div>

        <div class="flex flex-col space-y-4">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <Heading
                    variant="small"
                    title="Terms"
                    description="Terms must fall within the school year dates."
                />

                <Button as-child variant="outline">
                    <Link :href="createTerm(year.id)">Add term</Link>
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
                                Term
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 text-left font-medium"
                            >
                                Starts
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 text-left font-medium"
                            >
                                Ends
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
                            v-for="term in terms"
                            :key="term.id"
                            class="border-b last:border-b-0"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ term.name }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ term.start_date }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ term.end_date }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link
                                    :href="editTerm(term.id)"
                                    class="text-sm font-medium underline-offset-4 hover:underline"
                                >
                                    Edit
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!terms.length">
                            <td
                                colspan="4"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                No terms yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
