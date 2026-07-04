import { onMounted, ref } from 'vue';
import {
    DEFAULT_THEME_COLORS,
    loadStoredThemeColors,
    resetThemeColors,
    saveThemeColors,
    type ThemeColorSettings,
} from '@/lib/theme-colors';

function cloneDefaults(): ThemeColorSettings {
    return { ...DEFAULT_THEME_COLORS };
}

function isValidHex(hex: string): boolean {
    return /^#[0-9a-fA-F]{6}$/.test(hex);
}

export function useThemeColors() {
    const colors = ref<ThemeColorSettings>(cloneDefaults());
    const isCustomized = ref(false);

    onMounted(() => {
        const stored = loadStoredThemeColors();

        if (stored) {
            colors.value = { ...stored };
            isCustomized.value = true;
        }
    });

    function setColor(key: keyof ThemeColorSettings, value: string) {
        const normalized = value.startsWith('#') ? value : `#${value}`;

        if (!isValidHex(normalized)) {
            colors.value = { ...colors.value, [key]: normalized };
            return;
        }

        colors.value = { ...colors.value, [key]: normalized };
        isCustomized.value = true;
        saveThemeColors(colors.value);
    }

    function resetColors() {
        colors.value = cloneDefaults();
        isCustomized.value = false;
        resetThemeColors();
    }

    return {
        colors,
        isCustomized,
        setColor,
        resetColors,
        defaults: DEFAULT_THEME_COLORS,
    };
}
