export type WebsiteSeo = {
    title?: string | null;
    description?: string | null;
    image?: string | null;
};

export type WebsiteStat = {
    value: string | number;
    label: string;
};

export type WebsiteService = {
    slug: string;
    title: string;
    summary: string;
    excerpt?: string | null;
    body?: string | string[] | null;
    bullets?: string[];
    closing?: string | null;
    image?: string | null;
};

export type WebsiteProject = {
    slug: string;
    title: string;
    summary: string;
    excerpt?: string | null;
    body?: string | string[] | null;
    status?: 'ongoing' | 'completed' | string | null;
    date?: string | null;
    start_date?: string | null;
    end_date?: string | null;
    posted_at?: string | null;
    image?: string | null;
};

export type WebsiteTraining = {
    slug: string;
    title: string;
    summary: string;
    excerpt?: string | null;
    body?: string | string[] | null;
    bullets?: string[];
    image?: string | null;
};

export type WebsiteTestimonial = {
    quote: string;
    name: string;
    role?: string | null;
};

export type WebsiteCertificate = {
    src: string;
    alt?: string | null;
    title?: string | null;
};

export type WebsiteContact = {
    phone?: string | null;
    phone_raw?: string | null;
    email?: string | null;
    address?: string | null;
    whatsapp?: string | null;
};

export type WebsiteClientLogo = {
    src: string;
    alt?: string | null;
};

export type WebsiteAbout = {
    title?: string | null;
    image?: string | null;
    paragraphs?: string[];
    body?: string | null;
    lead?: string | null;
};

export type WebsiteWhatWeDo = {
    title?: string | null;
    paragraphs?: string[];
    body?: string | null;
    lead?: string | null;
    sections?: { title: string; body: string }[];
};

export function asParagraphs(value: string | string[] | null | undefined): string[] {
    if (!value) {
        return [];
    }
    if (Array.isArray(value)) {
        return value.map((part) => part.trim()).filter(Boolean);
    }

    return value
        .split(/\n\s*\n/)
        .map((part) => part.trim())
        .filter(Boolean);
}
