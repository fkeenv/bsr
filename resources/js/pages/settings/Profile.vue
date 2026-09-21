<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useInitials } from '@/composables/useInitials';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Profile settings',
                href: edit(),
            },
        ],
    },
});

const page = usePage();
const { getInitials } = useInitials();
const user = computed(() => {
    const current = page.props.auth.user;

    if (!current) {
        throw new Error(
            'Profile settings require an authenticated User Account.',
        );
    }

    return current;
});
</script>

<template>
    <Head title="Profile settings" />

    <h1 class="sr-only">Profile settings</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Profile"
            description="Update your name, email address, mobile number, title, and avatar"
        />

        <Form
            v-bind="ProfileController.update.form()"
            enctype="multipart/form-data"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="flex items-center gap-4">
                <Avatar class="size-16 overflow-hidden rounded-full">
                    <AvatarImage
                        v-if="user.avatar"
                        :src="user.avatar"
                        :alt="user.name"
                    />
                    <AvatarFallback
                        class="bg-neutral-200 text-lg font-semibold text-black dark:bg-neutral-700 dark:text-white"
                    >
                        {{ getInitials(user.name) }}
                    </AvatarFallback>
                </Avatar>
                <div class="grid flex-1 gap-2">
                    <Label for="avatar">Avatar</Label>
                    <Input
                        id="avatar"
                        class="mt-1 block w-full"
                        name="avatar"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                    />
                    <p class="text-muted-foreground text-sm">
                        Optional. Without an upload, your initials are shown
                        (for example, Keen Vergara → KV).
                    </p>
                    <InputError class="mt-2" :message="errors.avatar" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    class="mt-1 block w-full"
                    name="name"
                    :default-value="user.name"
                    required
                    autocomplete="name"
                    placeholder="Full name"
                />
                <InputError class="mt-2" :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="title">Title</Label>
                <Input
                    id="title"
                    class="mt-1 block w-full"
                    name="title"
                    :default-value="user.title ?? ''"
                    autocomplete="organization-title"
                    placeholder="e.g. Treasurer"
                />
                <p class="text-muted-foreground text-sm">
                    Optional display title. Not a platform role.
                </p>
                <InputError class="mt-2" :message="errors.title" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    name="email"
                    :default-value="user.email"
                    required
                    autocomplete="username"
                    placeholder="Email address"
                />
                <InputError class="mt-2" :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="mobile_number">Mobile number</Label>
                <Input
                    id="mobile_number"
                    type="tel"
                    class="mt-1 block w-full"
                    name="mobile_number"
                    :default-value="user.mobile_number ?? ''"
                    autocomplete="tel"
                    placeholder="Mobile number"
                />
                <InputError class="mt-2" :message="errors.mobile_number" />
            </div>

            <div v-if="page.props.mustVerifyEmail && !user.email_verified_at">
                <p class="text-muted-foreground -mt-4 text-sm">
                    Your email address is unverified.
                    <Link
                        :href="send()"
                        as="button"
                        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-if="page.props.status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing" data-test="update-profile-button"
                    >Save</Button
                >
            </div>
        </Form>
    </div>

    <DeleteUser />
</template>
