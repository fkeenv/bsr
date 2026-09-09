import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Alert } from "./Alert.vue"
export { default as AlertDescription } from "./AlertDescription.vue"
export { default as AlertTitle } from "./AlertTitle.vue"

export const alertVariants = cva(
  "relative w-full rounded-lg border px-4 py-3 text-sm grid has-[>svg]:grid-cols-[calc(var(--spacing)*4)_1fr] grid-cols-[0_1fr] has-[>svg]:gap-x-3 gap-y-0.5 items-start [&>svg]:size-4 [&>svg]:translate-y-0.5 [&>svg]:text-current",
  {
    variants: {
      variant: {
        default: "bg-card text-card-foreground",
        warning:
          "border-yellow-200 bg-yellow-50 text-yellow-950 dark:border-yellow-800 dark:bg-yellow-950 dark:text-yellow-50 [&>svg]:text-yellow-700 dark:[&>svg]:text-yellow-300 *:data-[slot=alert-description]:text-yellow-950/90 dark:*:data-[slot=alert-description]:text-yellow-50/90",
        success:
          "border-green-200 bg-green-50 text-green-900 dark:border-green-800 dark:bg-green-950 dark:text-green-50 [&>svg]:text-green-700 dark:[&>svg]:text-green-300 *:data-[slot=alert-description]:text-green-900/90 dark:*:data-[slot=alert-description]:text-green-50/90",
        destructive:
          "border-red-200 bg-red-50 text-red-900 dark:border-red-800 dark:bg-red-950 dark:text-red-50 [&>svg]:text-red-700 dark:[&>svg]:text-red-300 *:data-[slot=alert-description]:text-red-900/90 dark:*:data-[slot=alert-description]:text-red-50/90",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  },
)

export type AlertVariants = VariantProps<typeof alertVariants>
