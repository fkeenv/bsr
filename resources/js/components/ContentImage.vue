<script setup lang="ts">
import { computed, ref, useSlots, watch } from 'vue';

defineOptions({ inheritAttrs: false });
const props = withDefaults(
    defineProps<{
        src?: string | null;
        alt: string;
        fallbackSrc?: string;
        fit?: 'cover' | 'contain';
        loading?: 'eager' | 'lazy';
        as?: 'div' | 'figure';
    }>(),
    { fit: 'cover', loading: 'lazy', as: 'div' },
);
const slots = useSlots();
const failedSources = ref(new Set<string>());
const imageSrc = computed(() =>
    [props.src, props.fallbackSrc].find(
        (src) => src && !failedSources.value.has(src),
    ),
);
watch(
    () => [props.src, props.fallbackSrc],
    () => failedSources.value.clear(),
);

function imageFailed(event: Event): void {
    const source = (event.currentTarget as HTMLImageElement).getAttribute(
        'src',
    );
    if (source) {
        failedSources.value.add(source);
    }
}
</script>

<template>
    <component
        :is="as"
        v-if="imageSrc || slots.fallback"
        data-slot="content-image"
        v-bind="$attrs"
        class="overflow-hidden"
    >
        <img
            v-if="imageSrc"
            :key="imageSrc"
            :src="imageSrc"
            :alt="imageSrc === src ? alt : ''"
            :width="fit === 'cover' ? 960 : undefined"
            :height="fit === 'cover' ? 540 : undefined"
            :loading="loading"
            decoding="async"
            draggable="false"
            class="bg-muted block w-full outline-1 -outline-offset-1 outline-black/10 dark:outline-white/10"
            :class="
                fit === 'cover'
                    ? 'aspect-video object-cover'
                    : 'max-h-[70vh] object-contain'
            "
            @error="imageFailed"
        />
        <slot v-else name="fallback" />
    </component>
</template>
