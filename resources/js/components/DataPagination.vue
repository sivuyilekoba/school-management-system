<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

type Props = {
    currentPage: number;
    lastPage: number;
    total: number;
    prevUrl: string | null;
    nextUrl: string | null;
    itemLabel: string;
};

const props = defineProps<Props>();
</script>

<template>
    <div
        v-if="props.lastPage > 1"
        class="flex flex-wrap items-center justify-between gap-3"
    >
        <p class="text-sm text-muted-foreground">
            Page {{ props.currentPage }} of {{ props.lastPage }} ·
            {{ props.total }} {{ props.itemLabel }}
        </p>

        <div class="flex gap-2">
            <Button v-if="props.prevUrl" variant="outline" size="sm" as-child>
                <Link :href="props.prevUrl" preserve-scroll>Previous</Link>
            </Button>

            <Button v-if="props.nextUrl" variant="outline" size="sm" as-child>
                <Link :href="props.nextUrl" preserve-scroll>Next</Link>
            </Button>
        </div>
    </div>
</template>
