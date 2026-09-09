export type User = {
    id: number;
    name: string;
    email: string;
    mobile_number: string | null;
    is_super_admin: boolean;
    avatar?: string;
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

export type Auth = {
    user: User;
    capabilities: AuthCapabilities | null;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
