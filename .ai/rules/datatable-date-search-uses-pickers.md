# Data table date search uses pickers / ranges

**Glob:** `resources/js/components/DataTable.vue`, `resources/js/pages/**/Index.vue`

When a DataTable column or filter is a **date**, do not put it in the free-text `searchables` box. Dates are hard to type and easy to get wrong.

## Pattern

1. Expose the date as a column (display value).
2. Add it under DataTable `dateRanges` (query params `{key}_from` / `{key}_to`).
3. Render **one** range field in the toolbar: Popover + shadcn-vue `RangeCalendar` (`DataTableDateRangeFilter`). Do not use two separate native date inputs.
4. Server-side, interpret from/to as **Asia/Manila** calendar days and convert to UTC bounds for the query.

## Reference

See `resources/js/components/data-table/DataTableDateRangeFilter.vue` and Officer Payments (`recorded` range on `created_at`).
