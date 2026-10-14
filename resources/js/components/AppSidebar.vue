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
import { Accordion } from '@/components/ui/accordion';
import { computed, onMounted, onUnmounted, watch } from 'vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarGroup,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useStaffPermissions } from '@/composables/useStaffPermissions';
import { useAnnouncementPermissions } from '@/composables/useAnnouncementPermissions';
import { useNavigationSection } from '@/composables/useNavigationSection';
import { dashboard, home, joinProperty } from '@/routes';
import { edit as propertyProfile } from '@/routes/property-profile';
import { dashboard as administratorDashboard } from '@/routes/administrator';
import { index as userAccountsIndex } from '@/routes/administrator/user-accounts';
import { index as eventCheckInIndex } from '@/routes/officer/event-check-in';
import { index as eventFinancesIndex } from '@/routes/officer/event-finances';
import { index as myRegistrationsIndex } from '@/routes/event-registrations';
import { index as staffRegistrationsIndex } from '@/routes/officer/event-registrations';
import { index as eventsIndex } from '@/routes/events';
import { index as manageEventsIndex } from '@/routes/officer/events';
import { index as announcementsIndex } from '@/routes/announcements';
import { dashboard as officerDashboard } from '@/routes/officer';
import {
    create as createAnnouncement,
    index as officerAnnouncementsIndex,
} from '@/routes/officer/announcements';
import { edit as billSettingsEdit } from '@/routes/officer/bill-settings';
import { index as chargesIndex } from '@/routes/officer/charges';
import { create as generateCharges } from '@/routes/officer/charges/generate';
import {
    create as createFeeType,
    index as feeTypesIndex,
} from '@/routes/officer/fee-types';
import { edit as levySettingsEdit } from '@/routes/officer/levy-settings';
import { index as membershipsIndex } from '@/routes/officer/memberships';
import {
    create as createPayment,
    index as paymentsIndex,
} from '@/routes/officer/payments';
import { create as createInvitation } from '@/routes/officer/property-invitations';
import { create as createProperty } from '@/routes/officer/properties';
import { create as importProperties } from '@/routes/officer/properties/import';
import { create as createSuspend } from '@/routes/officer/suspends';
import { index as propertyInvitationsIndex } from '@/routes/officer/property-invitations';
import { index as propertiesIndex } from '@/routes/officer/properties';
import { index as suspendsIndex } from '@/routes/officer/suspends';
import { index as unpaidIndex } from '@/routes/officer/unpaid';
import {
    index as statementOfAccountIndex,
    show as statementOfAccountShow,
} from '@/routes/statement-of-account';
import { dashboard as superAdminDashboard } from '@/routes/super-admin';
import { index as accessHistoryIndex } from '@/routes/super-admin/access-history';
import { index as rolePermissionsIndex } from '@/routes/super-admin/role-permissions';
import { edit as editPrivacyPolicy } from '@/routes/super-admin/privacy-policy';
import { edit as editTermsOfService } from '@/routes/super-admin/terms-of-service';
import type { NavItem, NavSection } from '@/types';

