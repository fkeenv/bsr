export type AssociationEvent = {
    id: number;
    title: string;
    description: string;
    excerpt: string;
    image_url: string | null;
    venue: string;
    starts_at: string;
    ends_at: string;
    registration_deadline: string;
    audience: 'public' | 'members-only';
    audience_label: string;
    status: 'draft' | 'published' | 'rescheduled' | 'cancelled';
    capacity: number | null;
    price_centavos: number;
    payment_instructions: string | null;
};

export type EventScheduleChange = {
    id: number;
    changed_by_user_id: number;
    changed_at: string;
    old_starts_at: string;
    old_ends_at: string;
    old_registration_deadline: string;
    new_starts_at: string;
    new_ends_at: string;
    new_registration_deadline: string;
};

export type EventManagement = {
    registration_fields_locked: boolean;
    reserved_places: number;
};
