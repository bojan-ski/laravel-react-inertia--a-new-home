export interface NavTranslations {
    brand: string
    features: string
    how_it_works: string
    listings: string
    login: string
    get_started: string
    dashboard: string
    sign_out: string
    open_menu: string
    close_menu: string
}

export interface NavLink {
    label: string
    href: string
    internal: boolean
}

export interface NavbarAuthActionsProps {
    onNavigate?: () => void
}

export interface NavbarMobilePanelProps {
    links: NavLink[]
    currentPath: string
    onClose: () => void
}
