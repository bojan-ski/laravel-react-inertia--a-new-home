import { Link } from '@inertiajs/react'
import type { AuthSwitchPromptProps } from '@/types/auth'

export default function AuthSwitchPrompt({ text, linkLabel, href }: AuthSwitchPromptProps) {
    return (
        <p className="text-sm text-ink-muted">
            {text}{' '}
            <Link
                href={href}
                className="border-b border-current text-forest transition-colors duration-150 hover:text-forest-600"
            >
                {linkLabel}
            </Link>
        </p>
    )
}
