<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { PanelLeftClose, PanelLeftOpen } from "@lucide/vue"
import { cn } from "@/lib/utils"
import { Button } from '@/components/ui/button'
import { useSidebar } from "./utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()

const { isMobile, state, openMobile, toggleSidebar } = useSidebar()
</script>

<template>
  <Button
    data-sidebar="trigger"
    data-slot="sidebar-trigger"
    :aria-expanded="isMobile ? openMobile : state === 'expanded'"
    :aria-controls="isMobile ? 'association-mobile-navigation' : undefined"
    variant="ghost"
    size="icon"
    :class="cn('h-10 w-10', isMobile ? 'w-auto px-2' : '', props.class)"
    @click="toggleSidebar"
  >
    <PanelLeftOpen v-if="isMobile || state === 'collapsed'" />
    <PanelLeftClose v-else />
    <span v-if="isMobile" class="ml-1 text-sm">Menu</span>
    <span class="sr-only">Toggle association navigation</span>
  </Button>
</template>
