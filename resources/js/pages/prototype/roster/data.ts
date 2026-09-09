/**
 * PROTOTYPE fake data for Officer unpaid roster variants.
 * Scenarios via ?scenario=mixed|clear|crowded
 *
 * Roster = Properties with Outstanding Balance > ₱0 (ADR-0010).
 * Billing Period in view: September 2026.
 */

export type PeriodStatus = 'paid' | 'unpaid' | 'partial';
export type PaymentMethod = 'cash' | 'bank' | 'gcash' | 'maya';
export type PaymentStatus = 'pending' | 'confirmed' | 'rejected';

export type FeeLine = {
    name: string;
    amount: number;
};

export type PaymentRecord = {
    id: string;
    amount: number;
    method: PaymentMethod;
    reference: string | null;
    status: PaymentStatus;
    date: string;
    note?: string;
};

export type BillingPeriodView = {
    id: string;
    label: string;
    status: PeriodStatus;
    lines: FeeLine[];
    chargeTotal: number;
    remaining: number;
    payments: PaymentRecord[];
};

export type RosterProperty = {
    id: string;
    block: number;
    lot: number;
    propertyLabel: string;
    recordedOwner: string | null;
    outstandingBalance: number;
    openingBalanceRemaining: number;
    prepaidRemaining: number;
    thisPeriodStatus: PeriodStatus;
    /** Count of Billing Periods with remaining > 0 (Opening Balance is separate). */
    unpaidPeriodCount: number;
    /** Human hint for how far behind — Opening Balance, a month label, or null when clear. */
    oldestOpenLabel: string | null;
    pendingDeclarations: PaymentRecord[];
    periods: BillingPeriodView[];
};

export type ScenarioKey = 'mixed' | 'clear' | 'crowded';

export const thisBillingPeriodLabel = 'September 2026';

export const scenarioMeta: Record<
    ScenarioKey,
    { label: string; blurb: string }
> = {
    mixed: {
        label: 'Mixed',
        blurb: 'Opening-only, this-month-only, months-behind, and partial — the shapes Officers actually scan',
    },
    clear: {
        label: 'All clear',
        blurb: 'Every Property at ₱0 — empty unpaid roster',
    },
    crowded: {
        label: 'Crowded',
        blurb: 'Longer list to feel scan density (~18 unpaid Properties)',
    },
};

/** Thin Officer surfaces — where unpaid sits among the rest (MVP). */
export const officerNav = [
    { key: 'announcements', label: 'Announcements', active: false },
    { key: 'unpaid', label: 'Unpaid', active: true },
    { key: 'applications', label: 'Applications', active: false },
    { key: 'payments', label: 'Payments', active: false },
    { key: 'levy', label: 'Levy', active: false },
] as const;

const guardGarbage = (): FeeLine[] => [
    { name: 'Guard', amount: 200 },
    { name: 'Garbage collection', amount: 200 },
];

function period(
    id: string,
    label: string,
    status: PeriodStatus,
    remaining: number,
    payments: PaymentRecord[] = [],
): BillingPeriodView {
    return {
        id,
        label,
        status,
        lines: guardGarbage(),
        chargeTotal: 400,
        remaining,
        payments,
    };
}

function property(partial: {
    id: string;
    block: number;
    lot: number;
    recordedOwner: string | null;
    openingBalanceRemaining: number;
    prepaidRemaining?: number;
    periods: BillingPeriodView[];
    pendingDeclarations?: PaymentRecord[];
}): RosterProperty {
    const unpaidPeriods = partial.periods.filter((p) => p.remaining > 0);
    const chargeRemaining = unpaidPeriods.reduce((s, p) => s + p.remaining, 0);
    const outstanding =
        partial.openingBalanceRemaining +
        chargeRemaining -
        (partial.prepaidRemaining ?? 0);
    const thisPeriod =
        partial.periods.find((p) => p.id === '2026-09') ?? partial.periods[0];
    const oldestCharge = [...unpaidPeriods].sort((a, b) =>
        a.id.localeCompare(b.id),
    )[0];
    let oldestOpenLabel: string | null = null;
    if (partial.openingBalanceRemaining > 0) {
        oldestOpenLabel = 'Opening Balance';
    } else if (oldestCharge) {
        oldestOpenLabel = oldestCharge.label;
    }

    return {
        id: partial.id,
        block: partial.block,
        lot: partial.lot,
        propertyLabel: `Block ${partial.block} Lot ${partial.lot}`,
        recordedOwner: partial.recordedOwner,
        outstandingBalance: Math.max(0, outstanding),
        openingBalanceRemaining: partial.openingBalanceRemaining,
        prepaidRemaining: partial.prepaidRemaining ?? 0,
        thisPeriodStatus: thisPeriod.status,
        unpaidPeriodCount: unpaidPeriods.length,
        oldestOpenLabel,
        pendingDeclarations: partial.pendingDeclarations ?? [],
        periods: partial.periods,
    };
}

