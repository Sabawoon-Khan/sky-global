import { ref, watch } from 'vue';
import { slugify } from '@/lib/utils';

export function useAutoSlug() {
    const name = ref('');
    const slug = ref('');
    const syncWithName = ref(true);

    watch(name, (value) => {
        if (syncWithName.value) {
            slug.value = slugify(value);
        }
    });

    function onSlugInput(value: string | number): void {
        slug.value = String(value);
        syncWithName.value = false;
    }

    function initialize(initialName: string, initialSlug: string): void {
        name.value = initialName;
        slug.value = initialSlug || slugify(initialName);
        syncWithName.value =
            initialSlug === '' || initialSlug === slugify(initialName);
    }

    function reset(): void {
        name.value = '';
        slug.value = '';
        syncWithName.value = true;
    }

    return {
        name,
        slug,
        onSlugInput,
        initialize,
        reset,
    };
}
