export type ChargeLine = {
    id: number;
    fee_type_id: number | null;
    fee_type_name: string;
    amount: string;
};

export type Charge = {
    id: number;
    property_id: number;
    property_label: string;
    year: number;
    month: number;
    period_label: string;
    is_frozen: boolean;
    lines: ChargeLine[];
};
