<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronDown } from '@lucide/vue';
import {
    AccordionContent,
    AccordionHeader,
    AccordionItem,
    AccordionTrigger,
} from 'reka-ui';
import { computed } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarGroup,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import type { NavItem, NavSection } from '@/types';

const props = defineProps<{
    section: NavSection;
    isActive: boolean;
}>();

const { isCurrentUrl, currentUrl } = useCurrentUrl();
const { isMobile, state } = useSidebar();
const showAccordion = computed(
    () => isMobile.value || state.value === 'expanded',
);
const groupLabels = {
    collection: 'Payments & collection',
    people: 'Properties & people',
    settings: 'Association settings',
};

const itemGroups = computed(() => {
    const groups = new Map<NavItem['group'], NavItem[]>();
    for (const item of props.section.items) {
        const id = item.group;
        const items = groups.get(id) ?? [];
        items.push(item);
        groups.set(id, items);
    }
    return Array.from(groups, ([id, items]) => ({
        id,
        title: id ? groupLabels[id] : '',
        isDisclosure: id === 'settings',
        items,
    }));
});

const activeItem = computed(() => {
    const exactMatch = props.section.items.find((item) =>
        isCurrentUrl(item.href),
    );
    if (exactMatch) {
        return exactMatch;
    }

    return props.section.items
        .filter((item) => {
            const path = toUrl(item.href);
            return currentUrl.value.startsWith(`${path}/`);
        })
        .sort((a, b) => toUrl(b.href).length - toUrl(a.href).length)[0];
});

function isActiveItem(item: NavItem): boolean {
    return activeItem.value === item;
}
</script>

<template>
    <SidebarGroup class="px-2 py-1" :data-tour="`nav-${section.id}`">
        <AccordionItem
            v-if="showAccordion"
            :value="section.id"
            class="group/nav-section"
        >
            <AccordionHeader>
                <AccordionTrigger as-child>
                    <SidebarMenuButton
                        :is-active="isActive"
                        class="h-10 px-3 text-xs font-medium tracking-wide data-[active=true]:bg-transparent"
                    >
                        <component :is="section.icon" />
                        <span>{{ section.title }}</span>
                        <ChevronDown
                            class="ml-auto transition-transform group-data-[state=open]/nav-section:rotate-180"
                        />
                    </SidebarMenuButton>
                </AccordionTrigger>
            </AccordionHeader>

            <AccordionContent class="overflow-hidden">
                <component
                    :is="group.isDisclosure ? 'details' : 'div'"
                    v-for="group in itemGroups"
                    :key="group.id ?? 'main'"
                    :open="
                        group.isDisclosure
                            ? group.items.some(isActiveItem)
                            : undefined
                    "
                    class="group/settings mt-2"
                >
                    <component
                        :is="group.isDisclosure ? 'summary' : 'p'"
                        v-if="group.title"
                        class="border-sidebar-border text-sidebar-foreground mx-3 border-t pt-4 pb-2 text-xs font-medium"
                        :class="
                            group.isDisclosure
                                ? 'focus-visible:outline-sidebar-ring flex cursor-pointer list-none items-center justify-between rounded-sm focus-visible:outline-2'
                                : ''
                        "
                    >
                        {{ group.title }}
                        <ChevronDown
                            v-if="group.isDisclosure"
                            class="size-4 transition-transform group-open/settings:rotate-180"
                        />
                    </component>
                    <SidebarMenuSub class="mx-0 gap-1 border-0 px-0 py-1">
                        <SidebarMenuSubItem
                            v-for="item in group.items"
                            :key="item.title"
                        >
                            <SidebarMenuSubButton
                                as-child
                                :is-active="isActiveItem(item)"
                                class="h-auto min-h-10 px-3 py-2 text-[15px] leading-5 [&>span:last-child]:whitespace-normal"
                            >
                                <Link
                                    :href="item.href"
                                    :aria-current="
                                        isActiveItem(item) ? 'page' : undefined
                                    "
                                >
                                    <span>{{ item.title }}</span>
                                </Link>
                            </SidebarMenuSubButton>
                        </SidebarMenuSubItem>
                    </SidebarMenuSub>
                </component>
            </AccordionContent>
        </AccordionItem>

        <SidebarMenu v-else>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <SidebarMenuButton
                            :is-active="isActive"
                            class="h-10 px-3 text-xs font-medium tracking-wide data-[active=true]:bg-transparent"
                        >
                            <component :is="section.icon" />
                            <span>{{ section.title }}</span>
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        side="right"
                        align="start"
                        :side-offset="4"
                        class="min-w-56"
                    >
                        <DropdownMenuLabel>
                            {{ section.title }}
                        </DropdownMenuLabel>
                        <DropdownMenuItem
                            v-for="item in section.items"
                            :key="item.title"
                            as-child
                        >
                            <Link
                                :href="item.href"
                                :aria-current="
                                    isActiveItem(item) ? 'page' : undefined
                                "
                                :class="{
                                    'bg-accent text-accent-foreground':
                                        isActiveItem(item),
                                }"
                            >
                                <component :is="item.icon" v-if="item.icon" />
                                <span>{{ item.title }}</span>
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
