import type { InertiaLinkProps } from '@inertiajs/vue3';

export type OnboardingExperience = 'member' | 'officer';

export type MemberOnboardingStepKey =
    | 'announcements'
    | 'statement-of-account'
    | 'property-profile';

export type OfficerOnboardingStepKey =
    | 'officer-invitations'
    | 'officer-properties'
    | 'officer-memberships'
    | 'officer-payments'
    | 'officer-charges'
    | 'officer-announcements';

export type OnboardingStepKey =
    | MemberOnboardingStepKey
    | OfficerOnboardingStepKey;

export type Onboarding = {
    experience: OnboardingExperience;
    version: number;
    tour_acknowledged: boolean;
    steps: OnboardingStepKey[];
    completed_steps: OnboardingStepKey[];
};

export type OnboardingChecklistItem = {
    key: OnboardingStepKey;
    title: string;
    description: string;
    isComplete: boolean;
    link: Pick<InertiaLinkProps, 'href' | 'data' | 'as'>;
};
