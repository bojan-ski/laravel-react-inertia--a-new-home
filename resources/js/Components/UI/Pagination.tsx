import { Link } from '@inertiajs/react'
import type { PageLinkProps, PaginationProps } from '@/types/pagination'

const itemClasses = 'inline-flex min-h-11 items-center text-sm text-ink sm:min-h-0'

function PageLink({ url, label }: PageLinkProps) {
    const text = <span className="border-b border-current">{label}</span>

    if (!url) {
        return (
            <span aria-disabled="true" className={`${itemClasses} opacity-40`}>
                {text}
            </span>
        )
    }

    return (
        <Link href={url} className={`${itemClasses} transition-colors duration-150 hover:text-forest`}>
            {text}
        </Link>
    )
}

export default function Pagination({ paginator, labels, className = 'mt-12' }: PaginationProps) {
    if (paginator.last_page <= 1) {
        return null
    }

    const pageOf = labels.page_of
        .replace(':current', String(paginator.current_page))
        .replace(':last', String(paginator.last_page))

    return (
        <nav
            aria-label={labels.aria_label}
            className={`${className} flex items-center justify-between gap-4 sm:justify-end sm:gap-8`}
        >
            <p className="order-2 text-sm text-ink-muted sm:order-first sm:mr-auto">{pageOf}</p>
            <span className="order-1 sm:order-0">
                <PageLink url={paginator.prev_page_url} label={labels.previous} />
            </span>
            <span className="order-3 sm:order-0">
                <PageLink url={paginator.next_page_url} label={labels.next} />
            </span>
        </nav>
    )
}
