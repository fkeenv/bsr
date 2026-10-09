export type PropertyInvitationStatus =
    | 'unused'
    | 'consumed'
    | 'expired'
    | 'revoked';

export type PropertyInvitation = {
    id: number;
    property_id: number;
    property_label: string;
    role: string;
    creator_name: string;
    created_at: string;
    expires_at: string;
    status: PropertyInvitationStatus;
    can_revoke: boolean;
    can_share: boolean;
    sharing_unavailable_reason: string | null;
};

export type PropertyInvitationPropertyOption = {
    id: number;
    label: string;
};
