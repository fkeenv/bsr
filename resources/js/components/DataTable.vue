<script setup lang="ts" generic="TData extends RowData">
import { router } from '@inertiajs/vue3';
import type { ColumnDef, RowData } from '@tanstack/vue-table';
import { FlexRender, useTable } from '@tanstack/vue-table';
import { useDebounceFn } from '@vueuse/core';
import { ChevronDown } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import {
    dataTableFeatures,
    type DataTableFeatures,
} from '@/components/data-table/features';
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
    values?: DataTableValues;
    searchableLabels?: Record<string, string>;
    filterLabels?: Record<string, string>;
    emptyText?: string;
};

const props = withDefaults(defineProps<Props>(), {
    searchables: () => [],
    filters: () => [],
    filterOptions: () => ({}),
    values: () => ({}),
    searchableLabels: () => ({}),
    filterLabels: () => ({}),
    emptyText: 'No results.',
});

const search = ref(props.values.search ?? '');
const data = computed(() => props.data);

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
        return 'Filter…';
    }

    return `Filter ${labels.join(', ')}…`;
});

const filterLabel = (key: string): string =>
    props.filterLabels[key] ?? key.charAt(0).toUpperCase() + key.slice(1);

const hasToolbar = computed(() => showSearch.value || props.filters.length > 0);

const hasActiveFilters = computed(() => {
    if ((props.values.search ?? '') !== '') {
        return true;
    }

    return props.filters.some((key) => (props.values[key] ?? '') !== '');
});

function currentQuery(): Record<string, string> {
    const query: Record<string, string> = {};

    if (search.value.trim() !== '') {
        query.search = search.value.trim();
    }

    for (const key of props.filters) {
        const value = props.values[key];

        if (value) {
            query[key] = value;
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

const visitSearch = useDebounceFn(() => {
    const query = currentQuery();

    if (search.value.trim() === '') {
        delete query.search;
    } else {
        query.search = search.value.trim();
    }

    visit(query);
}, 300);

function onSearchUpdate(value: string | number): void {
    search.value = String(value);
    visitSearch();
}

function filterModelValue(key: string): string {
    return props.values[key] || ALL_VALUE;
}

function onFilterChange(key: string, value: string): void {
    const query = currentQuery();

    if (value === ALL_VALUE) {
        delete query[key];
    } else {
        query[key] = value;
    }

    visit(query);
}

function clearFilters(): void {
    search.value = '';
    visit({});
}
</script>

<template>
    <div>
        <div
            v-if="hasToolbar"
            class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center"
        >
            <Input
                v-if="showSearch"
                type="search"
                :model-value="search"
                :placeholder="searchPlaceholder"
                class="max-w-sm"
                @update:model-value="onSearchUpdate"
            />

            <div class="flex flex-wrap items-center gap-2 sm:ml-auto">
                <Select
                    v-for="filterKey in filters"
                    :key="filterKey"
                    :model-value="filterModelValue(filterKey)"
                    @update:model-value="
                        (value) =>
                            onFilterChange(
                                filterKey,
                                typeof value === 'string' ? value : ALL_VALUE,
                            )
                    "
                >
                    <SelectTrigger class="w-[10rem]">
                        <SelectValue :placeholder="filterLabel(filterKey)" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="ALL_VALUE">
                            All {{ filterLabel(filterKey).toLowerCase() }}
                        </SelectItem>
                        <SelectItem
                            v-for="option in filterOptions[filterKey] ?? []"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>

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
                            :checked="column.getIsVisible()"
                            @update:checked="
                                (value: boolean) =>
                                    column.toggleVisibility(!!value)
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
        </div>

        <div class="overflow-hidden rounded-md border">
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
