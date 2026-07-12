import { toast } from 'vue-sonner';

const TOAST_DURATION = 5000;

export function useNotify() {
    return {
        success: (message: string) =>
            toast.success(message, { duration: TOAST_DURATION }),
        error: (message: string) =>
            toast.error(message, { duration: TOAST_DURATION }),
        info: (message: string) => toast.info(message, { duration: TOAST_DURATION }),
        warning: (message: string) =>
            toast.warning(message, { duration: TOAST_DURATION }),
    };
}
