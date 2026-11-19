<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { index, show } from '@/routes/portal/reports';

type StudentRow = {
    id: number;
    name: string;
};

type ReportCardRow = {
    id: number;
    student: {
        id: number;
        name: string;
    };
    term: {
        id: number;
        name: string;
        start_date: string;
        end_date: string;
        school_year: string;
    };
    published_at: string | null;
};

defineProps<{
    students: StudentRow[];
    reportCards: ReportCardRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Report cards',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Report cards" />

    <div class="flex flex-col gap-6">
        <PageHeader
            eyebrow="Portal"
            title="Report cards"
            description="Published term report cards. Draft report cards stay hidden until the school publishes them."
        />

        <div
            v-if="reportCards.length"
            class="divide-y overflow-hidden rounded-xl border bg-card shadow-card"
        >
            <Link
                v-for="reportCard in reportCards"
                :key="reportCard.id"
                :href="
                    show({
                        student: reportCard.student.id,
                        term: reportCard.term.id,
                    })
                "
                class="flex flex-wrap items-center justify-between gap-3 px-4 py-4 transition-colors hover:bg-muted/40"
            >
                <div class="space-y-1">
                    <p class="font-medium">{{ reportCard.student.name }}</p>
                    <p class="text-sm text-muted-foreground">
                        {{ reportCard.term.school_year }} ·
                        {{ reportCard.term.name }} ·
                        {{ reportCard.term.start_date }} to
                        {{ reportCard.term.end_date }}
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <StatusBadge label="Published" tone="success" />
                    <span class="text-xs text-muted-foreground">
                        {{ reportCard.published_at ?? '' }}
                    </span>
                </div>
            </Link>
        </div>

        <EmptyState
            v-else
            :title="`No report cards have been published for ${students.length === 1 ? 'this student' : 'your students'} yet.`"
            :description="
                students.length
                    ? 'Published report cards will appear here.'
                    : 'No student record is linked to this account. Contact the school office.'
            "
        />
    </div>
</template>
