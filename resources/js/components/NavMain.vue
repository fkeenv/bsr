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
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
    useSidebar,
} from '@/components/ui/sidebar';
import type { NavItem, NavSection } from '@/types';

const props = defineProps<{
    section: NavSection;
    isActive: boolean;
    activeItem?: NavItem;
}>();

const { isMobile, state } = useSidebar();
const showAccordion = computed(
    () => isMobile.value || state.value === 'expanded',
);
function isActiveItem(item: NavItem): boolean {
    return props.isActive && props.activeItem === item;
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
                <SidebarMenuSub class="border-sidebar-border/40">
                    <SidebarMenuSubItem
                        v-for="item in section.items"
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
