<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, ChevronDown, CircleHelp } from '@lucide/vue';
import { computed, nextTick, ref } from 'vue';
import OnboardingChecklist from '@/components/OnboardingChecklist.vue';
import { Button } from '@/components/ui/button';
import { useOnboardingTour } from '@/composables/useOnboardingTour';
import type { TourStep } from '@/composables/useTour';
import { dashboard as officerDashboard } from '@/routes/officer';
import {
    create as newAnnouncement,
    index as announcements,
} from '@/routes/officer/announcements';
import { index as charges } from '@/routes/officer/charges';
import { index as memberships } from '@/routes/officer/memberships';
import { index as payments } from '@/routes/officer/payments';
import { index as properties } from '@/routes/officer/properties';
import { index as propertyInvitations } from '@/routes/officer/property-invitations';
import { index as unpaidRoster } from '@/routes/officer/unpaid';
import { store as completeOnboardingStep } from '@/routes/onboarding/steps';
import type {
    OfficerOnboardingStepKey,
    Onboarding,
    OnboardingChecklistItem,
} from '@/types/onboarding';

const props = defineProps<{
    onboarding: Onboarding | null;
}>();

const page = usePage();
const orientationOpen = ref(false);
const officerName = computed(() => page.props.auth.user?.name?.trim() ?? '');
const workspaceGroups = [
    {
        id: 'collection',
        title: 'Payments & collection',
        description: 'Keep records clear and follow up on balances.',
        links: [
            { title: 'Review Payments', href: payments() },
            { title: 'Open the Unpaid roster', href: unpaidRoster() },
            { title: 'View Charges', href: charges() },
        ],
    },
    {
        id: 'people',
        title: 'Properties & people',
        description: 'Keep the roster and Memberships up to date.',
        links: [
            { title: 'Browse Properties', href: properties() },
            {
                title: 'Issue a Property Invitation',
                href: propertyInvitations(),
            },
            { title: 'Manage Memberships', href: memberships() },
        ],
    },
];

const checklistCopy: Record<
    OfficerOnboardingStepKey,
    { title: string; description: string }
> = {
    'officer-invitations': {
        title: 'Look at Invitations',
        description: 'How new Members join a Property.',
    },
    'officer-properties': {
        title: 'Browse the Properties roster',
        description: 'Every house, its block and lot, and its status.',
    },
    'officer-memberships': {
        title: 'Review Memberships',
        description: 'Who belongs to which Property, as owner or resident.',
    },
    'officer-payments': {
        title: 'Check Payments and Unpaid',
        description:
            'Declarations waiting for you. Unpaid sits beside it in the menu.',
    },
    'officer-charges': {
        title: 'See the Charges',
        description: 'What each Property is levied every Billing Period.',
    },
    'officer-announcements': {
        title: 'Visit Announcements',
        description: 'Where you draft and publish notices for Members.',
    },
};

const checklistItems = computed((): OnboardingChecklistItem[] => {
    const onboarding = props.onboarding;

    if (onboarding === null) {
        return [];
    }

    return (onboarding.steps as OfficerOnboardingStepKey[]).map((step) => ({
        key: step,
        ...checklistCopy[step],
        isComplete: onboarding.completed_steps.includes(step),
        link: {
            href: completeOnboardingStep('officer'),
            data: { step },
            as: 'button',
        },
    }));
});

const onboardingTour = useOnboardingTour({
    experience: 'officer',
    onboarding: props.onboarding,
    steps: ({ isMobile, revealNavigationSection }): TourStep[] => {
        const steps: TourStep[] = [
            {
                target: 'officer-workspace',
                title: 'Your association workspace',
                description:
                    'Return here for Payments and collection, or Properties and people. Each shortcut opens an existing workspace.',
                side: 'bottom',
            },
        ];

        if (checklistItems.value.length > 0) {
            steps.push({
                target: 'officer-checklist',
                title: 'Your Officer orientation',
                description:
                    'Open each area once to see where things live. Nothing here changes money, roles, or published notices.',
                side: 'bottom',
                prepare: async (signal) => {
                    if (!signal.aborted) {
                        orientationOpen.value = true;
                        await nextTick();
                    }
                },
            });
        }

        if (!isMobile) {
            steps.push({
                target: 'nav-officer',
                title: 'Officer menu',
                description:
                    'Households: Properties, Invitations, and Memberships. Money: Payments, Unpaid, and Charges. Notices: Announcements.',
                side: 'right',
                prepare: revealNavigationSection('officer'),
            });
        }

        steps.push({
            target: 'officer-help',
            title: 'Need a refresher?',
            description: 'Select Help any time to replay this tour.',
            side: 'bottom',
        });

        return steps;
    },
});

function trackOrientationOpen(event: Event): void {
    orientationOpen.value = (event.target as HTMLDetailsElement).open;
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Officer',
                href: officerDashboard(),
            },
        ],
    },
});
</script>

