<script setup lang="ts" generic="TData extends RowData">
import { router } from '@inertiajs/vue3';
import type { ColumnDef, RowData } from '@tanstack/vue-table';
import { FlexRender, useTable } from '@tanstack/vue-table';
import { ChevronDown, Search, SlidersHorizontal } from '@lucide/vue';
import { computed, nextTick, onBeforeUnmount, ref, useId, watch } from 'vue';
import { onClickOutside, useDebounceFn } from '@vueuse/core';
import {
    dataTableFeatures,
    type DataTableFeatures,
} from '@/components/data-table/features';
import DataTableDateRangeFilter from '@/components/data-table/DataTableDateRangeFilter.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type {
    DataTableFilterOption,
    DataTableValues,
} from '@/types/data-table';

const ALL_VALUE = '__all__';

type Props = {
    columns: ColumnDef<DataTableFeatures, TData, any>[];
    data: TData[];
    /** Inertia GET URL for search/filter query params. */
    action: string;
    searchables?: string[];
    filters?: string[];
    filterOptions?: Record<string, DataTableFilterOption[]>;
    /** Keys that use from/to date pickers (`{key}_from`, `{key}_to` query params). */
    dateRanges?: string[];
    values?: DataTableValues;
    searchableLabels?: Record<string, string>;
    filterLabels?: Record<string, string>;
    dateRangeLabels?: Record<string, string>;
    emptyText?: string;
};

const props = withDefaults(defineProps<Props>(), {
    searchables: () => [],
    filters: () => [],
    filterOptions: () => ({}),
    dateRanges: () => [],
    values: () => ({}),
    searchableLabels: () => ({}),
    filterLabels: () => ({}),
    dateRangeLabels: () => ({}),
    emptyText: 'No results.',
});

const search = ref(props.values.search ?? '');
const data = computed(() => props.data);
const toolbarId = useId();
const activeTool = ref<'search' | 'filters' | null>(null);
const searchOpen = computed(() => activeTool.value === 'search');
const filtersOpen = computed(() => activeTool.value === 'filters');

function toggleTool(tool: 'search' | 'filters'): void {
    activeTool.value = activeTool.value === tool ? null : tool;
}
const searchRegion = ref<HTMLElement | null>(null);
let searchVersion = 0;
onBeforeUnmount(() => {
    searchVersion++;
});
onClickOutside(searchRegion, () => {
    if (activeTool.value === 'search') activeTool.value = null;
});
watch(searchOpen, async (open) => {
    if (open) {
        await nextTick();
        searchRegion.value?.querySelector?.('input')?.focus();
    }
});

watch(
    () => props.values.search,
    (value) => {
        search.value = value ?? '';
    },
);

const table = useTable({
    features: dataTableFeatures,
    columns: props.columns,
    data,
    getRowId: (row: TData) => {
        if (
            typeof row === 'object' &&
            row !== null &&
            'id' in row &&
            (typeof row.id === 'string' || typeof row.id === 'number')
        ) {
            return String(row.id);
        }

        return JSON.stringify(row);
    },
});

const headerGroups = computed(() => table.getHeaderGroups());
const rows = computed(() => table.getRowModel().rows);
const hideableColumns = computed(() =>
    table.getAllColumns().filter((column) => column.getCanHide()),
);

const showSearch = computed(() => props.searchables.length > 0);

const searchPlaceholder = computed(() => {
    const labels = props.searchables.map(
        (key) => props.searchableLabels[key] ?? key,
    );

    if (labels.length === 0) {
        return 'Search…';
    }

    return `Search ${labels.join(', ')}…`;
});

const filterLabel = (key: string): string =>
    props.filterLabels[key] ?? key.charAt(0).toUpperCase() + key.slice(1);

const dateRangeLabel = (key: string): string =>
    props.dateRangeLabels[key] ?? key.charAt(0).toUpperCase() + key.slice(1);

