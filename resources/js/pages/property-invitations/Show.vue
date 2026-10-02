<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { CalendarClock, CircleCheck, Home, ShieldCheck } from '@lucide/vue';
import PropertyInvitationRedemptionController from '@/actions/App/Http/Controllers/PropertyInvitationRedemptionController';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { show as showPrivacyPolicy } from '@/routes/privacy-policy';
import { show as showTermsOfService } from '@/routes/terms-of-service';

type Invitation = {
    property_label: string;
    role: 'owner' | 'resident';
    expires_at: string;
    terms_of_service_version_id: number;
    privacy_policy_version_id: number;
};

const props = defineProps<{
    token: string;
    invitation: Invitation;
}>();

const form = useForm({
    accept_terms: false,
    accept_privacy: false,
    terms_of_service_version_id: props.invitation.terms_of_service_version_id,
    privacy_policy_version_id: props.invitation.privacy_policy_version_id,
});

const redemptionErrors = computed(
    () =>
        form.errors as Partial<
            Record<'invitation' | 'legal_documents', string>
        >,
);

const submit = () => {
    form.transform((data) => ({
        ...data,
        accept_terms: data.accept_terms ? '1' : '0',
        accept_privacy: data.accept_privacy ? '1' : '0',
    })).post(PropertyInvitationRedemptionController.store.url(props.token), {
        preserveScroll: true,
        onFinish: () => {
            form.transform((data) => data);
        },
    });
};

const formatDateTime = (value: string): string =>
    new Intl.DateTimeFormat('en-PH', {
        dateStyle: 'long',
        timeStyle: 'short',
        timeZone: 'Asia/Manila',
    }).format(new Date(value));
</script>

<template>
    <Head title="Confirm Property Invitation" />

    <div class="flex w-full flex-col gap-6 p-4">
        <div class="mx-auto flex w-full max-w-2xl flex-col gap-6">
            <Heading
                title="Confirm Property Invitation"
                description="Review the Property and Membership role before accepting this invitation."
            />

            <Card>
                <CardHeader>
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex gap-3">
                            <div
                                class="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-md"
                            >
                                <Home class="size-5" />
                            </div>
                            <div>
                                <CardTitle>{{
                                    invitation.property_label
                                }}</CardTitle>
                                <CardDescription>
                                    This invitation grants an immediate
                                    Membership.
                                </CardDescription>
                            </div>
                        </div>
                        <Badge variant="secondary" class="capitalize">
                            {{ invitation.role }}
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent class="flex flex-col gap-4">
                    <div
                        class="text-muted-foreground flex items-center gap-2 text-sm"
                    >
                        <CalendarClock class="size-4" />
                        Expires {{ formatDateTime(invitation.expires_at) }}
                    </div>

                    <div class="bg-muted/50 flex gap-3 rounded-md border p-4">
                        <ShieldCheck
                            class="text-primary mt-0.5 size-5 shrink-0"
                        />
                        <p
                            class="text-muted-foreground text-sm leading-relaxed"
                        >
                            Redemption requires accepting the current
                            <Link
                                :href="showTermsOfService()"
                                class="text-foreground font-medium underline underline-offset-4"
                                target="_blank"
                            >
                                Terms of Service
                            </Link>
                            and
                            <Link
                                :href="showPrivacyPolicy()"
                                class="text-foreground font-medium underline underline-offset-4"
                                target="_blank"
                            >
                                Privacy Policy </Link
                            >.
                        </p>
                    </div>

                    <form class="flex flex-col gap-3" @submit.prevent="submit">
                        <label
                            class="hover:bg-muted/30 flex cursor-pointer items-start gap-3 rounded-md border p-3"
                        >
                            <input
                                id="accept_terms"
                                v-model="form.accept_terms"
                                type="checkbox"
                                class="border-input text-primary focus-visible:ring-ring accent-primary mt-0.5 size-4 shrink-0 rounded-[4px] border shadow-xs focus-visible:ring-[3px] focus-visible:outline-none"
                            />
                            <span class="grid gap-1 text-sm">
                                <span>
                                    I accept the
                                    <Link
                                        :href="showTermsOfService()"
                                        class="underline"
                                        target="_blank"
                                        @click.stop
                                    >
                                        Terms of Service
                                    </Link>
                                </span>
                                <InputError
                                    :message="form.errors.accept_terms"
                                />
                            </span>
                        </label>

                        <label
                            class="hover:bg-muted/30 flex cursor-pointer items-start gap-3 rounded-md border p-3"
                        >
                            <input
                                id="accept_privacy"
                                v-model="form.accept_privacy"
                                type="checkbox"
                                class="border-input text-primary focus-visible:ring-ring accent-primary mt-0.5 size-4 shrink-0 rounded-[4px] border shadow-xs focus-visible:ring-[3px] focus-visible:outline-none"
                            />
                            <span class="grid gap-1 text-sm">
                                <span>
                                    I accept the
                                    <Link
                                        :href="showPrivacyPolicy()"
                                        class="underline"
                                        target="_blank"
                                        @click.stop
                                    >
                                        Privacy Policy
                                    </Link>
                                </span>
                                <InputError
                                    :message="form.errors.accept_privacy"
                                />
                            </span>
                        </label>

                        <InputError
                            :message="
                                redemptionErrors.invitation ??
                                redemptionErrors.legal_documents ??
                                form.errors.terms_of_service_version_id ??
                                form.errors.privacy_policy_version_id
                            "
                        />

                        <Button
                            type="submit"
                            class="w-full sm:w-auto sm:self-start"
                            :disabled="form.processing"
                        >
                            <CircleCheck class="size-4" />
                            Accept invitation
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
