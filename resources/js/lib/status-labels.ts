import type { MisTranslator } from '@/composables/useMisPage';

const PROJECT_STATUS_KEYS: Record<string, string> = {
    draft: 'Draft',
    submitted: 'Submitted',
    won: 'won',
    lost: 'lost',
    active: 'Active',
    suspended: 'Suspended',
    completed: 'Completed',
    closed: 'Closed',
};

const BID_STATUS_KEYS: Record<string, string> = {
    draft: 'Draft',
    submitted: 'Submitted',
    under_review: 'Under review',
    won: 'won',
    lost: 'lost',
    cancelled: 'Cancelled',
};

export function translateProjectStatus(t: MisTranslator, status: string): string {
    const key = PROJECT_STATUS_KEYS[status] ?? status.charAt(0).toUpperCase() + status.slice(1);
    const translated = t(key);

    return translated !== key ? translated : status;
}

export function translateBidStatus(t: MisTranslator, status: string): string {
    const key = BID_STATUS_KEYS[status] ?? status;
    const translated = t(key);

    return translated !== key ? translated : status;
}
