import { useEffect, useId, useRef, useState } from 'react'
import { Link, usePage } from '@inertiajs/react'
import { ArrowLeft, Equal, X } from 'lucide-react'
import NavbarAuthActions from '@/Components/NavbarAuthActions'
import NavbarLink from '@/Components/NavbarLink'
import NavbarMobilePanel from '@/Components/NavbarMobilePanel'
import { buildNavItems, isNavItemActive } from '@/Components/navItems'
import type { NavbarProps, NavTone } from '@/types/navigation'
import type { SharedProps } from '@/types/shared'

const wordmarkToneClasses: Record<NavTone, string> = {
    ink: 'text-ink',
    paper: 'text-paper hover:text-forest-200 focus-visible:outline-paper',
}

export default function Navbar({ back, variant = 'default' }: NavbarProps) {
    const page = usePage<SharedProps>()
    const { nav } = page.props
    const user = page.props.auth?.user ?? null
    const currentPath = page.url
    const items = buildNavItems(nav, user)
    const [open, setOpen] = useState(false)
    const menuButtonRef = useRef<HTMLButtonElement>(null)
    const panelId = useId()
    const onPhoto = variant === 'on-photo'
    const desktopTone: NavTone = onPhoto ? 'paper' : 'ink'
    const mobileTone: NavTone = onPhoto && !open ? 'paper' : 'ink'

    useEffect(() => {
        if (!open) {
            return
        }

        function handleKeyDown(event: KeyboardEvent) {
            if (event.key === 'Escape') {
                setOpen(false)
                menuButtonRef.current?.focus()
            }
        }

        document.addEventListener('keydown', handleKeyDown)

        return () => document.removeEventListener('keydown', handleKeyDown)
    }, [open])

    const wordmark = (size: string, tone: NavTone) => (
        <Link
            href="/"
            className={`font-medium tracking-[0.04em] transition-colors duration-150 ${wordmarkToneClasses[tone]} ${size}`}
        >
            {nav.brand}
        </Link>
    )

    const headerClasses = onPhoto
        ? `absolute inset-x-0 top-0 z-20 text-paper ${open ? 'max-lg:bg-paper max-lg:text-ink' : ''}`
        : 'bg-paper text-ink'

    return (
        <header className={headerClasses}>
            <div className="mx-auto hidden max-w-360 items-center justify-between px-16 py-9 lg:flex">
                {wordmark('text-[17px]', desktopTone)}

                <nav className="flex items-center gap-10 text-sm tracking-[0.03em]">
                    {items.map((item) => (
                        <NavbarLink
                            key={item.key}
                            item={item}
                            active={isNavItemActive(item, currentPath)}
                            tone={desktopTone}
                            className=""
                        />
                    ))}
                    <NavbarAuthActions user={user} variant="desktop" tone={desktopTone} />
                </nav>
            </div>

            {back ? (
                <div className="grid grid-cols-[44px_1fr_44px] items-center py-2 pr-3 pl-2 lg:hidden">
                    <Link
                        href={back.href}
                        aria-label={back.label}
                        className="flex size-11 items-center justify-center text-ink transition-colors duration-150 hover:text-forest"
                    >
                        <ArrowLeft className="size-5" strokeWidth={1.25} aria-hidden="true" />
                    </Link>
                    <div className="text-center">{wordmark('text-[15px]', 'ink')}</div>
                    <span aria-hidden="true" />
                </div>
            ) : (
                <div className="flex items-center justify-between px-5 py-4 md:px-10 lg:hidden">
                    {wordmark('text-[15px]', mobileTone)}

                    <button
                        ref={menuButtonRef}
                        type="button"
                        onClick={() => setOpen((value) => !value)}
                        className={`flex size-11 items-center justify-end ${
                            mobileTone === 'paper' ? 'text-paper focus-visible:outline-paper' : 'text-ink'
                        }`}
                        aria-label={open ? nav.close_menu : nav.open_menu}
                        aria-expanded={open}
                        aria-controls={panelId}
                    >
                        {open ? (
                            <X className="size-5.5" strokeWidth={1.25} aria-hidden="true" />
                        ) : (
                            <Equal className="size-5.5" strokeWidth={1.25} aria-hidden="true" />
                        )}
                    </button>
                </div>
            )}

            {open && !back && (
                <NavbarMobilePanel
                    id={panelId}
                    items={items}
                    currentPath={currentPath}
                    user={user}
                    onClose={() => setOpen(false)}
                />
            )}
        </header>
    )
}
