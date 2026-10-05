<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { CircleHelp, HousePlus } from '@lucide/vue';
import { computed } from 'vue';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import OnboardingChecklist from '@/components/OnboardingChecklist.vue';
import { Button } from '@/components/ui/button';
import { useOnboardingTour } from '@/composables/useOnboardingTour';
import type { TourStep } from '@/composables/useTour';
import { dashboardMembershipColumns } from '@/pages/dashboard-membership-columns';
import { dashboard, joinProperty } from '@/routes';
import { store as completeOnboardingStep } from '@/routes/onboarding/steps';
import { edit as editPropertyProfile } from '@/routes/property-profile';
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
const canJoinProperty = computed(
    () => page.props.auth.capabilities?.isSuperAdmin !== true,
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
            {
                target: 'member-checklist',
                title: 'Getting started',
                description:
                    'A short checklist to help you settle in. It stays here until each item is done.',
                side: 'bottom',
            },
        ];

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
    <Head title="Dashboard" />

    <div class="flex flex-col space-y-6 p-4">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <Heading
                title="Dashboard"
                description="Your live Memberships. You may end one yourself, or join another Property with an invitation link."
            />

            <Button
                v-if="hasMemberships"
                variant="outline"
                class="self-start"
                data-tour="member-help"
                :disabled="onboardingTour.isRunning.value"
                @click="onboardingTour.replay()"
            >
                <CircleHelp class="size-4" />
                Help
            </Button>
        </div>

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
            class="flex flex-wrap items-center gap-3 rounded-md border p-4 text-sm"
        >
            <p>We couldn’t save your tour preference. Please try again.</p>
            <Button
                variant="outline"
                @click="onboardingTour.retryAcknowledgement()"
            >
                Retry
            </Button>
        </div>

        <div
            v-if="!hasMemberships && canJoinProperty"
            class="flex flex-col items-start gap-3 rounded-md border border-dashed p-6"
        >
            <div class="grid gap-1">
                <h2 class="text-base font-medium">
                    You have not joined a Property yet
                </h2>
                <p class="text-muted-foreground text-sm">
                    Ask an Officer for a Property Invitation, then open the link
                    to join.
                </p>
            </div>
            <Button as-child>
                <Link :href="joinProperty()">
                    <HousePlus class="size-4" />
                    Join a Property
                </Link>
            </Button>
        </div>

        <template v-else>
            <OnboardingChecklist
                v-if="checklistItems.length > 0"
                data-tour="member-checklist"
                title="Getting started"
                :items="checklistItems"
            />

            <div data-tour="member-memberships">
                <DataTable
                    :columns="dashboardMembershipColumns"
                    :data="memberships"
                    :action="dashboard.url()"
                    empty-text="You have no live Memberships."
                />
            </div>
        </template>
    </div>
</template>
