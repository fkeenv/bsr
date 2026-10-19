import { createColumnHelper } from '@tanstack/vue-table';
import { h } from 'vue';
import type { DataTableFeatures } from '@/components/data-table/features';
import { Badge } from '@/components/ui/badge';
import { formatEventMoney } from '@/lib/eventMoney';
import type { EventFinanceRow, EventFinanceWork } from '@/types/event-finance';
import EventFinanceRowActions from './EventFinanceRowActions.vue';

const helper = createColumnHelper<DataTableFeatures, EventFinanceRow>();
const labels: Record<EventFinanceWork, string> = {
    'payment-review': 'Payment review',
    'cancellation-review': 'Cancellation review',
    'refund-due': 'Refund due',
};

export function eventFinanceColumns(
    canReviewPayments: boolean,
    canReviewRefunds: boolean,
) {
    return helper.columns([
        helper.accessor((row) => row.registration.event.title, {
            id: 'event',
            header: 'Event',
            enableSorting: false,
            cell: ({ row, getValue }) =>
                h('div', { class: 'min-w-40 max-w-64 whitespace-normal' }, [
                    h('p', { class: 'font-medium' }, getValue()),
                    h(
                        'p',
                        { class: 'text-muted-foreground text-sm' },
                        `Registration #${row.original.id}`,
                    ),
                ]),
        }),
        helper.accessor((row) => row.registration.account_name, {
            id: 'Registered by',
            header: 'Registered by',
            enableSorting: false,
            cell: ({ row, getValue }) =>
                h('div', { class: 'min-w-32 whitespace-normal' }, [
                    h('p', {}, getValue()),
                    h(
                        'p',
                        { class: 'text-muted-foreground text-sm' },
                        `${row.original.registration.attendee_count} attendees · ${row.original.registration.status_label}`,
                    ),
                ]),
        }),
        helper.accessor('work', {
            id: 'Needs attention',
            header: 'Needs attention',
            enableSorting: false,
            cell: ({ getValue }) =>
                getValue().length
                    ? h(
                          'div',
                          { class: 'flex min-w-32 flex-wrap gap-1' },
                          getValue().map((work) =>
                              h(
                                  Badge,
                                  { variant: 'secondary' },
                                  () => labels[work],
                              ),
                          ),
                      )
                    : h(
                          'span',
                          { class: 'text-muted-foreground text-sm' },
                          'No action for you',
                      ),
        }),
        helper.accessor((row) => row.registration.total_centavos, {
            id: 'Registration total',
            header: 'Registration total',
            enableSorting: false,
            cell: ({ getValue }) =>
                h(
                    'span',
                    { class: 'tabular-nums' },
                    formatEventMoney(getValue()),
                ),
        }),
        helper.accessor(
            (row) => row.financial.payment?.refund_due_centavos ?? 0,
            {
                id: 'Refund still due',
                header: 'Refund still due',
                enableSorting: false,
                cell: ({ getValue }) =>
                    h(
                        'span',
                        { class: 'font-medium tabular-nums' },
                        formatEventMoney(getValue()),
                    ),
            },
        ),
        helper.accessor(
            (row) => row.financial.payment?.refunded_centavos ?? 0,
            {
                id: 'Refunds recorded',
                header: 'Refunds recorded',
                enableSorting: false,
                cell: ({ getValue }) =>
                    h(
                        'span',
                        { class: 'text-muted-foreground tabular-nums' },
                        formatEventMoney(getValue()),
                    ),
            },
        ),
        helper.display({
            id: 'actions',
            enableHiding: false,
            enableSorting: false,
            header: () => h('div', { class: 'text-right' }, 'Actions'),
            cell: ({ row }) =>
                h(EventFinanceRowActions, {
                    item: row.original,
                    canReviewPayments,
                    canReviewRefunds,
                }),
        }),
    ]);
}
