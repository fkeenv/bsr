export type Membership = {
    id: number;
    user_id: number;
    property_id: number;
    role: string;
    started_at: string;
    ended_at: string | null;
    property_label: string | null;
    user_name: string | null;
};
