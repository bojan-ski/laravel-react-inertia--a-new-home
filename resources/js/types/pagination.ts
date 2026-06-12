export interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

export interface Paginator<T> {
    data: T[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
    path: string
    first_page_url: string
    last_page_url: string
    next_page_url: string | null
    prev_page_url: string | null
    links: PaginationLink[]
}

export interface PaginationLabels {
    page_of: string
    previous: string
    next: string
}

export interface PaginationProps {
    paginator: Paginator<unknown>
    labels: PaginationLabels
}
