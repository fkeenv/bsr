<script setup lang="ts">
import type { AccordionContentProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { Transition } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { AccordionContent, injectCollapsibleRootContext } from "reka-ui"
import { cn } from "@/lib/utils"

defineOptions({ inheritAttrs: false })

const props = defineProps<AccordionContentProps & { class?: HTMLAttributes["class"] }>()

const delegatedProps = reactiveOmit(props, "class")
const { open, unmountOnHide } = injectCollapsibleRootContext()
</script>

<template>
  <Transition name="ui-collapse">
    <div
      v-if="!unmountOnHide || open || props.forceMount"
      :class="unmountOnHide ? 'ui-collapse grid overflow-hidden' : undefined"
    >
      <AccordionContent
        data-slot="accordion-content"
        v-bind="{ ...$attrs, ...delegatedProps }"
        :force-mount="unmountOnHide || props.forceMount"
        class="min-h-0 overflow-hidden text-sm"
      >
        <div :class="cn('pt-0 pb-4', props.class)">
          <slot />
        </div>
      </AccordionContent>
    </div>
  </Transition>
</template>
