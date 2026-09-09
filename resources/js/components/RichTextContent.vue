<script setup lang="ts">
defineProps<{
    html: string;
}>();
</script>

<template>
    <div class="prose-legal text-sm leading-relaxed" v-html="html" />
</template>
