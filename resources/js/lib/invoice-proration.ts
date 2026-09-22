const parseDate = (value: string): Date =>
    new Date(`${value.slice(0, 10)}T00:00:00`);

const daysInMonth = (year: number, monthIndex: number): number =>
    new Date(year, monthIndex + 1, 0).getDate();

const addDays = (date: Date, days: number): Date => {
    const next = new Date(date);
    next.setDate(next.getDate() + days);

    return next;
};

const startOfDay = (date: Date): Date =>
    new Date(date.getFullYear(), date.getMonth(), date.getDate());

const endOfMonth = (date: Date): Date =>
    new Date(date.getFullYear(), date.getMonth() + 1, 0);

const diffDaysInclusive = (from: Date, to: Date): number => {
    const start = startOfDay(from).getTime();
    const end = startOfDay(to).getTime();

    return Math.round((end - start) / 86_400_000) + 1;
};

const proratedByCalendarMonths = (
    unitPrice: number,
    quantity: number,
    from: Date,
    to: Date,
): number => {
    let total = 0;
    let cursor = startOfDay(from);
    const end = startOfDay(to);

    while (cursor.getTime() <= end.getTime()) {
        const monthEnd = startOfDay(endOfMonth(cursor));
        const segmentEnd =
            monthEnd.getTime() > end.getTime() ? end : monthEnd;
        const daysInSegment = diffDaysInclusive(cursor, segmentEnd);
        const monthLength = daysInMonth(cursor.getFullYear(), cursor.getMonth());

        total += (unitPrice / monthLength) * quantity * daysInSegment;
        cursor = addDays(segmentEnd, 1);
    }

    return total;
};

export function proratedInvoiceLineTotal(
    unitPrice: number,
    quantity: number,
    days: number,
    periodStart?: string | null,
    periodEnd?: string | null,
    fallbackDate?: string | null,
): number {
    const billDays = Math.max(1, days);

    if (periodStart && periodEnd) {
        const from = parseDate(periodStart);
        const to = parseDate(periodEnd);

        if (!Number.isNaN(from.getTime()) && !Number.isNaN(to.getTime()) && to >= from) {
            const periodDays = diffDaysInclusive(from, to);
            const cappedDays = Math.min(billDays, periodDays);
            const billTo = addDays(from, cappedDays - 1);

            return roundMoney(
                proratedByCalendarMonths(unitPrice, quantity, from, billTo),
            );
        }
    }

    const reference = fallbackDate
        ? parseDate(fallbackDate)
        : new Date();
    const monthLength = daysInMonth(
        reference.getFullYear(),
        reference.getMonth(),
    );

    return roundMoney((unitPrice / monthLength) * quantity * billDays);
}

const roundMoney = (value: number): number =>
    Math.round(value * 100) / 100;
