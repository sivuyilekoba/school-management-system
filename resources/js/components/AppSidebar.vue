<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, Settings, ShieldCheck, Users } from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { edit as editSchoolPermissions } from '@/routes/school/permissions';
import { edit as editSchoolSettings } from '@/routes/school/settings';
import { index as users } from '@/routes/school/users';
import type { NavItem } from '@/types';

const page = usePage();

const canManageSettings = computed(() =>
    page.props.auth.permissions.includes('settings.manage'),
);

const canManagePermissions = computed(() =>
    page.props.auth.permissions.includes('roles.manage'),
);

const canViewUsers = computed(() =>
    page.props.auth.permissions.includes('users.view'),
);

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    ...(canViewUsers.value
        ? [
              {
                  title: 'Users',
                  href: users(),
                  icon: Users,
              },
          ]
        : []),
    ...(canManageSettings.value
        ? [
              {
                  title: 'School settings',
                  href: editSchoolSettings(),
                  icon: Settings,
              },
          ]
        : []),
    ...(canManagePermissions.value
        ? [
              {
                  title: 'Permissions',
                  href: editSchoolPermissions(),
                  icon: ShieldCheck,
              },
          ]
        : []),
]);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
