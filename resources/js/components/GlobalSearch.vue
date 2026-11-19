<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import type { LucideIcon } from '@lucide/vue';
import { Briefcase, GraduationCap, House, Search } from '@lucide/vue';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { index as familiesIndex } from '@/routes/school/families';
import { index as staffIndex } from '@/routes/school/staff';
import { index as studentsIndex } from '@/routes/school/students';

type Destination = {
    key: string;
    label: string;
    description: string;
    url: string;
    icon: LucideIcon;
};

const page = usePage();

const open = ref(false);
const query = ref('');
const searchField = ref<HTMLElement | null>(null);

const destinations = computed<Destination[]>(() => {
    const permissions = page.props.auth.permissions;

    return [
        {
            key: 'students',
            label: 'Students',
            description: 'Search records by name or email',
            url: studentsIndex.url(),
            icon: GraduationCap,
            permission: 'people.students.view',
        },
        {
            key: 'staff',
            label: 'Staff',
            description: 'Search staff by name or email',
            url: staffIndex.url(),
            icon: Briefcase,
            permission: 'people.staff.view',
        },
        {
            key: 'families',
            label: 'Families',
            description: 'Search families by name',
            url: familiesIndex.url(),
            icon: House,
            permission: 'people.families.view',
        },
    ]
        .filter((destination) => permissions.includes(destination.permission))
        .map(({ permission: _permission, ...destination }) => destination);
});

const shortcutLabel = /Mac|iPhone|iPad/.test(
    typeof navigator === 'undefined' ? '' : navigator.userAgent,
)
    ? '⌘K'
    : 'Ctrl K';

watch(open, async (isOpen) => {
    if (!isOpen) {
        query.value = '';

        return;
    }

    await nextTick();
    searchField.value?.querySelector('input')?.focus();
});

function searchIn(destination: Destination): void {
    const search = query.value.trim();

    router.get(destination.url, search ? { q: search } : {}, {
        preserveScroll: false,
    });

    open.value = false;
}

function searchFirst(): void {
    const [first] = destinations.value;

    if (first) {
        searchIn(first);
    }
}

function handleShortcut(event: KeyboardEvent): void {
    if (
        event.key.toLowerCase() !== 'k' ||
        !(event.metaKey || event.ctrlKey) ||
        !destinations.value.length
    ) {
        return;
    }

    event.preventDefault();
    open.value = !open.value;
}

onMounted(() => window.addEventListener('keydown', handleShortcut));
onBeforeUnmount(() => window.removeEventListener('keydown', handleShortcut));
</script>

<template>
    <div v-if="destinations.length">
        <Button
            variant="outline"
            size="sm"
            class="hidden h-9 gap-2 px-3 text-muted-foreground md:inline-flex"
            aria-label="Search the school"
            @click="open = true"
        >
            <Search class="size-4" aria-hidden="true" />
            <span>Search</span>
            <kbd
                class="ml-2 rounded border bg-muted px-1.5 py-0.5 font-sans text-[0.65rem] font-medium tracking-wide text-muted-foreground"
            >
                {{ shortcutLabel }}
            </kbd>
        </Button>

        <Button
            variant="ghost"
            size="icon"
            class="text-muted-foreground md:hidden"
            aria-label="Search the school"
            @click="open = true"
        >
            <Search aria-hidden="true" />
        </Button>

        <Dialog v-model:open="open">
            <DialogContent
                class="top-20 translate-y-0 gap-0 overflow-hidden p-0 sm:max-w-xl"
                :show-close-button="false"
            >
                <DialogTitle class="sr-only">Search the school</DialogTitle>
                <DialogDescription class="sr-only">
                    Search students, staff, and families by name or email.
                </DialogDescription>

                <div class="flex items-center gap-3 border-b px-4">
                    <Search
                        class="size-4 shrink-0 text-muted-foreground"
                        aria-hidden="true"
                    />

                    <div ref="searchField" class="min-w-0 flex-1">
                        <Input
                            v-model="query"
                            type="search"
                            autofocus
                            placeholder="Search students, staff, or families…"
                            aria-label="Search query"
                            class="h-12 border-0 bg-transparent px-0 text-base shadow-none focus-visible:ring-0"
                            @keydown.enter.prevent="searchFirst"
                        />
                    </div>

                    <kbd
                        class="hidden shrink-0 rounded border bg-muted px-1.5 py-0.5 font-sans text-[0.65rem] font-medium text-muted-foreground sm:block"
                    >
                        Esc
                    </kbd>
                </div>

                <ul class="max-h-80 overflow-y-auto p-2">
                    <li
                        v-for="destination in destinations"
                        :key="destination.key"
                    >
                        <button
                            type="button"
                            class="flex w-full items-center gap-3 rounded-md px-3 py-2.5 text-left text-sm transition-colors hover:bg-accent focus-visible:bg-accent focus-visible:outline-none"
                            @click="searchIn(destination)"
                        >
                            <span
                                class="flex size-8 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground"
                            >
                                <component
                                    :is="destination.icon"
                                    class="size-4"
                                    aria-hidden="true"
                                />
                            </span>

                            <span class="min-w-0">
                                <span class="block font-medium">
                                    {{ destination.label }}
                                </span>
                                <span
                                    class="block truncate text-xs text-muted-foreground"
                                >
                                    {{
                                        query.trim()
                                            ? `Search for “${query.trim()}”`
                                            : destination.description
                                    }}
                                </span>
                            </span>
                        </button>
                    </li>
                </ul>
            </DialogContent>
        </Dialog>
    </div>
</template>
