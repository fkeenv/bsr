import {
    columnVisibilityFeature,
    createSortedRowModel,
    rowSortingFeature,
    sortFn_alphanumeric,
    sortFn_basic,
    sortFn_text,
    tableFeatures,
} from '@tanstack/vue-table';

/** Shared TanStack features; roster search/filters stay on the server via Inertia. */
export const dataTableFeatures = tableFeatures({
    columnVisibilityFeature,
    rowSortingFeature,
    sortedRowModel: createSortedRowModel(),
    sortFns: {
        alphanumeric: sortFn_alphanumeric,
        basic: sortFn_basic,
        text: sortFn_text,
    },
});

export type DataTableFeatures = typeof dataTableFeatures;
