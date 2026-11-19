<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import ReportCardDetails from '@/components/ReportCardDetails.vue';
import type {
    AttendanceCounts,
    ClassRow,
} from '@/components/ReportCardDetails.vue';
import { Button } from '@/components/ui/button';
import {
    index,
    pdf as downloadPdf,
    print as printReport,
} from '@/routes/portal/reports';

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
</script>

<template>
    <Head :title="`Report card for ${student.name}`" />

    <div class="flex flex-col gap-8">
        <PageHeader
            eyebrow="Report card"
            :title="`Report card for ${student.name}`"
            :description="`${term.school_year.name} · ${term.name} · ${term.start_date} to ${term.end_date}`"
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <a
                        :href="
                            printReport({
                                student: student.id,
                                term: term.id,
                            }).url
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
                            downloadPdf({
                                student: student.id,
                                term: term.id,
                            }).url
                        "
                    >
                        Download PDF
                    </a>
                </Button>

                <Button variant="outline" as-child>
                    <Link :href="index()">Back to report cards</Link>
                </Button>
            </template>
        </PageHeader>

        <ReportCardDetails
            :term="term"
            :student="student"
            :classes="classes"
            :overall="overall"
            :attendance="attendance"
            :report-card="reportCard"
        />
    </div>
</template>
