import { useHttp } from '@inertiajs/vue3';
import { nextTick, onBeforeUnmount, onMounted, readonly, ref } from 'vue';
import { useSidebar } from '@/components/ui/sidebar';
import { useNavigationSection } from '@/composables/useNavigationSection';
import { useTour } from '@/composables/useTour';
import type { TourStep } from '@/composables/useTour';
import { store as acknowledgeOnboardingTour } from '@/routes/onboarding/tour-acknowledgement';
import type { Onboarding, OnboardingExperience } from '@/types/onboarding';

const SIDEBAR_TRANSITION_MS = 250;

export type OnboardingTourContext = {
    isMobile: boolean;
    revealNavigationSection: (sectionId: string) => () => Promise<void>;
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
    const sidebar = useSidebar();
    const { openNavigationSection } = useNavigationSection();
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

    const revealNavigationSection =
        (sectionId: string) => async (): Promise<void> => {
            sidebar.setOpen(true);
            openNavigationSection.value = sectionId;
            await nextTick();
            await new Promise((resolve) =>
                setTimeout(resolve, SIDEBAR_TRANSITION_MS),
            );
        };

    const run = (shouldAcknowledge: boolean): void => {
        const previousSidebarOpen = sidebar.open.value;
        const previousSection = openNavigationSection.value;

        void tour.start(
            options.steps({
                isMobile: sidebar.isMobile.value,
                revealNavigationSection,
            }),
            {
                onDismiss: () => {
                    if (!shouldAcknowledge) {
                        return;
                    }

                    void acknowledge();
                },
                onEnd: () => {
                    if (!sidebar.isMobile.value) {
                        sidebar.setOpen(previousSidebarOpen);
                    }

                    openNavigationSection.value = previousSection;
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
