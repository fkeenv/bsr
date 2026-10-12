import type { StaffPermission } from '@/types';

export type PermissionCategory = 'read' | 'write' | 'destructive';

export const permissionCategories = {
    'announcements.read': 'read',
    'announcements.create': 'write',
    'announcements.edit': 'write',
    'announcements.publish': 'write',
    'announcements.pin': 'write',
    'announcements.attachments': 'write',
    'properties.read': 'read',
    'properties.create': 'write',
    'properties.edit': 'write',
    'properties.delete': 'destructive',
    'properties.deactivate': 'destructive',
    'properties.reactivate': 'write',
    'properties.import': 'write',
    'properties.import-sample': 'read',
    'properties.profiles.read': 'read',
    'properties.profiles.edit': 'write',
    'invitations.read': 'read',
    'invitations.create': 'write',
    'invitations.revoke': 'destructive',
    'invitations.credentials': 'read',
    'memberships.read': 'read',
    'memberships.change-role': 'write',
    'memberships.end': 'destructive',
    'suspends.read': 'read',
    'suspends.create': 'write',
    'suspends.edit': 'write',
} satisfies Record<StaffPermission, PermissionCategory>;
