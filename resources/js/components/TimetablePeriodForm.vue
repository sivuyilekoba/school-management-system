<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import TimetablePeriodController from '@/actions/App/Http/Controllers/School/TimetablePeriodController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type TimetablePeriodRecord = {
    id: number;
    name: string;
    start_time: string;
    end_time: string;
};

const props = defineProps<{
    period?: TimetablePeriodRecord | null;
}>();

const isEditing = computed(() => Boolean(props.period));

const form = useForm({
    name: props.period?.name ?? '',
    start_time: props.period?.start_time ?? '',
    end_time: props.period?.end_time ?? '',
});

function submit(): void {
    if (props.period) {
        form.put(TimetablePeriodController.update.url(props.period.id), {
            preserveScroll: true,
        });

        return;
    }

    form.post(TimetablePeriodController.store.url(), { preserveScroll: true });
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <div class="grid gap-2">
            <Label for="name">Period name</Label>
            <Input
                id="name"
                v-model="form.name"
                required
                placeholder="e.g. Period 1"
            />
            <InputError :message="form.errors.name" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="start_time">Start time</Label>
                <Input
                    id="start_time"
                    v-model="form.start_time"
                    type="time"
                    required
                />
                <InputError :message="form.errors.start_time" />
            </div>

            <div class="grid gap-2">
                <Label for="end_time">End time</Label>
                <Input
                    id="end_time"
                    v-model="form.end_time"
                    type="time"
                    required
                />
                <InputError :message="form.errors.end_time" />
            </div>
        </div>

        <div class="flex items-center gap-4">
            <Button :disabled="form.processing" data-test="save-period">
                {{ isEditing ? 'Save changes' : 'Create period' }}
            </Button>
        </div>
    </form>
</template>
