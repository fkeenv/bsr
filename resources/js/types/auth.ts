export type User = {
    id: number;
    name: string;
    email: string;
    mobile_number: string | null;
    is_super_admin: boolean;
    title?: string | null;
    avatar?: string | null;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type AuthCapabilities = {
    isSuperAdmin: boolean;
    canAccessOfficer: boolean;
    canAccessAdministrator: boolean;
    isMembershipHolder: boolean;
};

export type AnnouncementPermission =
    | 'announcements.read'
    | 'announcements.create'
    | 'announcements.edit'
    | 'announcements.publish'
    | 'announcements.pin'
    | 'announcements.attachments';

export type HouseholdPermission =
    | 'properties.read'
    | 'properties.create'
    | 'properties.edit'
    | 'properties.delete'
    | 'properties.deactivate'
    | 'properties.reactivate'
    | 'properties.import'
    | 'properties.import-sample'
    | 'properties.profiles.read'
    | 'properties.profiles.edit'
    | 'invitations.read'
    | 'invitations.create'
    | 'invitations.revoke'
    | 'invitations.credentials'
    | 'memberships.read'
    | 'memberships.change-role'
    | 'memberships.end'
    | 'suspends.read'
    | 'suspends.create'
    | 'suspends.edit';

export type FinancialPermission =
    | 'payments.read'
    | 'payments.create'
    | 'payments.confirm'
    | 'payments.reject'
    | 'payments.void'
    | 'charges.read'
    | 'charges.edit'
    | 'charges.generate'
    | 'unpaid.read'
    | 'printed-bills.single'
    | 'printed-bills.batch'
    | 'fee-types.read'
    | 'fee-types.create'
    | 'fee-types.edit'
    | 'levy-settings.read'
    | 'levy-settings.edit'
    | 'bill-settings.read'
    | 'bill-settings.edit';

export type EventPermission =
    | 'events.manage'
    | 'events.registrations.read'
    | 'events.payments.review'
    | 'events.refunds.review'
    | 'events.check-in';

export type StaffPermission =
    | EventPermission
    | AnnouncementPermission
    | HouseholdPermission
    | FinancialPermission;

export type Auth = {
    user: User | null;
    capabilities: AuthCapabilities | null;
    announcementPermissions: AnnouncementPermission[];
    staffPermissions: StaffPermission[];
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
