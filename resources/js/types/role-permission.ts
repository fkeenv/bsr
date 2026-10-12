import type { StaffPermission } from './auth';

export type RolePermission = {
    name: string;
    label: string;
    grants: StaffPermission[];
};

export type PermissionOption = {
    value: StaffPermission;
    label: string;
    description: string;
    feature: string;
};
