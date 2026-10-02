<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
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
                            <CardTitle>Have an invitation link?</CardTitle>
                            <CardDescription>
                                Open it, or paste it below, to review the
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
                </CardContent>
            </Card>
        </div>
    </div>
</template>
