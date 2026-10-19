import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
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
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useStaffPermissions } from '@/composables/useStaffPermissions';
import { useAnnouncementPermissions } from '@/composables/useAnnouncementPermissions';
import { resolveNavigationSelection } from '@/lib/navigationSelection';
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
import {
    edit as editProperty,
    index as propertiesIndex,
} from '@/routes/officer/properties';
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

type WorkItem = NavItem & {
    group?: 'events' | 'collection' | 'people' | 'settings';
};

export function useAssociationNavigation() {
    const page = usePage();
    const { allows } = useAnnouncementPermissions();
    const { allows: allowsStaff } = useStaffPermissions();
    const { currentUrl } = useCurrentUrl();
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
                href: userAccountsIndex(),
                icon: KeyRound,
                tabs: [
                    {
                        title: 'User Accounts',
                        href: userAccountsIndex(),
                        activeRoutePatterns: [
                            superAdminDashboard.definition.url,
                        ],
                    },
                    {
                        title: 'Roles & permissions',
                        href: rolePermissionsIndex(),
                    },
                    { title: 'Access history', href: accessHistoryIndex() },
                ],
            },
            {
                title: 'Association documents',
                href: editTermsOfService(),
                icon: BookOpen,
                tabs: [
                    { title: 'Terms of Service', href: editTermsOfService() },
                    { title: 'Privacy Policy', href: editPrivacyPolicy() },
                ],
            },
        ];
    });

    const propertyRoutePatterns = [
        propertiesIndex.definition.url,
        createProperty.definition.url,
        editProperty.definition.url,
        importProperties.definition.url,
        propertyProfile.definition.url,
    ];

    const officerDestinations = computed((): WorkItem[] => {
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
            ...(allowsStaff('events.manage')
                ? [
                      {
                          title: 'Listing',
                          group: 'events' as const,
                          href: manageEventsIndex(),
                          icon: CalendarClock,
                      },
                  ]
                : []),
            ...(allowsStaff('events.registrations.read')
                ? [
                      {
                          title: 'Registrations',
                          group: 'events' as const,
                          href: staffRegistrationsIndex(),
                          icon: CalendarClock,
                      },
                  ]
                : []),
            ...(allowsStaff('events.payments.review') ||
            allowsStaff('events.refunds.review')
                ? [
                      {
                          title: 'Payments & refunds',
                          group: 'events' as const,
                          href: eventFinancesIndex(),
                          icon: CalendarClock,
                      },
                  ]
                : []),
            ...(allowsStaff('events.check-in')
                ? [
                      {
                          title: 'Check-in',
                          group: 'events' as const,
                          href: eventCheckInIndex(),
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
            ...(allowsStaff('properties.read')
                ? [
                      {
                          title: 'Properties',
                          group: 'people' as const,
                          href: propertiesIndex(),
                          activeRoutePatterns: propertyRoutePatterns,
                          icon: ClipboardList,
                      },
                  ]
                : allowsStaff('properties.create')
                  ? [
                        {
                            title: 'New Property',
                            group: 'people' as const,
                            href: createProperty(),
                            activeRoutePatterns: propertyRoutePatterns,
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
                              activeRoutePatterns: propertyRoutePatterns,
                              icon: ClipboardList,
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
            ...(allowsStaff('charges.generate') && !allowsStaff('charges.read')
                ? [
                      {
                          title: 'Charges',
                          group: 'collection' as const,
                          href: generateCharges(),
                          icon: CalendarClock,
                      },
                  ]
                : []),
            ...(allowsStaff('suspends.read')
                ? [
                      {
                          title: 'Suspends',
                          group: 'collection' as const,
                          href: suspendsIndex(),
                          icon: Pause,
                      },
                  ]
                : allowsStaff('suspends.create')
                  ? [
                        {
                            title: 'New Suspend',
                            group: 'collection' as const,
                            href: createSuspend(),
                            icon: ClipboardList,
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

    const officerNavItems = computed((): NavItem[] => {
        const destinations = officerDestinations.value;
        const groups: {
            group: WorkItem['group'];
            title: string;
            icon: NavSection['icon'];
        }[] = [
            { group: 'events', title: 'Events', icon: CalendarClock },
            { group: 'people', title: 'Properties & people', icon: Users },
            { group: 'collection', title: 'Dues & collection', icon: Receipt },
            {
                group: 'settings',
                title: 'Association settings',
                icon: Settings2,
            },
        ];
        return [
            ...destinations.filter((item) => !item.group),
            ...groups.flatMap(({ group, title, icon }) => {
                const tabs = destinations
                    .filter((item) => item.group === group)
                    .map(({ group: _group, ...item }) => item);
                return tabs.length
                    ? [{ title, icon, href: tabs[0].href, tabs }]
                    : [];
            }),
        ];
    });

    const administratorNavItems = computed((): NavItem[] => {
        if (
            !capabilities.value?.canAccessAdministrator ||
            capabilities.value.isSuperAdmin
        ) {
            return [];
        }
        return [
            {
                title: 'User Accounts',
                href: userAccountsIndex(),
                icon: Users,
                activeRoutePatterns: [administratorDashboard.definition.url],
            },
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
                activeRoutePatterns: !capabilities.value?.canAccessOfficer
                    ? [propertyProfile.definition.url]
                    : [],
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
            activeRoutePatterns: [statementOfAccountShow.definition.url],
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

    const navigationSelection = computed(() =>
        resolveNavigationSelection(navSections.value, currentUrl.value),
    );

    return { homeHref, navSections, navigationSelection };
}
