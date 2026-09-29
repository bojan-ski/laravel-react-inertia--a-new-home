import { Link, usePage } from '@inertiajs/react'
import { ArrowRight } from 'lucide-react'
import Button from '@/Components/UI/Button'
import type { NavbarAuthActionsProps } from '@/types/navigation'
import type { SharedProps } from '@/types/shared'

export default function NavbarAuthActions({ user, variant, onNavigate }: NavbarAuthActionsProps) {
    const { nav } = usePage<SharedProps>().props

    if (user) {
        return (
            <Link
                href="/logout"
                method="post"
                as="button"
                onClick={onNavigate}
                className={
                    variant === 'desktop'
                        ? 'text-ink-muted transition-colors duration-150 hover:text-forest'
                        : 'flex min-h-13 w-full items-center border-b border-rule text-left text-[17px] text-ink-muted transition-colors duration-150 hover:text-forest'
                }
            >
                {nav.sign_out}
            </Link>
        )
    }

    if (variant === 'desktop') {
        return (
            <Link
                href="/register"
                className="border-b border-current pb-0.5 font-medium text-ink transition-colors duration-150 hover:text-forest"
            >
                {nav.get_started}
            </Link>
        )
    }

    return (
        <Button
            href="/register"
            onClick={onNavigate}
            size="lg"
            fullWidth
            className="mt-6"
            trailing={<ArrowRight className="size-5" strokeWidth={1.25} aria-hidden="true" />}
        >
            {nav.get_started}
        </Button>
    )
}
