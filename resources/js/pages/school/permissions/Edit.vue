<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import RolePermissionsController from '@/actions/App/Http/Controllers/School/RolePermissionsController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { edit } from '@/routes/school/permissions';

type PermissionModule = {
    name: string;
    description: string;
    permissions: Record<string, string>;
};

type Role = {
    id: number;
    name: string;
};

type Props = {
    modules: PermissionModule[];
    roles: Role[];
    assignments: Record<string, string[]>;
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Permissions',
                href: edit(),
            },
        ],
    },
});

const form = useForm<{ permissions: Record<string, string[]> }>({
    permissions: Object.fromEntries(
        props.roles.map((role) => [
            role.name,
            props.assignments[role.name] ?? [],
        ]),
    ),
});

function isGranted(roleName: string, permission: string): boolean {
    return form.permissions[roleName]?.includes(permission) ?? false;
}

function toggle(roleName: string, permission: string): void {
    const granted = form.permissions[roleName] ?? [];

    form.permissions = {
        ...form.permissions,
        [roleName]: granted.includes(permission)
            ? granted.filter((name) => name !== permission)
            : [...granted, permission],
    };
}

function submit(): void {
    form.put(RolePermissionsController.update.url(), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Permissions" />

    <h1 class="sr-only">Permissions</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Permissions"
            description="Choose what each role is allowed to do. Administrators always have every permission."
        />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="overflow-x-auto rounded-lg border">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-muted/50">
                            <th
                                scope="col"
                                class="px-4 py-3 text-left font-medium"
                            >
                                Permission
                            </th>
                            <th
                                v-for="role in roles"
                                :key="role.id"
                                scope="col"
                                class="px-4 py-3 text-center font-medium whitespace-nowrap"
                            >
                                {{ role.name }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="module in modules" :key="module.name">
                            <tr class="border-y bg-muted/30">
                                <td
                                    :colspan="roles.length + 1"
                                    class="px-4 py-2"
                                >
                                    <p class="font-medium">{{ module.name }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ module.description }}
                                    </p>
                                </td>
                            </tr>
                            <tr
                                v-for="(
                                    label, permission
                                ) in module.permissions"
                                :key="permission"
                                class="border-b last:border-b-0"
                            >
                                <td class="px-4 py-2.5">
                                    <p>{{ label }}</p>
                                    <code
                                        class="text-xs text-muted-foreground"
                                        >{{ permission }}</code
                                    >
                                </td>
                                <td
                                    v-for="role in roles"
                                    :key="role.id"
                                    class="px-4 py-2.5 text-center"
                                >
                                    <Checkbox
                                        :model-value="
                                            isGranted(role.name, permission)
                                        "
                                        :aria-label="`${label} for ${role.name}`"
                                        @update:model-value="
                                            toggle(role.name, permission)
                                        "
                                    />
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <InputError :message="form.errors.permissions" />

            <div class="flex items-center gap-4">
                <Button
                    :disabled="form.processing"
                    data-test="save-role-permissions"
                >
                    Save permissions
                </Button>
                <p class="text-sm text-muted-foreground">
                    Changes apply to every user with that role.
                </p>
            </div>
        </form>
    </div>
</template>
