<script setup lang="ts">
import { Languages } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useLocale } from '@/composables/useLocale';
import { useTranslations } from '@/composables/useTranslations';

const { locale, locales, updateLocale } = useLocale();
const { t } = useTranslations();

const currentLocale = computed(() =>
    locales.value.find((option) => option.code === locale.value),
);
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" class="h-9 w-9">
                <Languages class="size-4" />
                <span class="sr-only">{{ currentLocale?.name ?? t('Language') }}</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuItem
                v-for="option in locales"
                :key="option.code"
                class="cursor-pointer"
                :class="{ 'bg-accent': locale === option.code }"
                @click="updateLocale(option.code)"
            >
                <span>{{ option.native }}</span>
                <span
                    v-if="option.native !== option.name"
                    class="ms-2 text-xs text-muted-foreground"
                >
                    {{ option.name }}
                </span>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
