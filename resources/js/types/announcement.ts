export type AnnouncementAttachment = {
    id: number;
    original_filename: string;
    mime_type: string;
    size: number;
    is_image: boolean;
    is_pdf: boolean;
    url: string;
};

export type AnnouncementVisibilityOption = {
    value: 'public' | 'private' | 'hidden';
    label: string;
    description: string;
};

export type Announcement = {
    id: number;
    title: string;
    body: string;
    excerpt: string;
    visibility: 'public' | 'private' | 'hidden';
    visibility_label: string;
    is_published: boolean;
    is_pinned: boolean;
    published_at: string | null;
    pinned_at: string | null;
    updated_at: string | null;
    attachments: AnnouncementAttachment[];
};
