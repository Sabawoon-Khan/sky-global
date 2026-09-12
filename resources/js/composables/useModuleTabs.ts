import { computed } from 'vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import {
    isNavItemActive,
    useMisNavigation,
} from '@/composables/useMisNavigation';
import { toUrl } from '@/lib/utils';
import type { NavItem } from '@/types';

export function navItemPath(href: NavItem['href']): string {
    const raw = toUrl(href).split('?')[0] ?? '';

    if (!raw.startsWith('http')) {
        return raw || '/';
    }

    try {
        return new URL(raw).pathname;
    } catch {
        return raw;
    }
}

function matchScore(item: NavItem, currentPath: string): number {
    const path = navItemPath(item.href);

    if (currentPath === path) {
        return path.length + 1000;
    }

    if (item.exact) {
        return -1;
    }

    if (path !== '/' && currentPath.startsWith(`${path}/`)) {
        return path.length;
    }

    return -1;
}

export function matchNavItem(
    items: NavItem[],
    currentPath: string,
): NavItem | null {
    let best: NavItem | null = null;
    let bestScore = -1;

    for (const item of items) {
        if (item.matchPrefixes?.length) {
            if (isNavItemActive(item, currentPath)) {
                const score = Math.max(
                    ...item.matchPrefixes.map((prefix) => prefix.length),
                );
                if (score > bestScore) {
                    best = item;
                    bestScore = score;
                }
            }
            continue;
        }

        const score = matchScore(item, currentPath);

        if (score > bestScore) {
            best = item;
            bestScore = score;
        }
    }

    return best;
}

export function useModuleTabs() {
    const { tabGroups } = useMisNavigation();
    const { currentUrl } = useCurrentUrl();

    const isSettingsArea = computed(() =>
        currentUrl.value.startsWith('/settings'),
    );

    const tabItems = computed<NavItem[]>(() => {
        if (isSettingsArea.value) {
            return [];
        }

        const path = currentUrl.value;
        let bestGroup = tabGroups.value[0];
        let bestScore = -1;

        for (const group of tabGroups.value) {
            const match = matchNavItem(group.items, path);

            if (!match) {
                continue;
            }

            const score = match.matchPrefixes?.length
                ? Math.max(...match.matchPrefixes.map((prefix) => prefix.length))
                : matchScore(match, path);

            if (score > bestScore) {
                bestGroup = group;
                bestScore = score;
            }
        }

        return bestGroup?.items ?? [];
    });

    return {
        tabItems,
        isSettingsArea,
    };
}
