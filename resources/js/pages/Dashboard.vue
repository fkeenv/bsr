<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, ChevronDown, CircleHelp, HousePlus } from '@lucide/vue';
import { computed, nextTick, ref } from 'vue';
import OnboardingChecklist from '@/components/OnboardingChecklist.vue';
import { Button } from '@/components/ui/button';
import { useOnboardingTour } from '@/composables/useOnboardingTour';
import type { TourStep } from '@/composables/useTour';
import DashboardMembershipRowActions from '@/pages/DashboardMembershipRowActions.vue';
import { dashboard, joinProperty } from '@/routes';
import { store as completeOnboardingStep } from '@/routes/onboarding/steps';
import { edit as editPropertyProfile } from '@/routes/property-profile';
import { show as statementOfAccount } from '@/routes/statement-of-account';
import type { Membership } from '@/types/membership';
import type {
    MemberOnboardingStepKey,
    Onboarding,
    OnboardingChecklistItem,
} from '@/types/onboarding';

type Props = {
    memberships: Membership[];
    onboarding: Onboarding | null;
};

const props = defineProps<Props>();

const page = usePage();
const checklistOpen = ref(false);
const canJoinProperty = computed(
    () => page.props.auth.capabilities?.isSuperAdmin !== true,
);
const residentName = computed(() => page.props.auth.user?.name?.trim() ?? '');
const canViewStatement = computed(
    () => page.props.auth.capabilities?.isMembershipHolder === true,
);
const canUpdateProfile = computed(
    () =>
        canViewStatement.value ||
        page.props.auth.capabilities?.canAccessOfficer === true,
);
const hasMemberships = computed(() => props.memberships.length > 0);
const isAnnouncementsPageListed = computed(
    () => page.props.announcementsPageListed !== false,
);
const profilePropertyId = computed(
    () => props.memberships[0]?.property_id ?? null,
);

const checklistCopy: Record<
    MemberOnboardingStepKey,
    { title: string; description: string }
> = {
    announcements: {
        title: 'Read the Announcements',
        description: 'Notices the association posts for every Member.',
    },
    'statement-of-account': {
        title: 'Open your Statement of Account',
        description: 'Charges, Payments, and what your Property owes.',
    },
    'property-profile': {
        title: 'Save your Property profile',
        description: 'Household Members, Emergency Contacts, and Vehicles.',
    },
};

const checklistItems = computed((): OnboardingChecklistItem[] => {
    const onboarding = props.onboarding;

    if (onboarding === null) {
        return [];
    }

    return (onboarding.steps as MemberOnboardingStepKey[])
        .filter(
            (step) =>
                step !== 'announcements' || isAnnouncementsPageListed.value,
        )
        .filter(
            (step) =>
                step !== 'property-profile' || profilePropertyId.value !== null,
        )
        .map((step) => ({
            key: step,
            ...checklistCopy[step],
            isComplete: onboarding.completed_steps.includes(step),
            link:
                step === 'property-profile' && profilePropertyId.value !== null
                    ? { href: editPropertyProfile(profilePropertyId.value) }
                    : {
                          href: completeOnboardingStep('member'),
                          data: { step },
                          as: 'button',
                      },
        }));
});

const onboardingTour = useOnboardingTour({
    experience: 'member',
    onboarding: props.onboarding,
    steps: ({ isMobile, revealNavigationSection }): TourStep[] => {
        const firstMembership = props.memberships[0];
        const steps: TourStep[] = [
            {
                target: 'member-memberships',
                title: 'Your Properties',
                description:
                    firstMembership?.property_label === null ||
                    firstMembership === undefined
                        ? 'Each Property you belong to is listed here.'
                        : `You are a ${firstMembership.role} of ${firstMembership.property_label}. Every Property you belong to is listed here.`,
                side: 'bottom',
            },
        ];

        if (checklistItems.value.length > 0) {
            steps.push({
                target: 'member-checklist',
                title: 'Getting started',
                description:
                    'A short checklist to help you settle in. Open it whenever you need it.',
                side: 'bottom',
                prepare: async (signal) => {
                    if (!signal.aborted) {
                        checklistOpen.value = true;
                        await nextTick();
                    }
                },
            });
        }

        if (!isMobile) {
            steps.push({
                target: 'nav-membership',
                title: 'Membership menu',
                description: isAnnouncementsPageListed.value
                    ? 'Announcements and your Statement of Account are always here.'
                    : 'Your Statement of Account is always here.',
                side: 'right',
                prepare: revealNavigationSection('membership'),
            });
        }

        steps.push({
            target: 'member-help',
            title: 'Need a refresher?',
            description: 'Select Help any time to replay this tour.',
            side: 'bottom',
        });

        return steps;
    },
});

