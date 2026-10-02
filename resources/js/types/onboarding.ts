export type OnboardingStepKey =
    | 'announcements'
    | 'statement-of-account'
    | 'property-profile';

export type Onboarding = {
    experience: 'member';
    version: number;
    tour_acknowledged: boolean;
    steps: OnboardingStepKey[];
    completed_steps: OnboardingStepKey[];
};
