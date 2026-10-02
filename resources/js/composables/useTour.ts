import { router } from '@inertiajs/vue3';
import { driver } from 'driver.js';
import type { Driver, DriveStep } from 'driver.js';
import 'driver.js/dist/driver.css';
import { onBeforeUnmount, readonly, ref } from 'vue';

export type TourStep = {
    target: string;
    title: string;
    description: string;
    side?: 'top' | 'right' | 'bottom' | 'left';
    prepare?: () => void | Promise<void>;
};

export type TourOptions = {
    onDismiss?: () => void;
    onEnd?: () => void;
};

function prefersReducedMotion(): boolean {
    return (
        typeof window !== 'undefined' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches
    );
}

function targetSelector(target: string): string {
    return `[data-tour="${target}"]`;
}

/**
 * The application's only entry point to Driver.js. Tours target elements by
 * their `data-tour` attribute and are torn down on navigation or unmount.
 */
export function useTour() {
    const isRunning = ref(false);
    let activeDriver: Driver | null = null;
    let removeNavigationListener: (() => void) | null = null;

    const destroy = (): void => {
        removeNavigationListener?.();
        removeNavigationListener = null;
        activeDriver?.destroy();
        activeDriver = null;
    };

    const start = async (
        steps: TourStep[],
        options: TourOptions = {},
    ): Promise<void> => {
        destroy();

        const availableSteps = steps.filter(
            (step) =>
                step.prepare !== undefined ||
                document.querySelector(targetSelector(step.target)) !== null,
        );

        if (availableSteps.length === 0) {
            return;
        }

        const dismiss = (): void => {
            options.onDismiss?.();
            destroy();
        };

        const moveTo = async (index: number): Promise<void> => {
            const step = availableSteps[index];

            if (step === undefined || activeDriver === null) {
                dismiss();

                return;
            }

            await step.prepare?.();
            activeDriver?.moveTo(index);
        };

        const driveSteps: DriveStep[] = availableSteps.map((step, index) => ({
            element: targetSelector(step.target),
            popover: {
                title: step.title,
                description: step.description,
                side: step.side,
                onNextClick: () => void moveTo(index + 1),
                onPrevClick: () => void moveTo(index - 1),
            },
        }));

        const animate = !prefersReducedMotion();

        activeDriver = driver({
            steps: driveSteps,
            animate,
            smoothScroll: animate,
            allowKeyboardControl: true,
            showProgress: true,
            popoverClass: 'app-tour-popover',
            nextBtnText: 'Next',
            prevBtnText: 'Back',
            doneBtnText: 'Done',
            onDestroyStarted: dismiss,
            onDestroyed: () => {
                isRunning.value = false;
                options.onEnd?.();
            },
        });

        removeNavigationListener = router.on('start', destroy);

        await availableSteps[0].prepare?.();
        isRunning.value = true;
        activeDriver.drive();
    };

    onBeforeUnmount(destroy);

    return {
        isRunning: readonly(isRunning),
        start,
        destroy,
    };
}
