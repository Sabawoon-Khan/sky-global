const VALID_CURRENCIES = new Set(['USD', 'AFN', 'EUR', 'GBP']);

export function normalizeCurrency(currency?: string | null): string {
    const code = currency?.trim().toUpperCase();

    if (code && VALID_CURRENCIES.has(code)) {
        return code;
    }

    return 'USD';
}

export function formatCurrency(
    value?: number | null,
    currency?: string | null,
): string {
    if (value == null) {
        return '—';
    }

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: normalizeCurrency(currency),
        maximumFractionDigits: 0,
    }).format(value);
}

export function formatDate(value?: string | null): string {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('en-US', { dateStyle: 'medium' }).format(
        new Date(value),
    );
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    links?: PaginationLink[];
    meta?: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };
}
