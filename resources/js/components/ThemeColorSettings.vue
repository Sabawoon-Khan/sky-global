<script setup lang="ts">
import { RotateCcw } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useThemeColors } from '@/composables/useThemeColors';
import { useTranslations } from '@/composables/useTranslations';
import { THEME_COLOR_FIELDS } from '@/lib/theme-colors';

const { t } = useTranslations();
const { colors, isCustomized, setColor, resetColors } = useThemeColors();
</script>

<template>
    <Card>
        <CardHeader
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <div>
                <CardTitle>{{ t('Theme colors') }}</CardTitle>
                <CardDescription>
                    {{
                        t(
                            'Customize brand and accent colors across the entire application.',
                        )
                    }}
                </CardDescription>
            </div>
            <Button
                type="button"
                variant="outline"
                size="sm"
                class="shrink-0"
                :disabled="!isCustomized"
                @click="resetColors"
            >
                <RotateCcw class="size-4" />
                {{ t('Reset to default') }}
            </Button>
        </CardHeader>
        <CardContent class="space-y-6">
            <div
                class="flex items-center gap-3 rounded-xl bg-muted/50 px-4 py-3"
            >
                <div
                    class="size-10 shrink-0 rounded-lg shadow-sm"
                    :style="{
                        background: `linear-gradient(135deg, ${colors.brand} 50%, ${colors.accent} 50%)`,
                    }"
                />
                <div class="min-w-0 text-sm">
                    <p class="font-medium">{{ t('Live preview') }}</p>
                    <p class="text-muted-foreground">
                        {{ t('Changes apply instantly across the system.') }}
                    </p>
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div
                    v-for="field in THEME_COLOR_FIELDS"
                    :key="field.key"
                    class="space-y-2"
                >
                    <Label :for="`theme-${field.key}`">{{
                        t(field.labelKey)
                    }}</Label>
                    <p class="text-xs text-muted-foreground">
                        {{ t(field.descriptionKey) }}
                    </p>
                    <div class="flex items-center gap-2">
                        <input
                            :id="`theme-${field.key}`"
                            type="color"
                            class="size-10 shrink-0 cursor-pointer rounded-lg border-0 bg-transparent p-0"
                            :value="colors[field.key]"
                            @input="
                                setColor(
                                    field.key,
                                    ($event.target as HTMLInputElement).value,
                                )
                            "
                        />
                        <Input
                            class="font-mono text-sm uppercase"
                            :model-value="colors[field.key]"
                            maxlength="7"
                            @update:model-value="
                                (value) =>
                                    setColor(field.key, String(value ?? ''))
                            "
                        />
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
