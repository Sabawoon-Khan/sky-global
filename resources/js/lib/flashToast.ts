import { toast } from 'vue-sonner';
import type { FlashToast } from '@/types/ui';

const TOAST_DURATION = 5000;

export function showFlashToast(flash: Record<string, unknown> | undefined): void {
    if (!flash) {
        return;
    }

    const data = flash.toast as FlashToast | undefined;

    if (data?.message && data?.type) {
        toast[data.type](data.message, { duration: TOAST_DURATION });
        return;
    }

    if (typeof flash.success === 'string') {
        toast.success(flash.success, { duration: TOAST_DURATION });
    } else if (typeof flash.error === 'string') {
        toast.error(flash.error, { duration: TOAST_DURATION });
    } else if (typeof flash.info === 'string') {
        toast.info(flash.info, { duration: TOAST_DURATION });
    } else if (typeof flash.warning === 'string') {
        toast.warning(flash.warning, { duration: TOAST_DURATION });
    }
}
