# Data table row actions use Manage → dialog

**Glob:** `resources/js/pages/**/*RowActions.vue`, `resources/js/pages/**/columns.ts`

Data table action cells stay compact. Do not put forms, inputs, or multi-button action stacks inline in the row.

## Pattern

1. Render a single **Manage** (or equally short label like **End**) outline button in the actions column.
2. Open a **Dialog** for the real form(s): fields, confirm/reject/void/update, Cancel.
3. On successful Inertia submit (`@success`), **close the dialog**.
4. Rely on server `FlashToast` + `initializeFlashToast` for the status toast — do not invent a second toast path.
5. Use `:options="{ preserveScroll: true }"` on the dialog forms so the table does not jump.

## Reference

See `resources/js/pages/officer/payments/PaymentRowActions.vue` and `resources/js/pages/officer/memberships/MembershipRowActions.vue`.
