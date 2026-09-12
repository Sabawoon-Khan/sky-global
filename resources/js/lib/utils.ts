import type { InertiaLinkProps } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
    return typeof href === 'string' ? href : href?.url;
}

export function slugify(value: string): string {
    return value
        .normalize('NFKD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

const CURRENCY_ALIASES: Record<string, string> = { AFG: 'AFN' };

export function resolveCurrency(
    code?: string | null,
    fallback?: string | null,
): string {
    const raw = String(code || fallback || pageBaseCurrency())
        .trim()
        .toUpperCase();
    const mapped = CURRENCY_ALIASES[raw] ?? raw;

    return /^[A-Z]{3}$/.test(mapped) ? mapped : pageBaseCurrency();
}

function pageBaseCurrency(): string {
    try {
        const base = (
            usePage().props.company as { base_currency?: string } | undefined
        )?.base_currency;
        const raw = String(base || 'AFN').trim().toUpperCase();
        const mapped = CURRENCY_ALIASES[raw] ?? raw;

        return /^[A-Z]{3}$/.test(mapped) ? mapped : 'AFN';
    } catch {
        return 'AFN';
    }
}

export function money(value: unknown, currency?: string | null) {
    const code = resolveCurrency(currency);
    const amount = Number(value ?? 0);
    const safeAmount = Number.isFinite(amount) ? amount : 0;
    const formatted = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(safeAmount);

    return `${code} ${formatted}`;
}

/**
 * Always returns AFN + USD (and any extras), including explicit zeros.
 * Fallback is only used when `amounts` itself is missing/null/undefined.
 */
export function normalizeCurrencyAmounts(
    amounts?: Record<string, number> | null,
    fallbackValue?: unknown,
): Record<string, number> {
    const merged: Record<string, number> = { AFN: 0, USD: 0 };
    const provided = amounts != null;

    for (const [code, amount] of Object.entries(amounts ?? {})) {
        const resolved = resolveCurrency(code);
        const value = Number(amount ?? 0);
        if (!Number.isFinite(value)) {
            continue;
        }
        merged[resolved] = (merged[resolved] ?? 0) + value;
    }

    if (!provided) {
        const fallback = Number(fallbackValue ?? 0);
        if (Number.isFinite(fallback) && fallback !== 0) {
            merged[pageBaseCurrency()] = fallback;
        }
    }

    return merged;
}

export function currencyAmountLines(
    amounts?: Record<string, number> | null,
    fallbackValue?: unknown,
): Array<{ code: string; value: number; text: string }> {
    const merged = normalizeCurrencyAmounts(amounts, fallbackValue);
    const preferred = ['AFN', 'USD'];
    const extras = Object.keys(merged)
        .filter((code) => !preferred.includes(code))
        .sort((a, b) => a.localeCompare(b));

    return [...preferred, ...extras].map((code) => {
        const value = merged[code] ?? 0;

        return {
            code,
            value,
            text: money(value, code),
        };
    });
}

export function moneyByCurrency(
    amounts?: Record<string, number> | null,
    fallbackValue?: unknown,
): string {
    return currencyAmountLines(amounts, fallbackValue)
        .map((line) => line.text)
        .join(' · ');
}
