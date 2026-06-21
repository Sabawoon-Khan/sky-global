import { toast } from 'vue-sonner';
import type { FlashToast } from '@/types/ui';

const TOAST_DURATION = 5000;

export function showFlashToast(flash: Record<string, unknown> | undefined): void {
    const data = flash?.toast as FlashToast | undefined;

    if (!data?.message || !data?.type) {
        return;
    }

    toast[data.type](data.message, { duration: TOAST_DURATION });
}
