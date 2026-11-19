<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import SchoolYearController from '@/actions/App/Http/Controllers/School/SchoolYearController';
import FormField from '@/components/FormField.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type SchoolYearRecord = {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
    is_current: boolean;
};

const props = defineProps<{
    year?: SchoolYearRecord | null;
}>();

const isEditing = computed(() => Boolean(props.year));

const form = useForm({
    name: props.year?.name ?? '',
    start_date: props.year?.start_date ?? '',
    end_date: props.year?.end_date ?? '',
    is_current: props.year?.is_current ?? false,
});

function setCurrent(value: boolean | 'indeterminate'): void {
    form.is_current = value === true;
}

function submit(): void {
    if (props.year) {
        form.put(SchoolYearController.update.url(props.year.id), {
            preserveScroll: true,
        });

        return;
    }

    form.post(SchoolYearController.store.url(), { preserveScroll: true });
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <FormField
            label="School year name"
            field-id="name"
            :error="form.errors.name"
            required
        >
            <Input
                id="name"
                v-model="form.name"
                required
                placeholder="e.g. 2026-2027"
            />
        </FormField>

        <div class="grid gap-4 sm:grid-cols-2">
            <FormField
                label="Start date"
                field-id="start_date"
                :error="form.errors.start_date"
                required
            >
                <Input
                    id="start_date"
                    v-model="form.start_date"
                    type="date"
                    required
                />
            </FormField>

            <FormField
                label="End date"
                field-id="end_date"
                :error="form.errors.end_date"
                required
            >
                <Input
                    id="end_date"
                    v-model="form.end_date"
                    type="date"
                    required
                />
            </FormField>
        </div>

        <div class="flex items-start gap-2">
            <Checkbox
                id="is_current"
                :model-value="form.is_current"
                @update:model-value="setCurrent"
            />
            <div class="grid gap-1">
                <Label for="is_current" class="font-normal">
                    Current school year
                </Label>
                <p class="text-sm text-muted-foreground">
                    Marking this year as current clears the flag from any other
                    year.
                </p>
                <InputError :message="form.errors.is_current" />
            </div>
        </div>

        <div class="flex items-center gap-4">
            <Button :disabled="form.processing" data-test="save-school-year">
                {{ isEditing ? 'Save changes' : 'Create school year' }}
            </Button>
        </div>
    </form>
</template>
