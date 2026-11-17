<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AssessmentController from '@/actions/App/Http/Controllers/School/AssessmentController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/school/assessment';

type AssessmentRow = {
    id: number;
    name: string;
    assessed_on: string;
    max_score: number;
    marks_count: number;
};

const props = defineProps<{
    courseClass: {
        id: number;
        name: string;
    };
    assessments: AssessmentRow[];
    students_count: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Assessment',
                href: index(),
            },
            {
                title: 'Class assessments',
                href: index(),
            },
        ],
    },
});

const page = usePage();

const canManage = computed(() =>
    page.props.auth.permissions.includes('assessment.manage'),
);

function remove(assessment: AssessmentRow): void {
    if (!confirm(`Remove "${assessment.name}" and all of its marks?`)) {
        return;
    }

    router.delete(AssessmentController.destroy.url(assessment.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="`Assessments for ${courseClass.name}`" />

    <h1 class="sr-only">Class assessments</h1>

    <div class="flex flex-col space-y-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                variant="small"
                :title="`Assessments for ${courseClass.name}`"
                description="Assessments and the marks recorded for this class."
            />

            <div class="flex flex-wrap gap-2">
                <Button variant="outline" as-child>
                    <Link :href="index()">Back to assessment</Link>
                </Button>

                <Button v-if="canManage" as-child>
                    <Link
                        :href="AssessmentController.create.url(courseClass.id)"
                    >
                        Add assessment
                    </Link>
                </Button>
            </div>
        </div>

        <div class="overflow-x-auto rounded-lg border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/50">
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Assessment
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Date
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Maximum score
                        </th>
                        <th scope="col" class="px-4 py-3 text-left font-medium">
                            Marks
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
                        v-for="assessment in assessments"
                        :key="assessment.id"
                        class="border-b last:border-b-0"
                    >
                        <td class="px-4 py-3 font-medium">
                            {{ assessment.name }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ assessment.assessed_on }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ assessment.max_score }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ assessment.marks_count }} / {{ students_count }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-3">
                                <Link
                                    :href="
                                        AssessmentController.marks.url(
                                            assessment.id,
                                        )
                                    "
                                    class="text-sm font-medium underline-offset-4 hover:underline"
                                >
                                    {{
                                        canManage && !assessment.marks_count
                                            ? 'Enter marks'
                                            : 'Marks'
                                    }}
                                </Link>
                                <template v-if="canManage">
                                    <Link
                                        :href="
                                            AssessmentController.edit.url(
                                                assessment.id,
                                            )
                                        "
                                        class="text-sm font-medium underline-offset-4 hover:underline"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        type="button"
                                        class="text-sm font-medium text-destructive underline-offset-4 hover:underline"
                                        @click="remove(assessment)"
                                    >
                                        Remove
                                    </button>
                                </template>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!assessments.length">
                        <td
                            colspan="5"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No assessments yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
