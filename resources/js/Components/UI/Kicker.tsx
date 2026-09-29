import type { KickerProps } from '@/types/ui'

const sizeClasses = {
    sm: 'text-[11px]',
    md: 'text-xs',
}

export default function Kicker({ as: Component = 'span', size = 'md', className = '', children }: KickerProps) {
    return (
        <Component className={`tracking-kicker text-forest-700 uppercase ${sizeClasses[size]} ${className}`}>
            {children}
        </Component>
    )
}
