<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { computed } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarGroupLabel,
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
    <component
        :is="showAccordion ? AccordionItem : SidebarMenu"
        :value="showAccordion ? section.id : undefined"
        :data-tour="`nav-${section.id}`"
        class="border-sidebar-border/40"
    >
        <template v-if="showAccordion">
            <AccordionTrigger
                :data-active="isActive"
                class="hover:bg-sidebar-accent/40 data-[active=true]:bg-sidebar-accent/40 items-center px-2 py-2 hover:no-underline"
            >
                <span class="flex items-center gap-2">
                    <component
                        :is="section.icon"
                        class="size-4 shrink-0"
                        aria-hidden="true"
                    />
                    {{ section.title }}
                </span>
            </AccordionTrigger>
            <AccordionContent class="pb-2">
                <div v-for="group in itemGroups" :key="group.id ?? 'main'">
                    <SidebarGroupLabel v-if="group.title">{{
                        group.title
                    }}</SidebarGroupLabel>
                    <SidebarMenuSub class="border-sidebar-border/40">
                        <SidebarMenuSubItem
                            v-for="item in group.items"
                            :key="item.title"
                        >
                            <SidebarMenuSubButton
                                as-child
                                :is-active="isActiveItem(item)"
                            >
                                <Link
                                    :href="item.href"
                                    :aria-current="
                                        isActiveItem(item) ? 'page' : undefined
                                    "
                                    :title="item.title"
                                >
                                    <span>{{ item.title }}</span>
                                </Link>
                            </SidebarMenuSubButton>
                        </SidebarMenuSubItem>
                    </SidebarMenuSub>
                </div>
            </AccordionContent>
        </template>
        <SidebarMenuItem v-else>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <SidebarMenuButton
                        :is-active="isActive"
                        :tooltip="section.title"
                        class="data-[active=true]:bg-sidebar-accent/40"
                    >
                        <component :is="section.icon" aria-hidden="true" />
                        <span>{{ section.title }}</span>
                    </SidebarMenuButton>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    side="right"
                    align="start"
                    :side-offset="4"
                >
                    <DropdownMenuLabel>{{ section.title }}</DropdownMenuLabel>
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
                                'bg-sidebar-accent text-sidebar-accent-foreground':
                                    isActiveItem(item),
                            }"
                        >
                            <component
                                :is="item.icon"
                                v-if="item.icon"
                                aria-hidden="true"
                            />
                            <span>{{ item.title }}</span>
                        </Link>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    </component>
</template>
