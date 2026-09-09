export type Suspend = {
    id: number;
    property_id: number;
    property_label: string;
    fee_type_id: number;
    fee_type_name: string;
    starts_year: number;
    starts_month: number;
    ends_year: number | null;
    ends_month: number | null;
};

export type PropertyOption = {
    id: number;
    label: string;
};
