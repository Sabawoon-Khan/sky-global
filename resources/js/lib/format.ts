import type { LocaleCode } from '@/types/locale';

const VALID_CURRENCIES = new Set(['USD', 'AFN', 'EUR', 'GBP']);

/** Always use Western (0–9) digits, including in Pashto/Dari UI. */
export const LATIN_NUMERALS = { numberingSystem: 'latn' as const };

const INTL_LOCALE_MAP: Record<LocaleCode, string> = {
    en: 'en-US',
    fa: 'fa-AF',
    ps: 'ps-AF',
};

export function getIntlLocale(locale: LocaleCode): string {
    return INTL_LOCALE_MAP[locale] ?? 'en-US';
}

export function formatNumber(
    value?: number | null,
    options?: Intl.NumberFormatOptions,
): string {
    if (value == null) {
        return '—';
    }

    return new Intl.NumberFormat('en-US', {
        ...LATIN_NUMERALS,
        ...options,
    }).format(value);
}

export function formatLocalizedDate(
    value: Date | string,
    locale: LocaleCode,
    options?: Intl.DateTimeFormatOptions,
): string {
    const date = typeof value === 'string' ? new Date(value) : value;

    return new Intl.DateTimeFormat(getIntlLocale(locale), {
        ...LATIN_NUMERALS,
        ...options,
    }).format(date);
}

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
        ...LATIN_NUMERALS,
        style: 'currency',
        currency: normalizeCurrency(currency),
        maximumFractionDigits: 0,
    }).format(value);
}

export function formatDate(value?: string | null): string {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('en-US', {
        ...LATIN_NUMERALS,
        dateStyle: 'medium',
    }).format(new Date(value));
}

export function formatFileSize(bytes?: number | null): string {
    if (bytes == null) {
        return '—';
    }

    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    let size = bytes;
    let unitIndex = 0;

    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex += 1;
    }

    return `${formatNumber(size, { maximumFractionDigits: unitIndex === 0 ? 0 : 1, minimumFractionDigits: unitIndex === 0 ? 0 : 1 })} ${units[unitIndex]}`;
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
