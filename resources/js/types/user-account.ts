export type UserAccountMembership = {
    id: number;
    property_label: string;
    role: 'owner' | 'resident';
    started_at: string;
    ended_at: string | null;
    end_reason: string | null;
    ended_by: string | null;
};
export type UserAccount = {
    id: number;
    name: string;
    email: string;
    mobile_number: string | null;
    registered_at: string | null;
    staff_roles: string[];
    live_memberships: UserAccountMembership[];
    is_super_admin: boolean;
    has_owner_membership: boolean;
};
export type StaffRoleOption = {
    name: string;
    label: string;
    assigned: boolean;
    can_manage: boolean;
};
