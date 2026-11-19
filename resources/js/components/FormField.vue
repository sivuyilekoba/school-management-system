<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

type Props = {
    label: string;
    fieldId?: string;
    error?: string;
    hint?: string;
    required?: boolean;
    class?: HTMLAttributes['class'];
};

const props = defineProps<Props>();
</script>

<template>
    <div :class="cn('grid gap-2', props.class)">
        <Label :for="props.fieldId">
            {{ props.label }}
            <span
                v-if="props.required"
                aria-hidden="true"
                class="text-destructive"
            >
                *
            </span>
        </Label>

        <slot />

        <p v-if="props.hint" class="text-sm text-muted-foreground">
            {{ props.hint }}
        </p>

        <InputError :message="props.error" />
    </div>
</template>