<template>
    <div
        class="flex min-h-full min-w-0 flex-1 flex-col gap-8 px-6 py-7 md:px-10 md:py-10 lg:px-16 lg:py-12"
    >
        <Head title="Association workspace" />

        <header
            class="flex flex-col items-start justify-between gap-6 sm:flex-row"
        >
            <div class="min-w-0 space-y-3.5">
                <p
                    class="text-muted-foreground text-[13px] tracking-[0.08em] uppercase"
                >
                    Association workspace
                </p>
                <h1
                    class="font-serif text-4xl leading-tight font-normal [overflow-wrap:anywhere] lg:text-[44px]"
                >
                    Hello<span v-if="officerName">, {{ officerName }}</span
                    >. Where shall we start?
                </h1>
                <p class="text-muted-foreground text-base leading-7">
                    A few clear paths to look after the community.
                </p>
            </div>
            <Button
                variant="outline"
                class="h-auto min-h-11 shrink-0 px-5"
                data-tour="officer-help"
                :disabled="onboardingTour.isRunning.value"
                @click="onboardingTour.replay()"
            >
                <CircleHelp class="size-4" aria-hidden="true" />
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
            class="border-destructive/30 bg-destructive/5 flex flex-col items-start gap-3 rounded-lg border p-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <p class="text-sm">
                We couldn’t save your tour preference. You can retry without
                replaying the tour.
            </p>
            <Button
                variant="outline"
                class="min-h-11 shrink-0"
                @click="onboardingTour.retryAcknowledgement()"
                >Retry</Button
            >
        </div>

        <section
            aria-labelledby="announcements-heading"
            class="bg-accent flex flex-col items-start justify-between gap-6 rounded-xl p-6 md:p-8 xl:flex-row xl:items-center"
        >
            <div class="min-w-0 space-y-3">
                <p class="text-primary text-[13px] tracking-[0.08em] uppercase">
                    Keep the community informed
                </p>
                <h2
                    id="announcements-heading"
                    class="font-serif text-[28px] leading-tight font-normal md:text-[32px]"
                >
                    Something to share?
                </h2>
                <p class="text-muted-foreground text-base leading-7">
                    Write an Announcement, or pick up a shared draft.
                </p>
            </div>
            <div
                class="flex w-full flex-col items-stretch gap-3 sm:w-auto sm:items-center"
            >
                <Button
                    as-child
                    class="h-auto min-h-11 px-6 py-3 whitespace-normal"
                >
                    <Link :href="newAnnouncement()"
                        >New draft
                        <ArrowRight class="size-4" aria-hidden="true"
                    /></Link>
                </Button>
                <Button
                    as-child
                    variant="link"
                    class="h-auto min-h-11 whitespace-normal"
                >
                    <Link :href="announcements()">View Announcements</Link>
                </Button>
            </div>
        </section>

        <div
            data-tour="officer-workspace"
            class="grid gap-9 lg:grid-cols-2 lg:gap-11"
        >
            <section
                v-for="group in workspaceGroups"
                :key="group.id"
                :aria-labelledby="`${group.id}-heading`"
                class="min-w-0 space-y-4"
            >
                <h2
                    :id="`${group.id}-heading`"
                    class="font-serif text-[27px] leading-tight font-normal"
                >
                    {{ group.title }}
                </h2>
                <p class="text-muted-foreground text-[15px] leading-6">
                    {{ group.description }}
                </p>
                <ul class="border-t">
                    <li
                        v-for="link in group.links"
                        :key="link.title"
                        class="border-b"
                    >
                        <Link
                            :href="link.href"
                            class="text-primary hover:bg-accent/50 focus-visible:ring-ring flex min-h-14 items-center justify-between gap-3 rounded-sm py-4 text-[15px] leading-6 focus-visible:ring-2 focus-visible:outline-none"
                        >
                            <span class="min-w-0 [overflow-wrap:anywhere]">{{
                                link.title
                            }}</span>
                            <ArrowRight
                                class="size-5 shrink-0"
                                aria-hidden="true"
                            />
                        </Link>
                    </li>
                </ul>
            </section>
        </div>

        <details
            v-if="checklistItems.length > 0"
            :open="orientationOpen"
            data-tour="officer-checklist"
            class="group/orientation border-t py-6"
            @toggle="trackOrientationOpen"
        >
            <summary
                class="focus-visible:ring-ring flex cursor-pointer list-none flex-col items-start justify-between gap-4 rounded-sm focus-visible:ring-2 focus-visible:outline-none sm:flex-row sm:items-center"
            >
                <span class="space-y-2">
                    <span class="block text-base font-semibold"
                        >New to Officer duties?</span
                    >
                    <span
                        class="text-muted-foreground block text-[15px] leading-6"
                        >Explore the workspace with a short guided
                        orientation.</span
                    >
                </span>
                <span
                    class="text-primary inline-flex min-h-11 items-center gap-2 text-sm font-medium"
                >
                    {{
                        orientationOpen
                            ? 'Hide orientation'
                            : 'Continue orientation'
                    }}
                    <ChevronDown
                        class="size-4 shrink-0 transition-transform group-open/orientation:rotate-180"
                        aria-hidden="true"
                    />
                </span>
            </summary>
            <OnboardingChecklist
                class="mt-6 border-0 bg-transparent shadow-none"
                title="Officer orientation"
                :items="checklistItems"
            />
        </details>
    </div>
</template>

<style scoped>
summary::-webkit-details-marker {
    display: none;
}
</style>
