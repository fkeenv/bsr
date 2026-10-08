<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    CalendarClock,
    ClipboardList,
    HousePlus,
    KeyRound,
    LayoutGrid,
    MailPlus,
    Megaphone,
    Pause,
    Receipt,
    Settings2,
    Shield,
    ShieldCheck,
    Users,
} from '@lucide/vue';
import { AccordionRoot } from 'reka-ui';
import { computed, onMounted, onUnmounted, watch } from 'vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useNavigationSection } from '@/composables/useNavigationSection';
import { dashboard, home, joinProperty } from '@/routes';
import { edit as propertyProfile } from '@/routes/property-profile';
import { dashboard as administratorDashboard } from '@/routes/administrator';
import { index as officersIndex } from '@/routes/administrator/officers';
import { index as announcementsIndex } from '@/routes/announcements';
import { dashboard as officerDashboard } from '@/routes/officer';
import { index as officerAnnouncementsIndex } from '@/routes/officer/announcements';
import { edit as billSettingsEdit } from '@/routes/officer/bill-settings';
import { index as chargesIndex } from '@/routes/officer/charges';
import { create as generateCharges } from '@/routes/officer/charges/generate';
import { index as feeTypesIndex } from '@/routes/officer/fee-types';
import { edit as levySettingsEdit } from '@/routes/officer/levy-settings';
import { index as membershipsIndex } from '@/routes/officer/memberships';
import { index as paymentsIndex } from '@/routes/officer/payments';
import { index as propertyInvitationsIndex } from '@/routes/officer/property-invitations';
import { index as propertiesIndex } from '@/routes/officer/properties';
import { index as suspendsIndex } from '@/routes/officer/suspends';
import { index as unpaidIndex } from '@/routes/officer/unpaid';
import {
    index as statementOfAccountIndex,
    show as statementOfAccountShow,
} from '@/routes/statement-of-account';
import { dashboard as superAdminDashboard } from '@/routes/super-admin';
import { index as administratorsIndex } from '@/routes/super-admin/administrators';
import { edit as editPrivacyPolicy } from '@/routes/super-admin/privacy-policy';
import { edit as editTermsOfService } from '@/routes/super-admin/terms-of-service';
import type { NavItem, NavSection } from '@/types';

const page = usePage();
const { currentUrl, isCurrentOrParentUrl, isCurrentUrl } = useCurrentUrl();
const capabilities = computed(() => page.props.auth.capabilities);
const announcementsPageListed = computed(
    () => page.props.announcementsPageListed !== false,
);

const homeHref = computed(() => {
    if (!capabilities.value) {
        return home();
    }

    return dashboard();
});

const platformNavItems = computed((): NavItem[] => {
    if (!capabilities.value) {
        return [
            {
                title: 'Announcements',
                href: announcementsIndex(),
                icon: Megaphone,
            },
        ];
    }

    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];

    if (!capabilities.value.isSuperAdmin) {
        items.push({
            title: 'Join a Property',
            href: joinProperty(),
            icon: HousePlus,
        });
    }

    if (!capabilities.value.isMembershipHolder) {
        items.push({
            title: 'Announcements',
            href: announcementsIndex(),
            icon: Megaphone,
        });
    }

    return items;
});

const superAdminNavItems = computed((): NavItem[] => {
    if (!capabilities.value?.isSuperAdmin) {
        return [];
    }

    return [
        {
            title: 'Super Admin',
            href: superAdminDashboard(),
            icon: KeyRound,
        },
        {
            title: 'Administrators',
            href: administratorsIndex(),
            icon: ShieldCheck,
        },
        {
            title: 'Terms of Service',
            href: editTermsOfService(),
            icon: BookOpen,
        },
        {
            title: 'Privacy Policy',
            href: editPrivacyPolicy(),
            icon: BookOpen,
        },
    ];
});

const officerNavItems = computed((): NavItem[] => {
    if (!capabilities.value?.canAccessOfficer) {
        return [];
    }

    return [
        {
            title: 'Officer',
            href: officerDashboard(),
            icon: Shield,
        },
        {
            title: 'Announcements',
            href: officerAnnouncementsIndex(),
            icon: Megaphone,
        },
        {
            title: 'Unpaid',
            group: 'collection',
            href: unpaidIndex(),
            icon: Receipt,
        },
        {
            title: 'Memberships',
            group: 'people',
            href: membershipsIndex(),
            icon: Users,
        },
        {
            title: 'Invitations',
            group: 'people',
            href: propertyInvitationsIndex(),
            icon: MailPlus,
        },
        {
            title: 'Payments',
            group: 'collection',
            href: paymentsIndex(),
            icon: Receipt,
        },
        {
            title: 'Properties',
            group: 'people',
            href: propertiesIndex(),
            icon: ClipboardList,
        },
        {
            title: 'Fee Types',
            group: 'settings',
            href: feeTypesIndex(),
            icon: Receipt,
        },
        {
            title: 'Suspends',
            group: 'people',
            href: suspendsIndex(),
            icon: Pause,
        },
        {
            title: 'Charges',
            group: 'collection',
            href: chargesIndex(),
            icon: ClipboardList,
        },
        {
            title: 'Generate Charges',
            group: 'collection',
            href: generateCharges(),
            icon: CalendarClock,
        },
        {
            title: 'Levy day',
            group: 'settings',
            href: levySettingsEdit(),
            icon: Settings2,
        },
        {
            title: 'Printed Bill',
            group: 'settings',
            href: billSettingsEdit(),
            icon: Receipt,
        },
    ];
});

