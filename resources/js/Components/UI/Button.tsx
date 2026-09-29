import { Link } from '@inertiajs/react'
import type { ButtonProps, ButtonSize, ButtonVariant } from '@/types/ui'

const variantClasses: Record<ButtonVariant, string> = {
    primary: 'bg-forest text-paper hover:bg-forest-600 active:bg-forest-700',
    secondary: 'border border-rule text-ink hover:bg-ink/7 active:bg-ink/14',
    link: 'text-sm text-ink-muted hover:text-forest',
    'strong-link': 'border-b border-forest text-forest hover:border-forest-600 hover:text-forest-600',
    'ink-outline': 'border border-ink text-ink hover:bg-ink hover:text-paper',
    'on-photo': 'bg-paper text-ink hover:bg-sand',
    'on-photo-outline': 'border border-paper/50 text-paper hover:border-paper',
}

const sizeClasses: Record<ButtonSize, string> = {
    sm: 'h-11 px-4 text-sm',
    md: 'h-12 px-4.5 text-[15px]',
    lg: 'h-14 px-5 text-[15px]',
}

const textVariants: ButtonVariant[] = ['link', 'strong-link']

function withoutButtonProps<T extends ButtonProps>({
    variant,
    size,
    fullWidth,
    trailing,
    className,
    children,
    external,
    ...rest
}: T) {
    return rest
}

export default function Button(props: ButtonProps) {
    const { variant = 'primary', size = 'md', fullWidth = false, trailing, className = '', children } = props
    const boxed = !textVariants.includes(variant)

    const classes = [
        'inline-flex items-center gap-2 font-medium transition-colors duration-150 disabled:cursor-not-allowed disabled:opacity-45',
        variantClasses[variant],
        boxed ? sizeClasses[size] : null,
        boxed ? (fullWidth ? 'w-full justify-between' : 'justify-center') : null,
        className,
    ]
        .filter(Boolean)
        .join(' ')

    const content = (
        <>
            <span>{children}</span>
            {trailing}
        </>
    )

    if (props.href === undefined) {
        return (
            <button type="button" className={classes} {...withoutButtonProps(props)}>
                {content}
            </button>
        )
    }

    if (props.external) {
        return (
            <a className={classes} {...withoutButtonProps(props)}>
                {content}
            </a>
        )
    }

    return (
        <Link className={classes} {...withoutButtonProps(props)}>
            {content}
        </Link>
    )
}
