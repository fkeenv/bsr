export type DataTableFilterOption = {
    value: string;
    label: string;
};

export type DataTableValues = {
    search?: string | null;
    [key: string]: string | null | undefined;
};

export type DataTableConfig = {
    searchables: string[];
    filters: string[];
};
