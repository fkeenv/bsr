import type { StaffPermission } from './auth';

export type RolePermission = {
    id: number | string;
    name: string;
    label: string;
    grants: StaffPermission[];
    is_builtin: boolean;
    assigned_count: number;
};

export type PermissionOption = {
    value: StaffPermission;
    label: string;
    description: string;
    feature: string;
};
