import { Link } from '@inertiajs/react'
import { ChevronLeft, ChevronRight } from 'lucide-react'
import type { PaginationProps } from '@/types/pagination'

export default function Pagination({ paginator, labels }: PaginationProps) {
    if (paginator.last_page <= 1) {
        return null
    }

    const numberedLinks = paginator.links.slice(1, -1)
    const pageOf = labels.page_of
        .replace(':current', String(paginator.current_page))
        .replace(':last', String(paginator.last_page))

    return (
        <nav className="mt-12 flex flex-col items-center gap-3" aria-label="Pagination">
            <div className="flex items-center gap-1.5">
                {paginator.prev_page_url ? (
                    <Link
                        href={paginator.prev_page_url}
                        preserveScroll
                        aria-label={labels.previous}
                        className="flex size-9 items-center justify-center rounded-md border border-border text-text-soft transition-colors hover:bg-surface-muted"
                    >
                        <ChevronLeft className="size-4" />
                    </Link>
                ) : (
                    <span
                        aria-hidden="true"
                        className="flex size-9 items-center justify-center rounded-md border border-border text-text-muted/40"
                    >
                        <ChevronLeft className="size-4" />
                    </span>
                )}

                {numberedLinks.map((link, index) =>
                    link.url ? (
                        <Link
                            key={`${link.label}-${index}`}
                            href={link.url}
                            preserveScroll
                            aria-current={link.active ? 'page' : undefined}
                            className={`flex size-9 items-center justify-center rounded-md border text-sm font-medium transition-colors ${
                                link.active
                                    ? 'border-surface-dark bg-surface-dark text-text-inverse'
                                    : 'border-border text-text-soft hover:bg-surface-muted'
                            }`}
                        >
                            {link.label}
                        </Link>
                    ) : (
                        <span
                            key={`gap-${index}`}
                            className="flex size-9 items-center justify-center text-sm text-text-muted"
                        >
                            {link.label}
                        </span>
                    )
                )}

                {paginator.next_page_url ? (
                    <Link
                        href={paginator.next_page_url}
                        preserveScroll
                        aria-label={labels.next}
                        className="flex size-9 items-center justify-center rounded-md border border-border text-text-soft transition-colors hover:bg-surface-muted"
                    >
                        <ChevronRight className="size-4" />
                    </Link>
                ) : (
                    <span
                        aria-hidden="true"
                        className="flex size-9 items-center justify-center rounded-md border border-border text-text-muted/40"
                    >
                        <ChevronRight className="size-4" />
                    </span>
                )}
            </div>

            <p className="text-sm text-text-muted">{pageOf}</p>
        </nav>
    )
}
