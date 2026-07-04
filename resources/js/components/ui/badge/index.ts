import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Badge } from "./Badge.vue"

export const badgeVariants = cva(
  "inline-flex items-center justify-center rounded-full border px-2.5 py-1 text-xs font-semibold w-fit whitespace-nowrap shrink-0 [&>svg]:size-3 gap-1 [&>svg]:pointer-events-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] transition-[color,box-shadow] overflow-hidden ring-1 ring-inset",
  {
    variants: {
      variant: {
        default:
          "border-transparent bg-primary text-primary-foreground ring-primary/20 [a&]:hover:bg-primary/90",
        secondary:
          "border-transparent bg-secondary text-secondary-foreground ring-border/40 [a&]:hover:bg-secondary/90",
        destructive:
         "border-transparent bg-destructive text-white ring-destructive/20 [a&]:hover:bg-destructive/90",
        outline:
          "text-foreground ring-border/60 [a&]:hover:bg-accent [a&]:hover:text-accent-foreground",
        success:
          "border-transparent bg-success text-success-foreground ring-emerald-200/60",
        warning:
          "border-transparent bg-warning text-warning-foreground ring-amber-200/60",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  },
)
export type BadgeVariants = VariantProps<typeof badgeVariants>
