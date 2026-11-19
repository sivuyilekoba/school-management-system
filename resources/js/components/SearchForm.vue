<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Props = {
    url: string;
    value?: string;
    placeholder: string;
    label: string;
};

const props = defineProps<Props>();

const query = ref(props.value ?? '');

function submit(): void {
    router.get(
        props.url,
        { q: query.value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}
</script>

<template>
    <form class="max-w-md" role="search" @submit.prevent="submit">
        <div class="flex gap-2">
            <Input
                v-model="query"
                type="search"
                :placeholder="props.placeholder"
                :aria-label="props.label"
            />
            <Button type="submit" variant="outline">Search</Button>
        </div>
    </form>
</template>
