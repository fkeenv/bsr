<script setup lang="ts">
import { Head, Link, useHttp, usePage } from '@inertiajs/vue3';
import { CircleHelp, HousePlus } from '@lucide/vue';
import { computed, nextTick, onMounted, ref } from 'vue';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import MemberOnboardingChecklist from '@/components/MemberOnboardingChecklist.vue';
import { Button } from '@/components/ui/button';
import { useSidebar } from '@/components/ui/sidebar';
import { useNavigationSection } from '@/composables/useNavigationSection';
import { useTour } from '@/composables/useTour';
import type { TourStep } from '@/composables/useTour';
import { dashboardMembershipColumns } from '@/pages/dashboard-membership-columns';
import { dashboard, joinProperty } from '@/routes';
import { store as acknowledgeOnboardingTour } from '@/routes/onboarding/tour-acknowledgement';
import type { Membership } from '@/types/membership';
import type { Onboarding } from '@/types/onboarding';

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

const sidebar = useSidebar();
const { openNavigationSection } = useNavigationSection();
const tour = useTour();
const tourAcknowledgement = useHttp(acknowledgeOnboardingTour('member'), {});
const isTourAcknowledged = ref(props.onboarding?.tour_acknowledged ?? true);

const memberTourSteps = (): TourStep[] => {
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

    if (!sidebar.isMobile.value) {
        steps.push({
            target: 'nav-membership',
            title: 'Membership menu',
            description:
                'Announcements and your Statement of Account are always here.',
            side: 'right',
            prepare: async () => {
                sidebar.setOpen(true);
                openNavigationSection.value = 'membership';
                await nextTick();
                await new Promise((resolve) => setTimeout(resolve, 250));
            },
        });
    }

    steps.push({
        target: 'member-help',
        title: 'Need a refresher?',
        description: 'Select Help any time to replay this tour.',
        side: 'bottom',
    });

    return steps;
};

const runMemberTour = (options: { acknowledge: boolean }) => {
    const previousSidebarOpen = sidebar.open.value;
    const previousSection = openNavigationSection.value;

    void tour.start(memberTourSteps(), {
        onDismiss: () => {
            if (!options.acknowledge || isTourAcknowledged.value) {
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
    if (props.onboarding !== null && !isTourAcknowledged.value) {
        runMemberTour({ acknowledge: true });
    }
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
                :disabled="tour.isRunning.value"
                @click="runMemberTour({ acknowledge: false })"
            >
                <CircleHelp class="size-4" />
                Help
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
            <MemberOnboardingChecklist
                v-if="onboarding !== null"
                :onboarding="onboarding"
                :profile-property-id="memberships[0]?.property_id ?? null"
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
