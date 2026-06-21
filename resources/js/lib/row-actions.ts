import type { Component } from 'vue';

export interface RowActionItem {
    label: string;
    icon?: Component;
    href?: string;
    method?: 'delete' | 'post' | 'put' | 'patch';
    onClick?: () => void;
    variant?: 'default' | 'destructive';
    disabled?: boolean;
    hidden?: boolean;
    confirm?: {
        title: string;
        description: string;
        confirmLabel?: string;
    };
    separator?: boolean;
}
