import { usePage } from '@inertiajs/vue3';
import type { StaffPermission } from '@/types';

export function useStaffPermissions() {
    const page = usePage();
    const allows = (permission: StaffPermission) =>
        page.props.auth.staffPermissions.includes(permission);
    return { allows };
}
