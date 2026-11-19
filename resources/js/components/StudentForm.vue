<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import StudentController from '@/actions/App/Http/Controllers/School/StudentController';
import FormField from '@/components/FormField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export type FamilyOption = {
    id: number;
    name: string;
};

export type AccountOption = {
    id: number;
    name: string;
    email: string;
};

export type StudentRecord = {
    id: number;
    first_name: string;
    surname: string;
    preferred_name: string | null;
    gender: string;
    date_of_birth: string | null;
    email: string | null;
    family_id: number | null;
    user_id: number | null;
};

const props = defineProps<{
    families: FamilyOption[];
    accounts: AccountOption[];
    genders: string[];
    student?: StudentRecord | null;
}>();

const isEditing = computed(() => Boolean(props.student));

const form = useForm({
    first_name: props.student?.first_name ?? '',
    surname: props.student?.surname ?? '',
    preferred_name: props.student?.preferred_name ?? '',
    gender: props.student?.gender ?? 'Unspecified',
    date_of_birth: props.student?.date_of_birth ?? '',
    email: props.student?.email ?? '',
    family_id: props.student?.family_id ?? null,
    user_id: props.student?.user_id ?? null,
});

const familyId = computed<string>({
    get: () => (form.family_id === null ? 'none' : String(form.family_id)),
    set: (value) => {
        form.family_id = value === 'none' ? null : Number(value);
    },
});

const accountId = computed<string>({
    get: () => (form.user_id === null ? 'none' : String(form.user_id)),
    set: (value) => {
        form.user_id = value === 'none' ? null : Number(value);
    },
});

function submit(): void {
    if (props.student) {
        form.put(StudentController.update.url(props.student.id), {
            preserveScroll: true,
        });

        return;
    }

    form.post(StudentController.store.url(), { preserveScroll: true });
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <div class="grid gap-4 sm:grid-cols-2">
            <FormField
                label="First name"
                field-id="first_name"
                :error="form.errors.first_name"
                required
            >
                <Input
                    id="first_name"
                    v-model="form.first_name"
                    required
                    autocomplete="given-name"
                />
            </FormField>

            <FormField
                label="Surname"
                field-id="surname"
                :error="form.errors.surname"
                required
            >
                <Input
                    id="surname"
                    v-model="form.surname"
                    required
                    autocomplete="family-name"
                />
            </FormField>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <FormField
                label="Preferred name"
                field-id="preferred_name"
                :error="form.errors.preferred_name"
                hint="Optional. Shown instead of the first name in lists."
            >
                <Input
                    id="preferred_name"
                    v-model="form.preferred_name"
                    autocomplete="nickname"
                />
            </FormField>

            <FormField
                label="Email address"
                field-id="email"
                :error="form.errors.email"
            >
                <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                />
            </FormField>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <FormField
                label="Date of birth"
                field-id="date_of_birth"
                :error="form.errors.date_of_birth"
            >
                <Input
                    id="date_of_birth"
                    v-model="form.date_of_birth"
                    type="date"
                />
            </FormField>

            <FormField
                label="Gender"
                field-id="gender"
                :error="form.errors.gender"
            >
                <Select v-model="form.gender">
                    <SelectTrigger id="gender" class="w-full">
                        <SelectValue placeholder="Select a gender" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="gender in genders"
                            :key="gender"
                            :value="gender"
                        >
                            {{ gender }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </FormField>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <FormField
                label="Family"
                field-id="family_id"
                :error="form.errors.family_id"
            >
                <Select v-model="familyId">
                    <SelectTrigger id="family_id" class="w-full">
                        <SelectValue placeholder="No family" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="none">No family</SelectItem>
                        <SelectItem
                            v-for="family in families"
                            :key="family.id"
                            :value="String(family.id)"
                        >
                            {{ family.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </FormField>

            <FormField
                label="Linked account"
                field-id="user_id"
                :error="form.errors.user_id"
            >
                <Select v-model="accountId">
                    <SelectTrigger id="user_id" class="w-full">
                        <SelectValue placeholder="No linked account" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="none">No linked account</SelectItem>
                        <SelectItem
                            v-for="account in accounts"
                            :key="account.id"
                            :value="String(account.id)"
                        >
                            {{ account.name }} ({{ account.email }})
                        </SelectItem>
                    </SelectContent>
                </Select>
            </FormField>
        </div>

        <div class="flex items-center gap-4">
            <Button :disabled="form.processing" data-test="save-student">
                {{ isEditing ? 'Save changes' : 'Create student' }}
            </Button>
        </div>
    </form>
</template>
