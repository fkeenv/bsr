import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from '@lucide/vue';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
    group?: 'collection' | 'people' | 'settings';
};

export type NavSection = {
    id: string;
    title: string;
    icon: LucideIcon;
    items: NavItem[];
    activeRoutePatterns?: string[];
};
