<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowRight, Home, Link2 } from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { store as lookupInvitationCode } from '@/routes/property-invitation-codes';
import { joinProperty } from '@/routes';
import { show as showPropertyInvitation } from '@/routes/property-invitations';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Join a Property',
                href: joinProperty(),
            },
        ],
    },
});

const codeForm = useForm({ code: '' });
const openCode = () => {
    codeForm.code = codeForm.code.trim().toLowerCase();
    if (
        !/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/.test(
            codeForm.code,
        )
    ) {
        codeForm.setError(
            'code',
            'Enter the full invitation code you received.',
        );
        return;
    }
    codeForm.clearErrors();
    codeForm.post(lookupInvitationCode.url(), {
        onSuccess: () => codeForm.reset(),
    });
};

const invitationLink = ref('');
const invitationLinkError = ref<string | null>(null);

const openInvitation = () => {
    const token = invitationLink.value
        .trim()
        .match(/(?:^|\/property-invitations\/)([A-Za-z0-9]{64})\/?$/)?.[1];

    if (token === undefined) {
        invitationLinkError.value =
            'Paste the full invitation link an Officer sent you.';

        return;
    }

    invitationLinkError.value = null;
    router.visit(showPropertyInvitation(token));
};
</script>

<template>
    <Head title="Join a Property" />

    <div class="flex w-full flex-col gap-6 p-4">
        <div class="mx-auto flex w-full max-w-2xl flex-col gap-6">
            <Heading
                title="Join a Property"
                description="Memberships start from a Property Invitation sent by an Officer."
            />

            <Card>
                <CardHeader>
                    <div class="flex gap-3">
                        <div
                            class="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-md"
                        >
                            <Home class="size-5" />
                        </div>
                        <div>
                            <CardTitle>Have an invitation?</CardTitle>
                            <CardDescription>
                                Open a link or enter a code below to review the
                                Property and accept the invitation. If you do
                                not have one, ask an Officer to invite you.
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <form
                        class="flex flex-col gap-3"
                        @submit.prevent="openInvitation"
                    >
                        <div class="grid gap-2">
                            <Label for="invitation_link">Invitation link</Label>
                            <div class="relative">
                                <Link2
                                    class="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2"
                                />
                                <Input
                                    id="invitation_link"
                                    v-model="invitationLink"
                                    class="pl-9"
                                    autocomplete="off"
                                    placeholder="https://…/property-invitations/…"
                                />
                            </div>
                            <InputError
                                :message="invitationLinkError ?? undefined"
                            />
                        </div>

                        <Button
                            type="submit"
                            class="w-full sm:w-auto sm:self-start"
                        >
                            Open invitation
                            <ArrowRight class="size-4" />
                        </Button>
                    </form>
                    <Separator class="my-6" />
                    <form
                        class="flex flex-col gap-3"
                        @submit.prevent="openCode"
                    >
                        <div class="grid gap-2">
                            <Label for="invitation_code">Invitation code</Label>
                            <Input
                                id="invitation_code"
                                v-model="codeForm.code"
                                autocomplete="off"
                                autocapitalize="none"
                                spellcheck="false"
                                placeholder="xxxxxxxx-xxxx-4xxx-xxxx-xxxxxxxxxxxx"
                                class="font-mono text-sm"
                                :aria-invalid="!!codeForm.errors.code"
                            />
                            <InputError :message="codeForm.errors.code" />
                        </div>
                        <Button
                            type="submit"
                            class="w-full sm:w-auto sm:self-start"
                            :disabled="codeForm.processing"
                            >Review invitation code <ArrowRight class="size-4"
                        /></Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
