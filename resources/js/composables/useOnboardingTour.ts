import { useHttp } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, readonly, ref } from 'vue';
import { useTour } from '@/composables/useTour';
import type { TourStep } from '@/composables/useTour';
import { store as acknowledgeOnboardingTour } from '@/routes/onboarding/tour-acknowledgement';
import type { Onboarding, OnboardingExperience } from '@/types/onboarding';

export type OnboardingTourContext = {
    isMobile: boolean;
    revealNavigationSection: (
        sectionId: string,
    ) => (signal: AbortSignal) => Promise<void>;
};

/**
 * Runs an onboarding experience's tour: once automatically until it is
 * acknowledged, and again on demand from Help, restoring the sidebar after.
 */
export function useOnboardingTour(options: {
    experience: OnboardingExperience;
    onboarding: Onboarding | null;
    steps: (context: OnboardingTourContext) => TourStep[];
}) {
    const tour = useTour();
    const tourAcknowledgement = useHttp(
        acknowledgeOnboardingTour(options.experience),
        {},
    );
    const acknowledgementStatus = ref<
        'unacknowledged' | 'saving' | 'acknowledged' | 'failed'
    >(
        (options.onboarding?.tour_acknowledged ?? true)
            ? 'acknowledged'
            : 'unacknowledged',
    );

    const acknowledge = async (): Promise<void> => {
        if (
            acknowledgementStatus.value === 'acknowledged' ||
            acknowledgementStatus.value === 'saving'
        ) {
            return;
        }

        acknowledgementStatus.value = 'saving';

        try {
            await tourAcknowledgement.submit({
                onSuccess: () => {
                    acknowledgementStatus.value = 'acknowledged';
                },
                onError: () => {
                    acknowledgementStatus.value = 'failed';
                },
            });
        } catch {
            acknowledgementStatus.value = 'failed';
        }
    };

    onBeforeUnmount(() => tourAcknowledgement.cancel());

    const run = (shouldAcknowledge: boolean): void => {
        void tour.start(
            options.steps({
                isMobile: tour.isMobile.value,
                revealNavigationSection: tour.revealNavigationSection,
            }),
            {
                onDismiss: () => {
                    if (!shouldAcknowledge) {
                        return;
                    }

                    void acknowledge();
                },
            },
        );
    };

    onMounted(() => {
        if (
            options.onboarding !== null &&
            acknowledgementStatus.value === 'unacknowledged'
        ) {
            run(true);
        }
    });

    return {
        isRunning: tour.isRunning,
        acknowledgementStatus: readonly(acknowledgementStatus),
        retryAcknowledgement: () => void acknowledge(),
        replay: () => run(false),
    };
}
