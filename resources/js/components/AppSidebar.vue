<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    ClipboardList,
    FolderGit2,
    KeyRound,
    LayoutGrid,
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
import type { NavItem } from '@/types';

const page = usePage();
const capabilities = computed(() => page.props.auth.capabilities);

const homeHref = computed(() =>
    capabilities.value?.isSuperAdmin || capabilities.value?.isMembershipHolder
        ? dashboard()
        : membershipApplicationCreate(),
);

const platformNavItems = computed((): NavItem[] => {
    if (capabilities.value?.isMembershipHolder || capabilities.value?.isSuperAdmin) {
        return [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutGrid,
            },
        ];
    }

    return [
        {
            title: 'Membership Application',
            href: membershipApplicationCreate(),
            icon: ClipboardList,
        },
    ];
});

const superAdminNavItems = computed((): NavItem[] => {
    if (!capabilities.value?.isSuperAdmin) {
        return [];
    }

    return [
        {
            title: 'Super Admin',
            href: dashboard(),
            icon: KeyRound,
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
