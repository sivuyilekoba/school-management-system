<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import SchoolSettingsController from '@/actions/App/Http/Controllers/School/SchoolSettingsController';
import PageHeader from '@/components/PageHeader.vue';
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
import { edit } from '@/routes/school/settings';

type Props = {
    settings: {
        name: string;
        short_name: string | null;
        email: string | null;
        phone: string | null;
        address: string | null;
        timezone: string;
        date_format: string;
        logo_url: string | null;
    };
    timezones: string[];
    dateFormats: Record<string, string>;
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'School settings',
                href: edit(),
            },
        ],
    },
});

const form = useForm({
    name: props.settings.name,
    short_name: props.settings.short_name ?? '',
    email: props.settings.email ?? '',
    phone: props.settings.phone ?? '',
    address: props.settings.address ?? '',
    timezone: props.settings.timezone,
    date_format: props.settings.date_format,
    logo: null as File | null,
});

const logoPreview = ref<string | null>(props.settings.logo_url);

function handleLogoChange(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;

    form.logo = file;
    logoPreview.value = file
        ? URL.createObjectURL(file)
        : props.settings.logo_url;
}

function submit(): void {
    form.patch(SchoolSettingsController.update.url(), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="School settings" />

    <div class="flex flex-col gap-6">
        <PageHeader
            eyebrow="Settings"
            title="School settings"
            description="Details used across the school, such as the name shown in the sidebar and on printed reports"
        />

        <form class="max-w-2xl space-y-6" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="name">School name</Label>
                <Input
                    id="name"
                    v-model="form.name"
                    class="mt-1 block w-full"
                    name="name"
                    required
                    placeholder="Springfield High School"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="short_name">Short name</Label>
                <Input
                    id="short_name"
                    v-model="form.short_name"
                    class="mt-1 block w-full"
                    name="short_name"
                    placeholder="SHS"
                />
                <p class="text-sm text-muted-foreground">
                    Shown in the sidebar when space is limited.
                </p>
                <InputError class="mt-2" :message="form.errors.short_name" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        v-model="form.email"
                        class="mt-1 block w-full"
                        type="email"
                        name="email"
                        placeholder="office@school.example"
                    />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="phone">Phone</Label>
                    <Input
                        id="phone"
                        v-model="form.phone"
                        class="mt-1 block w-full"
                        type="tel"
                        name="phone"
                        placeholder="+1 555 0100"
                    />
                    <InputError class="mt-2" :message="form.errors.phone" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="address">Address</Label>
                <textarea
                    id="address"
                    v-model="form.address"
                    name="address"
                    rows="3"
                    class="mt-1 block w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                    placeholder="Street, city, postal code"
                />
                <InputError class="mt-2" :message="form.errors.address" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="timezone">Timezone</Label>
                    <Select v-model="form.timezone">
                        <SelectTrigger id="timezone" class="mt-1 w-full">
                            <SelectValue placeholder="Select a timezone" />
                        </SelectTrigger>
                        <SelectContent class="max-h-72">
                            <SelectItem
                                v-for="timezone in timezones"
                                :key="timezone"
                                :value="timezone"
                            >
                                {{ timezone }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError class="mt-2" :message="form.errors.timezone" />
                </div>

                <div class="grid gap-2">
                    <Label for="date_format">Date format</Label>
                    <Select v-model="form.date_format">
                        <SelectTrigger id="date_format" class="mt-1 w-full">
                            <SelectValue placeholder="Select a date format" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="(example, format) in dateFormats"
                                :key="format"
                                :value="format"
                            >
                                {{ example }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError
                        class="mt-2"
                        :message="form.errors.date_format"
                    />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="logo">Logo</Label>
                <div class="mt-1 flex items-center gap-4">
                    <img
                        v-if="logoPreview"
                        :src="logoPreview"
                        alt="School logo"
                        class="size-16 rounded-md border object-contain p-1"
                    />
                    <div
                        v-else
                        class="flex size-16 items-center justify-center rounded-md border text-xs text-muted-foreground"
                    >
                        No logo
                    </div>
                    <Input
                        id="logo"
                        type="file"
                        name="logo"
                        accept="image/jpeg,image/png,image/webp"
                        class="max-w-xs"
                        @change="handleLogoChange"
                    />
                </div>
                <p class="text-sm text-muted-foreground">
                    JPG, PNG or WebP, up to 2 MB.
                </p>
                <InputError class="mt-2" :message="form.errors.logo" />
            </div>

            <div class="flex items-center gap-4">
                <Button
                    :disabled="form.processing"
                    data-test="save-school-settings"
                >
                    Save
                </Button>
            </div>
        </form>
    </div>
</template>
