export type LocaleCode = 'en' | 'fa' | 'ps';

export type TextDirection = 'ltr' | 'rtl';

export type LocaleOption = {
    code: LocaleCode;
    name: string;
    native: string;
    dir: TextDirection;
};

export type LocalePageProps = {
    locale: LocaleCode;
    dir: TextDirection;
    locales: LocaleOption[];
    translations: Record<string, string>;
};