const administratorNavItems = computed((): NavItem[] => {
    if (!capabilities.value?.canAccessAdministrator) {
        return [];
    }

    return [
        {
            title: 'Administrator',
            href: administratorDashboard(),
            icon: ShieldCheck,
        },
        {
            title: 'Officers',
            href: officersIndex(),
            icon: Users,
        },
    ];
});

const membershipNavItems = computed((): NavItem[] => {
    if (!capabilities.value?.isMembershipHolder) {
        return [];
    }

    const items: NavItem[] = [
        {
            title: 'Membership',
            href: dashboard(),
            icon: Users,
        },
    ];

    if (announcementsPageListed.value) {
        items.push({
            title: 'Announcements',
            href: announcementsIndex(),
            icon: Megaphone,
        });
    }

    items.push({
        title: 'Statement of Account',
        href: statementOfAccountIndex(),
        icon: Receipt,
    });

    return items;
});

const navSections = computed<NavSection[]>(() => {
    return [
        {
            id: 'platform',
            title: 'Getting around',
            icon: LayoutGrid,
            items: platformNavItems.value,
        },
        {
            id: 'super-admin',
            title: 'Super Admin',
            icon: KeyRound,
            items: superAdminNavItems.value,
        },
        {
            id: 'officer',
            title: 'Officer',
            icon: Shield,
            items: officerNavItems.value,
            activeRoutePatterns: [propertyProfile.definition.url],
        },
        {
            id: 'administrator',
            title: 'Administrator',
            icon: ShieldCheck,
            items: administratorNavItems.value,
        },
        {
            id: 'membership',
            title: 'My community',
            icon: Users,
            items: membershipNavItems.value,
            activeRoutePatterns: [
                statementOfAccountShow.definition.url,
                ...(!capabilities.value?.canAccessOfficer
                    ? [propertyProfile.definition.url]
                    : []),
            ],
        },
    ].filter((section) => section.items.length > 0);
});

function matchesRoutePattern(
    routePattern: string,
    currentPath: string,
): boolean {
    const routeSegments = routePattern.split('/');
    const currentSegments = currentPath.split('/');

    return (
        routeSegments.length === currentSegments.length &&
        routeSegments.every(
            (segment, index) =>
                (segment.startsWith('{') && segment.endsWith('}')) ||
                segment === currentSegments[index],
        )
    );
}

const activeSectionId = computed<string | undefined>(() => {
    const sectionsInPriorityOrder = [...navSections.value].reverse();
    const exactMatch = sectionsInPriorityOrder.find((section) =>
        section.items.some((item) => isCurrentUrl(item.href)),
    );

    if (exactMatch) {
        return exactMatch.id;
    }

    const routePatternMatch = sectionsInPriorityOrder.find((section) =>
        section.activeRoutePatterns?.some((routePattern) =>
            matchesRoutePattern(routePattern, currentUrl.value),
        ),
    );

    if (routePatternMatch) {
        return routePatternMatch.id;
    }

    return (
        sectionsInPriorityOrder.find((section) =>
            section.items.some((item) => isCurrentOrParentUrl(item.href)),
        )?.id ?? navSections.value[0]?.id
    );
});

const { openNavigationSection: openSection } = useNavigationSection();

watch(
    [currentUrl, navSections],
    () => {
        openSection.value = activeSectionId.value;
    },
    { immediate: true },
);

const { isMobile, setOpenMobile } = useSidebar();
const removeNavigationListeners: (() => void)[] = [];

onMounted(() => {
    const closeMobileNavigation = () => {
        if (isMobile.value) {
            setOpenMobile(false);
        }
    };

    removeNavigationListeners.push(
        router.on('start', closeMobileNavigation),
        router.on('navigate', closeMobileNavigation),
    );
});

onUnmounted(() => {
    removeNavigationListeners.forEach((removeListener) => removeListener());
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" class="association-navigation">
        <SidebarHeader
            class="px-4 pt-7 pb-5 group-data-[collapsible=icon]:px-2"
        >
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        size="lg"
                        as-child
                        class="h-auto px-0 py-2 hover:bg-transparent [&>span:last-child]:whitespace-normal"
                    >
                        <Link
                            :href="homeHref"
                            aria-label="Blessed Sacrament Residences home"
                        >
                            <span
                                class="border-sidebar-border text-sidebar-primary flex hidden size-8 shrink-0 items-center justify-center rounded-lg border font-serif text-sm group-data-[collapsible=icon]:flex"
                                aria-hidden="true"
                                >BSR</span
                            >
                            <span
                                class="flex flex-col gap-2 group-data-[collapsible=icon]:hidden"
                            >
                                <span
                                    class="text-sidebar-primary [font-family:Georgia,serif] text-[25px] leading-[30px]"
                                    >Blessed Sacrament<br />Residences</span
                                >
                                <span
                                    class="text-sidebar-foreground text-xs font-normal tracking-wide"
                                    >Homeowners Association</span
                                >
                            </span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <AccordionRoot v-model="openSection" type="single" as-child>
            <SidebarContent class="gap-3 px-2">
                <NavMain
                    v-for="section in navSections"
                    :key="section.id"
                    :section="section"
                    :is-active="activeSectionId === section.id"
                />
            </SidebarContent>
        </AccordionRoot>

        <SidebarFooter
            class="border-sidebar-border mx-4 border-t px-0 py-4 group-data-[collapsible=icon]:mx-2"
        >
            <NavUser />
        </SidebarFooter>
        <slot />
    </Sidebar>
</template>
