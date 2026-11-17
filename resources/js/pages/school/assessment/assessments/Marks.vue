<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AssessmentController from '@/actions/App/Http/Controllers/School/AssessmentController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index } from '@/routes/school/assessment';

type StudentRow = {
    id: number;
    name: string;
    score: string;
};

const props = defineProps<{
    courseClass: {
        id: number;
        name: string;
    };
    assessment: {
        id: number;
        name: string;
        assessed_on: string;
        max_score: number;
    };
    students: StudentRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Assessment',
                href: index(),
            },
            {
                title: 'Marks',
                href: index(),
            },
        ],
    },
});

const page = usePage();

const canManage = computed(() =>
    page.props.auth.permissions.includes('assessment.manage'),
);

const form = useForm({
    records: props.students.map((student) => ({
        student_id: student.id,
        score: student.score,
    })),
});

const enteredCount = computed(
    () => form.records.filter((record) => record.score.trim() !== '').length,
);

function save(): void {
    form.transform((data) => ({
        ...data,
        records: data.records.map((record) => ({
            ...record,
            score: record.score.trim() === '' ? null : record.score,
        })),
    })).put(AssessmentController.saveMarks.url(props.assessment.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="`Marks for ${assessment.name}`" />

    <h1 class="sr-only">Assessment marks</h1>

    <div class="flex flex-col space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                variant="small"
                :title="`Marks for ${assessment.name}`"
                :description="`${courseClass.name} · ${assessment.assessed_on} · out of ${assessment.max_score}`"
            />

            <Button variant="outline" as-child>
                <Link
                    :href="AssessmentController.classIndex.url(courseClass.id)"
                >
                    Back to class
                </Link>
            </Button>
        </div>

        <p class="text-sm text-muted-foreground">
            {{ enteredCount }} of {{ students.length }} marks entered.
        </p>

        <p
            v-if="!students.length"
            class="rounded-lg border px-4 py-8 text-center text-sm text-muted-foreground"
        >
            No students are enrolled in this class.
        </p>

        <template v-else>
            <div class="overflow-x-auto rounded-lg border">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-muted/50">
                            <th
                                scope="col"
                                class="px-4 py-3 text-left font-medium"
                            >
                                Student
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 text-left font-medium"
                            >
                                Score out of {{ assessment.max_score }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(record, recordIndex) in form.records"
                            :key="record.student_id"
                            class="border-b last:border-b-0"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{
                                    students.find(
                                        (student) =>
                                            student.id === record.student_id,
                                    )?.name
                                }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="max-w-40">
                                    <Input
                                        v-if="canManage"
                                        v-model="record.score"
                                        type="number"
                                        min="0"
                                        :max="assessment.max_score"
                                        step="0.01"
                                        :aria-label="`Score for student ${record.student_id}`"
                                        placeholder="Not submitted"
                                    />
                                    <span v-else>
                                        {{ record.score || '—' }}
                                    </span>
                                    <InputError
                                        :message="
                                            form.errors[
                                                `records.${recordIndex}.score`
                                            ]
                                        "
                                    />
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="canManage" class="flex items-center gap-4">
                <Button :disabled="form.processing" @click="save">
                    Save marks
                </Button>
                <InputError :message="form.errors.records" />
            </div>
        </template>
    </div>
</template>
