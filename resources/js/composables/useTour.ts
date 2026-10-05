import { router } from '@inertiajs/vue3';
import { driver } from 'driver.js';
import type { Driver, DriveStep } from 'driver.js';
import 'driver.js/dist/driver.css';
import { nextTick, onBeforeUnmount, readonly, ref } from 'vue';
import { useSidebar } from '@/components/ui/sidebar';
import { useNavigationSection } from '@/composables/useNavigationSection';

const SIDEBAR_TRANSITION_MS = 250;

type TourEndReason =
    | 'dismissed'
    | 'cancelled'
    | 'replaced'
    | 'failed'
    | 'navigation'
    | 'unmount';

export type TourStep = {
    target: string;
    title: string;
    description: string;
    side?: 'top' | 'right' | 'bottom' | 'left';
    prepare?: (signal: AbortSignal) => void | Promise<void>;
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
    const sidebar = useSidebar();
    const { openNavigationSection } = useNavigationSection();
    const isRunning = ref(false);
    let activeSession: {
        controller: AbortController;
        driver: Driver | null;
        removeNavigationListener: (() => void) | null;
        onEnd: (() => void) | undefined;
        sidebarState: { open: boolean; section: string | undefined } | null;
    } | null = null;

    const end = (
        session: NonNullable<typeof activeSession>,
        reason: TourEndReason,
    ): void => {
        if (session.controller.signal.aborted) {
            return;
        }

        session.controller.abort();
        session.removeNavigationListener?.();
        session.removeNavigationListener = null;

        if (activeSession === session) {
            activeSession = null;
            isRunning.value = false;
        }

        session.driver?.destroy();
        if (session.sidebarState !== null) {
            if (!sidebar.isMobile.value) {
                sidebar.setOpen(session.sidebarState.open);
            }

            if (reason !== 'navigation' && reason !== 'unmount') {
                openNavigationSection.value = session.sidebarState.section;
            }
        }
        session.onEnd?.();
    };

    const destroy = (): void => {
        if (activeSession !== null) {
            end(activeSession, 'cancelled');
        }
    };

    const start = async (
        steps: TourStep[],
        options: TourOptions = {},
    ): Promise<void> => {
        if (activeSession !== null) {
            end(activeSession, 'replaced');
        }

        const availableSteps = steps.filter(
            (step) =>
                step.prepare !== undefined ||
                document.querySelector(targetSelector(step.target)) !== null,
        );

        if (availableSteps.length === 0) {
            return;
        }

        const session: NonNullable<typeof activeSession> = {
            controller: new AbortController(),
            driver: null,
            removeNavigationListener: null,
            onEnd: options.onEnd,
            sidebarState: null,
        };
        activeSession = session;
        isRunning.value = true;

        const isCurrent = (): boolean =>
            activeSession === session && !session.controller.signal.aborted;

        const prepare = async (step: TourStep): Promise<boolean> => {
            if (!isCurrent()) {
                return false;
            }

            const signal = session.controller.signal;
            let removeAbortListener = (): void => {};
            const cancelled = new Promise<void>((resolve) => {
                const onAbort = (): void => resolve();
                signal.addEventListener('abort', onAbort, { once: true });
                removeAbortListener = () =>
                    signal.removeEventListener('abort', onAbort);
            });

            try {
                await Promise.race([
                    Promise.resolve().then(() =>
                        isCurrent() ? step.prepare?.(signal) : undefined,
                    ),
                    cancelled,
                ]);

                return isCurrent();
            } finally {
                removeAbortListener();
            }
        };

        const dismiss = (): void => {
            if (!isCurrent()) {
                return;
            }

            end(session, 'dismissed');
            options.onDismiss?.();
        };

        let isMoving = false;

        const moveTo = async (index: number): Promise<void> => {
            if (!isCurrent() || isMoving) {
                return;
            }

            const step = availableSteps[index];

            if (step === undefined) {
                dismiss();

                return;
            }

            isMoving = true;

            try {
                if ((await prepare(step)) && isCurrent()) {
                    session.driver?.moveTo(index);
                }
            } catch {
                end(session, 'failed');
            } finally {
                isMoving = false;
            }
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

        try {
            session.driver = driver({
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
                onDestroyed: () => end(session, 'cancelled'),
            });
            session.removeNavigationListener = router.on('start', () =>
                end(session, 'navigation'),
            );

            if ((await prepare(availableSteps[0])) && isCurrent()) {
                session.driver.drive();
            }
        } catch {
            end(session, 'failed');
        }
    };

    const revealNavigationSection =
        (sectionId: string) =>
        async (signal: AbortSignal): Promise<void> => {
            const session = activeSession;

            if (
                session === null ||
                session.controller.signal !== signal ||
                signal.aborted
            ) {
                return;
            }

            session.sidebarState ??= {
                open: sidebar.open.value,
                section: openNavigationSection.value,
            };
            sidebar.setOpen(true);
            openNavigationSection.value = sectionId;
            await nextTick();

            if (signal.aborted) {
                return;
            }

            await new Promise<void>((resolve) => {
                const finish = (): void => {
                    clearTimeout(timeout);
                    signal.removeEventListener('abort', finish);
                    resolve();
                };
                const timeout = setTimeout(finish, SIDEBAR_TRANSITION_MS);
                signal.addEventListener('abort', finish, { once: true });
            });
        };

    onBeforeUnmount(() => {
        if (activeSession !== null) {
            end(activeSession, 'unmount');
        }
    });

    return {
        isRunning: readonly(isRunning),
        isMobile: readonly(sidebar.isMobile),
        revealNavigationSection,
        start,
        destroy,
    };
}
