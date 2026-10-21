<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, watch } from 'vue';
import { Accordion } from '@/components/ui/accordion';
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
import { useNavigationSection } from '@/composables/useNavigationSection';
import { useAssociationNavigation } from '@/composables/useAssociationNavigation';

const { homeHref, navSections, navigationSelection } =
    useAssociationNavigation();
const { currentUrl } = useCurrentUrl();
const { openNavigationSection: openSection } = useNavigationSection();

watch(
    [currentUrl, navSections],
    () => {
        openSection.value = navigationSelection.value.sectionId;
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
                        :is-active="
                            navigationSelection.sectionId === section.id
                        "
                        :active-item="navigationSelection.item"
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