const dateFromKey = (key: string): string => `${key}_from`;
const dateToKey = (key: string): string => `${key}_to`;

const showFilters = computed(
    () => props.filters.length > 0 || props.dateRanges.length > 0,
);
const hasToolbar = computed(() => showSearch.value || showFilters.value);
const activeFilterCount = computed(
    () =>
        props.filters.filter((key) => Boolean(props.values[key])).length +
        props.dateRanges.filter(
            (key) =>
                props.values[dateFromKey(key)] || props.values[dateToKey(key)],
        ).length,
);
const hasActiveFilters = computed(
    () => Boolean(props.values.search) || activeFilterCount.value > 0,
);

function currentQuery(): Record<string, string> {
    const query: Record<string, string> = {};

    if (search.value.trim()) {
        query.search = search.value.trim();
    }

    for (const key of props.filters) {
        const value = props.values[key];

        if (value) {
            query[key] = value;
        }
    }

    for (const key of props.dateRanges) {
        const from = props.values[dateFromKey(key)];
        const to = props.values[dateToKey(key)];

        if (from) {
            query[dateFromKey(key)] = from;
        }

        if (to) {
            query[dateToKey(key)] = to;
        }
    }

    return query;
}

function visit(query: Record<string, string>): void {
    router.get(props.action, query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function applySearch(): void {
    searchVersion++;
    const query = currentQuery();
    if (search.value.trim()) query.search = search.value.trim();
    else delete query.search;
    visit(query);
}

const visitSearch = useDebounceFn((version: number) => {
    if (version === searchVersion) applySearch();
}, 300);

function onSearchUpdate(value: string | number): void {
    search.value = String(value);
    visitSearch(++searchVersion);
}

function filterModelValue(key: string): string {
    return props.values[key] || ALL_VALUE;
}

function onFilterChange(key: string, value: string): void {
    searchVersion++;
    const query = currentQuery();
    if (value === ALL_VALUE) delete query[key];
    else query[key] = value;
    visit(query);
}

function onDateRangeChange(
    rangeKey: string,
    from: string | null,
    to: string | null,
): void {
    searchVersion++;
    const query = currentQuery();
    if (from) query[dateFromKey(rangeKey)] = from;
    else delete query[dateFromKey(rangeKey)];
    if (to) query[dateToKey(rangeKey)] = to;
    else delete query[dateToKey(rangeKey)];
    visit(query);
}

function clearFilters(): void {
    searchVersion++;
    search.value = '';
    visit({});
}
</script>

<template>
    <div>
        <div v-if="hasToolbar" class="flex flex-wrap items-center gap-2 py-4">
            <div
                ref="searchRegion"
                class="flex min-w-0 flex-1 items-start gap-2"
            >
                <div class="flex shrink-0 items-center gap-2">
                    <Button
                        v-if="showSearch"
                        type="button"
                        size="icon"
                        :variant="values.search ? 'secondary' : 'outline'"
                        aria-label="Search table"
                        title="Search"
                        :aria-expanded="searchOpen"
                        :aria-controls="`${toolbarId}-search`"
                        @click="toggleTool('search')"
                    >
                        <Search class="size-4" />
                    </Button>
                    <Button
                        v-if="showFilters"
                        type="button"
                        class="relative shrink-0"
                        size="icon"
                        :variant="activeFilterCount ? 'secondary' : 'outline'"
                        aria-label="Filter table"
                        title="Filters"
                        :aria-expanded="filtersOpen"
                        :aria-controls="`${toolbarId}-filters`"
                        @click="toggleTool('filters')"
                    >
                        <SlidersHorizontal class="size-4" />
                        <span
                            v-if="activeFilterCount"
                            class="bg-primary text-primary-foreground absolute -top-1 -right-1 min-w-4 rounded-full px-1 text-[10px] leading-4"
                            >{{ activeFilterCount
                            }}<span class="sr-only"> active filters</span></span
                        >
                    </Button>
                </div>
                <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
                    <form
                        v-if="showSearch && searchOpen"
                        class="w-64 min-w-0 sm:w-80"
                        @submit.prevent="applySearch"
                        @keydown.esc="activeTool = null"
                    >
                        <Input
                            :id="`${toolbarId}-search`"
                            :model-value="search"
                            name="search"
                            type="search"
                            aria-label="Search records"
                            :placeholder="searchPlaceholder"
                            @update:model-value="onSearchUpdate"
                        />
                    </form>
                    <div
                        v-if="showFilters && filtersOpen"
                        :id="`${toolbarId}-filters`"
                        class="contents"
                    >
                        <DataTableDateRangeFilter
                            v-for="rangeKey in dateRanges"
                            :key="rangeKey"
                            :label="dateRangeLabel(rangeKey)"
                            :from="values[dateFromKey(rangeKey)]"
                            :to="values[dateToKey(rangeKey)]"
                            @change="
                                (from, to) =>
                                    onDateRangeChange(rangeKey, from, to)
                            "
                        />
                        <Select
                            v-for="filterKey in filters"
                            :key="filterKey"
                            :model-value="filterModelValue(filterKey)"
                            @update:model-value="
                                (value) =>
                                    onFilterChange(
                                        filterKey,
                                        typeof value === 'string'
                                            ? value
                                            : ALL_VALUE,
                                    )
                            "
                        >
                            <SelectTrigger
                                class="w-full min-w-0 sm:w-auto sm:min-w-[10rem]"
                                :aria-label="filterLabel(filterKey)"
                                ><SelectValue
                                    :placeholder="filterLabel(filterKey)"
                            /></SelectTrigger>
                            <SelectContent
                                ><SelectItem :value="ALL_VALUE"
                                    >All
                                    {{
                                        filterLabel(filterKey).toLowerCase()
                                    }}</SelectItem
                                ><SelectItem
                                    v-for="option in filterOptions[filterKey] ??
                                    []"
                                    :key="option.value"
                                    :value="option.value"
                                    >{{ option.label }}</SelectItem
                                ></SelectContent
                            >
                        </Select>
                    </div>
                    <span
                        v-if="values.search && !searchOpen"
                        class="text-muted-foreground max-w-48 truncate text-sm"
                        :title="`Search: ${values.search}`"
                        >Search: {{ values.search }}</span
                    >
                </div>
            </div>

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="outline" class="ml-auto">
                        Columns
                        <ChevronDown class="size-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuLabel>Toggle columns</DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <DropdownMenuCheckboxItem
                        v-for="column in hideableColumns"
                        :key="column.id"
                        class="capitalize"
                        :model-value="column.getIsVisible()"
                        @update:model-value="
                            (value: boolean) => column.toggleVisibility(!!value)
                        "
                    >
                        {{ column.id }}
                    </DropdownMenuCheckboxItem>
                </DropdownMenuContent>
            </DropdownMenu>

            <Button
                v-if="hasActiveFilters"
                variant="ghost"
                @click="clearFilters"
            >
                Reset
            </Button>
        </div>

        <div class="bg-card overflow-hidden rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow
                        v-for="headerGroup in headerGroups"
                        :key="headerGroup.id"
                    >
                        <TableHead
                            v-for="header in headerGroup.headers"
                            :key="header.id"
                        >
                            <FlexRender
                                v-if="!header.isPlaceholder"
                                :header="header"
                            />
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <template v-if="rows.length">
                        <TableRow v-for="row in rows" :key="row.id">
                            <TableCell
                                v-for="cell in row.getVisibleCells()"
                                :key="cell.id"
                            >
                                <FlexRender :cell="cell" />
                            </TableCell>
                        </TableRow>
                    </template>
                    <TableRow v-else>
                        <TableCell
                            :colspan="columns.length"
                            class="text-muted-foreground h-24 text-center"
                        >
                            {{ emptyText }}
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </div>
</template>
