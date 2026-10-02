import { useHttp } from '@inertiajs/vue3';
import { nextTick, onMounted, ref } from 'vue';
import { useSidebar } from '@/components/ui/sidebar';
import { useNavigationSection } from '@/composables/useNavigationSection';
import { useTour } from '@/composables/useTour';
import type { TourStep } from '@/composables/useTour';
import { store as acknowledgeOnboardingTour } from '@/routes/onboarding/tour-acknowledgement';
import type { Onboarding, OnboardingExperience } from '@/types/onboarding';

const SIDEBAR_TRANSITION_MS = 250;

/**
 * Runs an onboarding experience's tour: once automatically until it is
 * acknowledged, and again on demand from Help, restoring the sidebar after.
 */
export function useOnboardingTour(options: {
    experience: OnboardingExperience;
    onboarding: Onboarding | null;
    steps: () => TourStep[];
}) {
    const sidebar = useSidebar();
    const { openNavigationSection } = useNavigationSection();
    const tour = useTour();
    const tourAcknowledgement = useHttp(
        acknowledgeOnboardingTour(options.experience),
        {},
    );
    const isTourAcknowledged = ref(
        options.onboarding?.tour_acknowledged ?? true,
    );

    const revealNavigationSection =
        (sectionId: string) => async (): Promise<void> => {
            sidebar.setOpen(true);
            openNavigationSection.value = sectionId;
            await nextTick();
            await new Promise((resolve) =>
                setTimeout(resolve, SIDEBAR_TRANSITION_MS),
            );
        };

    const run = (acknowledge: boolean): void => {
        const previousSidebarOpen = sidebar.open.value;
        const previousSection = openNavigationSection.value;

        void tour.start(options.steps(), {
            onDismiss: () => {
                if (!acknowledge || isTourAcknowledged.value) {
                    return;
                }

                isTourAcknowledged.value = true;
                void tourAcknowledgement.submit();
            },
            onEnd: () => {
                if (!sidebar.isMobile.value) {
                    sidebar.setOpen(previousSidebarOpen);
                }

                openNavigationSection.value = previousSection;
            },
        });
    };

    onMounted(() => {
        if (options.onboarding !== null && !isTourAcknowledged.value) {
            run(true);
        }
    });

    return {
        isRunning: tour.isRunning,
        isMobile: sidebar.isMobile,
        replay: () => run(false),
        revealNavigationSection,
    };
}
