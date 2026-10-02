<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { CircleCheck, Circle, ClipboardCheck } from '@lucide/vue';
import { computed } from 'vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { store as completeOnboardingStep } from '@/routes/onboarding/steps';
import { edit as editPropertyProfile } from '@/routes/property-profile';
import type { Onboarding, OnboardingStepKey } from '@/types/onboarding';

const props = defineProps<{
    onboarding: Onboarding;
    profilePropertyId: number | null;
}>();

const page = usePage();

const stepCopy: Record<
    OnboardingStepKey,
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

const items = computed(() =>
    props.onboarding.steps
        .filter(
            (step) =>
                step !== 'announcements' ||
                page.props.announcementsPageListed !== false,
        )
        .filter(
            (step) =>
                step !== 'property-profile' || props.profilePropertyId !== null,
        )
        .map((step) => ({
            key: step,
            ...stepCopy[step],
            isComplete: props.onboarding.completed_steps.includes(step),
        })),
);

const linkProps = (step: OnboardingStepKey) =>
    step === 'property-profile' && props.profilePropertyId !== null
        ? { href: editPropertyProfile(props.profilePropertyId) }
        : {
              href: completeOnboardingStep('member'),
              data: { step },
              as: 'button' as const,
          };

const completedCount = computed(
    () => items.value.filter((item) => item.isComplete).length,
);
</script>

<template>
    <Card data-tour="member-checklist">
        <CardHeader>
            <div class="flex items-start gap-3">
                <div
                    class="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-md"
                >
                    <ClipboardCheck class="size-5" />
                </div>
                <div>
                    <CardTitle>Getting started</CardTitle>
                    <CardDescription>
                        {{ completedCount }} of {{ items.length }} done
                    </CardDescription>
                </div>
            </div>
        </CardHeader>
        <CardContent>
            <ul class="grid gap-2">
                <li v-for="item in items" :key="item.key">
                    <Link
                        v-bind="linkProps(item.key)"
                        class="hover:bg-muted/40 focus-visible:ring-ring flex w-full items-start gap-3 rounded-md border p-3 text-left focus-visible:ring-[3px] focus-visible:outline-none"
                    >
                        <CircleCheck
                            v-if="item.isComplete"
                            class="text-primary mt-0.5 size-4 shrink-0"
                            aria-hidden="true"
                        />
                        <Circle
                            v-else
                            class="text-muted-foreground mt-0.5 size-4 shrink-0"
                            aria-hidden="true"
                        />
                        <span class="grid gap-0.5 text-sm">
                            <span class="font-medium">
                                {{ item.title }}
                                <span class="sr-only">
                                    {{ item.isComplete ? '(done)' : '(to do)' }}
                                </span>
                            </span>
                            <span class="text-muted-foreground">
                                {{ item.description }}
                            </span>
                        </span>
                    </Link>
                </li>
            </ul>
        </CardContent>
    </Card>
</template>
