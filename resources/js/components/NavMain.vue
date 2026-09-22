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
import type { NavSection } from '@/types';

defineProps<{
    section: NavSection;
    isActive: boolean;
}>();

const { isCurrentUrl } = useCurrentUrl();
const { isMobile, state } = useSidebar();
const showAccordion = computed(
    () => isMobile.value || state.value === 'expanded',
);
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <AccordionItem
            v-if="showAccordion"
            :value="section.id"
            class="group/nav-section"
        >
            <AccordionHeader>
                <AccordionTrigger as-child>
                    <SidebarMenuButton :is-active="isActive">
                        <component :is="section.icon" />
                        <span>{{ section.title }}</span>
                        <ChevronDown
                            class="ml-auto transition-transform group-data-[state=open]/nav-section:rotate-180"
                        />
                    </SidebarMenuButton>
                </AccordionTrigger>
            </AccordionHeader>

            <AccordionContent class="overflow-hidden">
                <SidebarMenuSub>
                    <SidebarMenuSubItem
                        v-for="item in section.items"
                        :key="item.title"
                    >
                        <SidebarMenuSubButton
                            as-child
                            :is-active="isCurrentUrl(item.href)"
                        >
                            <Link :href="item.href">
                                <component :is="item.icon" v-if="item.icon" />
                                <span>{{ item.title }}</span>
                            </Link>
                        </SidebarMenuSubButton>
                    </SidebarMenuSubItem>
                </SidebarMenuSub>
            </AccordionContent>
        </AccordionItem>

        <SidebarMenu v-else>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <SidebarMenuButton :is-active="isActive">
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
                                :class="{
                                    'bg-accent text-accent-foreground':
                                        isCurrentUrl(item.href),
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