const mixedUnpaid: RosterProperty[] = [
    property({
        id: 'p-opening',
        block: 2,
        lot: 5,
        recordedOwner: 'Reyes, Ana',
        openingBalanceRemaining: 800,
        periods: [
            period('2026-09', 'September 2026', 'paid', 0, [
                {
                    id: 'pay-sep-ok',
                    amount: 400,
                    method: 'gcash',
                    reference: 'GC-100',
                    status: 'confirmed',
                    date: '2026-09-03',
                },
            ]),
            period('2026-08', 'August 2026', 'paid', 0),
        ],
    }),
    property({
        id: 'p-current',
        block: 4,
        lot: 12,
        recordedOwner: 'Santos, Miguel',
        openingBalanceRemaining: 0,
        periods: [
            period('2026-09', 'September 2026', 'unpaid', 400),
            period('2026-08', 'August 2026', 'paid', 0),
            period('2026-07', 'July 2026', 'paid', 0),
        ],
        pendingDeclarations: [
            {
                id: 'pend-1',
                amount: 400,
                method: 'maya',
                reference: 'MY-8821',
                status: 'pending',
                date: '2026-09-08',
                note: 'Waiting for Officer confirmation',
            },
        ],
    }),
    property({
        id: 'p-behind',
        block: 1,
        lot: 3,
        recordedOwner: null,
        openingBalanceRemaining: 600,
        periods: [
            period('2026-09', 'September 2026', 'unpaid', 400),
            period('2026-08', 'August 2026', 'unpaid', 400),
            period('2026-07', 'July 2026', 'partial', 200, [
                {
                    id: 'pay-jul',
                    amount: 200,
                    method: 'bank',
                    reference: 'BNK-44',
                    status: 'confirmed',
                    date: '2026-07-20',
                },
            ]),
            period('2026-06', 'June 2026', 'paid', 0),
        ],
    }),
    property({
        id: 'p-partial',
        block: 7,
        lot: 18,
        recordedOwner: 'Cruz, Liza',
        openingBalanceRemaining: 0,
        periods: [
            period('2026-09', 'September 2026', 'partial', 200, [
                {
                    id: 'pay-half',
                    amount: 200,
                    method: 'cash',
                    reference: null,
                    status: 'confirmed',
                    date: '2026-09-05',
                },
            ]),
            period('2026-08', 'August 2026', 'paid', 0),
        ],
    }),
    property({
        id: 'p-older-only',
        block: 5,
        lot: 9,
        recordedOwner: 'Garcia, Ben',
        openingBalanceRemaining: 0,
        periods: [
            period('2026-09', 'September 2026', 'paid', 0, [
                {
                    id: 'pay-sep2',
                    amount: 400,
                    method: 'bank',
                    reference: 'BNK-91',
                    status: 'confirmed',
                    date: '2026-09-02',
                },
            ]),
            period('2026-08', 'August 2026', 'unpaid', 400),
            period('2026-07', 'July 2026', 'unpaid', 400),
        ],
    }),
];

