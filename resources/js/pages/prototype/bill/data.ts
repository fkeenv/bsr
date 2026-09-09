/**
 * PROTOTYPE fake data for printable bill layout variants.
 * Scenarios via ?scenario=current|behind|opening
 */

export type FeeLine = {
    name: string;
    amount: number;
};

export type OpenPeriod = {
    label: string;
    remaining: number;
};

export type BillProperty = {
    blockLot: string;
    propertyLabel: string;
    recordedOwner: string | null;
    address: string | null;
    openingBalanceRemaining: number;
    olderUnpaid: OpenPeriod[];
    thisMonth: {
        label: string;
        lines: FeeLine[];
        remaining: number;
    };
    outstandingBalance: number;
};

export type AssociationLetterhead = {
    name: string;
    shortName: string;
    addressLines: string[];
    contact: string;
    treasurer: string;
};

export type PaymentChannel = {
    method: string;
    detail: string;
};

export type ScenarioKey = 'current' | 'behind' | 'opening';

export const letterhead: AssociationLetterhead = {
    name: 'Blessed Sacrament Residences Homeowners Association',
    shortName: 'BSR HOA',
    addressLines: [
        'Blessed Sacrament Residences',
        'Quezon City, Metro Manila',
    ],
    contact: 'treasurer@bsr.example (placeholder)',
    treasurer: 'Treasurer — Maria Santos (placeholder)',
};

/** Placeholder channels — real account numbers are a later decision. */
export const paymentChannels: PaymentChannel[] = [
    { method: 'Cash', detail: 'Pay the Treasurer in person; ask for a handwritten receipt.' },
    { method: 'Bank transfer', detail: 'BDO · Account name TBA · Account no. TBA' },
    { method: 'GCash', detail: 'TBA — name the Property (Block + Lot) in the note.' },
    { method: 'Maya', detail: 'TBA — name the Property (Block + Lot) in the note.' },
];

export const scenarioMeta: Record<
    ScenarioKey,
    { label: string; blurb: string }
> = {
    current: {
        label: 'Current only',
        blurb: 'This Billing Period unpaid; no Opening Balance; no older months',
    },
    behind: {
        label: 'Months behind',
        blurb: 'Opening Balance + two older months + this month',
    },
    opening: {
        label: 'Opening only',
        blurb: 'Only Opening Balance remaining; this month already paid',
    },
};

const guardGarbage = (): FeeLine[] => [
    { name: 'Guard', amount: 200 },
    { name: 'Garbage collection', amount: 200 },
];

function property(
    partial: Omit<BillProperty, 'outstandingBalance'> & {
        outstandingBalance?: number;
    },
): BillProperty {
    const olderSum = partial.olderUnpaid.reduce((s, p) => s + p.remaining, 0);
    const outstanding =
        partial.outstandingBalance ??
        partial.openingBalanceRemaining +
            olderSum +
            partial.thisMonth.remaining;

    return { ...partial, outstandingBalance: outstanding };
}

const focusProperty: Record<ScenarioKey, BillProperty> = {
    current: property({
        blockLot: 'B4-L12',
        propertyLabel: 'Block 4 Lot 12',
        recordedOwner: 'Juan Dela Cruz',
        address: '12 Sampaguita St.',
        openingBalanceRemaining: 0,
        olderUnpaid: [],
        thisMonth: {
            label: 'September 2026',
            lines: guardGarbage(),
            remaining: 400,
        },
    }),
    behind: property({
        blockLot: 'B4-L12',
        propertyLabel: 'Block 4 Lot 12',
        recordedOwner: 'Juan Dela Cruz',
        address: '12 Sampaguita St.',
        openingBalanceRemaining: 600,
        olderUnpaid: [
            { label: 'July 2026', remaining: 400 },
            { label: 'August 2026', remaining: 400 },
        ],
        thisMonth: {
            label: 'September 2026',
            lines: guardGarbage(),
            remaining: 400,
        },
    }),
    opening: property({
        blockLot: 'B7-L03',
        propertyLabel: 'Block 7 Lot 3',
        recordedOwner: null,
        address: null,
        openingBalanceRemaining: 1200,
        olderUnpaid: [],
        thisMonth: {
            label: 'September 2026',
            lines: guardGarbage(),
            remaining: 0,
        },
    }),
};

/** Unpaid roster slice for the Officer batch variant. */
export function batchRoster(scenario: ScenarioKey): BillProperty[] {
    const focus = structuredClone(focusProperty[scenario]);
    const neighbors: BillProperty[] = [
        property({
            blockLot: 'B2-L08',
            propertyLabel: 'Block 2 Lot 8',
            recordedOwner: 'Ana Reyes',
            address: '8 Rosal St.',
            openingBalanceRemaining: 0,
            olderUnpaid: [],
            thisMonth: {
                label: 'September 2026',
                lines: guardGarbage(),
                remaining: 400,
            },
        }),
        property({
            blockLot: 'B5-L01',
            propertyLabel: 'Block 5 Lot 1',
            recordedOwner: 'Pedro Gomez',
            address: null,
            openingBalanceRemaining: 200,
            olderUnpaid: [{ label: 'August 2026', remaining: 400 }],
            thisMonth: {
                label: 'September 2026',
                lines: guardGarbage(),
                remaining: 400,
            },
        }),
        property({
            blockLot: 'B9-L15',
            propertyLabel: 'Block 9 Lot 15',
            recordedOwner: null,
            address: '15 Ilang-Ilang St.',
            openingBalanceRemaining: 0,
            olderUnpaid: [
                { label: 'June 2026', remaining: 400 },
                { label: 'July 2026', remaining: 400 },
                { label: 'August 2026', remaining: 400 },
            ],
            thisMonth: {
                label: 'September 2026',
                lines: guardGarbage(),
                remaining: 400,
            },
        }),
    ];

    return [focus, ...neighbors].filter((p) => p.outstandingBalance > 0);
}

export function getScenario(key: ScenarioKey): BillProperty {
    return structuredClone(focusProperty[key]);
}

export function formatPhp(amount: number): string {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        maximumFractionDigits: 0,
    }).format(amount);
}

export function payeeLabel(bill: BillProperty): string {
    return bill.recordedOwner ?? bill.propertyLabel;
}
