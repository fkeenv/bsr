import type { AnnouncementPermission } from './auth';

export type RolePermission = {
    name: string;
    label: string;
    grants: AnnouncementPermission[];
};

export type PermissionOption = {
    value: AnnouncementPermission;
    label: string;
    description: string;
};
