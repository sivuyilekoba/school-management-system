<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import type { LucideIcon } from '@lucide/vue';
import {
    ArrowRight,
    ArrowUpRight,
    Briefcase,
    GraduationCap,
    House,
    Megaphone,
    MessageCircle,
    Plus,
    Settings,
    User,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatCard from '@/components/StatCard.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import { index as messages } from '@/routes/messages';
import { edit as editProfile } from '@/routes/profile';
import {
    create as createAnnouncement,
    index as announcementsIndex,
    show as showAnnouncement,
} from '@/routes/school/announcements';
import { index as familiesIndex } from '@/routes/school/families';
import { edit as editSchoolSettings } from '@/routes/school/settings';
import { index as staffIndex } from '@/routes/school/staff';
import { index as studentsIndex } from '@/routes/school/students';

type Stats = {
    students: number | null;
    staff: number | null;
    families: number | null;
    unreadMessages: number | null;
};

type AnnouncementRow = {
    id: number;
    title: string;
    audience_label: string;
    published_at: string | null;
    author: string | null;
};

type QuickLink = {
    label: string;
    href: string;
    icon: LucideIcon;
};

const props = defineProps<{
    stats: Stats;
    announcements: AnnouncementRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

const page = usePage();

const firstName = computed(
    () => page.props.auth.user.name.split(' ')[0] ?? page.props.auth.user.name,
);

const permissions = computed(() => page.props.auth.permissions);

const canViewAnnouncements = computed(() =>
    permissions.value.includes('announcements.view'),
);

const canManageAnnouncements = computed(() =>
    permissions.value.includes('announcements.manage'),
);

const statCards = computed(() => {
    const cards: Array<{
        key: string;
        label: string;
        value: number;
        hint: string;
        icon: LucideIcon;
    }> = [];

    if (props.stats.students !== null) {
        cards.push({
            key: 'students',
            label: 'Students',
            value: props.stats.students,
            hint: 'Student records on file',
            icon: GraduationCap,
        });
    }

    if (props.stats.staff !== null) {
        cards.push({
            key: 'staff',
            label: 'Staff',
            value: props.stats.staff,
            hint: 'People on the staff list',
            icon: Briefcase,
        });
    }

    if (props.stats.families !== null) {
        cards.push({
            key: 'families',
            label: 'Families',
            value: props.stats.families,
            hint: 'Family groups on file',
            icon: Users,
        });
    }

    if (props.stats.unreadMessages !== null) {
        cards.push({
            key: 'messages',
            label: 'Unread messages',
            value: props.stats.unreadMessages,
            hint:
                props.stats.unreadMessages > 0
                    ? 'Waiting in your inbox'
                    : 'You are all caught up',
            icon: MessageCircle,
        });
    }

    return cards;
});

const quickLinks = computed<QuickLink[]>(() => {
    const links: QuickLink[] = [];

    if (permissions.value.includes('people.students.view')) {
        links.push({
            label: 'Students',
            href: studentsIndex.url(),
            icon: GraduationCap,
        });
    }

    if (permissions.value.includes('people.staff.view')) {
        links.push({
            label: 'Staff',
            href: staffIndex.url(),
            icon: Briefcase,
        });
    }

    if (permissions.value.includes('people.families.view')) {
        links.push({
            label: 'Families',
            href: familiesIndex.url(),
            icon: House,
        });
    }

    if (canViewAnnouncements.value) {
        links.push({
            label: 'Announcements',
            href: announcementsIndex.url(),
            icon: Megaphone,
        });
    }

    if (permissions.value.includes('messages.view')) {
        links.push({
            label: 'Messages',
            href: messages.url(),
            icon: MessageCircle,
        });
    }

    if (permissions.value.includes('settings.manage')) {
        links.push({
            label: 'School settings',
            href: editSchoolSettings.url(),
            icon: Settings,
        });
    }

    links.push({
        label: 'Your profile',
        href: editProfile.url(),
        icon: User,
    });

    return links;
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col gap-6">
        <PageHeader
            eyebrow="Dashboard"
            :title="`Welcome back, ${firstName}`"
            :description="
                canViewAnnouncements
                    ? 'A quick look at your school, with the latest notices for staff.'
                    : 'A quick look at your school and the tools available to you.'
            "
        >
            <template #actions>
                <Button v-if="canManageAnnouncements" as-child class="gap-2">
                    <Link :href="createAnnouncement()">
                        <Plus aria-hidden="true" />
                        New announcement
                    </Link>
                </Button>

                <Button
                    v-else-if="canViewAnnouncements"
                    as-child
                    variant="outline"
                >
                    <Link :href="announcementsIndex()">
                        View announcements
                        <ArrowRight aria-hidden="true" />
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <div
            v-if="statCards.length"
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
        >
            <StatCard
                v-for="card in statCards"
                :key="card.key"
                :label="card.label"
                :value="card.value"
                :hint="card.hint"
                :icon="card.icon"
            />
        </div>

        <div class="grid items-start gap-6 lg:grid-cols-3">
            <Card
                v-if="canViewAnnouncements"
                class="gap-0 overflow-hidden py-0 lg:col-span-2"
            >
                <CardHeader class="border-b px-6 py-5">
                    <CardTitle>Latest announcements</CardTitle>
                    <CardDescription>
                        Recent published notices addressed to staff.
                    </CardDescription>
                    <CardAction>
                        <Button as-child variant="outline" size="sm">
                            <Link :href="announcementsIndex()">
                                View all
                                <ArrowRight aria-hidden="true" />
                            </Link>
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent class="p-0">
                    <ul v-if="announcements.length" class="divide-y">
                        <li
                            v-for="announcement in announcements"
                            :key="announcement.id"
                        >
                            <Link
                                :href="showAnnouncement(announcement.id)"
                                class="flex flex-col gap-1.5 px-6 py-4 transition-colors hover:bg-muted/50"
                            >
                                <div
                                    class="flex flex-wrap items-start justify-between gap-2"
                                >
                                    <p class="font-medium text-balance">
                                        {{ announcement.title }}
                                    </p>

                                    <Badge variant="outline">
                                        {{ announcement.audience_label }}
                                    </Badge>
                                </div>

                                <p class="text-xs text-muted-foreground">
                                    {{ announcement.published_at ?? '' }}
                                    <template v-if="announcement.author">
                                        · {{ announcement.author }}
                                    </template>
                                </p>
                            </Link>
                        </li>
                    </ul>

                    <EmptyState
                        v-else
                        class="border-0 bg-transparent"
                        :icon="Megaphone"
                        title="No announcements yet"
                        description="Published notices addressed to staff will appear here."
                    >
                        <Button
                            v-if="canManageAnnouncements"
                            as-child
                            variant="outline"
                            size="sm"
                        >
                            <Link :href="createAnnouncement()">
                                Write the first announcement
                            </Link>
                        </Button>
                    </EmptyState>
                </CardContent>
            </Card>

            <Card
                class="gap-0 py-0"
                :class="{ 'lg:col-span-3': !canViewAnnouncements }"
            >
                <CardHeader class="border-b px-6 py-5">
                    <CardTitle>Quick links</CardTitle>
                    <CardDescription>
                        Jump straight to the records you use most.
                    </CardDescription>
                </CardHeader>

                <CardContent class="p-2 pb-4">
                    <nav class="flex flex-col" aria-label="Quick links">
                        <Link
                            v-for="link in quickLinks"
                            :key="link.label"
                            :href="link.href"
                            class="group flex items-center gap-3 rounded-md px-3 py-2.5 text-sm transition-colors hover:bg-accent focus-visible:bg-accent"
                        >
                            <component
                                :is="link.icon"
                                aria-hidden="true"
                                class="size-4 text-muted-foreground"
                            />

                            <span class="font-medium">{{ link.label }}</span>

                            <ArrowUpRight
                                aria-hidden="true"
                                class="ml-auto size-4 text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100 group-focus-visible:opacity-100"
                            />
                        </Link>
                    </nav>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