const page = usePage();
const { allows } = useAnnouncementPermissions();
const { allows: allowsStaff } = useStaffPermissions();
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
            { title: 'Events', href: eventsIndex(), icon: CalendarClock },
            {
                title: 'Announcements',
                href: announcementsIndex(),
                icon: Megaphone,
            },
        ];
    }

    const items: NavItem[] = [
        {
            title: 'My registrations',
            href: myRegistrationsIndex(),
            icon: CalendarClock,
        },
        { title: 'Events', href: eventsIndex(), icon: CalendarClock },
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
            title: 'User Accounts',
            href: userAccountsIndex(),
            icon: ShieldCheck,
        },
        {
            title: 'Access history',
            href: accessHistoryIndex(),
            icon: BookOpen,
        },
        {
            title: 'Roles & permissions',
            href: rolePermissionsIndex(),
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
        ...(allows('announcements.read')
            ? [
                  {
                      title: 'Announcements',
                      href: officerAnnouncementsIndex(),
                      icon: Megaphone,
                  },
              ]
            : allows('announcements.create')
              ? [
                    {
                        title: 'New Announcement',
                        href: createAnnouncement(),
                        icon: Megaphone,
                    },
                ]
              : []),
        ...(allowsStaff('events.registrations.read')
            ? [
                  {
                      title: 'Event registrations',
                      href: staffRegistrationsIndex(),
                      icon: CalendarClock,
                  },
              ]
            : []),
        ...(allowsStaff('events.payments.review') ||
        allowsStaff('events.refunds.review')
            ? [
                  {
                      title: 'Event payments & refunds',
                      href: eventFinancesIndex(),
                      icon: CalendarClock,
                  },
              ]
            : []),
        ...(allowsStaff('events.check-in')
            ? [
                  {
                      title: 'Event check-in',
                      href: eventCheckInIndex(),
                      icon: CalendarClock,
                  },
              ]
            : []),
        ...(allowsStaff('events.manage')
            ? [
                  {
                      title: 'Events',
                      href: manageEventsIndex(),
                      icon: CalendarClock,
                  },
              ]
            : []),
        ...(allowsStaff('unpaid.read')
            ? [
                  {
                      title: 'Unpaid',
                      group: 'collection' as const,
                      href: unpaidIndex(),
                      icon: Receipt,
                  },
              ]
            : []),
        ...(allowsStaff('memberships.read')
            ? [
                  {
                      title: 'Memberships',
                      group: 'people' as const,
                      href: membershipsIndex(),
                      icon: Users,
                  },
              ]
            : []),
        ...(allowsStaff('invitations.read')
            ? [
                  {
                      title: 'Invitations',
                      group: 'people' as const,
                      href: propertyInvitationsIndex(),
                      icon: MailPlus,
                  },
              ]
            : allowsStaff('invitations.create')
              ? [
                    {
                        title: 'New Invitation',
                        group: 'people' as const,
                        href: createInvitation(),
                        icon: MailPlus,
                    },
                ]
              : []),
        ...(allowsStaff('payments.read')
            ? [
                  {
                      title: 'Payments',
                      group: 'collection' as const,
                      href: paymentsIndex(),
                      icon: Receipt,
                  },
              ]
            : allowsStaff('payments.create')
              ? [
                    {
                        title: 'Record Payment',
                        group: 'collection' as const,
                        href: createPayment(),
                        icon: Receipt,
                    },
                ]
              : []),
        ...(allowsStaff('properties.read')
            ? [
                  {
                      title: 'Properties',
                      group: 'people' as const,
                      href: propertiesIndex(),
                      icon: ClipboardList,
                  },
              ]
            : allowsStaff('properties.create')
              ? [
                    {
                        title: 'New Property',
                        group: 'people' as const,
                        href: createProperty(),
                        icon: ClipboardList,
                    },
                ]
              : allowsStaff('properties.import') ||
                  allowsStaff('properties.import-sample')
                ? [
                      {
                          title: 'Property import',
                          group: 'people' as const,
                          href: importProperties(),
                          icon: ClipboardList,
                      },
                  ]
                : []),
        ...(allowsStaff('fee-types.read')
            ? [
                  {
                      title: 'Fee Types',
                      group: 'settings' as const,
                      href: feeTypesIndex(),
                      icon: Receipt,
                  },
              ]
            : allowsStaff('fee-types.create')
              ? [
                    {
                        title: 'New Fee Type',
                        group: 'settings' as const,
                        href: createFeeType(),
                        icon: Receipt,
                    },
                ]
              : []),
        ...(allowsStaff('suspends.read')
            ? [
                  {
                      title: 'Suspends',
                      group: 'people' as const,
                      href: suspendsIndex(),
                      icon: Pause,
                  },
              ]
            : allowsStaff('suspends.create')
              ? [
                    {
                        title: 'New Suspend',
                        group: 'people' as const,
                        href: createSuspend(),
                        icon: ClipboardList,
                    },
                ]
              : []),
        ...(allowsStaff('charges.read')
            ? [
                  {
                      title: 'Charges',
                      group: 'collection' as const,
                      href: chargesIndex(),
                      icon: ClipboardList,
                  },
              ]
            : []),
        ...(allowsStaff('charges.generate')
            ? [
                  {
                      title: 'Generate Charges',
                      group: 'collection' as const,
                      href: generateCharges(),
                      icon: CalendarClock,
                  },
              ]
            : []),
        ...(allowsStaff('levy-settings.read') ||
        allowsStaff('levy-settings.edit')
            ? [
                  {
                      title: 'Levy day',
                      group: 'settings' as const,
                      href: levySettingsEdit(),
                      icon: Settings2,
                  },
              ]
            : []),
        ...(allowsStaff('bill-settings.read') ||
        allowsStaff('bill-settings.edit')
            ? [
                  {
                      title: 'Printed Bill',
                      group: 'settings' as const,
                      href: billSettingsEdit(),
                      icon: Receipt,
                  },
              ]
            : []),
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
        ...(!capabilities.value?.isSuperAdmin
            ? [
                  {
                      title: 'User Accounts',
                      href: userAccountsIndex(),
                      icon: Users,
                  },
              ]
            : []),
    ];
});

const membershipNavItems = computed((): NavItem[] => {
    if (!capabilities.value?.isMembershipHolder) {
        return [];
    }

    const items: NavItem[] = [
        {
            title: 'My registrations',
            href: myRegistrationsIndex(),
            icon: CalendarClock,
        },
        { title: 'Events', href: eventsIndex(), icon: CalendarClock },
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
    <Sidebar collapsible="icon">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link
                            :href="homeHref"
                            aria-label="Blessed Sacrament Residences home"
                            title="Blessed Sacrament Residences Homeowners Association"
                        >
                            <div
                                class="bg-sidebar-primary text-sidebar-primary-foreground flex aspect-square size-8 items-center justify-center rounded-lg text-xs font-semibold"
                                aria-hidden="true"
                            >
                                BSR
                            </div>
                            <div
                                class="grid flex-1 text-left text-sm leading-tight"
                            >
                                <span class="truncate font-semibold"
                                    >Blessed Sacrament Residences</span
                                >
                                <span class="truncate text-xs"
                                    >Homeowners Association</span
                                >
                            </div>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>
        <SidebarContent>
            <SidebarGroup>
                <Accordion v-model="openSection" type="single" collapsible>
                    <NavMain
                        v-for="section in navSections"
                        :key="section.id"
                        :section="section"
                        :is-active="activeSectionId === section.id"
                    />
                </Accordion>
            </SidebarGroup>
        </SidebarContent>
        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
        <slot />
    </Sidebar>
</template>
