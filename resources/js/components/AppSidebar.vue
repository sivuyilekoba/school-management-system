<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Briefcase,
    GraduationCap,
    House,
    LayoutGrid,
    Settings,
    ShieldCheck,
    Users,
} from '@lucide/vue';
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
import { index as families } from '@/routes/school/families';
import { edit as editSchoolPermissions } from '@/routes/school/permissions';
import { edit as editSchoolSettings } from '@/routes/school/settings';
import { index as staff } from '@/routes/school/staff';
import { index as students } from '@/routes/school/students';
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

const canViewStudents = computed(() =>
    page.props.auth.permissions.includes('people.students.view'),
);

const canViewStaff = computed(() =>
    page.props.auth.permissions.includes('people.staff.view'),
);

const canViewFamilies = computed(() =>
    page.props.auth.permissions.includes('people.families.view'),
);

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    ...(canViewStudents.value
        ? [
              {
                  title: 'Students',
                  href: students(),
                  icon: GraduationCap,
              },
          ]
        : []),
    ...(canViewStaff.value
        ? [
              {
                  title: 'Staff',
                  href: staff(),
                  icon: Briefcase,
              },
          ]
        : []),
    ...(canViewFamilies.value
        ? [
              {
                  title: 'Families',
                  href: families(),
                  icon: House,
              },
          ]
        : []),
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
