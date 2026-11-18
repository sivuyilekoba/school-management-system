<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ReportCardController from '@/actions/App/Http/Controllers/School/ReportCardController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/school/reports';

type AttendanceCounts = {
    present: number;
    absent: number;
    late: number;
    excused: number;
    total: number;
};

type AssessmentRow = {
    id: number;
    name: string;
    assessed_on: string;
    score: number | null;
    max_score: number;
    percentage: number | null;
};

type ClassRow = {
    id: number;
    name: string;
    assessments: AssessmentRow[];
    score: number;
    max_score: number;
    percentage: number | null;
};

const props = defineProps<{
    term: {
        id: number;
        name: string;
        start_date: string;
        end_date: string;
        school_year: {
            id: number;
            name: string;
        };
    };
    student: {
        id: number;
        name: string;
        email: string | null;
        family: string | null;
    };
    classes: ClassRow[];
    overall: {
        percentage: number | null;
        assessments_count: number;
        marked_count: number;
    };
    attendance: AttendanceCounts;
    reportCard: {
        comment: string | null;
        published: boolean;
        published_at: string | null;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Report cards',
                href: index(),
            },
            {
                title: 'Report card',
                href: index(),
            },
        ],
    },
});

const page = usePage();

const canPublish = computed(() =>
    page.props.auth.permissions.includes('reports.publish'),
);

const form = useForm({
    comment: props.reportCard.comment ?? '',
});

function saveComment(): void {
    form.put(
        ReportCardController.saveComment.url({
            student: props.student.id,
            term: props.term.id,
        }),
        { preserveScroll: true },
    );
}

function publish(): void {
    router.put(
        ReportCardController.publish.url({
            student: props.student.id,
            term: props.term.id,
        }),
        {},
        { preserveScroll: true },
    );
}

function unpublish(): void {
    if (!confirm('Return this report card to draft?')) {
        return;
    }

    router.delete(
        ReportCardController.unpublish.url({
            student: props.student.id,
            term: props.term.id,
        }),
        { preserveScroll: true },
    );
}

function formatPercentage(value: number | null): string {
    return value === null ? '—' : `${value}%`;
}

function formatScore(assessment: AssessmentRow): string {
    return assessment.score === null
        ? '—'
        : `${assessment.score} / ${assessment.max_score}`;
}
</script>

<template>
    <Head :title="`Report card for ${student.name}`" />

    <h1 class="sr-only">Report card</h1>

    <div class="flex flex-col space-y-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                variant="small"
                :title="`Report card for ${student.name}`"
                :description="`${term.school_year.name} · ${term.name} · ${term.start_date} to ${term.end_date}`"
            />

            <div class="flex flex-wrap items-center gap-2">
                <Badge
                    :variant="reportCard.published ? 'default' : 'secondary'"
                >
                    {{ reportCard.published ? 'Published' : 'Draft' }}
                </Badge>

                <Button variant="outline" as-child>
                    <a
                        :href="
                            ReportCardController.print.url({
                                student: student.id,
                                term: term.id,
                            })
                        "
                        target="_blank"
                        rel="noopener"
                    >
                        Print
                    </a>
                </Button>

                <Button variant="outline" as-child>
                    <a
                        :href="
                            ReportCardController.pdf.url({
                                student: student.id,
                                term: term.id,
                            })
                        "
                    >
                        Download PDF
                    </a>
                </Button>

                <Button variant="outline" as-child>
                    <Link :href="index.url({ query: { term: term.id } })">
                        Back to report cards
                    </Link>
                </Button>

                <template v-if="canPublish">
                    <Button
                        v-if="!reportCard.published"
                        :disabled="form.processing"
                        @click="publish"
                    >
                        Publish
                    </Button>
                    <Button
                        v-else
                        variant="outline"
                        :disabled="form.processing"
                        @click="unpublish"
                    >
                        Unpublish
                    </Button>
                </template>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-lg border p-4">
                <p class="text-sm text-muted-foreground">Overall average</p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ formatPercentage(overall.percentage) }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ overall.marked_count }} of
                    {{ overall.assessments_count }} assessments marked
                </p>
            </div>

            <div class="rounded-lg border p-4">
                <p class="text-sm text-muted-foreground">Attendance</p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ attendance.present }} / {{ attendance.total }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Present · {{ attendance.late }} late ·
                    {{ attendance.excused }} excused ·
                    {{ attendance.absent }} absent
                </p>
            </div>

            <div class="rounded-lg border p-4">
                <p class="text-sm text-muted-foreground">Student</p>
                <p class="mt-1 text-lg font-semibold">{{ student.name }}</p>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ student.email ?? 'No email' }}
                    <template v-if="student.family">
                        · {{ student.family }}
                    </template>
                </p>
            </div>
        </div>

        <p
            v-if="!classes.length"
            class="rounded-lg border px-4 py-8 text-center text-sm text-muted-foreground"
        >
            This student is not enrolled in any classes for this term.
        </p>

        <div v-else class="space-y-8">
            <section
                v-for="classRow in classes"
                :key="classRow.id"
                class="space-y-3"
            >
                <div
                    class="flex flex-wrap items-baseline justify-between gap-2"
                >
                    <h2 class="text-lg font-semibold">{{ classRow.name }}</h2>
                    <p class="text-sm text-muted-foreground">
                        Class average
                        <span class="font-medium text-foreground">
                            {{ formatPercentage(classRow.percentage) }}
                        </span>
                    </p>
                </div>

                <p
                    v-if="!classRow.assessments.length"
                    class="rounded-lg border px-4 py-6 text-center text-sm text-muted-foreground"
                >
                    No assessments were recorded for this class in this term.
                </p>

                <div v-else class="overflow-x-auto rounded-lg border">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/50">
                                <th
                                    scope="col"
                                    class="px-4 py-3 text-left font-medium"
                                >
                                    Assessment
                                </th>
                                <th
                                    scope="col"
                                    class="px-4 py-3 text-left font-medium"
                                >
                                    Date
                                </th>
                                <th
                                    scope="col"
                                    class="px-4 py-3 text-left font-medium"
                                >
                                    Score
                                </th>
                                <th
                                    scope="col"
                                    class="px-4 py-3 text-left font-medium"
                                >
                                    Percentage
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="assessment in classRow.assessments"
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
                                    {{ formatScore(assessment) }}
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">
                                    {{
                                        formatPercentage(assessment.percentage)
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <section class="space-y-3">
            <h2 class="text-lg font-semibold">Comment</h2>

            <form
                v-if="canPublish"
                class="space-y-3"
                @submit.prevent="saveComment"
            >
                <div class="grid gap-2">
                    <label for="report-comment" class="text-sm font-medium">
                        Teacher comment
                    </label>
                    <textarea
                        id="report-comment"
                        v-model="form.comment"
                        rows="5"
                        maxlength="5000"
                        class="min-h-32 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30"
                        placeholder="Add a comment for this term's report card."
                    />
                    <InputError :message="form.errors.comment" />
                </div>

                <Button :disabled="form.processing">Save comment</Button>
            </form>

            <p
                v-else-if="reportCard.comment"
                class="rounded-lg border p-4 text-sm whitespace-pre-line"
            >
                {{ reportCard.comment }}
            </p>

            <p v-else class="text-sm text-muted-foreground">
                No comment has been added yet.
            </p>
        </section>
    </div>
</template>
