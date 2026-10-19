<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, ref, watchPostEffect } from 'vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useAssociationNavigation } from '@/composables/useAssociationNavigation';
import { toUrl } from '@/lib/utils';

const { navigationSelection } = useAssociationNavigation();
const module = computed(() => navigationSelection.value.item);
const selectedTab = computed(() => navigationSelection.value.tab);
const tabStrip = ref<HTMLElement>();

function revealTab(element: HTMLElement | null | undefined) {
    const strip = tabStrip.value;
    if (!strip || !element) {
        return;
    }
    const viewport = strip.getBoundingClientRect();
    const tab = element.getBoundingClientRect();
    if (tab.left < viewport.left) {
        strip.scrollLeft += tab.left - viewport.left;
    } else if (tab.right > viewport.right) {
        strip.scrollLeft += tab.right - viewport.right;
    }
}

watchPostEffect(() => {
    if (selectedTab.value && typeof window !== 'undefined') {
        revealTab(tabStrip.value?.querySelector('[aria-current="page"]'));
    }
});

function activateLink(event: KeyboardEvent) {
    (event.currentTarget as HTMLElement).click();
}
</script>

<template>
    <Tabs
        v-if="module?.tabs && module.tabs.length > 1 && selectedTab"
        :model-value="toUrl(selectedTab.href)"
        activation-mode="manual"
        class="min-w-0 gap-0"
    >
        <div
            ref="tabStrip"
            class="min-w-0 overflow-x-auto overscroll-x-contain px-4 pt-4 sm:px-6"
            @focusin="revealTab($event.target as HTMLElement)"
        >
            <TabsList :aria-label="`${module.title} sections`">
                <TabsTrigger
                    v-for="tab in module.tabs"
                    :key="toUrl(tab.href)"
                    :value="toUrl(tab.href)"
                    as-child
                >
                    <Link
                        :href="tab.href"
                        :aria-current="tab === selectedTab ? 'page' : undefined"
                        @keydown.space.prevent="activateLink"
                    >
                        {{ tab.title }}
                    </Link>
                </TabsTrigger>
            </TabsList>
        </div>
        <TabsContent
            :key="toUrl(selectedTab.href)"
            :value="toUrl(selectedTab.href)"
            class="min-w-0"
        >
            <slot />
        </TabsContent>
    </Tabs>
    <slot v-else />
</template>
