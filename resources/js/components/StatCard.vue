<script setup lang="ts">
import type { Component } from 'vue';

type Props = {
    label: string;
    value: number | string;
    hint?: string;
    icon?: Component;
};

defineProps<Props>();
</script>

<template>
    <div class="rounded-xl border bg-card p-5 shadow-card">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-sm font-medium text-muted-foreground">
                    {{ label }}
                </p>

                <p
                    class="mt-2 text-3xl font-semibold tracking-tight text-balance tabular-nums"
                >
                    {{ value }}
                </p>
            </div>

            <span
                v-if="icon"
                aria-hidden="true"
                class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
            >
                <component :is="icon" class="size-4" />
            </span>
        </div>

        <p v-if="hint" class="mt-3 text-xs text-muted-foreground">
            {{ hint }}
        </p>
    </div>
</template>
