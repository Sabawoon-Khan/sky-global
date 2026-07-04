export type ThemeColorSettings = {
    brand: string;
    accent: string;
    backgroundLight: string;
    backgroundDark: string;
    accentDark: string;
};

export const THEME_COLORS_STORAGE_KEY = 'theme-colors';

export const DEFAULT_THEME_COLORS: ThemeColorSettings = {
    brand: '#0a0a0a',
    accent: '#dc2626',
    backgroundLight: '#f5f5f5',
    backgroundDark: '#0a0a0a',
    accentDark: '#ef4444',
};

export const THEME_COLOR_FIELDS: Array<{
    key: keyof ThemeColorSettings;
    labelKey: string;
    descriptionKey: string;
}> = [
    {
        key: 'brand',
        labelKey: 'Brand color',
        descriptionKey: 'Sidebar, text, and structural elements',
    },
    {
        key: 'accent',
        labelKey: 'Accent color',
        descriptionKey: 'Buttons, links, and highlights (light mode)',
    },
    {
        key: 'accentDark',
        labelKey: 'Accent color (dark mode)',
        descriptionKey: 'Buttons and highlights in dark mode',
    },
    {
        key: 'backgroundLight',
        labelKey: 'Background (light mode)',
        descriptionKey: 'Main page background in light mode',
    },
    {
        key: 'backgroundDark',
        labelKey: 'Background (dark mode)',
        descriptionKey: 'Main page background in dark mode',
    },
];

function hexToRgb(hex: string): { r: number; g: number; b: number } | null {
    const normalized = hex.replace('#', '');

    if (!/^[0-9a-fA-F]{6}$/.test(normalized)) {
        return null;
    }

    return {
        r: parseInt(normalized.slice(0, 2), 16),
        g: parseInt(normalized.slice(2, 4), 16),
        b: parseInt(normalized.slice(4, 6), 16),
    };
}

export function withAlpha(hex: string, alpha: number): string {
    const rgb = hexToRgb(hex);

    if (!rgb) {
        return hex;
    }

    return `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, ${alpha})`;
}

function isDarkMode(): boolean {
    if (typeof document === 'undefined') {
        return false;
    }

    return document.documentElement.classList.contains('dark');
}

export function applyThemeColors(settings: ThemeColorSettings | null): void {
    if (typeof document === 'undefined') {
        return;
    }

    const root = document.documentElement;
    const cssVars = [
        '--school-navy',
        '--school-gold',
        '--school-accent',
        '--school-panel',
        '--primary',
        '--accent-foreground',
        '--sidebar-background',
        '--sidebar',
        '--sidebar-primary',
        '--sidebar-ring',
        '--ring',
        '--accent',
        '--chart-1',
        '--chart-2',
        '--chart-3',
        '--background',
        '--secondary-foreground',
    ];

    if (!settings) {
        cssVars.forEach((name) => root.style.removeProperty(name));

        const meta = document.querySelector<HTMLMetaElement>(
            'meta[name="theme-color"]',
        );
        if (meta) {
            meta.remove();
        }

        return;
    }

    const dark = isDarkMode();
    const accent = dark ? settings.accentDark : settings.accent;
    const background = dark ? settings.backgroundDark : settings.backgroundLight;

    root.style.setProperty('--school-navy', settings.brand);
    root.style.setProperty('--school-gold', accent);
    root.style.setProperty('--school-accent', accent);
    root.style.setProperty('--school-panel', background);
    root.style.setProperty('--primary', accent);
    root.style.setProperty('--accent-foreground', accent);
    root.style.setProperty('--sidebar-background', settings.brand);
    root.style.setProperty('--sidebar', settings.brand);
    root.style.setProperty('--sidebar-primary', accent);
    root.style.setProperty('--sidebar-ring', withAlpha(accent, 0.4));
    root.style.setProperty('--ring', withAlpha(accent, dark ? 0.35 : 0.3));
    root.style.setProperty('--accent', withAlpha(accent, dark ? 0.15 : 0.1));
    root.style.setProperty('--chart-1', settings.brand);
    root.style.setProperty('--chart-2', accent);
    root.style.setProperty('--chart-3', settings.accentDark);
    root.style.setProperty('--background', background);
    root.style.setProperty('--secondary-foreground', settings.brand);

    let meta = document.querySelector<HTMLMetaElement>(
        'meta[name="theme-color"]',
    );
    if (!meta) {
        meta = document.createElement('meta');
        meta.name = 'theme-color';
        document.head.appendChild(meta);
    }
    meta.content = background;
}

export function loadStoredThemeColors(): ThemeColorSettings | null {
    if (typeof window === 'undefined') {
        return null;
    }

    try {
        const raw = localStorage.getItem(THEME_COLORS_STORAGE_KEY);

        if (!raw) {
            return null;
        }

        const parsed = JSON.parse(raw) as Partial<ThemeColorSettings>;

        return {
            ...DEFAULT_THEME_COLORS,
            ...parsed,
        };
    } catch {
        return null;
    }
}

export function saveThemeColors(settings: ThemeColorSettings): void {
    if (typeof window === 'undefined') {
        return;
    }

    localStorage.setItem(THEME_COLORS_STORAGE_KEY, JSON.stringify(settings));
    applyThemeColors(settings);
}

export function resetThemeColors(): void {
    if (typeof window === 'undefined') {
        return;
    }

    localStorage.removeItem(THEME_COLORS_STORAGE_KEY);
    applyThemeColors(null);
}

export function initializeThemeColors(): void {
    if (typeof window === 'undefined') {
        return;
    }

    applyThemeColors(loadStoredThemeColors());

    const observer = new MutationObserver(() => {
        const stored = loadStoredThemeColors();

        if (stored) {
            applyThemeColors(stored);
        }
    });

    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });
}
