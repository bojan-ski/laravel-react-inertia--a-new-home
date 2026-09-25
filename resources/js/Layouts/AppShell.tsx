import CookieBanner from '@/Components/CookieBanner'
import type { AppShellProps } from '@/types/layout'

export default function AppShell({ children }: AppShellProps) {
    return (
        <>
            {children}
            <CookieBanner />
        </>
    )
}
