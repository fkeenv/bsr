<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    CalendarClock,
    ClipboardList,
    FolderGit2,
    KeyRound,
    LayoutGrid,
    Pause,
    Receipt,
    Settings2,
    Shield,
    ShieldCheck,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
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
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { dashboard as administratorDashboard } from '@/routes/administrator';
import { create as membershipApplicationCreate } from '@/routes/membership-application';
import { dashboard as officerDashboard } from '@/routes/officer';
import { index as chargesIndex } from '@/routes/officer/charges';
import { create as generateCharges } from '@/routes/officer/charges/generate';
import { index as feeTypesIndex } from '@/routes/officer/fee-types';
import { edit as levySettingsEdit } from '@/routes/officer/levy-settings';
import { index as membershipApplicationsIndex } from '@/routes/officer/membership-applications';
import { index as membershipsIndex } from '@/routes/officer/memberships';
import { index as propertiesIndex } from '@/routes/officer/properties';
import { index as suspendsIndex } from '@/routes/officer/suspends';
import { dashboard as superAdminDashboard } from '@/routes/super-admin';
import { edit as editPrivacyPolicy } from '@/routes/super-admin/privacy-policy';
import { edit as editTermsOfService } from '@/routes/super-admin/terms-of-service';
import type { NavItem } from '@/types';

const page = usePage();
const capabilities = computed(() => page.props.auth.capabilities);

const homeHref = computed(() =>
    capabilities.value?.isSuperAdmin || capabilities.value?.isMembershipHolder
        ? dashboard()
        : membershipApplicationCreate(),
);

const platformNavItems = computed((): NavItem[] => {
    const items: NavItem[] = [];

    if (
        capabilities.value?.isMembershipHolder ||
        capabilities.value?.isSuperAdmin
    ) {
        items.push({
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        });
    }

    if (!capabilities.value?.isSuperAdmin) {
        items.push({
            title: 'Membership Application',
            href: membershipApplicationCreate(),
            icon: ClipboardList,
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
            title: 'Membership Applications',
            href: membershipApplicationsIndex(),
            icon: ClipboardList,
        },
        {
            title: 'Memberships',
            href: membershipsIndex(),
            icon: Users,
        },
        {
            title: 'Properties',
            href: propertiesIndex(),
            icon: ClipboardList,
        },
        {
            title: 'Fee Types',
            href: feeTypesIndex(),
            icon: Receipt,
        },
        {
            title: 'Suspends',
            href: suspendsIndex(),
            icon: Pause,
        },
        {
            title: 'Charges',
            href: chargesIndex(),
            icon: ClipboardList,
        },
        {
            title: 'Generate Charges',
            href: generateCharges(),
            icon: CalendarClock,
        },
        {
            title: 'Levy day',
            href: levySettingsEdit(),
            icon: Settings2,
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
    ];
});

const membershipNavItems = computed((): NavItem[] => {
    if (!capabilities.value?.isMembershipHolder) {
        return [];
    }

    return [
        {
            title: 'Membership',
            href: dashboard(),
            icon: Users,
        },
    ];
});

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="homeHref">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain label="Platform" :items="platformNavItems" />
            <NavMain
                v-if="superAdminNavItems.length"
                label="Super Admin"
                :items="superAdminNavItems"
            />
            <NavMain
                v-if="officerNavItems.length"
                label="Officer"
                :items="officerNavItems"
            />
            <NavMain
                v-if="administratorNavItems.length"
                label="Administrator"
                :items="administratorNavItems"
            />
            <NavMain
                v-if="membershipNavItems.length"
                label="Membership"
                :items="membershipNavItems"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
