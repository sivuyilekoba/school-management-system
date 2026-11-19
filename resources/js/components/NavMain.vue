<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import type { NavItem } from '@/types';

type Props = {
    items: NavItem[];
    label?: string;
};

const props = withDefaults(defineProps<Props>(), {
    label: 'Navigation',
});

const { currentUrl } = useCurrentUrl();

/**
 * The longest matching route wins, so a nested page highlights its own
 * section instead of also highlighting a shorter parent such as /portal.
 */
const activeHref = computed<string | null>(() => {
    const matches = props.items
        .map((item) => normalize(toUrl(item.href)))
        .filter((href): href is string => Boolean(href))
        .filter(
            (href) =>
                currentUrl.value === href ||
                currentUrl.value.startsWith(`${href}/`),
        )
        .sort((first, second) => second.length - first.length);

    return matches[0] ?? null;
});

function normalize(href: string | undefined): string | null {
    if (!href) {
        return null;
    }

    const path = href.split('?')[0].replace(/\/+$/, '');

    return path === '' ? '/' : path;
}

function isActive(item: NavItem): boolean {
    return normalize(toUrl(item.href)) === activeHref.value;
}
</script>

<template>
    <SidebarGroup>
        <SidebarGroupLabel>{{ label }}</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="isActive(item)"
                    :tooltip="item.title"
                >
                    <Link :href="item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
