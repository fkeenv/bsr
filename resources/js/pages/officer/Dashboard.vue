<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { CircleHelp } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import OnboardingChecklist from '@/components/OnboardingChecklist.vue';
import { Button } from '@/components/ui/button';
import { useOnboardingTour } from '@/composables/useOnboardingTour';
import type { TourStep } from '@/composables/useTour';
import { dashboard as officerDashboard } from '@/routes/officer';
import { store as completeOnboardingStep } from '@/routes/onboarding/steps';
import type {
    OfficerOnboardingStepKey,
    Onboarding,
    OnboardingChecklistItem,
} from '@/types/onboarding';

const props = defineProps<{
    onboarding: Onboarding | null;
}>();

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
        description: 'Declarations waiting for you, and who still owes.',
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
    steps: (): TourStep[] => {
        const steps: TourStep[] = [
            {
                target: 'officer-checklist',
                title: 'Your Officer orientation',
                description:
                    'Open each area once to see where things live. Nothing here changes money, roles, or published notices.',
                side: 'bottom',
            },
        ];

        if (!onboardingTour.isMobile.value) {
            const revealOfficerNavigation =
                onboardingTour.revealNavigationSection('officer');

            steps.push(
                {
                    target: 'nav-officer-invitations',
                    title: 'Households',
                    description:
                        'Invitations, Memberships, and the Properties roster: who lives where and how they join.',
                    side: 'right',
                    prepare: revealOfficerNavigation,
                },
                {
                    target: 'nav-officer-payments',
                    title: 'Money',
                    description:
                        'Payments, Unpaid, and Charges: what is owed, declared, and confirmed.',
                    side: 'right',
                    prepare: revealOfficerNavigation,
                },
                {
                    target: 'nav-officer-announcements',
                    title: 'Announcements',
                    description: 'Draft and publish notices for every Member.',
                    side: 'right',
                    prepare: revealOfficerNavigation,
                },
            );
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
    <Head title="Officer" />

    <div class="flex flex-col space-y-6 p-4">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <Heading
                title="Officer"
                description="Manage the roster, dues, Payments, and Announcements for the association."
            />

            <Button
                variant="outline"
                class="self-start"
                data-tour="officer-help"
                :disabled="onboardingTour.isRunning.value"
                @click="onboardingTour.replay()"
            >
                <CircleHelp class="size-4" />
                Help
            </Button>
        </div>

        <OnboardingChecklist
            v-if="checklistItems.length > 0"
            data-tour="officer-checklist"
            title="Officer orientation"
            :items="checklistItems"
        />
    </div>
</template>
