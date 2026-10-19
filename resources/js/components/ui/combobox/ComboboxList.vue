<script setup lang="ts">
import type { ComboboxContentEmits, ComboboxContentProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { Transition } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { ComboboxContent, ComboboxPortal, injectComboboxRootContext, useForwardPropsEmits } from "reka-ui"
import { cn } from "@/lib/utils"

defineOptions({
  inheritAttrs: false,
})

const props = withDefaults(defineProps<ComboboxContentProps & { class?: HTMLAttributes["class"] }>(), {
  position: "popper",
  align: "center",
  sideOffset: 4,
})
const emits = defineEmits<ComboboxContentEmits>()

const delegatedProps = reactiveOmit(props, "class")
const forwarded = useForwardPropsEmits(delegatedProps, emits)
const root = injectComboboxRootContext()
let focusWasInside = false

function rememberFocus(element: Element) {
  focusWasInside = element.contains(element.ownerDocument.activeElement)
}

function restoreFocus(element: Element) {
  const { activeElement, body } = element.ownerDocument
  if (focusWasInside && !root.open.value && (!activeElement || activeElement === body)) {
    root.triggerElement.value?.focus()
  }
  focusWasInside = false
}
</script>

<template>
  <ComboboxPortal>
    <Transition name="ui-fade" @before-leave="rememberFocus" @after-leave="restoreFocus">
      <ComboboxContent
        data-slot="combobox-list"
        v-bind="{ ...$attrs, ...forwarded }"
        :class="cn('z-50 w-[200px] rounded-md border bg-popover text-popover-foreground origin-(--reka-combobox-content-transform-origin) overflow-hidden shadow-md outline-none', props.class)"
      >
        <slot />
      </ComboboxContent>
    </Transition>
  </ComboboxPortal>
</template>
