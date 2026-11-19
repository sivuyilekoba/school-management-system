<script setup lang="ts">
import { Monitor, Moon, Sun } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useAppearance } from '@/composables/useAppearance';
import type { Appearance } from '@/types';

const { appearance, resolvedAppearance, updateAppearance } = useAppearance();

const TriggerIcon = computed(() =>
    resolvedAppearance.value === 'dark' ? Moon : Sun,
);

const options = [
    { value: 'light', label: 'Light', icon: Sun },
    { value: 'dark', label: 'Dark', icon: Moon },
    { value: 'system', label: 'System', icon: Monitor },
] as const satisfies ReadonlyArray<{
    value: Appearance;
    label: string;
    icon: typeof Sun;
}>;

function update(value: unknown): void {
    if (value === 'light' || value === 'dark' || value === 'system') {
        updateAppearance(value satisfies Appearance);
    }
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                class="text-muted-foreground"
                aria-label="Change theme"
            >
                <component :is="TriggerIcon" aria-hidden="true" />
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-44">
            <DropdownMenuLabel>Theme</DropdownMenuLabel>
            <DropdownMenuRadioGroup
                :model-value="appearance"
                @update:model-value="update"
            >
                <DropdownMenuRadioItem
                    v-for="option in options"
                    :key="option.value"
                    :value="option.value"
                >
                    <component
                        :is="option.icon"
                        class="size-4"
                        aria-hidden="true"
                    />
                    {{ option.label }}
                </DropdownMenuRadioItem>
            </DropdownMenuRadioGroup>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
