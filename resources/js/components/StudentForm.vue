<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import StudentController from '@/actions/App/Http/Controllers/School/StudentController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
            <div class="grid gap-2">
                <Label for="first_name">First name</Label>
                <Input
                    id="first_name"
                    v-model="form.first_name"
                    required
                    autocomplete="given-name"
                />
                <InputError :message="form.errors.first_name" />
            </div>

            <div class="grid gap-2">
                <Label for="surname">Surname</Label>
                <Input
                    id="surname"
                    v-model="form.surname"
                    required
                    autocomplete="family-name"
                />
                <InputError :message="form.errors.surname" />
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="preferred_name">Preferred name</Label>
                <Input
                    id="preferred_name"
                    v-model="form.preferred_name"
                    autocomplete="nickname"
                />
                <p class="text-sm text-muted-foreground">
                    Optional. Shown instead of the first name in lists.
                </p>
                <InputError :message="form.errors.preferred_name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                />
                <InputError :message="form.errors.email" />
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="date_of_birth">Date of birth</Label>
                <Input
                    id="date_of_birth"
                    v-model="form.date_of_birth"
                    type="date"
                />
                <InputError :message="form.errors.date_of_birth" />
            </div>

            <div class="grid gap-2">
                <Label for="gender">Gender</Label>
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
                <InputError :message="form.errors.gender" />
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="family_id">Family</Label>
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
                <InputError :message="form.errors.family_id" />
            </div>

            <div class="grid gap-2">
                <Label for="user_id">Linked account</Label>
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
                <InputError :message="form.errors.user_id" />
            </div>
        </div>

        <div class="flex items-center gap-4">
            <Button :disabled="form.processing" data-test="save-student">
                {{ isEditing ? 'Save changes' : 'Create student' }}
            </Button>
        </div>
    </form>
</template>
