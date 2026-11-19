<script setup lang="ts">
import type { Component, HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

type Props = {
    title: string;
    description?: string;
    icon?: Component;
    class?: HTMLAttributes['class'];
};

const props = defineProps<Props>();
</script>

<template>
    <div
        :class="
            cn(
                'flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed bg-surface-1 px-6 py-12 text-center',
                props.class,
            )
        "
    >
        <div
            v-if="icon"
            aria-hidden="true"
            class="flex size-10 items-center justify-center rounded-full bg-muted text-muted-foreground"
        >
            <component :is="icon" class="size-5" />
        </div>

        <div class="space-y-1">
            <p class="text-sm font-medium">{{ title }}</p>

            <p
                v-if="description"
                class="mx-auto max-w-sm text-sm text-pretty text-muted-foreground"
            >
                {{ description }}
            </p>
        </div>

        <div v-if="$slots.default" class="mt-1">
            <slot />
        </div>
    </div>
</template>
