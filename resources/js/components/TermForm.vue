<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import TermController from '@/actions/App/Http/Controllers/School/TermController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type TermRecord = {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
};

export type SchoolYearSummary = {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
};

const props = defineProps<{
    schoolYear: SchoolYearSummary;
    term?: TermRecord | null;
}>();

const isEditing = computed(() => Boolean(props.term));

const form = useForm({
    name: props.term?.name ?? '',
    start_date: props.term?.start_date ?? '',
    end_date: props.term?.end_date ?? '',
});

function submit(): void {
    if (props.term) {
        form.put(TermController.update.url(props.term.id), {
            preserveScroll: true,
        });

        return;
    }

    form.post(TermController.store.url(props.schoolYear.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <p class="text-sm text-muted-foreground">
            Term dates must fall within
            {{ schoolYear.name }} ({{ schoolYear.start_date }} to
            {{ schoolYear.end_date }}).
        </p>

        <div class="grid gap-2">
            <Label for="name">Term name</Label>
            <Input
                id="name"
                v-model="form.name"
                required
                placeholder="e.g. Term 1"
            />
            <InputError :message="form.errors.name" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="start_date">Start date</Label>
                <Input
                    id="start_date"
                    v-model="form.start_date"
                    type="date"
                    required
                />
                <InputError :message="form.errors.start_date" />
            </div>

            <div class="grid gap-2">
                <Label for="end_date">End date</Label>
                <Input
                    id="end_date"
                    v-model="form.end_date"
                    type="date"
                    required
                />
                <InputError :message="form.errors.end_date" />
            </div>
        </div>

        <div class="flex items-center gap-4">
            <Button :disabled="form.processing" data-test="save-term">
                {{ isEditing ? 'Save changes' : 'Create term' }}
            </Button>
        </div>
    </form>
</template>
