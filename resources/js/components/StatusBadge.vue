<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';

type Tone = 'success' | 'warning' | 'info' | 'danger' | 'neutral';

const props = withDefaults(
    defineProps<{
        label: string;
        tone?: Tone;
    }>(),
    {
        tone: 'neutral',
    },
);

const toneClasses: Record<Tone, string> = {
    success:
        'border-transparent bg-success-muted text-success-muted-foreground',
    warning:
        'border-transparent bg-warning-muted text-warning-muted-foreground',
    info: 'border-transparent bg-info-muted text-info-muted-foreground',
    danger: 'border-transparent bg-destructive/10 text-destructive dark:bg-destructive/20',
    neutral: 'border-transparent bg-muted text-muted-foreground',
};

const classes = computed(() => toneClasses[props.tone]);
</script>

<template>
    <Badge variant="outline" :class="classes">
        {{ props.label }}
    </Badge>
</template>
