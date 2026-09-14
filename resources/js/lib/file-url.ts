export function fileDownloadUrl(href: string): string {
    if (!href) {
        return href;
    }

    return href.includes('?') ? `${href}&download=1` : `${href}?download=1`;
}