function padCrowded(base: RosterProperty[]): RosterProperty[] {
    const extras: RosterProperty[] = [];
    const seeds = [
        [3, 1, 'Lim, Joy'],
        [3, 4, 'Tan, Rico'],
        [3, 8, null],
        [6, 2, 'Ong, Maya'],
        [6, 11, 'Diaz, Carlo'],
        [8, 3, 'Flores, Pia'],
        [8, 7, null],
        [9, 1, 'Navarro, Sam'],
        [9, 14, 'Bautista, Tess'],
        [10, 2, 'Villanueva, Jun'],
        [10, 6, null],
        [11, 5, 'Aquino, Kate'],
        [12, 1, 'Mendoza, Theo'],
    ] as const;

    seeds.forEach(([block, lot, owner], i) => {
        const kind = i % 4;
        if (kind === 0) {
            extras.push(
                property({
                    id: `crowd-${i}`,
                    block,
                    lot,
                    recordedOwner: owner,
                    openingBalanceRemaining: 400 + (i % 3) * 200,
                    periods: [
                        period('2026-09', 'September 2026', 'paid', 0),
                        period('2026-08', 'August 2026', 'paid', 0),
                    ],
                }),
            );
        } else if (kind === 1) {
            extras.push(
                property({
                    id: `crowd-${i}`,
                    block,
                    lot,
                    recordedOwner: owner,
                    openingBalanceRemaining: 0,
                    periods: [
                        period('2026-09', 'September 2026', 'unpaid', 400),
                        period('2026-08', 'August 2026', 'paid', 0),
                    ],
                }),
            );
        } else if (kind === 2) {
            extras.push(
                property({
                    id: `crowd-${i}`,
                    block,
                    lot,
                    recordedOwner: owner,
                    openingBalanceRemaining: 200,
                    periods: [
                        period('2026-09', 'September 2026', 'unpaid', 400),
                        period('2026-08', 'August 2026', 'unpaid', 400),
                        period('2026-07', 'July 2026', 'unpaid', 400),
                    ],
                }),
            );
        } else {
            extras.push(
                property({
                    id: `crowd-${i}`,
                    block,
                    lot,
                    recordedOwner: owner,
                    openingBalanceRemaining: 0,
                    periods: [
                        period('2026-09', 'September 2026', 'partial', 200, [
                            {
                                id: `crowd-pay-${i}`,
                                amount: 200,
                                method: 'gcash',
                                reference: `GC-C${i}`,
                                status: 'confirmed',
                                date: '2026-09-04',
                            },
                        ]),
                        period('2026-08', 'August 2026', 'paid', 0),
                    ],
                }),
            );
        }
    });

    return [...base, ...extras];
}

export type RosterScenario = {
    unpaid: RosterProperty[];
    /** Total Properties on the association roster (for empty-state copy). */
    rosterCount: number;
};

const scenarios: Record<ScenarioKey, RosterScenario> = {
    mixed: {
        unpaid: mixedUnpaid,
        rosterCount: 148,
    },
    clear: {
        unpaid: [],
        rosterCount: 148,
    },
    crowded: {
        unpaid: padCrowded(mixedUnpaid),
        rosterCount: 148,
    },
};

export function getScenario(key: ScenarioKey): RosterScenario {
    return structuredClone(scenarios[key]);
}

/** Default scan order: highest Outstanding Balance first. */
export function sortByOutstandingDesc(
    rows: RosterProperty[],
): RosterProperty[] {
    return [...rows].sort(
        (a, b) =>
            b.outstandingBalance - a.outstandingBalance ||
            a.block - b.block ||
            a.lot - b.lot,
    );
}

export function sortByBlockLot(rows: RosterProperty[]): RosterProperty[] {
    return [...rows].sort(
        (a, b) => a.block - b.block || a.lot - b.lot,
    );
}

export function formatPhp(amount: number): string {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        maximumFractionDigits: 0,
    }).format(amount);
}

export function thisPeriodLabel(status: PeriodStatus): string {
    const labels: Record<PeriodStatus, string> = {
        paid: 'Paid',
        unpaid: 'Unpaid',
        partial: 'Partial',
    };

    return labels[status];
}

export function methodLabel(method: PaymentMethod): string {
    const labels: Record<PaymentMethod, string> = {
        cash: 'Cash',
        bank: 'Bank transfer',
        gcash: 'GCash',
        maya: 'Maya',
    };

    return labels[method];
}

export function statusLabel(status: PeriodStatus): string {
    return thisPeriodLabel(status);
}

/** Bucket for Variant B severity groups. */
export type SeverityBucket =
    | 'this_month'
    | 'behind'
    | 'opening_only'
    | 'older_only';

export function severityBucket(row: RosterProperty): SeverityBucket {
    const thisUnpaid =
        row.thisPeriodStatus === 'unpaid' || row.thisPeriodStatus === 'partial';
    const hasOlderCharges = row.periods.some(
        (p) => p.id !== '2026-09' && p.remaining > 0,
    );
    const hasOpening = row.openingBalanceRemaining > 0;

    if (thisUnpaid && (hasOlderCharges || hasOpening)) {
        return 'behind';
    }

    if (thisUnpaid) {
        return 'this_month';
    }

    if (hasOpening && !hasOlderCharges) {
        return 'opening_only';
    }

    return 'older_only';
}

export const severityMeta: Record<
    SeverityBucket,
    { title: string; hint: string }
> = {
    this_month: {
        title: 'This Billing Period unpaid',
        hint: 'Owes September; older months clear',
    },
    behind: {
        title: 'Months behind',
        hint: 'September open plus Opening Balance and/or older Charges',
    },
    opening_only: {
        title: 'Opening Balance only',
        hint: 'This Billing Period paid; prior arrears remain',
    },
    older_only: {
        title: 'Older months only',
        hint: 'September paid; earlier Charges still open',
    },
};
