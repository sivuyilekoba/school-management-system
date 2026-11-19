<script setup lang="ts">
type Props = {
    title: string;
    description?: string;
    eyebrow?: string;
};

defineProps<Props>();
</script>

<template>
    <header
        class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
    >
        <div class="min-w-0 space-y-1">
            <p
                v-if="eyebrow"
                class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
            >
                {{ eyebrow }}
            </p>

            <h1 class="text-2xl font-semibold tracking-tight text-balance">
                {{ title }}
            </h1>

            <p
                v-if="description"
                class="max-w-prose text-sm text-pretty text-muted-foreground"
            >
                {{ description }}
            </p>
        </div>

        <div
            v-if="$slots.actions"
            class="flex flex-wrap items-center gap-2 sm:justify-end"
        >
            <slot name="actions" />
        </div>
    </header>
</template>