function trackChecklistOpen(event: Event): void {
    checklistOpen.value = (event.target as HTMLDetailsElement).open;
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <div
        class="resident-overview flex min-h-full flex-1 flex-col gap-9 px-6 py-7 md:px-10 md:py-10 lg:px-16 lg:py-12"
    >
        <Head title="Your Properties" />

        <header
            class="flex flex-col items-start justify-between gap-5 sm:flex-row"
        >
            <div class="min-w-0 space-y-3">
                <p
                    class="text-muted-foreground text-xs tracking-[0.08em] uppercase"
                >
                    Your community space
                </p>
                <h1
                    class="community-heading text-4xl leading-tight [overflow-wrap:anywhere] lg:text-[44px]"
                >
                    Welcome back<span v-if="residentName"
                        >, {{ residentName }}</span
                    >.
                </h1>
                <p class="text-muted-foreground text-base leading-6">
                    Your Properties and community essentials, all in one place.
                </p>
            </div>
            <Button
                v-if="hasMemberships"
                variant="outline"
                class="min-h-11 shrink-0 shadow-none"
                data-tour="member-help"
                :disabled="onboardingTour.isRunning.value"
                @click="onboardingTour.replay()"
            >
                <CircleHelp class="size-4" />
                Help
            </Button>
        </header>

        <p
            v-if="onboardingTour.acknowledgementStatus.value === 'saving'"
            role="status"
            class="text-muted-foreground text-sm"
        >
            Saving your tour preference…
        </p>
        <div
            v-else-if="onboardingTour.acknowledgementStatus.value === 'failed'"
            role="alert"
            class="flex flex-wrap items-center gap-3 rounded-lg border p-4 text-sm"
        >
            <p>We couldn’t save your tour preference. Please try again.</p>
            <Button
                variant="outline"
                @click="onboardingTour.retryAcknowledgement()"
                >Retry</Button
            >
        </div>

        <section
            data-tour="member-memberships"
            aria-labelledby="your-properties-heading"
            class="space-y-6"
        >
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 id="your-properties-heading" class="text-xl font-semibold">
                    Your Properties
                </h2>
                <Link
                    v-if="canJoinProperty && hasMemberships"
                    :href="joinProperty()"
                    class="text-primary focus-visible:ring-ring inline-flex min-h-11 items-center gap-2 rounded-md text-sm font-medium focus-visible:ring-2 focus-visible:outline-none"
                >
                    Join another Property
                    <ArrowRight class="size-4" aria-hidden="true" />
                </Link>
            </div>

            <div v-if="hasMemberships" class="grid gap-6">
                <article
                    v-for="membership in memberships"
                    :key="membership.id"
                    class="bg-card rounded-xl border p-6 md:p-8"
                    :aria-labelledby="`property-${membership.id}-heading`"
                >
                    <div
                        class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center"
                    >
                        <div class="min-w-0 space-y-2">
                            <p
                                class="text-muted-foreground text-xs tracking-wide uppercase"
                            >
                                Your Property
                            </p>
                            <h3
                                :id="`property-${membership.id}-heading`"
                                class="community-heading text-[28px] leading-tight [overflow-wrap:anywhere] md:text-[32px]"
                            >
                                {{
                                    membership.property_label ||
                                    `Property ${membership.property_id}`
                                }}
                            </h3>
                        </div>
                        <span
                            class="bg-accent text-primary shrink-0 rounded-full px-3.5 py-2 text-sm"
                        >
                            {{
                                membership.role.charAt(0).toUpperCase() +
                                membership.role.slice(1)
                            }}
                            Membership
                        </span>
                    </div>
                    <div
                        class="mt-6 flex flex-col items-stretch gap-3 border-t pt-5 sm:flex-row sm:flex-wrap sm:items-center"
                    >
                        <Button
                            v-if="canViewStatement"
                            as-child
                            class="h-auto min-h-11 px-5 py-3 text-[15px] whitespace-normal"
                        >
                            <Link
                                :href="
                                    statementOfAccount(membership.property_id)
                                "
                            >
                                View Statement of Account
                                <ArrowRight class="size-4" aria-hidden="true" />
                            </Link>
                        </Button>
                        <Button
                            v-if="canUpdateProfile"
                            as-child
                            variant="ghost"
                            class="text-primary h-auto min-h-11 whitespace-normal"
                        >
                            <Link
                                :href="
                                    editPropertyProfile(membership.property_id)
                                "
                                >Update Property Profile
                                <ArrowRight class="size-4" aria-hidden="true"
                            /></Link>
                        </Button>
                        <DashboardMembershipRowActions
                            v-if="
                                canViewStatement &&
                                membership.user_id === page.props.auth.user?.id
                            "
                            :membership="membership"
                            class="sm:ml-auto"
                        />
                    </div>
                </article>
            </div>

            <div v-else class="bg-card space-y-4 rounded-xl border p-6 md:p-8">
                <h3 class="community-heading text-2xl">
                    A place for your Properties
                </h3>
                <template v-if="canJoinProperty">
                    <p class="text-muted-foreground max-w-xl leading-7">
                        You have not joined a Property yet. Ask an Officer for a
                        Property Invitation, then open the invitation link to
                        join as an owner or resident.
                    </p>
                    <Button as-child class="h-auto min-h-11 whitespace-normal">
                        <Link :href="joinProperty()"
                            ><HousePlus class="size-4" />Join a Property</Link
                        >
                    </Button>
                </template>
                <p v-else class="text-muted-foreground max-w-xl leading-7">
                    You have no live Memberships. Your association tools are in
                    the navigation. Properties you hold a Membership for will
                    appear here.
                </p>
            </div>
        </section>

        <details
            v-if="checklistItems.length > 0"
            :open="checklistOpen"
            class="group/getting-started border-y py-6"
            data-tour="member-checklist"
            @toggle="trackChecklistOpen"
        >
            <summary
                class="focus-visible:ring-ring flex cursor-pointer list-none flex-col items-start justify-between gap-4 rounded-sm focus-visible:ring-2 focus-visible:outline-none sm:flex-row sm:items-center"
            >
                <span class="space-y-2">
                    <span class="block text-lg font-semibold"
                        >Make yourself at home</span
                    >
                    <span
                        class="text-muted-foreground block text-[15px] leading-6"
                        >A short guide to your community space.</span
                    >
                </span>
                <span
                    class="text-primary inline-flex min-h-11 items-center gap-2 text-sm font-medium"
                >
                    {{
                        checklistOpen
                            ? 'Hide getting started'
                            : 'Continue getting started'
                    }}
                    <ChevronDown
                        class="size-4 transition-transform group-open/getting-started:rotate-180"
                        aria-hidden="true"
                    />
                </span>
            </summary>
            <OnboardingChecklist
                class="mt-6 border-0 bg-transparent shadow-none"
                title="Getting started"
                :items="checklistItems"
            />
        </details>

        <section v-if="canJoinProperty && hasMemberships" class="space-y-3">
            <h2 class="community-heading text-[26px]">
                Joining another Property?
            </h2>
            <p class="text-muted-foreground max-w-xl leading-7">
                Ask an Officer for a Property Invitation. Open the invitation
                link to add an owner or resident Membership.
            </p>
        </section>
    </div>
</template>

<style scoped>
.resident-overview {
    --background: #f7f6f2;
    --foreground: #252c29;
    --card: #ffffff;
    --card-foreground: #252c29;
    --primary: #365847;
    --primary-foreground: #ffffff;
    --muted-foreground: #626a62;
    --accent: #e8ece4;
    --accent-foreground: #365847;
    --border: #ddded5;
    --ring: #365847;
    background: var(--background);
    color: var(--foreground);
}

.dark .resident-overview {
    --background: #202824;
    --foreground: #e6eae3;
    --card: #28322c;
    --card-foreground: #e6eae3;
    --primary: #c1d5c3;
    --primary-foreground: #202824;
    --muted-foreground: #bac5bc;
    --accent: #34443a;
    --accent-foreground: #d8e4d8;
    --border: #405047;
    --ring: #a3c6ad;
}

summary::-webkit-details-marker {
    display: none;
}

.community-heading {
    font-family: Georgia, serif;
    font-weight: 400;
}
</style>
