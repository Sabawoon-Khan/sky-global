export function resolveNotificationPath(url: string | null | undefined): string | null {
    if (!url) {
        return null;
    }

    if (url.startsWith('/')) {
        return url;
    }

    try {
        const parsed = new URL(url, window.location.origin);

        return `${parsed.pathname}${parsed.search}`;
    } catch {
        return url;
    }
}
