<script setup lang="ts">
import { ArrowDown, ArrowUp, ChevronsUpDown } from '@lucide/vue';
import { Button } from '@/components/ui/button';

/** Loose column shape so h() headers work across row types (Column<TData> is invariant). */
type SortableColumnHeader = {
    getIsSorted: () => false | 'asc' | 'desc';
    toggleSorting: (desc?: boolean, isMulti?: boolean) => void;
    clearSorting: () => void;
};

const props = defineProps<{
    column: SortableColumnHeader;
    title: string;
}>();

function toggleSort(): void {
    const sorted = props.column.getIsSorted();

    if (sorted === 'asc') {
        props.column.toggleSorting(true);
        return;
    }

    if (sorted === 'desc') {
        props.column.clearSorting();
        return;
    }

    props.column.toggleSorting(false);
}
</script>

<template>
    <Button variant="ghost" class="-ml-3 h-8" @click="toggleSort">
        <span>{{ title }}</span>
        <ArrowDown v-if="column.getIsSorted() === 'asc'" class="size-4" />
        <ArrowUp v-else-if="column.getIsSorted() === 'desc'" class="size-4" />
        <ChevronsUpDown v-else class="size-4" />
    </Button>
</template>
