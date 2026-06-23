import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

function normalizePermissions(raw: unknown): string[] {
    if (!raw) {
        return [];
    }

    if (Array.isArray(raw)) {
        return raw.map((permission) => {
            if (typeof permission === 'string') {
                return permission;
            }

            if (
                permission &&
                typeof permission === 'object' &&
                'name' in permission &&
                typeof permission.name === 'string'
            ) {
                return permission.name;
            }

            return String(permission);
        });
    }

    if (typeof raw === 'object') {
        return Object.values(raw).map((permission) => String(permission));
    }

    return [];
}

export function usePermissions() {
    const page = usePage();

    const permissions = computed(() =>
        normalizePermissions(page.props.auth?.user?.permissions),
    );

    const can = (permission: string): boolean =>
        permissions.value.includes(permission);

    const canAny = (permissionList: string[]): boolean =>
        permissionList.some((permission) => permissions.value.includes(permission));

    return { permissions, can, canAny };
}
