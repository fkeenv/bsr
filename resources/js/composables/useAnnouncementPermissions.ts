import { usePage } from '@inertiajs/vue3';
import type { AnnouncementPermission } from '@/types';

export function useAnnouncementPermissions() {
    const page = usePage();

    const allows = (permission: AnnouncementPermission) =>
        page.props.auth.announcementPermissions.includes(permission);

    return { allows };
}
