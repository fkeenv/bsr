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
    status: 'draft' | 'published' | 'cancelled';
    capacity: number | null;
    price_centavos: number;
    payment_instructions: string | null;
};
