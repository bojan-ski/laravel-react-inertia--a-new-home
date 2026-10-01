import { usePage } from '@inertiajs/react'
import type { SharedProps } from '@/types/shared'

export function useActionLabel(): (action: string, item: string) => string {
    const template = usePage<SharedProps>().props.ui.action_for

    return (action, item) => (item ? template.replace(':action', action).replace(':item', item) : action)
}
