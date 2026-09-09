import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import type { FlashToast, FlashToastType } from '@/types/ui';

const toastTypes: FlashToastType[] = ['success', 'info', 'warning', 'error'];

function isFlashToastType(type: string): type is FlashToastType {
    return toastTypes.includes(type as FlashToastType);
}

export function initializeFlashToast(): void {
    router.on('flash', (event) => {
        const flash = (event as CustomEvent).detail?.flash;
        const data = flash?.toast as FlashToast | undefined;

        if (!data?.message) {
            return;
        }

        if (isFlashToastType(data.type)) {
            toast[data.type](data.message);

            return;
        }

        toast(data.message);
    });
}
